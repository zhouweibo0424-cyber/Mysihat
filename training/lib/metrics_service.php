<?php
/**
 * Metrics Calculation Service
 * Computes weekly adherence, RPE, streaks, etc.
 */

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/plan_service.php";

class MetricsService {
  private $pdo;
  private $plan_service;

  public function __construct($pdo) {
    $this->pdo = $pdo;
    $this->plan_service = new PlanService($pdo);
  }

  /**
   * Compute weekly metrics for a given week
   */
  public function compute_weekly_metrics($user_id, $week_start) {
    // Get plan for this week
    $stmt = $this->pdo->prepare("
      SELECT plan_id, days_per_week, session_duration
      FROM plans
      WHERE user_id = ? AND week_start_date = ?
    ");
    $stmt->execute([$user_id, $week_start]);
    $plan = $stmt->fetch();
    
    if (!$plan) {
      return null;
    }
    
    $plan_id = (int)$plan['plan_id'];
    
    // Get plan_days
    $stmt = $this->pdo->prepare("
      SELECT plan_day_id, date, estimated_minutes
      FROM plan_days
      WHERE plan_id = ?
      ORDER BY date ASC
    ");
    $stmt->execute([$plan_id]);
    $plan_days = $stmt->fetchAll();
    
    $planned_sessions = count($plan_days);
    
    // Get completed workouts
    $plan_day_ids = array_column($plan_days, 'plan_day_id');
    if (empty($plan_day_ids)) {
      $completed_sessions = 0;
      $workouts = [];
    } else {
      $placeholders = implode(',', array_fill(0, count($plan_day_ids), '?'));
      $stmt = $this->pdo->prepare("
        SELECT w.workout_id, w.plan_day_id, w.start_time, w.end_time, w.duration_min, pd.date, pd.estimated_minutes
        FROM workouts w
        JOIN plan_days pd ON w.plan_day_id = pd.plan_day_id
        WHERE w.user_id = ? AND w.plan_day_id IN ({$placeholders}) AND w.status = 'completed'
        ORDER BY w.start_time ASC
      ");
      $stmt->execute(array_merge([$user_id], $plan_day_ids));
      $workouts = $stmt->fetchAll();
      $completed_sessions = count($workouts);
    }
    
    // Adherence rate
    $adherence_rate = $planned_sessions > 0 ? $completed_sessions / $planned_sessions : 0;
    
    // Average RPE (from workout_items)
    $avg_rpe = null;
    if (!empty($workouts)) {
      $workout_ids = array_column($workouts, 'workout_id');
      $placeholders = implode(',', array_fill(0, count($workout_ids), '?'));
      $stmt = $this->pdo->prepare("
        SELECT AVG(rpe) as avg_rpe
        FROM workout_items
        WHERE workout_id IN ({$placeholders}) AND rpe IS NOT NULL
      ");
      $stmt->execute($workout_ids);
      $result = $stmt->fetch();
      $avg_rpe = $result ? (float)$result['avg_rpe'] : null;
    }
    
    // Miss streak (consecutive missed sessions)
    $miss_streak = 0;
    foreach ($plan_days as $plan_day) {
      $has_workout = false;
      foreach ($workouts as $workout) {
        if ((int)$workout['plan_day_id'] === (int)$plan_day['plan_day_id']) {
          $has_workout = true;
          break;
        }
      }
      if (!$has_workout) {
        $miss_streak++;
      } else {
        break; // Reset on first completed
      }
    }
    
    // High RPE streak (consecutive sessions with avg_rpe >= 8)
    $high_rpe_streak = 0;
    if (!empty($workouts)) {
      // Get session avg RPE for each workout
      $workout_ids = array_column($workouts, 'workout_id');
      $placeholders = implode(',', array_fill(0, count($workout_ids), '?'));
      $stmt = $this->pdo->prepare("
        SELECT workout_id, AVG(rpe) as session_avg_rpe
        FROM workout_items
        WHERE workout_id IN ({$placeholders}) AND rpe IS NOT NULL
        GROUP BY workout_id
        ORDER BY workout_id DESC
      ");
      $stmt->execute($workout_ids);
      $session_rpes = $stmt->fetchAll();
      
      foreach ($session_rpes as $session) {
        if ((float)$session['session_avg_rpe'] >= 8.0) {
          $high_rpe_streak++;
        } else {
          break;
        }
      }
    }
    
    // Duration ratio and over_time_count
    $duration_ratios = [];
    $over_time_count = 0;
    foreach ($workouts as $workout) {
      if ($workout['duration_min'] && $workout['estimated_minutes']) {
        $ratio = $workout['duration_min'] / $workout['estimated_minutes'];
        $duration_ratios[] = $ratio;
        if ($ratio > 1.2) {
          $over_time_count++;
        }
      }
    }
    $avg_duration_ratio = !empty($duration_ratios) ? array_sum($duration_ratios) / count($duration_ratios) : null;
    
    return [
      'planned_sessions' => $planned_sessions,
      'completed_sessions' => $completed_sessions,
      'adherence_rate' => $adherence_rate,
      'avg_rpe' => $avg_rpe,
      'miss_streak' => $miss_streak,
      'high_rpe_streak' => $high_rpe_streak,
      'avg_duration_ratio' => $avg_duration_ratio,
      'over_time_count' => $over_time_count,
    ];
  }
}

