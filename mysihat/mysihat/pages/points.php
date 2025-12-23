<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

$user_id = (int)($_SESSION["user_id"] ?? 0);
$today = date("Y-m-d");

$message = "";
$error_message = "";

// Create points from today's steps (simple rule)
if (isset($_POST["action"]) && $_POST["action"] === "generate_points") {
  // Read today's steps
  $step_statement = $pdo->prepare("SELECT steps FROM daily_steps WHERE user_id = :user_id AND step_date = :day LIMIT 1");
  $step_statement->execute([":user_id" => $user_id, ":day" => $today]);
  $row = $step_statement->fetch();
  $steps = $row ? (int)$row["steps"] : 0;

  $earned_points = intdiv($steps, 100); // 100 steps = 1 point

  // For safety: clear today's auto-generated points first (same reason label)
  $delete = $pdo->prepare("DELETE FROM points_ledger WHERE user_id = :user_id AND point_date = :day AND reason = :reason");
  $delete->execute([":user_id" => $user_id, ":day" => $today, ":reason" => "Auto points from steps"]);

  // Insert new record
  $insert = $pdo->prepare("
    INSERT INTO points_ledger (user_id, point_date, points_earned, points_spent, reason)
    VALUES (:user_id, :day, :earned, 0, :reason)
  ");
  $insert->execute([
    ":user_id" => $user_id,
    ":day" => $today,
    ":earned" => $earned_points,
    ":reason" => "Auto points from steps"
  ]);

  $message = "Points generated from today's steps. Earned points: " . $earned_points;
}

// Fetch today's net points
$today_net_statement = $pdo->prepare("
  SELECT COALESCE(SUM(points_earned - points_spent), 0) AS net_points
  FROM points_ledger
  WHERE user_id = :user_id AND point_date = :day
");
$today_net_statement->execute([":user_id" => $user_id, ":day" => $today]);
$today_net = (int)($today_net_statement->fetch()["net_points"] ?? 0);

// Fetch latest ledger entries
$ledger_statement = $pdo->prepare("
  SELECT point_date, points_earned, points_spent, reason, created_at
  FROM points_ledger
  WHERE user_id = :user_id
  ORDER BY created_at DESC
  LIMIT 20
");
$ledger_statement->execute([":user_id" => $user_id]);
$ledger = $ledger_statement->fetchAll();
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<div class="container py-4" style="max-width: 960px;">
  <h3 class="fw-bold mb-3">Points</h3>

  <?php if ($message !== ""): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <?php if ($error_message !== ""): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
  <?php endif; ?>

  <div class="row g-3 mb-3">
    <div class="col-12 col-md-6">
      <div class="card card-metric p-3">
        <div class="small-muted">Today</div>
        <div class="fs-2 fw-bold"><?php echo htmlspecialchars((string)$today_net); ?> points</div>
        <div class="small-muted">Rule: 100 steps = 1 point</div>

        <form method="post" class="mt-3">
          <input type="hidden" name="action" value="generate_points">
          <button class="btn btn-primary" type="submit"><i class="bi bi-arrow-repeat"></i> Generate points from steps</button>
        </form>
      </div>
    </div>

    <div class="col-12 col-md-6">
      <div class="card card-metric p-3">
        <div class="fw-semibold mb-1">How it works</div>
        <div class="small-muted">
          Steps page stores daily steps. This page reads today's steps and writes a points record (auto points from steps).
          Later you can add more rules, for example streak bonuses or daily challenges.
        </div>
      </div>
    </div>
  </div>

  <div class="card card-metric p-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div class="fw-semibold">Latest point records</div>
      <div class="small-muted">Showing last 20 records</div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>Date</th>
            <th>Earned</th>
            <th>Spent</th>
            <th>Reason</th>
            <th>Time</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($ledger) === 0): ?>
            <tr><td colspan="5" class="small-muted">No point records yet.</td></tr>
          <?php endif; ?>

          <?php foreach ($ledger as $item): ?>
            <tr>
              <td class="small-muted"><?php echo htmlspecialchars($item["point_date"]); ?></td>
              <td><?php echo htmlspecialchars((string)$item["points_earned"]); ?></td>
              <td><?php echo htmlspecialchars((string)$item["points_spent"]); ?></td>
              <td><?php echo htmlspecialchars($item["reason"]); ?></td>
              <td class="small-muted"><?php echo htmlspecialchars($item["created_at"]); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>
