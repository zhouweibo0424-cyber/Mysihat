<?php
/**
 * Workout History Page
 * List and view past workouts
 */
require_once __DIR__ . "/lib/db.php";
require_once __DIR__ . "/lib/auth.php";

$user_id = get_user_id();

// Get workout detail if workout_id provided
$workout_id = isset($_GET["workout_id"]) ? (int)$_GET["workout_id"] : null;
$workout_detail = null;

if ($workout_id) {
  $stmt = $pdo->prepare("
    SELECT w.*, pd.date, pd.estimated_minutes
    FROM workouts w
    LEFT JOIN plan_days pd ON w.plan_day_id = pd.plan_day_id
    WHERE w.workout_id = ? AND w.user_id = ?
  ");
  $stmt->execute([$workout_id, $user_id]);
  $workout_detail = $stmt->fetch();
  
  if ($workout_detail) {
    // Get workout items
    $stmt = $pdo->prepare("
      SELECT wi.*, e.name as exercise_name
      FROM workout_items wi
      JOIN exercises e ON wi.exercise_id = e.exercise_id
      WHERE wi.workout_id = ?
      ORDER BY wi.workout_item_id ASC
    ");
    $stmt->execute([$workout_id]);
    $workout_detail['items'] = $stmt->fetchAll();
    
    // Calculate completion rate and avg RPE
    $completed = count(array_filter($workout_detail['items'], fn($i) => $i['is_completed']));
    $total = count($workout_detail['items']);
    $workout_detail['completion_rate'] = $total > 0 ? $completed / $total : 0;
    
    $rpes = array_filter(array_column($workout_detail['items'], 'rpe'), fn($r) => $r !== null);
    $workout_detail['avg_rpe'] = !empty($rpes) ? array_sum($rpes) / count($rpes) : null;
  }
}

// Get workout list
$stmt = $pdo->prepare("
  SELECT w.workout_id, w.start_time, w.end_time, w.duration_min, w.status,
         pd.date, pd.estimated_minutes,
         COUNT(wi.workout_item_id) as total_items,
         SUM(wi.is_completed) as completed_items,
         AVG(wi.rpe) as avg_rpe
  FROM workouts w
  LEFT JOIN plan_days pd ON w.plan_day_id = pd.plan_day_id
  LEFT JOIN workout_items wi ON w.workout_id = wi.workout_id
  WHERE w.user_id = ?
  GROUP BY w.workout_id
  ORDER BY w.start_time DESC
  LIMIT 50
");
$stmt->execute([$user_id]);
$workouts = $stmt->fetchAll();

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/nav.php";
?>

<h3 class="fw-bold mb-3">Workout History</h3>

<?php if ($workout_detail): ?>
  <div class="mb-3">
    <a href="/mysihat/training/history.php" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i> Back to List
    </a>
  </div>
  
  <div class="card card-metric p-4">
    <h5 class="fw-bold mb-3">Workout Details</h5>
    
    <div class="row g-3 mb-3">
      <div class="col-6 col-md-3">
        <div class="small-muted">Date</div>
        <div class="fw-semibold"><?php echo date('M j, Y', strtotime($workout_detail['start_time'])); ?></div>
      </div>
      <div class="col-6 col-md-3">
        <div class="small-muted">Duration</div>
        <div class="fw-semibold">
          <?php echo $workout_detail['duration_min'] ?? 'N/A'; ?> min
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="small-muted">Completion</div>
        <div class="fw-semibold">
          <?php echo round($workout_detail['completion_rate'] * 100); ?>%
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="small-muted">Avg RPE</div>
        <div class="fw-semibold">
          <?php echo $workout_detail['avg_rpe'] ? number_format($workout_detail['avg_rpe'], 1) : 'N/A'; ?>
        </div>
      </div>
    </div>
    
    <div class="table-responsive">
      <table class="table table-sm">
        <thead>
          <tr>
            <th>Exercise</th>
            <th>Completed</th>
            <th>RPE</th>
            <th>Sets</th>
            <th>Reps</th>
            <th>Weight</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($workout_detail['items'] as $item): ?>
            <tr>
              <td><?php echo htmlspecialchars($item['exercise_name']); ?></td>
              <td>
                <?php if ($item['is_completed']): ?>
                  <i class="bi bi-check-circle text-success"></i>
                <?php else: ?>
                  <i class="bi bi-x-circle text-muted"></i>
                <?php endif; ?>
              </td>
              <td><?php echo $item['rpe'] ?? '-'; ?></td>
              <td><?php echo $item['sets'] ?? '-'; ?></td>
              <td><?php echo $item['reps'] ?? '-'; ?></td>
              <td><?php echo $item['weight'] ? number_format($item['weight'], 1) : '-'; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php else: ?>
  <div class="card card-metric p-4">
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Date</th>
            <th>Duration</th>
            <th>Completion</th>
            <th>Avg RPE</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($workouts)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted">No workouts recorded yet.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($workouts as $workout): ?>
              <tr>
                <td><?php echo date('M j, Y H:i', strtotime($workout['start_time'])); ?></td>
                <td><?php echo $workout['duration_min'] ?? 'N/A'; ?> min</td>
                <td>
                  <?php 
                  $total = (int)$workout['total_items'];
                  $completed = (int)$workout['completed_items'];
                  $rate = $total > 0 ? $completed / $total : 0;
                  echo round($rate * 100); 
                  ?>%
                </td>
                <td><?php echo $workout['avg_rpe'] ? number_format($workout['avg_rpe'], 1) : '-'; ?></td>
                <td>
                  <span class="badge bg-<?php echo $workout['status'] === 'completed' ? 'success' : 'secondary'; ?>">
                    <?php echo htmlspecialchars($workout['status']); ?>
                  </span>
                </td>
                <td>
                  <a href="/mysihat/training/history.php?workout_id=<?php echo $workout['workout_id']; ?>" 
                     class="btn btn-sm btn-outline-primary">
                    View
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . "/includes/footer.php"; ?>

