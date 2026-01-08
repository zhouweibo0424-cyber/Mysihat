<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

$user_id = (int)($_SESSION["user_id"] ?? 0);
$today = date("Y-m-d");

$message = "";
$error_message = "";

// Handle step update
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $steps_input = (int)($_POST["steps"] ?? 0);

  if ($steps_input < 0) {
    $error_message = "Steps cannot be negative.";
  } else {
    // Insert or update today's steps
    $statement = $pdo->prepare("
      INSERT INTO daily_steps (user_id, step_date, steps)
      VALUES (:user_id, :step_date, :steps)
      ON DUPLICATE KEY UPDATE steps = :steps_update
    ");
    $statement->execute([
      ":user_id" => $user_id,
      ":step_date" => $today,
      ":steps" => $steps_input,
      ":steps_update" => $steps_input
    ]);

    $message = "Saved today's steps successfully.";
  }
}

// Fetch today's steps
$fetch = $pdo->prepare("SELECT steps FROM daily_steps WHERE user_id = :user_id AND step_date = :step_date LIMIT 1");
$fetch->execute([":user_id" => $user_id, ":step_date" => $today]);
$row = $fetch->fetch();
$current_steps = $row ? (int)$row["steps"] : 0;
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<div class="container py-4" style="max-width: 760px;">
  <h3 class="fw-bold mb-3">Steps</h3>

  <?php if ($message !== ""): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <?php if ($error_message !== ""): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
  <?php endif; ?>

  <div class="card card-metric p-3 mb-3">
    <div class="small-muted">Today: <?php echo htmlspecialchars($today); ?></div>
    <div class="fs-2 fw-bold"><?php echo htmlspecialchars((string)$current_steps); ?> steps</div>

    <form method="post" class="mt-3">
      <label class="form-label">Update today's steps (manual input for web version)</label>
      <input class="form-control" type="number" name="steps" min="0" value="<?php echo htmlspecialchars((string)$current_steps); ?>" required>
      <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-save"></i> Save</button>
    </form>
  </div>

  <div class="alert alert-warning">
    <div class="fw-semibold">Note</div>
    <div class="small-muted">
      Web version uses manual steps input. If you later switch to a mobile application, you can integrate automatic step syncing.
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>
