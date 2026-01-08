<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/../config/diet_bootstrap.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  $user_id = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : 0;
  $start = isset($_GET["start"]) ? trim((string)$_GET["start"]) : "";
  $end = isset($_GET["end"]) ? trim((string)$_GET["end"]) : "";

  if ($user_id <= 0 || $start === "" || $end === "") {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing user_id/start/end"]);
    exit;
  }

  $sumSql = "
    SELECT
      COALESCE(SUM(calories),0) AS calories,
      COALESCE(SUM(protein_g),0) AS protein_g,
      COALESCE(SUM(carbs_g),0) AS carbs_g,
      COALESCE(SUM(fat_g),0) AS fat_g
    FROM diet_logs
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
  ";
  $sumStmt = $conn->prepare($sumSql);
  $sumStmt->bind_param("iss", $user_id, $start, $end);
  $sumStmt->execute();
  $summary = $sumStmt->get_result()->fetch_assoc();

  $dailySql = "
    SELECT
      log_date AS date,
      COALESCE(SUM(calories),0) AS calories,
      COALESCE(SUM(protein_g),0) AS protein_g,
      COALESCE(SUM(carbs_g),0) AS carbs_g,
      COALESCE(SUM(fat_g),0) AS fat_g
    FROM diet_logs
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
    GROUP BY log_date
    ORDER BY log_date ASC
  ";
  $dailyStmt = $conn->prepare($dailySql);
  $dailyStmt->bind_param("iss", $user_id, $start, $end);
  $dailyStmt->execute();
  $dailyRes = $dailyStmt->get_result();
  $daily = [];
  while ($row = $dailyRes->fetch_assoc()) $daily[] = $row;

  $topSql = "
    SELECT
      food_name,
      COUNT(*) AS count
    FROM diet_logs
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
    GROUP BY food_name
    ORDER BY count DESC, food_name ASC
    LIMIT 10
  ";
  $topStmt = $conn->prepare($topSql);
  $topStmt->bind_param("iss", $user_id, $start, $end);
  $topStmt->execute();
  $topRes = $topStmt->get_result();
  $top = [];
  while ($row = $topRes->fetch_assoc()) $top[] = $row;

  echo json_encode([
    "success" => true,
    "data" => [
      "summary" => [
        "calories" => (int)$summary["calories"],
        "protein_g" => (float)$summary["protein_g"],
        "carbs_g" => (float)$summary["carbs_g"],
        "fat_g" => (float)$summary["fat_g"]
      ],
      "daily" => $daily,
      "top_foods" => $top
    ]
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
