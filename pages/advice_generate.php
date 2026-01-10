<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

require_once __DIR__ . "/../config/diet_bootstrap.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$GEMINI_API_KEY = "AIzaSyArSgn4dITe82L-cvHOtlngd2ge1ln1zI8";

try {
    $user_id = isset($_GET["user_id"]) ? (int)$_GET["user_id"] : 0;
    $type = isset($_GET["type"]) ? strtolower(trim($_GET["type"])) : "maintain";

    if ($user_id <= 0) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid user_id"]);
        exit;
    }

    if (!in_array($type, ["vegetarian", "maintain", "cut", "bulk"])) {
        $type = "maintain";
    }

    $end = date("Y-m-d");
    $start = date("Y-m-d", strtotime($end . " -6 days"));

    // ===== 7-day summary =====
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
    $stmt = $conn->prepare($sumSql);
    $stmt->bind_param("iss", $user_id, $start, $end);
    $stmt->execute();
    $sum = $stmt->get_result()->fetch_assoc();

    if ((int)$sum["entries"] <= 0) {
        echo json_encode([
            "success" => true,
            "advice" => "No diet logs found. Please record meals before generating advice."
        ]);
        exit;
    }

    $days = 7;
    $avgCal = round($sum["calories"] / $days);
    $avgP   = round($sum["protein_g"] / $days, 1);
    $avgC   = round($sum["carbs_g"] / $days, 1);
    $avgF   = round($sum["fat_g"] / $days, 1);

    // ===== Top foods =====
    $topSql = "
        SELECT food_name, COUNT(*) AS cnt
        FROM diet_logs
        WHERE user_id = ? AND log_date BETWEEN ? AND ?
        GROUP BY food_name
        ORDER BY cnt DESC
        LIMIT 5
    ";
    $stmt2 = $conn->prepare($topSql);
    $stmt2->bind_param("iss", $user_id, $start, $end);
    $stmt2->execute();
    $res = $stmt2->get_result();

    $foods = [];
    while ($r = $res->fetch_assoc()) {
        $foods[] = $r["food_name"];
    }

    // ===== Prompt =====
    $summary = "7-day diet summary:
Calories avg: {$avgCal}
Protein avg(g): {$avgP}
Carbs avg(g): {$avgC}
Fat avg(g): {$avgF}
Top foods: " . implode(", ", $foods);

    $goalMap = [
        "vegetarian" => "vegetarian diet",
        "maintain"   => "maintaining current body weight",
        "cut"        => "fat loss",
        "bulk"       => "muscle gain"
    ];

    $prompt = "You are a nutrition assistant.
User goal: {$goalMap[$type]}.
Based on the following diet summary, give clear, practical diet advice.
Focus on food choices, portion control, and daily habits.
Avoid medical claims.

{$summary}";

    $payload = json_encode([
        "contents" => [[
            "parts" => [[ "text" => $prompt ]]
        ]]
    ]);

    // ===== Gemini API call（✅唯一正确）=====
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . $GEMINI_API_KEY,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30
    ]);

    $resp = curl_exec($ch);

    if ($resp === false) {
        throw new Exception("Gemini request failed: " . curl_error($ch));
    }

    curl_close($ch);

    $json = json_decode($resp, true);

    if (isset($json["error"])) {
        echo json_encode([
            "success" => false,
            "message" => $json["error"]["message"],
            "raw" => $json
        ]);
        exit;
    }

    $aiText = $json["candidates"][0]["content"]["parts"][0]["text"] ?? null;

    if (!$aiText) {
        echo json_encode([
            "success" => false,
            "message" => "Gemini returned no text",
            "raw" => $json
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "advice" => $aiText
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
