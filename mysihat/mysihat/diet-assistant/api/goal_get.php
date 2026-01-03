<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . "/../config/bootstrap.php";

$user_id = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : 0;

if ($user_id <= 0) {
  echo json_encode(["success" => false, "message" => "Missing user_id"]);
  exit;
}

$sql = "SELECT id, user_id, goal_type, daily_calorie_target, protein_target_g, carbs_target_g, fat_target_g, created_at, updated_at
        FROM diet_goals
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Prepare failed"]);
  exit;
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

$row = $res ? $res->fetch_assoc() : null;

$stmt->close();

if (!$row) {
  echo json_encode(["success" => true, "data" => null]);
  exit;
}

echo json_encode([
  "success" => true,
  "data" => [
    "id" => (int)$row["id"],
    "user_id" => (int)$row["user_id"],
    "goal_type" => $row["goal_type"],
    "daily_calorie_target" => (float)$row["daily_calorie_target"],
    "protein_target_g" => (float)$row["protein_target_g"],
    "carbs_target_g" => (float)$row["carbs_target_g"],
    "fat_target_g" => (float)$row["fat_target_g"],
    "created_at" => $row["created_at"],
    "updated_at" => $row["updated_at"]
  ]
]);
