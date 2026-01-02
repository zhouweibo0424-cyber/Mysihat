
<?php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../config/bootstrap.php";

function read_body(): array {
  $raw = file_get_contents("php://input");
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function respond_json(array $payload, int $status = 200): void {
  http_response_code($status);
  echo json_encode($payload);
  exit;
}

function stmt_bind(mysqli_stmt $stmt, string $types, array &$params): void {
  $refs = [];
  $refs[] = &$types;
  foreach ($params as $k => $v) {
    $refs[] = &$params[$k];
  }
  $stmt->bind_param(...$refs);
}

$body = function_exists("read_json_body") ? read_json_body() : read_body();

$q = trim((string)($body["ingredients"] ?? $body["q"] ?? ""));
$healthy_only = isset($body["healthy_only"]) ? (int)$body["healthy_only"] : 1;
$limit = isset($body["limit"]) ? (int)$body["limit"] : 20;
if ($limit < 1) $limit = 20;
if ($limit > 50) $limit = 50;

$terms = [];
if ($q !== "") {
  $parts = preg_split("/[,\n]+/", mb_strtolower($q, "UTF-8"));
  foreach ($parts as $p) {
    $t = trim($p);
    if ($t !== "") $terms[] = $t;
  }
  $terms = array_values(array_unique($terms));
}

$sql = "SELECT id, recipe_name, ingredients, instructions, calories, protein_g, carbs_g, fat_g, is_healthy
        FROM meal_recipes";
$where = [];
$params = [];
$types = "";

if ($healthy_only === 1) {
  $where[] = "is_healthy = 1";
}

foreach ($terms as $t) {
  $where[] = "(LOWER(recipe_name) LIKE ? OR LOWER(ingredients) LIKE ?)";
  $like = "%" . $t . "%";
  $params[] = $like;
  $params[] = $like;
  $types .= "ss";
}

if (count($where) > 0) {
  $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY is_healthy DESC, calories ASC, id ASC LIMIT ?";
$params[] = $limit;
$types .= "i";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  respond_json(["success" => false, "message" => "Prepare failed"]);
}

stmt_bind($stmt, $types, $params);

if (!$stmt->execute()) {
  respond_json(["success" => false, "message" => "Execute failed"]);
}

$res = $stmt->get_result();
$rows = [];
while ($row = $res->fetch_assoc()) {
  $rows[] = $row;
}
$stmt->close();

respond_json(["success" => true, "count" => count($rows), "recipes" => $rows]);
