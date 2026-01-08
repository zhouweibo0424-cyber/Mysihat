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

  $q = "";
  if (isset($in["q"])) $q = trim((string)$in["q"]);
  else if (isset($in["food_name"])) $q = trim((string)$in["food_name"]);
  else if (isset($in["name"])) $q = trim((string)$in["name"]);
  else if (isset($in["food"])) $q = trim((string)$in["food"]);

  $quantity = 0;
  if (isset($in["quantity"])) $quantity = (int)$in["quantity"];
  else if (isset($in["grams"])) $quantity = (int)$in["grams"];

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
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Food not found"]);
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
    "status" => "OK",
    "insert_id" => (int)$conn->insert_id
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => $e->getMessage()
  ]);
}
