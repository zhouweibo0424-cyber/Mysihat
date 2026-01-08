<?php
/**
 * Insight & Recommendation Page
 * Weekly metrics and automatic plan adjustments
 */
require_once __DIR__ . "/lib/db.php";
require_once __DIR__ . "/lib/auth.php";
require_once __DIR__ . "/lib/plan_service.php";
require_once __DIR__ . "/lib/metrics_service.php";
require_once __DIR__ . "/lib/recommendation_service.php";

$user_id = get_user_id();
$plan_service = new PlanService($pdo);
$metrics_service = new MetricsService($pdo);
$recommendation_service = new RecommendationService($pdo);

// Get week selection
$week_start = $_GET["week"] ?? $plan_service->get_week_start();
$message = "";

// Handle apply recommendation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["apply_recommendation"])) {
  try {
    $recommendation = $recommendation_service->generate_recommendation($user_id, $week_start);
    if ($recommendation) {
      $result = $recommendation_service->apply_recommendation($user_id, $recommendation);
      $message = "Recommendation applied! Next week plan generated.";
    } else {
      $message = "No recommendation available for this week.";
    }
  } catch (Exception $e) {
    $message = "Error: " . $e->getMessage();
  }
}

// Get current week plan
$current_plan = $plan_service->get_current_plan($user_id);
$current_week_start = $plan_service->get_week_start();

// Compute metrics
$metrics = $metrics_service->compute_weekly_metrics($user_id, $week_start);

// Generate recommendation
$recommendation = null;
if ($metrics) {
  $recommendation = $recommendation_service->generate_recommendation($user_id, $week_start);
}

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/nav.php";
?>

<h3 class="fw-bold mb-3">Insight & Recommendations</h3>

<?php if ($message): ?>
  <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?>">
    <?php echo htmlspecialchars($message); ?>
  </div>
<?php endif; ?>

<div class="row g-4 mb-4">
  <div class="col-12">
    <div class="card card-metric p-3">
      <label class="form-label">Select Week</label>
      <form method="get" class="d-flex gap-2">
        <input type="date" name="week" class="form-control" 
               value="<?php echo htmlspecialchars($week_start); ?>" 
               required>
        <button type="submit" class="btn btn-primary">View</button>
      </form>
    </div>
  </div>
</div>

<?php if (!$metrics): ?>
  <div class="card card-metric p-4 text-center">
    <div class="text-muted">
      <i class="bi bi-graph-down fs-1"></i>
      <p class="mt-3">No data available for week starting <?php echo htmlspecialchars($week_start); ?>.</p>
      <p class="small">Generate a plan and complete workouts to see insights.</p>
    </div>
  </div>
<?php else: ?>
  <div class="row g-4 mb-4">
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card card-metric p-3">
        <div class="small-muted">Planned Sessions</div>
        <div class="fs-3 fw-bold"><?php echo $metrics['planned_sessions']; ?></div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card card-metric p-3">
        <div class="small-muted">Completed Sessions</div>
        <div class="fs-3 fw-bold"><?php echo $metrics['completed_sessions']; ?></div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card card-metric p-3">
        <div class="small-muted">Adherence Rate</div>
        <div class="fs-3 fw-bold"><?php echo round($metrics['adherence_rate'] * 100); ?>%</div>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-3">
      <div class="card card-metric p-3">
        <div class="small-muted">Avg RPE</div>
        <div class="fs-3 fw-bold">
          <?php echo $metrics['avg_rpe'] ? number_format($metrics['avg_rpe'], 1) : 'N/A'; ?>
        </div>
      </div>
    </div>
  </div>
  
  <div class="row g-4 mb-4">
    <div class="col-12 col-md-6">
      <div class="card card-metric p-3">
        <div class="small-muted mb-2">Adherence Progress</div>
        <div class="progress" style="height: 30px;">
          <div class="progress-bar" role="progressbar" 
               style="width: <?php echo $metrics['adherence_rate'] * 100; ?>%"
               aria-valuenow="<?php echo $metrics['adherence_rate'] * 100; ?>" 
               aria-valuemin="0" aria-valuemax="100">
            <?php echo round($metrics['adherence_rate'] * 100); ?>%
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="card card-metric p-3">
        <div class="small-muted">Miss Streak</div>
        <div class="fs-4 fw-bold"><?php echo $metrics['miss_streak']; ?> days</div>
        <div class="small-muted">High RPE Streak: <?php echo $metrics['high_rpe_streak']; ?></div>
      </div>
    </div>
  </div>
  
  <?php if ($recommendation && !empty($recommendation['reasons'])): ?>
    <div class="card card-metric p-4 mb-4">
      <h5 class="fw-bold mb-3">Recommendation</h5>
      
      <div class="row g-4 mb-4">
        <div class="col-12 col-md-6">
          <div class="p-3 bg-light rounded">
            <div class="fw-semibold mb-2">Current Plan</div>
            <div class="small-muted">
              <div><?php echo $recommendation['original']['days_per_week']; ?> days/week</div>
              <div><?php echo $recommendation['original']['session_duration']; ?> min/session</div>
              <div>Mode: <?php echo htmlspecialchars($recommendation['original']['mode']); ?></div>
              <div class="mt-2 fw-semibold">
                Total: <?php echo $recommendation['original']['weekly_minutes']; ?> min/week
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="p-3 bg-primary bg-opacity-10 rounded border border-primary">
            <div class="fw-semibold mb-2 text-primary">Recommended Plan</div>
            <div class="small-muted">
              <div><?php echo $recommendation['recommended']['days_per_week']; ?> days/week</div>
              <div><?php echo $recommendation['recommended']['session_duration']; ?> min/session</div>
              <div>Mode: <?php echo htmlspecialchars($recommendation['recommended']['mode']); ?></div>
              <div class="mt-2 fw-semibold text-primary">
                Total: <?php echo $recommendation['recommended']['weekly_minutes']; ?> min/week
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="mb-3">
        <div class="fw-semibold mb-2">Reasons</div>
        <ul class="list-unstyled">
          <?php foreach ($recommendation['reasons'] as $reason): ?>
            <li class="mb-1">
              <i class="bi bi-check-circle text-success"></i>
              <?php echo htmlspecialchars($reason); ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      
      <?php if (isset($recommendation['reduce_sets'])): ?>
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i> Recommendation includes reducing sets by 1 for main exercises (min 2 sets).
        </div>
      <?php endif; ?>
      
      <?php if (isset($recommendation['add_accessory'])): ?>
        <div class="alert alert-info">
          <i class="bi bi-info-circle"></i> Recommendation includes adding 1 accessory exercise or +1 set.
        </div>
      <?php endif; ?>
      
      <form method="post" onsubmit="return confirm('Apply this recommendation and generate next week plan?');">
        <button type="submit" name="apply_recommendation" class="btn btn-primary btn-lg">
          <i class="bi bi-check-circle"></i> Apply Recommendation
        </button>
      </form>
    </div>
  <?php else: ?>
    <div class="card card-metric p-4">
      <div class="text-muted text-center">
        <i class="bi bi-info-circle fs-1"></i>
        <p class="mt-3">No recommendations available yet. Complete more workouts to get personalized suggestions.</p>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

