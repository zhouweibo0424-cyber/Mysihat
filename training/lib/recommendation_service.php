<?php
/**
 * Recommendation Service
 * Generates recommendations based on weekly metrics
 */

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/metrics_service.php";
require_once __DIR__ . "/plan_service.php";

class RecommendationService {
  private $pdo;
  private $metrics_service;
  private $plan_service;

  public function __construct($pdo) {
    $this->pdo = $pdo;
    $this->metrics_service = new MetricsService($pdo);
    $this->plan_service = new PlanService($pdo);
  }

  /**
   * Generate recommendations for next week
   */
  public function generate_recommendation($user_id, $week_start) {
    // Get user preferences
    $stmt = $this->pdo->prepare("
      SELECT equipment_level, default_days_per_week, default_session_duration, prefer_home
      FROM users
      WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
      return null;
    }
    
    // Get current week metrics
    $metrics = $this->metrics_service->compute_weekly_metrics($user_id, $week_start);
    
    if (!$metrics) {
      return null;
    }
    
    // Get current plan
    $current_plan = $this->plan_service->get_current_plan($user_id);
    
    // Initialize recommendation
    $recommendation = [
      'original' => [
        'days_per_week' => (int)$user['default_days_per_week'],
        'session_duration' => (int)$user['default_session_duration'],
        'equipment_level' => $user['equipment_level'],
        'mode' => $current_plan ? $current_plan['mode'] : ($user['prefer_home'] ? 'home' : 'gym'),
      ],
      'recommended' => [
        'days_per_week' => (int)$user['default_days_per_week'],
        'session_duration' => (int)$user['default_session_duration'],
        'equipment_level' => $user['equipment_level'],
        'mode' => $user['prefer_home'] ? 'home' : 'gym',
      ],
      'reasons' => [],
      'rules_applied' => [],
    ];
    
    // R1: Miss streak >= 2 -> downgrade
    if ($metrics['miss_streak'] >= 2) {
      $recommendation['recommended']['days_per_week'] = max(3, $recommendation['recommended']['days_per_week'] - 1);
      $recommendation['recommended']['session_duration'] = min($recommendation['recommended']['session_duration'], 30);
      if ($user['prefer_home'] || $metrics['miss_streak'] >= 2) {
        $recommendation['recommended']['mode'] = 'home';
        $recommendation['recommended']['equipment_level'] = 'home';
      }
      $recommendation['reasons'][] = "Missed {$metrics['miss_streak']} sessions in a row — lowering the barrier to restore consistency.";

      $recommendation['rules_applied'][] = 'R1';
    }
    
    // R2: Adherence rate < 0.6 -> force 30min
    if ($metrics['adherence_rate'] < 0.6) {
      $recommendation['recommended']['session_duration'] = 30;
      $recommendation['reasons'][] = "Adherence is " . round($metrics['adherence_rate'] * 100) . "% — switching to a micro-workout mode (30 minutes) to make it easier to stay consistent.";

      $recommendation['rules_applied'][] = 'R2';
    }
    
    // R3: High RPE streak >= 2 OR avg_rpe >= 8 -> reduce sets
    if ($metrics['high_rpe_streak'] >= 2 || ($metrics['avg_rpe'] !== null && $metrics['avg_rpe'] >= 8)) {

      $recommendation['reasons'][] = "High intensity detected (RPE ≥ 8) — reduce volume (fewer sets) and increase rest to support recovery.";

      $recommendation['rules_applied'][] = 'R3';
      $recommendation['reduce_sets'] = true;
      $recommendation['add_rest'] = true;
    }
    
    // R4: Adherence >= 0.9 AND RPE 6-7.5 -> slight increase
    if ($metrics['adherence_rate'] >= 0.9 && 
        $metrics['avg_rpe'] !== null && 
        $metrics['avg_rpe'] >= 6 && 
        $metrics['avg_rpe'] <= 7.5) {
      // Add accessory or +1 set (max +1)

      $recommendation['reasons'][] = "Adherence is " . round($metrics['adherence_rate'] * 100) . "% with a moderate effort level — a small progressive increase can help avoid stagnation.";

      $recommendation['rules_applied'][] = 'R4';
      $recommendation['add_accessory'] = true;
    }
    
    // R5: Over time count >= 2 OR duration_ratio > 1.2 -> simplify
    if ($metrics['over_time_count'] >= 2 || 
        ($metrics['avg_duration_ratio'] !== null && $metrics['avg_duration_ratio'] > 1.2)) {



      $recommendation['reasons'][] = "Workouts often run over time — simplify the session structure or reduce the number of exercises to fit the time budget.";

      $recommendation['rules_applied'][] = 'R5';
      $recommendation['reduce_slots'] = true;
    }
    
    // R6: Prefer home OR miss_streak >= 2 -> switch to home
    if ($user['prefer_home'] || $metrics['miss_streak'] >= 2) {
      $recommendation['recommended']['mode'] = 'home';
      $recommendation['recommended']['equipment_level'] = 'home';
      if (!in_array('R1', $recommendation['rules_applied'])) {

        $recommendation['reasons'][] = "Switching to a home-friendly plan to reduce friction and improve adherence.";

        $recommendation['rules_applied'][] = 'R6';
      }
    }
    
    // Calculate estimated weekly minutes
    $recommendation['original']['weekly_minutes'] = 
      $recommendation['original']['days_per_week'] * $recommendation['original']['session_duration'];
    $recommendation['recommended']['weekly_minutes'] = 
      $recommendation['recommended']['days_per_week'] * $recommendation['recommended']['session_duration'];
    
    return $recommendation;
  }

  /**
   * Apply recommendation and generate next week plan
   */
  public function apply_recommendation($user_id, $recommendation) {
    // Update user preferences
    $stmt = $this->pdo->prepare("
      UPDATE users
      SET default_days_per_week = ?,
          default_session_duration = ?,
          equipment_level = ?,
          prefer_home = ?
      WHERE id = ?
    ");
    $stmt->execute([
      $recommendation['recommended']['days_per_week'],
      $recommendation['recommended']['session_duration'],
      $recommendation['recommended']['equipment_level'],
      $recommendation['recommended']['mode'] === 'home' ? 1 : 0,
      $user_id,
    ]);
    
    // Generate next week plan
    $current_week_start = $this->plan_service->get_week_start();
    $next_week_start = date('Y-m-d', strtotime($current_week_start . ' +7 days'));
    
    $plan_result = $this->plan_service->generate_plan(
      $user_id,
      $recommendation['recommended']['days_per_week'],
      $recommendation['recommended']['session_duration'],
      $recommendation['recommended']['equipment_level'],
      $recommendation['recommended']['mode'] === 'home',
      $next_week_start
    );
    
    // Mark plan as from recommendation
    $stmt = $this->pdo->prepare("
      UPDATE plans
      SET created_from = 'recommendation'
      WHERE plan_id = ?
    ");
    $stmt->execute([$plan_result['plan_id']]);
    
    return $plan_result;
  }
}

