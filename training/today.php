<?php
require_once __DIR__ . "/lib/db.php";
require_once __DIR__ . "/lib/auth.php";
require_once __DIR__ . "/lib/plan_service.php";

date_default_timezone_set('Asia/Kuala_Lumpur');

$user_id = get_user_id();
$plan_service = new PlanService($pdo);

/**
 * FIX: Start Next -> PRG redirect so URL keeps date.
 */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["start_next"])) {
  $next_date = $_POST["next_date"] ?? "";
  if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $next_date)) {
    header("Location: ./today.php?date=" . urlencode($next_date) . "&early=1");
    exit();
  }
}

$today_date  = date('Y-m-d');
$target_date = $_GET["date"] ?? $today_date;
$is_early = (isset($_GET['early']) && $_GET['early'] === '1');

$week_start = $plan_service->get_week_start($target_date);

// Find plan_day for target date
$stmt = $pdo->prepare("
  SELECT pd.plan_day_id, pd.date, pd.estimated_minutes, p.user_id
  FROM plan_days pd
  JOIN plans p ON pd.plan_id = p.plan_id
  WHERE p.user_id = ? AND pd.date = ?
  LIMIT 1
");
$stmt->execute([$user_id, $target_date]);
$plan_day = $stmt->fetch(PDO::FETCH_ASSOC);

// If no plan today: find next plan day + preview
$next_plan_day = null;
$next_preview_items = [];

if (!$plan_day) {
  $stmt = $pdo->prepare("
    SELECT pd.plan_day_id, pd.date, pd.estimated_minutes
    FROM plan_days pd
    JOIN plans p ON pd.plan_id = p.plan_id
    WHERE p.user_id = ? AND pd.date > ?
    ORDER BY pd.date ASC
    LIMIT 1
  ");
  $stmt->execute([$user_id, $target_date]);
  $next_plan_day = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($next_plan_day) {
    $stmt = $pdo->prepare("
      SELECT e.name AS exercise_name, pi.target_sets, pi.target_reps
      FROM plan_items pi
      JOIN exercises e ON e.exercise_id = pi.exercise_id
      WHERE pi.plan_day_id = ?
      ORDER BY pi.order_no ASC
    ");
    $stmt->execute([(int)$next_plan_day['plan_day_id']]);
    $next_preview_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}

// If plan_day exists: load full plan day + ownership check
$full_plan_day = null;
if ($plan_day) {
  $plan_day_id = (int)$plan_day['plan_day_id'];
  $full_plan_day = $plan_service->get_plan_day($plan_day_id);
  if ((int)$full_plan_day['user_id'] !== $user_id) {
    $error = "Access denied";
    $plan_day = null;
  }
}

// Check active/completed workout
$active_workout = null;
$completed_workout = null;

if ($plan_day) {
  $plan_day_id = (int)$plan_day['plan_day_id'];

  $stmt = $pdo->prepare("
    SELECT workout_id, start_time, end_time, duration_min, status
    FROM workouts
    WHERE user_id = ? AND plan_day_id = ? AND status = 'completed'
    ORDER BY start_time DESC
    LIMIT 1
  ");
  $stmt->execute([$user_id, $plan_day_id]);
  $completed_workout = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle start workout
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["start_workout"])) {
  if (!$plan_day) {
    $error = "No plan found";
  } else {
    $stmt = $pdo->prepare("
      INSERT INTO workouts (user_id, plan_day_id, start_time, status)
      VALUES (?, ?, NOW(), 'completed')
    ");
    $stmt->execute([$user_id, $plan_day_id]);
    $workout_id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("
      INSERT INTO workout_items (workout_id, exercise_id, is_completed)
      SELECT ?, exercise_id, 0
      FROM plan_items
      WHERE plan_day_id = ?
      ORDER BY order_no
    ");
    $stmt->execute([$workout_id, $plan_day_id]);

    $redir = "./today.php?date=" . urlencode($target_date);
    if ($is_early) $redir .= "&early=1";
    header("Location: " . $redir);
    exit();
  }
}

// Load workout items if workout exists
$workout_items = [];
$is_finished = false;

if ($completed_workout) {
  $is_finished = !empty($completed_workout['end_time']);

  $stmt = $pdo->prepare("
    SELECT wi.*, e.name as exercise_name, pi.target_sets, pi.target_reps
    FROM workout_items wi
    JOIN exercises e ON wi.exercise_id = e.exercise_id
    LEFT JOIN plan_items pi ON pi.plan_day_id = ? AND pi.exercise_id = wi.exercise_id
    WHERE wi.workout_id = ?
    ORDER BY wi.workout_item_id ASC
  ");
  $stmt->execute([$plan_day_id, $completed_workout['workout_id']]);
  $workout_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/nav.php";
?>

<h3 class="fw-bold mb-3">Today's Workout</h3>

<?php if (isset($error)): ?>
  <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (!$plan_day): ?>
  <div class="alert alert-info">
    <strong>Today is a rest day.</strong>
    <div class="mt-1">
      No plan scheduled for <?php echo htmlspecialchars(date('M j, Y', strtotime($target_date))); ?>.
    </div>
  </div>

  <?php if ($next_plan_day): ?>
    <div class="card card-metric p-4">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <div class="small-muted">Next training day</div>
          <div class="fw-bold">
            <?php echo htmlspecialchars(date('l, M j, Y', strtotime($next_plan_day['date']))); ?>
          </div>
          <div class="small text-muted">
            Estimated: <?php echo (int)$next_plan_day['estimated_minutes']; ?> min
          </div>
        </div>
        <div class="text-end">
          <form method="post" class="m-0">
            <input type="hidden" name="next_date" value="<?php echo htmlspecialchars($next_plan_day['date']); ?>">
            <button type="submit"
                    name="start_next"
                    class="btn btn-primary"
                    onclick="return confirm('Start the next scheduled workout early? This will use the plan for <?php echo htmlspecialchars($next_plan_day['date']); ?>.');">
              <i class="bi bi-play-circle"></i> Start Next Workout Now
            </button>
          </form>
        </div>
      </div>

      <?php if (!empty($next_preview_items)): ?>
        <div class="mt-3">
          <div class="small-muted mb-2">Preview (Next workout exercises)</div>
          <div class="list-group">
            <?php foreach ($next_preview_items as $pi): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <span><?php echo htmlspecialchars($pi['exercise_name']); ?></span>
                <span class="badge bg-secondary">
                  <?php echo (int)$pi['target_sets']; ?> × <?php echo (int)$pi['target_reps']; ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="mt-3">
        <a href="./plan.php" class="btn btn-outline-secondary">Go to Plan Builder</a>
      </div>
    </div>
  <?php else: ?>
    <div class="card card-metric p-4 text-center">
      <div class="text-muted">
        <i class="bi bi-calendar-x fs-1"></i>
        <p class="mt-3 mb-3">No upcoming training day found. Please generate a plan.</p>
        <a href="./plan.php" class="btn btn-primary">Go to Plan Builder</a>
      </div>
    </div>
  <?php endif; ?>

<?php else: ?>
  <?php if ($is_early): ?>
    <div class="alert alert-warning">
      You are starting the next scheduled workout early (scheduled date:
      <strong><?php echo htmlspecialchars(date('M j, Y', strtotime($target_date))); ?></strong>).
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <div class="col-12 col-md-8">
      <div class="card card-metric p-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <h5 class="fw-bold mb-0"><?php echo date('l, M j', strtotime($plan_day['date'])); ?></h5>
          <div class="small-muted">Estimated: <?php echo (int)$plan_day['estimated_minutes']; ?> min</div>
        </div>

        <?php if (!$completed_workout): ?>
          <form method="post" action="./today.php?date=<?php echo urlencode($target_date); ?><?php echo $is_early ? '&early=1' : ''; ?>">
            <button type="submit" name="start_workout" class="btn btn-primary btn-lg w-100 mb-4">
              <i class="bi bi-play-circle"></i> Start Workout
            </button>
          </form>

          <div class="list-group">
            <?php foreach ($full_plan_day['items'] as $item): ?>
              <div class="list-group-item">
                <div class="d-flex justify-content-between align-items-center">
                  <span class="fw-semibold"><?php echo htmlspecialchars($item['exercise_name']); ?></span>
                  <span class="badge bg-secondary">
                    <?php echo (int)$item['target_sets']; ?> × <?php echo (int)$item['target_reps']; ?>
                  </span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        <?php else: ?>
          <div id="workout-timer" class="mb-3 p-3 bg-light rounded">
            <div class="d-flex align-items-center justify-content-between">
              <div>
                <div class="small-muted">Workout Time</div>
                <div class="fs-4 fw-bold" id="timer-display">00:00:00</div>
              </div>
              <div class="text-end">
                <div class="small-muted">Started</div>
                <div><?php echo date('H:i', strtotime($completed_workout['start_time'])); ?></div>
                <?php if ($is_finished): ?>
                  <div class="small text-success mt-1">
                    Finished <?php echo date('H:i', strtotime($completed_workout['end_time'])); ?>
                  </div>
                <?php else: ?>
                  <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="pause-resume-btn">
                    Pause
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php if ($is_finished): ?>
            <div class="alert alert-success">
              Workout already finished. You can view details in <a href="./history.php" class="alert-link">History</a>.
            </div>
          <?php endif; ?>

          <form id="workout-form"
                data-workout-id="<?php echo (int)$completed_workout['workout_id']; ?>"
                data-workout-start="<?php echo htmlspecialchars($completed_workout['start_time']); ?>"
                data-workout-end="<?php echo htmlspecialchars($completed_workout['end_time'] ?? ''); ?>"
                data-duration-min="<?php echo htmlspecialchars($completed_workout['duration_min'] ?? ''); ?>">
            <div class="list-group mb-3">
              <?php foreach ($workout_items as $item): ?>
                <div class="list-group-item workout-item"
                     data-item-id="<?php echo (int)$item['workout_item_id']; ?>"
                     data-exercise-id="<?php echo (int)$item['exercise_id']; ?>">
                  <div class="d-flex align-items-start gap-3">
                    <div class="form-check mt-1">
                      <input class="form-check-input item-completed" type="checkbox"
                             <?php echo ((int)$item['is_completed'] === 1) ? 'checked' : ''; ?>
                             id="item-<?php echo (int)$item['workout_item_id']; ?>"
                             <?php echo $is_finished ? 'disabled' : ''; ?>>
                    </div>

                    <div class="flex-grow-1">
                      <label class="form-check-label fw-semibold" for="item-<?php echo (int)$item['workout_item_id']; ?>">
                        <?php echo htmlspecialchars($item['exercise_name']); ?>
                      </label>

                      <div class="small-muted">
                        Target: <?php echo (int)$item['target_sets']; ?> × <?php echo (int)$item['target_reps']; ?>
                      </div>

                      <div class="mt-2">
                        <label class="form-label small">RPE (1-10)</label>
                        <input type="range" class="form-range item-rpe" min="1" max="10"
                               value="<?php echo htmlspecialchars($item['rpe'] ?? 5); ?>"
                               data-item-id="<?php echo (int)$item['workout_item_id']; ?>"
                               <?php echo $is_finished ? 'disabled' : ''; ?>>
                        <div class="d-flex justify-content-between small text-muted">
                          <span>1</span>
                          <span id="rpe-value-<?php echo (int)$item['workout_item_id']; ?>">
                            <?php echo htmlspecialchars($item['rpe'] ?? 5); ?>
                          </span>
                          <span>10</span>
                        </div>
                      </div>

                      <button class="btn btn-sm btn-outline-secondary mt-2" type="button"
                              data-bs-toggle="collapse"
                              data-bs-target="#details-<?php echo (int)$item['workout_item_id']; ?>"
                              <?php echo $is_finished ? 'disabled' : ''; ?>>
                        <i class="bi bi-chevron-down"></i> Details (optional)
                      </button>

                      <div class="collapse mt-2" id="details-<?php echo (int)$item['workout_item_id']; ?>">
                        <div class="row g-2">
                          <div class="col-4">
                            <label class="form-label small mb-1">Sets</label>
                            <input type="number" class="form-control form-control-sm item-sets"
                                   value="<?php echo htmlspecialchars($item['sets'] ?? ''); ?>"
                                   <?php echo $is_finished ? 'disabled' : ''; ?>>
                          </div>
                          <div class="col-4">
                            <label class="form-label small mb-1">Reps</label>
                            <input type="number" class="form-control form-control-sm item-reps"
                                   value="<?php echo htmlspecialchars($item['reps'] ?? ''); ?>"
                                   <?php echo $is_finished ? 'disabled' : ''; ?>>
                          </div>
                          <div class="col-4">
                            <label class="form-label small mb-1">Weight</label>
                            <input type="number" step="0.1" class="form-control form-control-sm item-weight"
                                   value="<?php echo htmlspecialchars($item['weight'] ?? ''); ?>"
                                   <?php echo $is_finished ? 'disabled' : ''; ?>>
                          </div>
                        </div>
                      </div>

                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <?php if (!$is_finished): ?>
              <button type="button" class="btn btn-success btn-lg w-100" id="finish-workout-btn">
                <i class="bi bi-check-circle"></i> Finish Workout
              </button>
            <?php endif; ?>
          </form>

        <?php endif; ?>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card card-metric p-3">
        <h6 class="fw-bold mb-3">Quick Stats</h6>
        <?php if ($completed_workout): ?>
          <div class="mb-2">
            <div class="small-muted">Exercises</div>
            <div class="fw-bold"><?php echo count($workout_items); ?> planned</div>
          </div>
          <div class="mb-2">
            <div class="small-muted">Completed</div>
            <div class="fw-bold" id="completed-count">
              <?php echo count(array_filter($workout_items, fn($i) => (int)$i['is_completed'] === 1)); ?>
            </div>
          </div>
        <?php else: ?>
          <div class="text-muted">Start workout to see stats</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
