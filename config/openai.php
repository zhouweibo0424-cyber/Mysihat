<?php
// OpenAI config (student project)

define('OPENAI_API_KEY', '');

function openai_api_key() {
  return defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '';
}

/**
 * Call OpenAI and return plain text (or AI ERROR: ...)
 */
function openai_symptom_explain(array $selectedSymptoms, $ruleResult, array $safeMeds, array $warnings) {
  $key = openai_api_key();
  if (!$key) return "AI ERROR: OPENAI_API_KEY not set";

  // Build a simple prompt (non-medical disclaimer)
  $symptomsText = $selectedSymptoms ? implode(", ", $selectedSymptoms) : "none";
  $matchedRule  = is_array($ruleResult) && isset($ruleResult["rule_name"]) ? $ruleResult["rule_name"] : "N/A";
  $severity     = is_array($ruleResult) && isset($ruleResult["severity"]) ? $ruleResult["severity"] : "N/A";
  $advice       = is_array($ruleResult) && isset($ruleResult["advice"]) ? $ruleResult["advice"] : "";

  $safeText = $safeMeds ? implode(", ", $safeMeds) : "none";
  $warnText = $warnings ? implode("; ", $warnings) : "none";

  $prompt = "You are a health education assistant (NOT a doctor). "
    . "Explain the possible meaning of the selected symptoms in simple terms, "
    . "based on the rule-based result below. "
    . "Give 3 sections: (1) What it could mean (2) Self-care suggestions (3) When to seek medical help.\n\n"
    . "Selected symptoms: {$symptomsText}\n"
    . "Matched rule: {$matchedRule}\n"
    . "Severity: {$severity}\n"
    . "Rule advice: {$advice}\n"
    . "Safe medicines (after allergy check): {$safeText}\n"
    . "Warnings: {$warnText}\n\n"
    . "Keep it short, clear, and include a safety disclaimer.";

  $payload = [
    "model" => "gpt-4o-mini",
    "messages" => [
      ["role" => "system", "content" => "You provide general health education, not diagnosis."],
      ["role" => "user", "content" => $prompt]
    ],
    "temperature" => 0.4
  ];

  $ch = curl_init("https://api.openai.com/v1/chat/completions");
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
      "Content-Type: application/json",
      "Authorization: Bearer " . $key
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 30
  ]);

  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($resp === false) return "AI ERROR: cURL " . $err;

  $data = json_decode($resp, true);
  if (!is_array($data)) return "AI ERROR: invalid JSON response (HTTP {$code})";

  // OpenAI error
  if (isset($data["error"]["message"])) {
    return "AI ERROR: " . $data["error"]["message"];
  }

  $text = $data["choices"][0]["message"]["content"] ?? "";
  $text = is_string($text) ? trim($text) : "";

  return $text ?: "AI ERROR: empty output (HTTP {$code})";
}
