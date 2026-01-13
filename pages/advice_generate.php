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
  if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Missing or invalid user_id"]);
    exit;
  }

  $start = isset($_GET["start"]) ? trim((string)$_GET["start"]) : "";
  $end = isset($_GET["end"]) ? trim((string)$_GET["end"]) : "";

  if ($start === "" || $end === "") {
    $end = date("Y-m-d");
    $start = date("Y-m-d", strtotime($end . " -6 days"));
  }

  $sumSql = "
    SELECT
      COALESCE(SUM(calories),0) AS calories,
      COALESCE(SUM(protein_g),0) AS protein_g,
      COALESCE(SUM(carbs_g),0) AS carbs_g,
      COALESCE(SUM(fat_g),0) AS fat_g,
      COUNT(*) AS entries
    FROM diet_logs
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
  ";
  $sumStmt = $conn->prepare($sumSql);
  $sumStmt->bind_param("iss", $user_id, $start, $end);
  $sumStmt->execute();
  $sum = $sumStmt->get_result()->fetch_assoc();

  $entries = (int)$sum["entries"];
  $cal = (int)$sum["calories"];
  $p = (float)$sum["protein_g"];
  $c = (float)$sum["carbs_g"];
  $f = (float)$sum["fat_g"];

  if ($entries <= 0) {
    echo json_encode([
      "success" => true,
      "advice" => "No diet logs found for the selected range (" . $start . " to " . $end . "). Add meals in Diet Logs to generate advice."
    ]);
    exit;
  }

  $days = 1;
  $d1 = strtotime($start);
  $d2 = strtotime($end);
  if ($d1 !== false && $d2 !== false && $d2 >= $d1) {
    $days = (int)floor(($d2 - $d1) / 86400) + 1;
    if ($days <= 0) $days = 1;
  }

  $avgCal = (int)round($cal / $days, 0);
  $avgP = round($p / $days, 1);
  $avgC = round($c / $days, 1);
  $avgF = round($f / $days, 1);

  $topSql = "
    SELECT food_name, COUNT(*) AS count
    FROM diet_logs
    WHERE user_id = ? AND log_date BETWEEN ? AND ?
    GROUP BY food_name
    ORDER BY count DESC, food_name ASC
    LIMIT 5
  ";
  $topStmt = $conn->prepare($topSql);
  $topStmt->bind_param("iss", $user_id, $start, $end);
  $topStmt->execute();
  $topRes = $topStmt->get_result();
  $top = [];
  while ($r = $topRes->fetch_assoc()) {
    $top[] = $r["food_name"];
  }

  $msg = "Range: " . $start . " to " . $end . ". ";
  $msg .= "Daily average: Calories " . $avgCal . ", Protein " . $avgP . "g, Carbs " . $avgC . "g, Fat " . $avgF . "g. ";

  if (count($top) > 0) {
    $msg .= "Top foods: " . implode(", ", $top) . ". ";
  }

  if ($avgP < 50) {
    $msg .= "Suggestion: Increase protein (lean meat, eggs, tofu, Greek yogurt) to support satiety and muscle maintenance. ";
  } else {
    $msg .= "Suggestion: Protein intake looks reasonable; keep spreading protein across meals. ";
  }

  if ($avgC > 250) {
    $msg .= "Suggestion: Consider reducing refined carbs and add more vegetables/whole grains. ";
  } else {
    $msg .= "Suggestion: Keep choosing complex carbs (oats, brown rice, fruit) and include fiber. ";
  }

  if ($avgF > 80) {
    $msg .= "Suggestion: Watch high-fat snacks; prefer unsaturated fats and control portions. ";
  } else {
    $msg .= "Suggestion: Maintain healthy fats (nuts, olive oil, fish) in moderate portions. ";
  }

  $msg .= "Hydration and consistent meal timing will also help.";

  echo json_encode(["success" => true, "advice" => $msg]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
