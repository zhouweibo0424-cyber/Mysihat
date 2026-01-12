<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/diet_bootstrap.php";

if (!isset($conn) || !($conn instanceof mysqli)) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "DB not connected"]);
  exit;
}

$q = isset($_GET["ingredients"]) ? trim((string)$_GET["ingredients"]) : "";
$healthy_only = isset($_GET["healthy_only"]) ? (int)$_GET["healthy_only"] : 1;
$limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 20;
$match = isset($_GET["match"]) ? strtolower(trim((string)$_GET["match"])) : "any";

if ($limit < 1) $limit = 20;
if ($limit > 50) $limit = 50;

if ($q === "") {
  http_response_code(200);
  echo json_encode([]);
  exit;
}

$parts = preg_split("/,+/u", mb_strtolower($q, "UTF-8"));
$terms = [];
foreach ($parts as $p) {
  $t = trim($p);
  if ($t !== "" && mb_strlen($t, "UTF-8") >= 2) $terms[] = $t;
}
$terms = array_values(array_unique($terms));

if (count($terms) === 0) {
  http_response_code(200);
  echo json_encode([]);
  exit;
}

$sql = "SELECT id, recipe_name, ingredients, instructions, calories, protein_g, carbs_g, fat_g, is_healthy
        FROM meal_recipes";
if ($healthy_only === 1) {
  $sql .= " WHERE is_healthy = 1";
}
$sql .= " ORDER BY calories ASC, id ASC LIMIT 500";

$res = $conn->query($sql);
if (!$res) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Query failed", "error" => $conn->error]);
  exit;
}

$matched = [];

while ($row = $res->fetch_assoc()) {
  $hay = mb_strtolower((string)$row["ingredients"], "UTF-8");

  $hit = 0;
  for ($i = 0; $i < count($terms); $i++) {
    $term = $terms[$i];
    if (mb_strpos($hay, $term, 0, "UTF-8") !== false) $hit++;
  }

  $ok = ($match === "all") ? ($hit === count($terms)) : ($hit > 0);
  if (!$ok) continue;

  $ings = isset($row["ingredients"]) ? (string)$row["ingredients"] : "";
  $arr = preg_split("/[;,\n]+/", $ings);
  $clean = [];
  foreach ($arr as $x) {
    $v = trim($x);
    if ($v !== "") $clean[] = $v;
  }

  $matched[] = [
    "id" => (int)$row["id"],
    "name" => (string)$row["recipe_name"],
    "ingredients" => $clean,
    "instructions" => (string)$row["instructions"],
    "calories" => isset($row["calories"]) ? (int)$row["calories"] : null,
    "protein_g" => isset($row["protein_g"]) ? (float)$row["protein_g"] : null,
    "carbs_g" => isset($row["carbs_g"]) ? (float)$row["carbs_g"] : null,
    "fat_g" => isset($row["fat_g"]) ? (float)$row["fat_g"] : null,
    "is_healthy" => isset($row["is_healthy"]) ? (int)$row["is_healthy"] : 0
  ];

  if (count($matched) >= $limit) break;
}

http_response_code(200);
echo json_encode($matched);
exit;
