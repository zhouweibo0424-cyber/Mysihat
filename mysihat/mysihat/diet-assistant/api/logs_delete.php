<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/bootstrap.php";

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) $data = [];

$user_id = isset($data["user_id"]) ? (int)$data["user_id"] : 0;
$id = isset($data["id"]) ? (int)$data["id"] : 0;

if ($user_id <= 0 || $id <= 0) {
  http_response_code(400);
  echo json_encode(["success" => false, "message" => "Missing user_id or id"]);
  exit;
}

$sql = "DELETE FROM diet_logs WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Prepare failed"]);
  exit;
}
$stmt->bind_param("ii", $id, $user_id);
$ok = $stmt->execute();

if (!$ok) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Delete failed"]);
  exit;
}

echo json_encode(["success" => true, "deleted" => (int)$stmt->affected_rows]);

