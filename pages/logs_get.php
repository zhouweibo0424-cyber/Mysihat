<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/diet_bootstrap.php";

$user_id = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : 0;
$log_date = isset($_GET["log_date"]) ? trim($_GET["log_date"]) : "";

if ($user_id <= 0 || $log_date === "") {
  http_response_code(400);
  echo json_encode(["success" => false, "message" => "Missing user_id or log_date"]);
  exit;
}

$listSql = "
SELECT
  id,
  food_id,
  food_name,
  servings,
  calories,
  protein_g,
  carbs_g,
  fat_g
FROM diet_logs
WHERE user_id = ? AND log_date = ?
ORDER BY id ASC
";
$listStmt = $conn->prepare($listSql);
if (!$listStmt) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Prepare failed"]);
  exit;
}
$listStmt->bind_param("is", $user_id, $log_date);
$listStmt->execute();
$listRes = $listStmt->get_result();

$entries = [];
while ($row = $listRes->fetch_assoc()) {
  $entries[] = [
    "id" => (int)$row["id"],
    "food_id" => (int)$row["food_id"],
    "food_name" => (string)$row["food_name"],
    "servings" => (float)$row["servings"],
    "quantity" => (int)round((float)$row["servings"], 0),
    "calories" => (int)$row["calories"],
    "protein_g" => (int)$row["protein_g"],
    "carbs_g" => (int)$row["carbs_g"],
    "fat_g" => (int)$row["fat_g"]
  ];
}

$sumSql = "
SELECT
  IFNULL(SUM(calories),0) AS calories,
  IFNULL(SUM(protein_g),0) AS protein,
  IFNULL(SUM(carbs_g),0) AS carbs,
  IFNULL(SUM(fat_g),0) AS fat
FROM diet_logs
WHERE user_id = ? AND log_date = ?
";
$sumStmt = $conn->prepare($sumSql);
if (!$sumStmt) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Prepare failed"]);
  exit;
}
$sumStmt->bind_param("is", $user_id, $log_date);
$sumStmt->execute();
$totalsRow = $sumStmt->get_result()->fetch_assoc();

$totals = [
  "calories" => (int)($totalsRow["calories"] ?? 0),
  "protein" => (int)($totalsRow["protein"] ?? 0),
  "carbs" => (int)($totalsRow["carbs"] ?? 0),
  "fat" => (int)($totalsRow["fat"] ?? 0)
];

echo json_encode([
  "success" => true,
  "entries" => $entries,
  "totals" => $totals
]);
