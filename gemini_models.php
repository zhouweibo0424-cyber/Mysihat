<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$GEMINI_API_KEY = "AIzaSyArSgn4dITe82L-cvHOtlngd2ge1ln1zI8";

$url = "https://generativelanguage.googleapis.com/v1/models?key=" . $GEMINI_API_KEY;

$ch = curl_init();
curl_setopt_array($ch, [
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 30
]);

$resp = curl_exec($ch);
if ($resp === false) {
  http_response_code(500);
  echo json_encode(["success" => false, "message" => curl_error($ch)]);
  exit;
}
curl_close($ch);

echo $resp;
