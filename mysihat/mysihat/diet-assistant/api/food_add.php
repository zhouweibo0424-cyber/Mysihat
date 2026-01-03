<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/../config/bootstrap.php";

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(["success" => false, "status" => "error", "message" => "Invalid JSON"]);
  exit;
}

$name = trim((string)($data["name"] ?? $data["food_name"] ?? ""));
$calories = $data["calories"] ?? $data["food_calories"] ?? null;
$protein = $data["protein_g"] ?? $data["food_protein"] ?? $data["protein"] ?? null;
$carbs = $data["carbs_g"] ?? $data["food_carbs"] ?? $data["carbs"] ?? null;
$fat = $data["fat_g"] ?? $data["food_fat"] ?? $data["fat"] ?? null;

if ($name === "" || $calories === null || $protein === null || $carbs === null || $fat === null) {
  http_response_code(400);
  echo json_encode(["success" => false, "status" => "error", "message" => "Invalid input data"]);
  exit;
}

if (!is_numeric($calories) || !is_numeric($protein) || !is_numeric($carbs) || !is_numeric($fat)) {
  http_response_code(400);
  echo json_encode(["success" => false, "status" => "error", "message" => "Invalid input data"]);
  exit;
}

$calories = (float)$calories;
$protein = (float)$protein;
$carbs = (float)$carbs;
$fat = (float)$fat;

if ($calories < 0 || $protein < 0 || $carbs < 0 || $fat < 0) {
  http_response_code(400);
  echo json_encode(["success" => false, "status" => "error", "message" => "Calories/Protein/Carbs/Fat must be non-negative numbers"]);
  exit;
}

$check = $conn->prepare("SELECT id FROM food_items WHERE LOWER(name) = LOWER(?) LIMIT 1");
if (!$check) {
  http_response_code(500);
  echo json_encode(["success" => false, "status" => "error", "message" => "Prepare failed"]);
  exit;
}
$check->bind_param("s", $name);
$check->execute();
$checkRes = $check->get_result();
$exists = $checkRes ? $checkRes->fetch_assoc() : null;
$check->close();

if ($exists) {
  echo json_encode([
    "success" => true,
    "status" => "success",
    "message" => "Food already exists",
    "id" => (int)$exists["id"]
  ]);
  exit;
}

$stmt = $conn->prepare("INSERT INTO food_items (name, calories, protein_g, carbs_g, fat_g) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
  http_response_code(500);
  echo json_encode(["success" => false, "status" => "error", "message" => "Prepare failed"]);
  exit;
}

$stmt->bind_param("sdddd", $name, $calories, $protein, $carbs, $fat);
$ok = $stmt->execute();
if (!$ok) {
  http_response_code(500);
  echo json_encode(["success" => false, "status" => "error", "message" => "Insert failed"]);
  $stmt->close();
  exit;
}

$newId = (int)$stmt->insert_id;
$stmt->close();

echo json_encode([
  "success" => true,
  "status" => "success",
  "message" => "Food added",
  "id" => $newId,
  "data" => [
    "id" => $newId,
    "name" => $name,
    "calories" => $calories,
    "protein_g" => $protein,
    "carbs_g" => $carbs,
    "fat_g" => $fat
  ]
]);
