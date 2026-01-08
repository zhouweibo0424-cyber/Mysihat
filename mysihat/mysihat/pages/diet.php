<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

$user_id = (int)($_SESSION["user_id"] ?? 0);
$message = "";
$error_message = "";

// Add meal
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $meal_text = trim($_POST["meal_text"] ?? "");
  $estimated_kcal = trim($_POST["estimated_kcal"] ?? "");

  if ($meal_text === "") {
    $error_message = "Meal text cannot be empty.";
  } else {
    $kcal_value = null;
    if ($estimated_kcal !== "") {
      $kcal_value = (int)$estimated_kcal;
      if ($kcal_value < 0) {
        $kcal_value = null;
      }
    }

    $statement = $pdo->prepare("
      INSERT INTO meals (user_id, meal_time, meal_text, estimated_kcal)
      VALUES (:user_id, NOW(), :meal_text, :estimated_kcal)
    ");
    $statement->execute([
      ":user_id" => $user_id,
      ":meal_text" => $meal_text,
      ":estimated_kcal" => $kcal_value
    ]);

    $message = "Meal logged successfully.";
  }
}

// Fetch latest meals
$list = $pdo->prepare("
  SELECT id, meal_time, meal_text, estimated_kcal
  FROM meals
  WHERE user_id = :user_id
  ORDER BY meal_time DESC
  LIMIT 20
");
$list->execute([":user_id" => $user_id]);
$meals = $list->fetchAll();
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<div class="container py-4" style="max-width: 960px;">
  <h3 class="fw-bold mb-3">Diet</h3>

  <?php if ($message !== ""): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <?php if ($error_message !== ""): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
  <?php endif; ?>

  <div class="card card-metric p-3 mb-3">
    <form method="post">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-8">
          <label class="form-label">What did you eat?</label>
          <input class="form-control" name="meal_text" placeholder="Example: nasi lemak, teh tarik, chicken rice" required>
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Estimated calories (optional)</label>
          <input class="form-control" name="estimated_kcal" type="number" min="0" placeholder="Example: 650">
        </div>
      </div>
      <button class="btn btn-primary mt-3" type="submit"><i class="bi bi-plus-circle"></i> Add meal</button>
    </form>
  </div>

  <div class="card card-metric p-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <div class="fw-semibold">Latest meals</div>
      <div class="small-muted">Showing last 20 records</div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>Time</th>
            <th>Meal</th>
            <th>Estimated calories</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($meals) === 0): ?>
            <tr><td colspan="3" class="small-muted">No meals yet.</td></tr>
          <?php endif; ?>

          <?php foreach ($meals as $meal): ?>
            <tr>
              <td class="small-muted"><?php echo htmlspecialchars($meal["meal_time"]); ?></td>
              <td><?php echo htmlspecialchars($meal["meal_text"]); ?></td>
              <td class="small-muted"><?php echo $meal["estimated_kcal"] === null ? "-" : htmlspecialchars((string)$meal["estimated_kcal"]) . " kcal"; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>
