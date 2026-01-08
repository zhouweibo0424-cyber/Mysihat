<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/../config/diet_bootstrap.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  $method = strtoupper($_SERVER["REQUEST_METHOD"] ?? "GET");

  $user_id = 0;
  $id = 0;

  if ($method === "GET") {
    $user_id = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : 0;
    $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
  } else {
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    if (!is_array($data)) $data = [];
    $user_id = isset($data["user_id"]) ? (int)$data["user_id"] : 0;
    $id = isset($data["id"]) ? (int)$data["id"] : 0;

    if ($user_id <= 0 && isset($_GET["user_id"])) $user_id = (int)$_GET["user_id"];
    if ($id <= 0 && isset($_GET["id"])) $id = (int)$_GET["id"];
  }

  if ($user_id <= 0 || $id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing user_id or id"]);
    exit;
  }

  $sql = "DELETE FROM diet_logs WHERE id = ? AND user_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("ii", $id, $user_id);
  $stmt->execute();

  $deleted = (int)$stmt->affected_rows;

  if ($deleted <= 0) {
    echo json_encode(["success" => false, "message" => "Not found or not allowed"]);
    exit;
  }

  echo json_encode(["success" => true, "deleted" => $deleted]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
