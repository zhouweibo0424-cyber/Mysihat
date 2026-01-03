<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/../config/bootstrap.php";

$raw = file_get_contents("php://input");
$body = json_decode($raw, true);
if (!is_array($body)) $body = [];

$user_id = isset($body["user_id"]) ? (int)$body["user_id"] : 0;
$goal_type = isset($body["goal_type"]) ? (string)$body["goal_type"] : "maintain";

$daily = array_key_exists("daily_calorie_target", $body) ? $body["daily_calorie_target"] : null;
$protein = array_key_exists("protein_target_g", $body) ? $body["protein_target_g"] : null;
$carbs = array_key_exists("carbs_target_g", $body) ? $body["carbs_target_g"] : null;
$fat = array_key_exists("fat_target_g", $body) ? $body["fat_target_g"] : null;

if ($user_id <= 0) {
  http_response_code(400);
  echo json_encode(["success" => false, "message" => "Missing user_id"]);
  exit;
}

$nums = ["daily_calorie_target" => $daily, "protein_target_g" => $protein, "carbs_target_g" => $carbs, "fat_target_g" => $fat];
foreach ($nums as $k => $v) {
  if ($v === null || $v === "" || !is_numeric($v)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid input data"]);
    exit;
  }
  if ((float)$v < 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Numbers must be 0 or greater"]);
    exit;
  }
}

$daily = (float)$daily;
$protein = (float)$protein;
$carbs = (float)$carbs;
$fat = (float)$fat;

$check = $conn->prepare("SELECT id FROM diet_goals WHERE user_id = ? ORDER BY id DESC LIMIT 1");
if (!$check) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Prepare failed"]);
  exit;
}
$check->bind_param("i", $user_id);
$check->execute();
$res = $check->get_result();
$existing = $res ? $res->fetch_assoc() : null;
$check->close();

if ($existing && isset($existing["id"])) {
  $id = (int)$existing["id"];

  $stmt = $conn->prepare("UPDATE diet_goals
    SET goal_type = ?, daily_calorie_target = ?, protein_target_g = ?, carbs_target_g = ?, fat_target_g = ?, updated_at = NOW()
    WHERE id = ?");
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Prepare failed"]);
    exit;
  }

  $stmt->bind_param("sddddi", $goal_type, $daily, $protein, $carbs, $fat, $id);
  $ok = $stmt->execute();
  $stmt->close();

  if (!$ok) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Update failed"]);
    exit;
  }

  echo json_encode(["success" => true, "message" => "Goal saved", "mode" => "update", "id" => $id]);
  exit;
}

$stmt = $conn->prepare("INSERT INTO diet_goals
  (user_id, goal_type, daily_calorie_target, protein_target_g, carbs_target_g, fat_target_g, created_at, updated_at)
  VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
if (!$stmt) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Prepare failed"]);
  exit;
}

$stmt->bind_param("isdddd", $user_id, $goal_type, $daily, $protein, $carbs, $fat);
$ok = $stmt->execute();
$newId = $stmt->insert_id;
$stmt->close();

if (!$ok) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Insert failed"]);
  exit;
}

echo json_encode(["success" => true, "message" => "Goal saved", "mode" => "insert", "id" => (int)$newId]);
