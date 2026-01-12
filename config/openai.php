<?php

define('GEMINI_API_KEY', 'AIzaSyA8bUiOwcHpz4GkRW12A3ekqlTwYks-1iQ');
define('GEMINI_MODEL', 'gemini-2.5-flash');

function gemini_api_key() {
  return defined('GEMINI_API_KEY') ? trim((string)GEMINI_API_KEY) : '';
}

function gemini_call_text(string $prompt, int $max_output_tokens = 512, float $temperature = 0.4): string {
  $key = gemini_api_key();
  if ($key === '') return 'AI ERROR: GEMINI_API_KEY not set';

  $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent';

  $payload = [
    'contents' => [
      [
        'parts' => [
          ['text' => $prompt]
        ]
      ]
    ],
    'generationConfig' => [
      'temperature' => $temperature,
      'maxOutputTokens' => $max_output_tokens
    ]
  ];

  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-goog-api-key: ' . $key
  ]);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);

  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($resp === false) return 'AI ERROR: cURL ' . $err;

  $data = json_decode($resp, true);
  if (!is_array($data)) return "AI ERROR: invalid JSON (HTTP {$code})";

  if (isset($data['error']['message'])) {
    return 'AI ERROR: ' . $data['error']['message'];
  }

  $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
  $text = is_string($text) ? trim($text) : '';

  return $text !== '' ? $text : "AI ERROR: empty output (HTTP {$code})";
}

/* 保留原函数名：health.php 里已经在调用 openai_symptom_explain() */
function openai_symptom_explain(array $selectedSymptoms, $ruleResult, array $safeMeds, array $warnings): string {
  $symptoms_text = $selectedSymptoms ? implode(', ', $selectedSymptoms) : 'none';
  $rule_name = is_array($ruleResult) ? (string)($ruleResult['rule_name'] ?? 'N/A') : 'N/A';
  $severity  = is_array($ruleResult) ? (string)($ruleResult['severity'] ?? 'N/A') : 'N/A';
  $advice    = is_array($ruleResult) ? (string)($ruleResult['advice'] ?? '') : '';

  $safe_text = $safeMeds ? implode(', ', $safeMeds) : 'none';
  $warn_text = $warnings ? implode(' | ', $warnings) : 'none';

  $prompt =
    "You are a health education assistant for a student prototype. NOT a doctor.\n"
    . "User symptoms: {$symptoms_text}\n"
    . "Matched rule: {$rule_name}\n"
    . "Severity: {$severity}\n"
    . "Rule-based advice: {$advice}\n"
    . "Safe medicines after allergy check: {$safe_text}\n"
    . "Medication warnings (blocked): {$warn_text}\n\n"
    . "Output rules:\n"
    . "- Write exactly 3 bullet tips for self-care and safe OTC guidance (no dosage).\n"
    . "- Then write exactly 1 warning line starting with 'Warning:'.\n"
    . "- Use simple English. No diagnosis. No extra paragraphs.\n";

  return gemini_call_text($prompt, 512, 0.4);
}

function gemini_alert_advice(string $title, string $description, string $prevention, string $start_date, string $end_date): string {
    $title = trim((string)$title);
    $description = trim((string)$description);
    $prevention = trim((string)$prevention);
    $start_date = trim((string)$start_date);
    $end_date = trim((string)$end_date);

    $prompt =
        "You are a public health assistant for a student prototype.\n"
        . "Alert title: {$title}\n"
        . "Date range: {$start_date} to {$end_date}\n"
        . "Description: {$description}\n"
        . "Existing prevention: {$prevention}\n\n"
        . "Output rules:\n"
        . "- Write exactly 3 bullet points.\n"
        . "- Each bullet must be 2 short sentences.\n"
        . "- Then write exactly 1 warning line starting with 'Warning:'.\n"
        . "- Use simple English. No diagnosis. No dosage. No extra paragraphs.\n";

    return gemini_call_text($prompt, 768, 0.55);
}
