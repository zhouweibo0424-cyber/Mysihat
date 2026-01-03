<?php
/**
 * Plan Generation Service
 * Generates weekly training plans from templates
 */

require_once __DIR__ . "/db.php";
require_once __DIR__ . "/templates.php";
require_once __DIR__ . "/auth.php";

class PlanService {
  private $pdo;
  private $templates;

  public function __construct($pdo) {
    $this->pdo = $pdo;
    $this->templates = require __DIR__ . "/templates.php";
  }

  /**
   * Get Monday of current week (Asia/Kuala_Lumpur timezone)
   */
  public function get_week_start($date = null) {
    $tz = new DateTimeZone('Asia/Kuala_Lumpur');
    $dt = $date ? new DateTime($date, $tz) : new DateTime('now', $tz);
    $day_of_week = (int)$dt->format('N'); // 1=Monday, 7=Sunday
    $days_to_monday = $day_of_week - 1;
    $dt->modify("-{$days_to_monday} days");
    return $dt->format('Y-m-d');
  }

  /**
   * Get training dates for a week based on days_per_week
   */
  public function get_training_dates($week_start, $days_per_week) {
    $tz = new DateTimeZone('Asia/Kuala_Lumpur');
    $start = new DateTime($week_start, $tz);
    $dates = [];
    
    // Training day patterns
    $patterns = [
      3 => [1, 3, 5], // Mon, Wed, Fri
      4 => [1, 2, 4, 5], // Mon, Tue, Thu, Fri
      5 => [1, 2, 4, 5, 6], // Mon, Tue, Thu, Fri, Sat
    ];
    
    $day_indices = $patterns[$days_per_week] ?? $patterns[4];
    
    foreach ($day_indices as $index => $day_offset) {
      $date = clone $start;
      $date->modify("+{$day_offset} days");
      $dates[] = [
        'date' => $date->format('Y-m-d'),
        'day_index' => $index + 1,
      ];
    }
    
    return $dates;
  }

  /**
   * Find compatible exercise by pattern and equipment
   */
  private function find_exercise($pattern, $user_equipment, $pdo) {
    // Equipment compatibility: home -> home only; dumbbell -> home+dumbbell; gym -> all
    $equipment_map = [
      'home' => ['home'],
      'dumbbell' => ['home', 'dumbbell'],
      'gym' => ['home', 'dumbbell', 'gym'],
    ];
    
    $allowed_equipment = $equipment_map[$user_equipment] ?? ['home'];
    $placeholders = implode(',', array_fill(0, count($allowed_equipment), '?'));
    
    $stmt = $pdo->prepare("
      SELECT exercise_id, name, alt_exercise_id
      FROM exercises
      WHERE pattern = ? AND equipment IN ({$placeholders})
      ORDER BY difficulty ASC
      LIMIT 1
    ");
    
    $params = array_merge([$pattern], $allowed_equipment);
    $stmt->execute($params);
    $exercise = $stmt->fetch();
    
    if ($exercise) {
      return $exercise;
    }
    
    // Fallback: try alternative chain
    $stmt = $pdo->prepare("
      SELECT exercise_id, name, alt_exercise_id
      FROM exercises
      WHERE pattern = ? AND equipment = 'home'
      ORDER BY difficulty ASC
      LIMIT 1
    ");
    $stmt->execute([$pattern]);
    return $stmt->fetch();
  }

  /**
   * Generate plan for user
   */
  public function generate_plan($user_id, $days_per_week, $session_duration, $equipment_level, $prefer_home = false, $week_start = null) {
    $this->pdo->beginTransaction();
    
    try {
      // Determine week start
      if (!$week_start) {
        $week_start = $this->get_week_start();
      }
      
      // Get template
      $template_key = "{$days_per_week}d_{$session_duration}m";
      $goal_templates = $this->templates['consistency'] ?? [];
      $template = $goal_templates[$template_key] ?? null;
      
      if (!$template) {
        throw new Exception("Template not found for {$days_per_week} days × {$session_duration} minutes");
      }
      
      // Determine mode
      $mode = ($prefer_home || $equipment_level === 'home') ? 'home' : 'gym';
      
      // Delete existing plan for this week
      $stmt = $this->pdo->prepare("SELECT plan_id FROM plans WHERE user_id = ? AND week_start_date = ?");
      $stmt->execute([$user_id, $week_start]);
      $existing = $stmt->fetch();
      
      if ($existing) {
        $plan_id = (int)$existing['plan_id'];
        // Delete plan_items, plan_days, then plan
        $this->pdo->prepare("DELETE FROM plan_items WHERE plan_day_id IN (SELECT plan_day_id FROM plan_days WHERE plan_id = ?)")->execute([$plan_id]);
        $this->pdo->prepare("DELETE FROM plan_days WHERE plan_id = ?")->execute([$plan_id]);
        $this->pdo->prepare("DELETE FROM plans WHERE plan_id = ?")->execute([$plan_id]);
      }
      
      // Create plan
      $stmt = $this->pdo->prepare("
        INSERT INTO plans (user_id, week_start_date, days_per_week, session_duration, mode, created_from)
        VALUES (?, ?, ?, ?, ?, 'manual')
      ");
      $stmt->execute([$user_id, $week_start, $days_per_week, $session_duration, $mode]);
      $plan_id = (int)$this->pdo->lastInsertId();
      
      // Get training dates
      $training_dates = $this->get_training_dates($week_start, $days_per_week);
      $weekly_pattern = $template['weekly_pattern'];
      
      // Create plan_days and plan_items
      foreach ($training_dates as $date_info) {
        $day_type_key = $weekly_pattern[$date_info['day_index'] - 1] ?? 'A';
        $day_type = $template['day_types'][$day_type_key] ?? [];
        
        // Create plan_day
        $stmt = $this->pdo->prepare("
          INSERT INTO plan_days (plan_id, date, day_index, estimated_minutes)
          VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$plan_id, $date_info['date'], $date_info['day_index'], $session_duration]);
        $plan_day_id = (int)$this->pdo->lastInsertId();
        
        // Create plan_items for each slot
        $order_no = 1;
        foreach ($day_type as $slot) {
          $exercise = $this->find_exercise($slot['pattern'], $equipment_level, $this->pdo);
          
          if ($exercise) {
            $stmt = $this->pdo->prepare("
              INSERT INTO plan_items (plan_day_id, exercise_id, target_sets, target_reps, order_no)
              VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
              $plan_day_id,
              $exercise['exercise_id'],
              $slot['sets'],
              $slot['reps'],
              $order_no++,
            ]);
          }
        }
      }
      
      $this->pdo->commit();
      
      return [
        'plan_id' => $plan_id,
        'week_start' => $week_start,
        'days_per_week' => $days_per_week,
        'session_duration' => $session_duration,
        'mode' => $mode,
      ];
      
    } catch (Exception $e) {
      $this->pdo->rollBack();
      throw $e;
    }
  }

  /**
   * Get current week plan for user
   */
  public function get_current_plan($user_id) {
    $week_start = $this->get_week_start();
    
    $stmt = $this->pdo->prepare("
      SELECT p.*, 
        COUNT(DISTINCT pd.plan_day_id) as days_count
      FROM plans p
      LEFT JOIN plan_days pd ON p.plan_id = pd.plan_id
      WHERE p.user_id = ? AND p.week_start_date = ?
      GROUP BY p.plan_id
    ");
    $stmt->execute([$user_id, $week_start]);
    $plan = $stmt->fetch();
    
    if (!$plan) {
      return null;
    }
    
    // Load plan_days with items
    $stmt = $this->pdo->prepare("
      SELECT pd.*,
        GROUP_CONCAT(
          CONCAT(pi.plan_item_id, ':', e.name, ':', pi.target_sets, 'x', pi.target_reps)
          ORDER BY pi.order_no
          SEPARATOR '|'
        ) as items
      FROM plan_days pd
      LEFT JOIN plan_items pi ON pd.plan_day_id = pi.plan_day_id
      LEFT JOIN exercises e ON pi.exercise_id = e.exercise_id
      WHERE pd.plan_id = ?
      GROUP BY pd.plan_day_id
      ORDER BY pd.date ASC
    ");
    $stmt->execute([$plan['plan_id']]);
    $plan['days'] = $stmt->fetchAll();
    
    return $plan;
  }

  /**
   * Get plan day with items
   */
  public function get_plan_day($plan_day_id) {
    $stmt = $this->pdo->prepare("
      SELECT pd.*, p.user_id, p.week_start_date
      FROM plan_days pd
      JOIN plans p ON pd.plan_id = p.plan_id
      WHERE pd.plan_day_id = ?
    ");
    $stmt->execute([$plan_day_id]);
    $day = $stmt->fetch();
    
    if (!$day) {
      return null;
    }
    
    $stmt = $this->pdo->prepare("
      SELECT pi.*, e.name as exercise_name, e.pattern
      FROM plan_items pi
      JOIN exercises e ON pi.exercise_id = e.exercise_id
      WHERE pi.plan_day_id = ?
      ORDER BY pi.order_no ASC
    ");
    $stmt->execute([$plan_day_id]);
    $day['items'] = $stmt->fetchAll();
    
    return $day;
  }
}

