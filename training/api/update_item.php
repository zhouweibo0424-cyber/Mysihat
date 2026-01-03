<?php
/**
 * API: Update Workout Item
 * Accepts JSON POST with workout_id, exercise_id, is_completed, rpe, sets, reps, weight
 */
header('Content-Type: application/json');

require_once __DIR__ . "/../lib/db.php";
require_once __DIR__ . "/../lib/auth.php";

$user_id = get_user_id();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
  exit();
}

$workout_id = isset($input['workout_id']) ? (int)$input['workout_id'] : 0;
$exercise_id = isset($input['exercise_id']) ? (int)$input['exercise_id'] : 0;
$is_completed = isset($input['is_completed']) ? (int)$input['is_completed'] : 0;
$rpe = isset($input['rpe']) ? (int)$input['rpe'] : null;
$sets = isset($input['sets']) ? (int)$input['sets'] : null;
$reps = isset($input['reps']) ? (int)$input['reps'] : null;
$weight = isset($input['weight']) ? (float)$input['weight'] : null;

// Validate
if ($workout_id <= 0 || $exercise_id <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid workout_id or exercise_id']);
  exit();
}

// Verify workout belongs to user
$stmt = $pdo->prepare("SELECT user_id FROM workouts WHERE workout_id = ?");
$stmt->execute([$workout_id]);
$workout = $stmt->fetch();

if (!$workout || (int)$workout['user_id'] !== $user_id) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'Access denied']);
  exit();
}

// Validate RPE range
if ($rpe !== null && ($rpe < 1 || $rpe > 10)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'RPE must be 1-10']);
  exit();
}

// Find or create workout_item
$stmt = $pdo->prepare("
  SELECT workout_item_id FROM workout_items
  WHERE workout_id = ? AND exercise_id = ?
  LIMIT 1
");
$stmt->execute([$workout_id, $exercise_id]);
$existing = $stmt->fetch();

if ($existing) {
  // Update
  $stmt = $pdo->prepare("
    UPDATE workout_items
    SET is_completed = ?, rpe = ?, sets = ?, reps = ?, weight = ?
    WHERE workout_item_id = ?
  ");
  $stmt->execute([
    $is_completed,
    $rpe,
    $sets,
    $reps,
    $weight,
    $existing['workout_item_id'],
  ]);
  $item_id = (int)$existing['workout_item_id'];
} else {
  // Insert
  $stmt = $pdo->prepare("
    INSERT INTO workout_items (workout_id, exercise_id, is_completed, rpe, sets, reps, weight)
    VALUES (?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->execute([
    $workout_id,
    $exercise_id,
    $is_completed,
    $rpe,
    $sets,
    $reps,
    $weight,
  ]);
  $item_id = (int)$pdo->lastInsertId();
}

// Return updated item
$stmt = $pdo->prepare("SELECT * FROM workout_items WHERE workout_item_id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

echo json_encode([
  'ok' => true,
  'item' => $item,
]);
