<?php
header("Content-Type: application/json; charset=utf-8");

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "diet_assistant";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => "Database connection failed"]);
  exit;
}
$conn->set_charset("utf8mb4");

function read_json_body(): array {
  $raw = file_get_contents("php://input");
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function respond(array $payload, int $status = 200): void {
  http_response_code($status);
  echo json_encode($payload);
  exit;
}

function require_fields(array $data, array $fields): bool {
  foreach ($fields as $f) {
    if (!isset($data[$f])) return false;
  }
  return true;
}

function get_user_id(): int {
  return 1;
}

function get_goal(mysqli $conn, int $userId): ?array {
  $stmt = $conn->prepare("
    SELECT goal_type,
           daily_calorie_target,
           protein_target_g,
           carbs_target_g,
           fat_target_g
    FROM diet_goals
    WHERE user_id = ?
    ORDER BY id DESC
    LIMIT 1
  ");
  $stmt->bind_param("i", $userId);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  return $row ?: null;
}

function get_totals(mysqli $conn, int $userId, string $date): array {
  $stmt = $conn->prepare("
    SELECT
      COALESCE(SUM(dl.calories), 0) AS calories,
      COALESCE(SUM(dl.protein_g), 0) AS proteinG,
      COALESCE(SUM(dl.carbs_g), 0) AS carbsG,
      COALESCE(SUM(dl.fat_g), 0) AS fatG
    FROM diet_logs dl
    WHERE dl.user_id = ?
      AND dl.log_date = ?
  ");
  $stmt->bind_param("is", $userId, $date);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();

  return [
    "calories" => round((float)$row["calories"], 2),
    "proteinG" => round((float)$row["proteinG"], 2),
    "carbsG" => round((float)$row["carbsG"], 2),
    "fatG" => round((float)$row["fatG"], 2)
  ];
}

function build_report(mysqli $conn, int $userId, string $date): array {
  $totals = get_totals($conn, $userId, $date);
  $goal = get_goal($conn, $userId);

  $comparison = null;
  if ($goal) {
    $comparison = [
      "goalType" => $goal["goal_type"],
      "targets" => [
        "calories" => (int)$goal["daily_calorie_target"],
        "proteinG" => (int)$goal["protein_target_g"],
        "carbsG" => (int)$goal["carbs_target_g"],
        "fatG" => (int)$goal["fat_target_g"]
      ],
      "delta" => [
        "calories" => round($totals["calories"] - (int)$goal["daily_calorie_target"], 2),
        "proteinG" => round($totals["proteinG"] - (int)$goal["protein_target_g"], 2),
        "carbsG" => round($totals["carbsG"] - (int)$goal["carbs_target_g"], 2),
        "fatG" => round($totals["fatG"] - (int)$goal["fat_target_g"], 2)
      ]
    ];
  }

  return [
    "date" => $date,
    "totals" => $totals,
    "comparison" => $comparison
  ];
}
