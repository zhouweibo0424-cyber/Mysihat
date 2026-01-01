<?php
/**
 * API: Finish Workout
 * Accepts JSON POST with workout_id
 * Server authoritative:
 * - Ignore client end_time / duration_min
 * - Use server time for end_time
 * - Use MySQL TIMESTAMPDIFF for duration_min
 */
header('Content-Type: application/json');

require_once __DIR__ . "/../lib/db.php";
require_once __DIR__ . "/../lib/auth.php";

$user_id = get_user_id();
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
  exit();
}

$workout_id = isset($input['workout_id']) ? (int)$input['workout_id'] : 0;
if ($workout_id <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid workout_id']);
  exit();
}

// Verify workout belongs to user and exists
$stmt = $pdo->prepare("SELECT workout_id, user_id, start_time, status FROM workouts WHERE workout_id = ?");
$stmt->execute([$workout_id]);
$workout = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$workout || (int)$workout['user_id'] !== (int)$user_id) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Access denied']);
  exit();
}

if (empty($workout['start_time'])) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Workout has no start_time']);
  exit();
}

// Always use server time (ignore client-supplied end_time/duration_min)
$end_time = date('Y-m-d H:i:s');

// Update workout using MySQL diff (stable, avoids client timezone issues)
$stmt = $pdo->prepare("
  UPDATE workouts
  SET end_time = ?,
      duration_min = GREATEST(0, TIMESTAMPDIFF(MINUTE, start_time, ?)),
      status = 'completed'
  WHERE workout_id = ?
");
$stmt->execute([$end_time, $end_time, $workout_id]);

// Re-read duration_min to return authoritative value
$stmt = $pdo->prepare("SELECT duration_min FROM workouts WHERE workout_id = ?");
$stmt->execute([$workout_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$duration_min = isset($row['duration_min']) ? (int)$row['duration_min'] : 0;

// Compute summary (keep your original structure, make volume null-safe)
$stmt = $pdo->prepare("
  SELECT 
    COUNT(*) as total_items,
    SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_items,
    AVG(CASE WHEN rpe IS NOT NULL THEN rpe END) as avg_rpe,
    SUM(
      COALESCE(sets, 0) * COALESCE(reps, 0) * COALESCE(weight, 1)
    ) as volume
  FROM workout_items
  WHERE workout_id = ?
");
$stmt->execute([$workout_id]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

$total_items = (int)($summary['total_items'] ?? 0);
$completed_items = (int)($summary['completed_items'] ?? 0);

$completion_rate = $total_items > 0 ? ($completed_items / $total_items) : 0;

$avg_rpe = $summary['avg_rpe'];
$avg_rpe = ($avg_rpe !== null) ? round((float)$avg_rpe, 1) : null;

$volume = $summary['volume'];
$volume = ($volume !== null) ? round((float)$volume, 1) : null;

echo json_encode([
  'ok' => true,
  'summary' => [
    'completion_rate' => round($completion_rate * 100, 1),
    'avg_rpe' => $avg_rpe,
    'duration_min' => $duration_min,
    'volume' => $volume,
    'total_items' => $total_items,
    'completed_items' => $completed_items,
  ],
]);
