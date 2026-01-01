<?php
/**
 * Plan Builder Page
 * Generate and view weekly training plans
 */
require_once __DIR__ . "/lib/db.php";
require_once __DIR__ . "/lib/auth.php";
require_once __DIR__ . "/lib/plan_service.php";

$user_id = get_user_id();
$plan_service = new PlanService($pdo);
$message = "";
$error = "";

// Handle plan generation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["generate_plan"])) {
  $days_per_week = (int)($_POST["days_per_week"] ?? 4);
  $session_duration = (int)($_POST["session_duration"] ?? 45);
  $equipment_level = $_POST["equipment_level"] ?? "home";
  $prefer_home = isset($_POST["prefer_home"]) ? 1 : 0;
  
  // Validate
  if (!in_array($days_per_week, [3, 4, 5])) {
    $error = "Invalid days per week";
  } elseif (!in_array($session_duration, [30, 45, 60])) {
    $error = "Invalid session duration";
  } elseif (!in_array($equipment_level, ["home", "dumbbell", "gym"])) {
    $error = "Invalid equipment level";
  } else {
    // Update user preferences
    $stmt = $pdo->prepare("
      UPDATE users
      SET default_days_per_week = ?,
          default_session_duration = ?,
          equipment_level = ?,
          prefer_home = ?
      WHERE id = ?
    ");
    $stmt->execute([$days_per_week, $session_duration, $equipment_level, $prefer_home, $user_id]);
    
    // Generate plan
    try {
      $result = $plan_service->generate_plan($user_id, $days_per_week, $session_duration, $equipment_level, $prefer_home);
      $message = "Plan generated successfully for week starting " . $result['week_start'];
    } catch (Exception $e) {
      $error = "Failed to generate plan: " . $e->getMessage();
    }
  }
}

// Get user preferences
$stmt = $pdo->prepare("SELECT default_days_per_week, default_session_duration, equipment_level, prefer_home FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch() ?: ['default_days_per_week' => 4, 'default_session_duration' => 45, 'equipment_level' => 'home', 'prefer_home' => 1];

// Get current week plan
$current_plan = $plan_service->get_current_plan($user_id);
$week_start = $plan_service->get_week_start();

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/nav.php";
?>

<h3 class="fw-bold mb-3">Plan Builder</h3>

<?php if ($message): ?>
  <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="row g-4">
  <div class="col-12 col-md-4">
    <div class="card card-metric p-4">
      <h5 class="fw-bold mb-3">Generate Weekly Plan</h5>
      <form method="post">
        <div class="mb-3">
          <label class="form-label">Days per Week</label>
          <select class="form-select" name="days_per_week" required>
            <option value="3" <?php echo $user['default_days_per_week'] == 3 ? 'selected' : ''; ?>>3 days</option>
            <option value="4" <?php echo $user['default_days_per_week'] == 4 ? 'selected' : ''; ?>>4 days</option>
            <option value="5" <?php echo $user['default_days_per_week'] == 5 ? 'selected' : ''; ?>>5 days</option>
          </select>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Session Duration</label>
          <select class="form-select" name="session_duration" required>
            <option value="30" <?php echo $user['default_session_duration'] == 30 ? 'selected' : ''; ?>>30 minutes</option>
            <option value="45" <?php echo $user['default_session_duration'] == 45 ? 'selected' : ''; ?>>45 minutes</option>
            <option value="60" <?php echo $user['default_session_duration'] == 60 ? 'selected' : ''; ?>>60 minutes</option>
          </select>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Equipment Level</label>
          <select class="form-select" name="equipment_level" required>
            <option value="home" <?php echo $user['equipment_level'] == 'home' ? 'selected' : ''; ?>>Home (Bodyweight)</option>
            <option value="dumbbell" <?php echo $user['equipment_level'] == 'dumbbell' ? 'selected' : ''; ?>>Dumbbell</option>
            <option value="gym" <?php echo $user['equipment_level'] == 'gym' ? 'selected' : ''; ?>>Gym</option>
          </select>
        </div>
        
        <div class="mb-3 form-check">
          <input class="form-check-input" type="checkbox" name="prefer_home" id="prefer_home" 
                 <?php echo $user['prefer_home'] ? 'checked' : ''; ?>>
          <label class="form-check-label" for="prefer_home">
            Prefer home workouts
          </label>
        </div>
        
        <button type="submit" name="generate_plan" class="btn btn-primary w-100">
          <i class="bi bi-plus-circle"></i> Generate Plan
        </button>
      </form>
      
      <?php if ($current_plan): ?>
        <div class="mt-4 p-3 bg-light rounded">
          <div class="small-muted">Current Week</div>
          <div class="fw-bold"><?php echo htmlspecialchars($week_start); ?></div>
          <div class="small-muted mt-2">
            <?php echo $current_plan['days_per_week']; ?> days × 
            <?php echo $current_plan['session_duration']; ?> min
          </div>
          <div class="small-muted">Mode: <?php echo htmlspecialchars($current_plan['mode']); ?></div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  
  <div class="col-12 col-md-8">
    <?php if ($current_plan): ?>
      <div class="card card-metric p-4">
        <h5 class="fw-bold mb-3">Weekly Plan</h5>
        
        <?php
        $today = date('Y-m-d');
        $has_today_plan = false;
        $today_plan_day = null;
        ?>
        
        <?php foreach ($current_plan['days'] as $day): ?>
          <?php
          $items = explode('|', $day['items'] ?? '');
          $is_today = $day['date'] === $today;
          if ($is_today) {
            $has_today_plan = true;
            $today_plan_day = $day;
          }
          ?>
          <div class="mb-4 <?php echo $is_today ? 'border-start border-primary border-3 ps-3' : ''; ?>">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <div>
                <span class="fw-semibold"><?php echo date('D, M j', strtotime($day['date'])); ?></span>
                <?php if ($is_today): ?>
                  <span class="badge bg-primary ms-2">Today</span>
                <?php endif; ?>
              </div>
              <div class="small-muted"><?php echo $day['estimated_minutes']; ?> min</div>
            </div>
            
            <div class="list-group list-group-flush">
              <?php foreach ($items as $item_str): ?>
                <?php if (empty($item_str)) continue; ?>
                <?php list($item_id, $exercise_name, $sets_reps) = explode(':', $item_str, 3); ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                  <span><?php echo htmlspecialchars($exercise_name); ?></span>
                  <span class="badge bg-secondary"><?php echo htmlspecialchars($sets_reps); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
            
            <?php if ($is_today): ?>
              <div class="mt-2">
                <a href="/mysihat/training/today.php?date=<?php echo htmlspecialchars($day['date']); ?>" 
                   class="btn btn-primary btn-sm">
                  <i class="bi bi-play-circle"></i> Start Today's Workout
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="card card-metric p-4 text-center">
        <div class="text-muted">
          <i class="bi bi-calendar-x fs-1"></i>
          <p class="mt-3">No plan generated yet. Use the form on the left to create your weekly plan.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

