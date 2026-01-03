<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/../config/diet_bootstrap.php";

function is_valid_date($s) {
  return is_string($s) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
}

$user_id = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : 0;
$start = isset($_GET["start"]) ? trim((string)$_GET["start"]) : "";
$end = isset($_GET["end"]) ? trim((string)$_GET["end"]) : "";

if ($user_id <= 0 || !is_valid_date($start) || !is_valid_date($end) || $start > $end) {
  http_response_code(400);
  echo json_encode(["success" => false, "message" => "Missing or invalid user_id/start/end"]);
  exit;
}

$goalSql = "SELECT goal_type, daily_calorie_target, protein_target_g, carbs_target_g, fat_target_g
FROM diet_goals
WHERE user_id = ?
ORDER BY id DESC
LIMIT 1";
$goalStmt = $conn->prepare($goalSql);
$goalStmt->bind_param("i", $user_id);
$goalStmt->execute();
$goal = $goalStmt->get_result()->fetch_assoc();

$sumSql = "
SELECT
  COUNT(DISTINCT log_date) AS days,
  IFNULL(SUM(calories),0) AS calories,
  IFNULL(SUM(protein_g),0) AS protein_g,
  IFNULL(SUM(carbs_g),0) AS carbs_g,
  IFNULL(SUM(fat_g),0) AS fat_g
FROM diet_logs
WHERE user_id = ? AND log_date BETWEEN ? AND ?
";
$sumStmt = $conn->prepare($sumSql);
$sumStmt->bind_param("iss", $user_id, $start, $end);
$sumStmt->execute();
$sum = $sumStmt->get_result()->fetch_assoc();

$days = (int)($sum["days"] ?? 0);
$total_cal = (float)($sum["calories"] ?? 0);
$total_p = (float)($sum["protein_g"] ?? 0);
$total_c = (float)($sum["carbs_g"] ?? 0);
$total_f = (float)($sum["fat_g"] ?? 0);

$avg_cal = $days > 0 ? $total_cal / $days : 0;
$avg_p = $days > 0 ? $total_p / $days : 0;
$avg_c = $days > 0 ? $total_c / $days : 0;
$avg_f = $days > 0 ? $total_f / $days : 0;

$topSql = "
SELECT food_name, COUNT(*) AS entries, IFNULL(SUM(servings),0) AS quantity, IFNULL(SUM(calories),0) AS calories
FROM diet_logs
WHERE user_id = ? AND log_date BETWEEN ? AND ?
GROUP BY food_name
ORDER BY calories DESC
LIMIT 5
";
$topStmt = $conn->prepare($topSql);
$topStmt->bind_param("iss", $user_id, $start, $end);
$topStmt->execute();
$topRes = $topStmt->get_result();

$topFoods = [];
while ($r = $topRes->fetch_assoc()) {
  $topFoods[] = [
    "food_name" => (string)$r["food_name"],
    "entries" => (int)$r["entries"],
    "quantity" => (int)round((float)$r["quantity"], 0),
    "calories" => (int)$r["calories"]
  ];
}

$goal_type = $goal ? (string)($goal["goal_type"] ?? "") : "";
$g_cal = $goal ? (float)($goal["daily_calorie_target"] ?? 0) : 0;
$g_p = $goal ? (float)($goal["protein_target_g"] ?? 0) : 0;
$g_c = $goal ? (float)($goal["carbs_target_g"] ?? 0) : 0;
$g_f = $goal ? (float)($goal["fat_target_g"] ?? 0) : 0;

$lines = [];

if ($days <= 0) {
  $lines[] = "No diet log data found in the selected date range.";
  $lines[] = "Add entries in Diet Logs, then generate the report again.";
  echo json_encode(["success" => true, "advice" => implode("\n", $lines), "top_foods" => $topFoods]);
  exit;
}

$lines[] = "Diet Advice (based on your selected date range)";
$lines[] = "Range: {$start} to {$end} ({$days} day(s) with logs)";
$lines[] = "";
$lines[] = "Average intake per day:";
$lines[] = "- Calories: " . (int)round($avg_cal, 0);
$lines[] = "- Protein(g): " . (int)round($avg_p, 0);
$lines[] = "- Carbs(g): " . (int)round($avg_c, 0);
$lines[] = "- Fat(g): " . (int)round($avg_f, 0);
$lines[] = "";

if ($goal && ($g_cal > 0 || $g_p > 0 || $g_c > 0 || $g_f > 0)) {
  $lines[] = "Your current goal:";
  if ($goal_type !== "") $lines[] = "- Goal type: {$goal_type}";
  if ($g_cal > 0) $lines[] = "- Calories/day target: " . (int)round($g_cal, 0);
  if ($g_p > 0) $lines[] = "- Protein/day target(g): " . (int)round($g_p, 0);
  if ($g_c > 0) $lines[] = "- Carbs/day target(g): " . (int)round($g_c, 0);
  if ($g_f > 0) $lines[] = "- Fat/day target(g): " . (int)round($g_f, 0);
  $lines[] = "";

  $dCal = $avg_cal - $g_cal;
  $dP = $avg_p - $g_p;
  $dC = $avg_c - $g_c;
  $dF = $avg_f - $g_f;

  $lines[] = "What to adjust:";
  if ($g_cal > 0) {
    $lines[] = $dCal > 50 ? "- Calories are above target. Reduce portion sizes or swap to lower-calorie foods."
      : ($dCal < -50 ? "- Calories are below target. Add a healthy snack or increase portions slightly."
      : "- Calories are close to target. Keep consistency.");
  }
  if ($g_p > 0) {
    $lines[] = $dP < -10 ? "- Protein is below target. Add lean protein: eggs, chicken breast, fish, tofu, Greek yogurt."
      : ($dP > 10 ? "- Protein is above target (not necessarily bad). Keep it balanced with enough carbs and fiber."
      : "- Protein is close to target.");
  }
  if ($g_c > 0) {
    $lines[] = $dC > 15 ? "- Carbs are above target. Reduce sugary drinks/snacks; choose whole grains and vegetables."
      : ($dC < -15 ? "- Carbs are below target. Add complex carbs: oats, rice, potatoes, fruits."
      : "- Carbs are close to target.");
  }
  if ($g_f > 0) {
    $lines[] = $dF > 10 ? "- Fat is above target. Reduce fried foods; use smaller amounts of oil/sauces."
      : ($dF < -10 ? "- Fat is below target. Add healthy fats: nuts, avocado, olive oil."
      : "- Fat is close to target.");
  }
  $lines[] = "";
} else {
  $lines[] = "Tip: Save your goal in Goal Settings to enable goal-based advice.";
  $lines[] = "";
}

if (count($topFoods) > 0) {
  $lines[] = "Top foods by calories in this range:";
  foreach ($topFoods as $i => $x) {
    $n = $i + 1;
    $lines[] = "{$n}) {$x["food_name"]} - {$x["calories"]} kcal (entries: {$x["entries"]}, qty: {$x["quantity"]})";
  }
  $lines[] = "";
  $lines[] = "Actionable suggestion:";
  $lines[] = "- If the top foods are very high-calorie, reduce quantity or replace with lower-calorie options.";
  $lines[] = "- Add more vegetables and high-fiber foods to improve fullness and micronutrients.";
}

echo json_encode([
  "success" => true,
  "advice" => implode("\n", $lines),
  "top_foods" => $topFoods
]);
