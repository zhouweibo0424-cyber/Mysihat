<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/../config/bootstrap.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  $raw = file_get_contents("php://input");
  $data = json_decode($raw, true);
  if (!is_array($data)) $data = [];

  $user_id = isset($data["user_id"]) ? (int)$data["user_id"] : 0;
  $log_date = isset($data["log_date"]) ? trim((string)$data["log_date"]) : "";
  $q = isset($data["q"]) ? trim((string)$data["q"]) : "";

  $quantity = 0;
  if (isset($data["quantity"])) {
    $quantity = (int)$data["quantity"];
  } else if (isset($data["grams"])) {
    $quantity = (int)$data["grams"];
  }

  if ($user_id <= 0 || $log_date === "" || $q === "" || $quantity <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing or invalid fields"]);
    exit;
  }

  $findSql = "
  SELECT id, name, calories, protein_g, carbs_g, fat_g
  FROM food_items
  WHERE name LIKE CONCAT('%', ?, '%')
  ORDER BY
    CASE
      WHEN name = ? THEN 1
      WHEN name LIKE CONCAT(?, '%') THEN 2
      ELSE 3
    END,
    name ASC
  LIMIT 1
  ";
  $findStmt = $conn->prepare($findSql);
  $findStmt->bind_param("sss", $q, $q, $q);
  $findStmt->execute();
  $foodRes = $findStmt->get_result();
  $food = $foodRes ? $foodRes->fetch_assoc() : null;

  if (!$food) {
    echo json_encode(["success" => true, "status" => "NO RESULT"]);
    exit;
  }

  $factor = (float)$quantity;
  $servings = (float)$quantity;

  $calories = (int)round(((float)$food["calories"]) * $factor, 0);
  $protein_g = (int)round(((float)$food["protein_g"]) * $factor, 0);
  $carbs_g = (int)round(((float)$food["carbs_g"]) * $factor, 0);
  $fat_g = (int)round(((float)$food["fat_g"]) * $factor, 0);

  $insSql = "
  INSERT INTO diet_logs (user_id, log_date, food_id, food_name, servings, calories, protein_g, carbs_g, fat_g)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
  ";
  $insStmt = $conn->prepare($insSql);

  $food_id = (int)$food["id"];
  $food_name = (string)$food["name"];

  $insStmt->bind_param(
    "isisdiiii",
    $user_id,
    $log_date,
    $food_id,
    $food_name,
    $servings,
    $calories,
    $protein_g,
    $carbs_g,
    $fat_g
  );

  $insStmt->execute();

  echo json_encode([
    "success" => true,
    "status" => "FOUND",
    "insert_id" => (int)$conn->insert_id
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => $e->getMessage()
  ]);
}
