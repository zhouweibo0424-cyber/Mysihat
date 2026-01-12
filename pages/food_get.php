<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . '/../config/diet_bootstrap.php';

$q = "";
if (isset($_GET["q"])) {
  $q = trim((string)$_GET["q"]);
} else {
  $raw = file_get_contents("php://input");
  $data = json_decode($raw, true);
  if (is_array($data) && isset($data["q"])) {
    $q = trim((string)$data["q"]);
  }
}

if ($q !== "") {
  $like = "%" . $q . "%";
  $stmt = $conn->prepare("SELECT id, name, calories, protein_g, carbs_g, fat_g FROM food_items WHERE name LIKE ? ORDER BY name ASC, id ASC");
  if (!$stmt) {
    http_response_code(500);
    echo json_encode(["success" => false, "status" => "error", "message" => "Prepare failed"]);
    exit;
  }
  $stmt->bind_param("s", $like);
  $stmt->execute();
  $result = $stmt->get_result();
} else {
  $result = $conn->query("SELECT id, name, calories, protein_g, carbs_g, fat_g FROM food_items ORDER BY name ASC, id ASC");
}

if (!$result) {
  http_response_code(500);
  echo json_encode(["success" => false, "status" => "error", "message" => "Query failed"]);
  exit;
}

$foods = [];
while ($row = $result->fetch_assoc()) {
  $foods[] = [
    "id" => (int)$row["id"],
    "name" => $row["name"],
    "food_name" => $row["name"],
    "calories" => (float)$row["calories"],
    "food_calories" => (float)$row["calories"],
    "protein_g" => (float)$row["protein_g"],
    "food_protein" => (float)$row["protein_g"],
    "carbs_g" => (float)$row["carbs_g"],
    "food_carbs" => (float)$row["carbs_g"],
    "fat_g" => (float)$row["fat_g"],
    "food_fat" => (float)$row["fat_g"]
  ];
}

if (isset($stmt) && $stmt) {
  $stmt->close();
}

echo json_encode([
  "success" => true,
  "status" => "success",
  "foods" => $foods,
  "data" => $foods
]);
