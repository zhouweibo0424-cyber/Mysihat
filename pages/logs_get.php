<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/../config/diet_bootstrap.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  $raw = file_get_contents("php://input");
  $data = json_decode($raw, true);
  if (!is_array($data)) $data = [];

  $in = array_merge($_GET, $_POST, $data);

  $user_id = isset($in["user_id"]) ? (int)$in["user_id"] : 0;

  $log_date = "";
  if (isset($in["log_date"])) $log_date = trim((string)$in["log_date"]);
  else if (isset($in["date"])) $log_date = trim((string)$in["date"]);

  if ($user_id <= 0 || $log_date === "") {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing user_id or log_date"]);
    exit;
  }

  $sql = "
    SELECT id, user_id, log_date, food_id, food_name, servings, calories, protein_g, carbs_g, fat_g
    FROM diet_logs
    WHERE user_id = ? AND log_date = ?
    ORDER BY id DESC
  ";

  $stmt = $conn->prepare($sql);
  $stmt->bind_param("is", $user_id, $log_date);
  $stmt->execute();

  $res = $stmt->get_result();
  $rows = [];

  if ($res) {
    while ($r = $res->fetch_assoc()) {
      $servings = (float)$r["servings"];
      $calories = (float)$r["calories"];
      $protein_g = (float)$r["protein_g"];
      $carbs_g = (float)$r["carbs_g"];
      $fat_g = (float)$r["fat_g"];

      $rows[] = [
        "id" => (int)$r["id"],
        "user_id" => (int)$r["user_id"],
        "log_date" => (string)$r["log_date"],
        "food_id" => (int)$r["food_id"],
        "food_name" => (string)$r["food_name"],
        "name" => (string)$r["food_name"],
        "servings" => $servings,
        "quantity" => $servings,
        "calories" => $calories,
        "total_calories" => $calories,
        "protein_g" => $protein_g,
        "total_protein_g" => $protein_g,
        "carbs_g" => $carbs_g,
        "total_carbs_g" => $carbs_g,
        "fat_g" => $fat_g,
        "total_fat_g" => $fat_g
      ];
    }
  }

  echo json_encode([
    "success" => true,
    "logs" => $rows
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => $e->getMessage()
  ]);
}
