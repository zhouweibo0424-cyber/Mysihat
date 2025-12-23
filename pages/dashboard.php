<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

$user_id = (int)($_SESSION["user_id"] ?? 0);
$today = date("Y-m-d");

// Fetch today's steps
$steps_statement = $pdo->prepare("SELECT steps FROM daily_steps WHERE user_id = :user_id AND step_date = :step_date LIMIT 1");
$steps_statement->execute([":user_id" => $user_id, ":step_date" => $today]);
$steps_row = $steps_statement->fetch();
$today_steps = $steps_row ? (int)$steps_row["steps"] : 0;

// Fetch today's meal count
$meal_statement = $pdo->prepare("SELECT COUNT(*) AS meal_count FROM meals WHERE user_id = :user_id AND DATE(meal_time) = :day");
$meal_statement->execute([":user_id" => $user_id, ":day" => $today]);
$meal_count = (int)($meal_statement->fetch()["meal_count"] ?? 0);

// Fetch today's net points
$points_statement = $pdo->prepare("
  SELECT COALESCE(SUM(points_earned - points_spent), 0) AS net_points
  FROM points_ledger
  WHERE user_id = :user_id AND point_date = :day
");
$points_statement->execute([":user_id" => $user_id, ":day" => $today]);
$today_points = (int)($points_statement->fetch()["net_points"] ?? 0);

// Simple tip (placeholder)
$tip_text = "Tip: Try to reach 6,000–10,000 steps today. Small walk after meals helps.";
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h3 class="fw-bold mb-0">Dashboard</h3>
    <span class="small-muted">Today: <?php echo htmlspecialchars($today); ?></span>
  </div>

  <div class="row g-3">
    <div class="col-12 col-md-4">
      <div class="card card-metric p-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="small-muted">Today's Steps</div>
            <div class="fs-3 fw-bold"><?php echo htmlspecialchars((string)$today_steps); ?></div>
          </div>
          <div class="fs-2"><i class="bi bi-person-walking"></i></div>
        </div>
        <div class="small-muted mt-2">Update in Steps page.</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card card-metric p-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="small-muted">Today's Meals Logged</div>
            <div class="fs-3 fw-bold"><?php echo htmlspecialchars((string)$meal_count); ?></div>
          </div>
          <div class="fs-2"><i class="bi bi-egg-fried"></i></div>
        </div>
        <div class="small-muted mt-2">Log your meal in Diet page.</div>
      </div>
    </div>

    <div class="col-12 col-md-4">
      <div class="card card-metric p-3">
        <div class="d-flex align-items-center justify-content-between">
          <div>
            <div class="small-muted">Today's Net Points</div>
            <div class="fs-3 fw-bold"><?php echo htmlspecialchars((string)$today_points); ?></div>
          </div>
          <div class="fs-2"><i class="bi bi-stars"></i></div>
        </div>
        <div class="small-muted mt-2">Points are recorded in Points page.</div>
      </div>
    </div>

    <div class="col-12">
      <div class="card card-metric p-3">
        <div class="d-flex align-items-center gap-2">
          <div class="fs-4"><i class="bi bi-lightbulb"></i></div>
          <div>
            <div class="fw-semibold">Daily Tip</div>
            <div class="small-muted"><?php echo htmlspecialchars($tip_text); ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>
