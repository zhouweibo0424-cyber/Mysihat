<?php
// ==============================
// Health Assistant (Student Version)
// Features:
// 1) Symptom-based advice (rule-based)
// 2) Current health alerts (from DB)
// 3) Allergy-safe medicine filtering
// 4) Period tracker + simple period/PMS tips
// ==============================

session_start();
require_once __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

$user_id = $_SESSION['user_id'] ?? 1;  // student demo fallback
$today = date('Y-m-d');

// -------- helper (simple) --------
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function norm_key($s) {
  $s = strtolower(trim($s));
  $s = preg_replace('/\s+/', '_', $s);
  $s = preg_replace('/[^a-z0-9_]/', '', $s);
  return $s;
}

// ------------------------------
// 1) Handle actions (POST)
// ------------------------------
$msg = "";
$result = null;         // advice result
$safeMeds = [];         // filtered meds
$warnings = [];         // warnings for blocked meds

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // A) Add allergy
  if ($action === 'add_allergy') {
    $allergy_name = trim($_POST['allergy_name'] ?? '');
    if ($allergy_name !== '') {
      $allergy_key = norm_key($allergy_name);

      // avoid duplicates for the same user
      $chk = $pdo->prepare("SELECT id FROM user_allergies WHERE user_id=? AND allergy_key=? LIMIT 1");
      $chk->execute([$user_id, $allergy_key]);
      if (!$chk->fetchColumn()) {
        $ins = $pdo->prepare("INSERT INTO user_allergies (user_id, allergy_key, allergy_name) VALUES (?,?,?)");
        $ins->execute([$user_id, $allergy_key, $allergy_name]);
        $msg = "Allergy saved.";
      } else {
        $msg = "This allergy is already saved.";
      }
    } else {
      $msg = "Please enter an allergy name.";
    }
  }

  // B) Save period record
  if ($action === 'add_period') {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? null;
    $cycle_length = (int)($_POST['cycle_length'] ?? 28);
    $notes = trim($_POST['notes'] ?? '');

    if ($start_date === '') {
      $msg = "Please select a start date.";
    } else {
      if ($end_date === '') $end_date = null;
      if ($cycle_length < 20 || $cycle_length > 40) $cycle_length = 28;

      $ins = $pdo->prepare("
        INSERT INTO menstrual_cycles (user_id, start_date, end_date, cycle_length, notes)
        VALUES (?,?,?,?,?)
      ");
      $ins->execute([$user_id, $start_date, $end_date, $cycle_length, $notes ?: null]);
      $msg = "Period info saved.";
    }
  }

  // C) Get advice (symptom checker)
  if ($action === 'get_advice') {
    $selected = $_POST['symptoms'] ?? [];
    $selected = array_values(array_unique(array_filter($selected)));

    if (count($selected) === 0) {
      $msg = "Please select at least one symptom.";
    } else {
      // 1) Load all rules (simple approach)
      $rules = $pdo->query("SELECT rule_name, symptoms_csv, severity, advice, red_flags, recommend_meds_csv FROM symptom_rules")
                   ->fetchAll(PDO::FETCH_ASSOC);

      // 2) Find best match (most overlapping symptoms)
      $best = null;
      $bestScore = -1;
      foreach ($rules as $r) {
        $ruleSymptoms = array_filter(array_map('trim', explode(',', (string)$r['symptoms_csv'])));
        $hits = 0;
        foreach ($ruleSymptoms as $rs) {
          if (in_array($rs, $selected, true)) $hits++;
        }
        if ($hits > $bestScore) {
          $bestScore = $hits;
          $best = $r;
        }
      }

      // If no good match, show general advice
      if (!$best || $bestScore <= 0) {
        $result = [
          'rule_name' => 'General advice',
          'severity' => 'low',
          'advice' => "Rest, drink enough water, and monitor your symptoms. If symptoms get worse, consult a doctor.",
          'red_flags' => null,
          'recommend_meds_csv' => ''
        ];
      } else {
        $result = $best;
      }

      // 3) Allergy-safe filtering
      $allergyKeys = $pdo->prepare("SELECT allergy_key FROM user_allergies WHERE user_id=?");
      $allergyKeys->execute([$user_id]);
      $allergyKeys = $allergyKeys->fetchAll(PDO::FETCH_COLUMN);

      $recommended = array_filter(array_map('trim', explode(',', strtolower((string)($result['recommend_meds_csv'] ?? '')))));

      $safeMeds = [];
      $warnings = [];

      if (count($recommended) > 0 && count($allergyKeys) > 0) {
        // get contraindications for user's allergies
        $inAll = implode(',', array_fill(0, count($allergyKeys), '?'));
        $stmt = $pdo->prepare("SELECT allergy_key, med_key, note FROM contraindications WHERE allergy_key IN ($inAll)");
        $stmt->execute($allergyKeys);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $blockedMap = [];
        foreach ($rows as $row) {
          $blockedMap[strtolower($row['med_key'])] = $row['note'] ?: 'Not recommended.';
        }

        foreach ($recommended as $m) {
          if (isset($blockedMap[$m])) {
            $warnings[] = $m . ": " . $blockedMap[$m];
          } else {
            $safeMeds[] = $m;
          }
        }
      } else {
        // no allergies, all recommended meds are safe
        $safeMeds = $recommended;
      }
    }
  }
}

// ------------------------------
// 2) Fetch data for page display
// ------------------------------

// A) health alerts
$alerts = [];
try {
  $stmt = $pdo->prepare("
    SELECT title, description, prevention_tips, start_date, end_date
    FROM health_alerts
    WHERE is_active=1 AND ? BETWEEN start_date AND end_date
    ORDER BY start_date DESC
  ");
  $stmt->execute([$today]);
  $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $alerts = []; }

// B) user allergies
$userAllergies = [];
try {
  $stmt = $pdo->prepare("SELECT allergy_key, allergy_name FROM user_allergies WHERE user_id=? ORDER BY created_at DESC");
  $stmt->execute([$user_id]);
  $userAllergies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $userAllergies = []; }

// C) symptom options (from DB)
$symptoms = [];
try {
  $stmt = $pdo->query("SELECT symptom_key, symptom_name FROM symptom_catalog ORDER BY symptom_name ASC");
  $symptoms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $symptoms = []; }

// D) latest period info
$periodInfo = null;
try {
  $stmt = $pdo->prepare("SELECT * FROM menstrual_cycles WHERE user_id=? ORDER BY start_date DESC LIMIT 1");
  $stmt->execute([$user_id]);
  $periodInfo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $periodInfo = null; }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Health Assistant</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 35px; background:#fff; }
    .box { border: 1px solid #ccc; padding: 18px; margin-bottom: 18px; border-radius: 10px; }
    .alert { border: 1px solid orange; background:#fff7e6; padding: 12px; border-radius: 8px; margin-top:10px; }
    .tag { display:inline-block; padding: 4px 10px; border:1px solid #ddd; border-radius: 999px; margin: 6px 6px 0 0; background:#fafafa; }
    .warning { color: #b91c1c; }
    .note { color: #d97706; }
    button { padding: 8px 12px; border: 0; border-radius: 8px; background:#1677ff; color:#fff; cursor:pointer; }
    input[type="text"], input[type="number"], input[type="date"] { padding: 8px; border:1px solid #ddd; border-radius: 8px; }
    .grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 8px 16px; margin-top: 8px; }
    .small { color:#666; font-size: 12px; }
  </style>
</head>
<body>

<h1>Health Assistant</h1>
<p class="small">Rule-based symptom advice + outbreak alerts + allergy safety + period reminder.</p>

<?php if ($msg !== ""): ?>
  <div class="box" style="border-color:#99c2ff;background:#eef6ff;">
    <?= h($msg) ?>
  </div>
<?php endif; ?>

<!-- ========================= -->
<!-- Feature 2: Current Alerts -->
<!-- ========================= -->
<div class="box">
  <h2>Current Health Alerts</h2>
  <?php if (count($alerts) === 0): ?>
    <p class="small">No active alerts right now.</p>
  <?php else: ?>
    <?php foreach ($alerts as $a): ?>
      <div class="alert">
        <strong><?= h($a['title']) ?></strong><br>
        <span class="small"><?= h($a['start_date']) ?> → <?= h($a['end_date']) ?></span>
        <hr>
        <div><?= h($a['description']) ?></div>
        <div style="margin-top:8px;"><b>Prevention:</b> <?= h($a['prevention_tips']) ?></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ========================= -->
<!-- Feature 4: Period Tracker -->
<!-- ========================= -->
<div class="box">
  <h2>Period Tracker</h2>
  <p class="small">Save your period dates. The system shows a simple reminder (not diagnosis).</p>

  <form method="POST">
    <input type="hidden" name="action" value="add_period">
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
      <div>
        <div class="small">Start date</div>
        <input
  type="text"
  name="start_date"
  placeholder="YYYY-MM-DD"
  pattern="\d{4}-\d{2}-\d{2}"
  required
>

      </div>
      <div>
        <div class="small">End date (optional)</div>
        <input
  type="text"
  name="end_date"
  placeholder="YYYY-MM-DD"
  pattern="\d{4}-\d{2}-\d{2}"
>

      </div>
      <div>
        <div class="small">Cycle length (days)</div>
        <input type="number" name="cycle_length" value="28" min="20" max="40">
      </div>
    </div>

    <div style="margin-top:10px;">
      <div class="small">Notes (optional)</div>
      <input type="text" name="notes" placeholder="e.g., cramps, mood..." style="width:60%;">
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Save Period</button>
    </div>
  </form>

  <hr>

  <h3>Latest cycle info</h3>
  <?php if (!$periodInfo): ?>
    <p class="small">No period data saved yet.</p>
  <?php else: ?>
    <?php
      $lastStart = $periodInfo['start_date'];
      $cycleLen  = (int)($periodInfo['cycle_length'] ?? 28);
      if ($cycleLen < 20 || $cycleLen > 40) $cycleLen = 28;

      $nextStart = date('Y-m-d', strtotime($lastStart . " +{$cycleLen} days"));

      echo "<p><b>Last start:</b> " . h($lastStart) . "</p>";
      if (!empty($periodInfo['end_date'])) {
        echo "<p><b>Last end:</b> " . h($periodInfo['end_date']) . "</p>";
      }
      echo "<p><b>Cycle length:</b> " . h($cycleLen) . " days</p>";
      echo "<p><b>Estimated next period:</b> " . h($nextStart) . "</p>";

      // --- simple reminder + period status ---
      $todayTs = strtotime($today);
      $startTs = strtotime($lastStart);

      // If end_date missing, assume 5 days period (simple student rule)
      if (!empty($periodInfo['end_date'])) $endTs = strtotime($periodInfo['end_date']);
      else $endTs = strtotime($lastStart . " +5 days");

      // 1) On-period message
      if ($todayTs >= $startTs && $todayTs <= $endTs) {
        echo "<div style='margin-top:12px;padding:10px;border:1px solid #fca5a5;background:#fff1f2;border-radius:8px;'>";
        echo "<b>Period status:</b> You may be on your period now.";
        echo "<ul style='margin:8px 0 0 18px;'>";
        echo "<li>Drink warm water and rest more.</li>";
        echo "<li>If you feel cramps, try a hot pack.</li>";
        echo "<li>Avoid very spicy food if it makes you uncomfortable.</li>";
        echo "<li>If pain is very strong or unusual, ask a doctor.</li>";
        echo "</ul>";
        echo "<div class='small'>This is a basic reminder, not a diagnosis.</div>";
        echo "</div>";
      }

      // 2) PMS window (3 days before next start)
      $pmsStart = strtotime($nextStart . " -3 days");
      $pmsEnd   = strtotime($nextStart . " -1 days");

      if ($todayTs >= $pmsStart && $todayTs <= $pmsEnd) {
        echo "<div style='margin-top:12px;padding:10px;border:1px solid #fde68a;background:#fffbeb;border-radius:8px;'>";
        echo "<b>Pre-period note:</b> Your period may start soon (PMS time).";
        echo "<ul style='margin:8px 0 0 18px;'>";
        echo "<li>Sleep earlier and reduce stress if possible.</li>";
        echo "<li>Prepare pads/tampons.</li>";
        echo "<li>Light exercise can help some people.</li>";
        echo "</ul>";
        echo "<div class='small'>Everyone is different—this is just a simple guide.</div>";
        echo "</div>";
      }

      // 3) Days to next (simple)
      $daysToNext = (strtotime($nextStart) - strtotime($today)) / 86400;
      if ($daysToNext <= 3 && $daysToNext >= 0) {
        echo "<p class='note'><b>Reminder:</b> Next period may start soon.</p>";
      } elseif ($daysToNext < 0 && $daysToNext >= -5) {
        echo "<p class='warning'><b>Note:</b> Estimated date already passed (possible delay).</p>";
      } else {
        echo "<p class='small'>Tip: keep tracking for better reminders.</p>";
      }

      if (!empty($periodInfo['notes'])) {
        echo "<p><b>Notes:</b> " . h($periodInfo['notes']) . "</p>";
      }
    ?>
  <?php endif; ?>
</div>

<!-- ========================= -->
<!-- Feature 3: Allergies -->
<!-- ========================= -->
<div class="box">
  <h2>Your Allergies</h2>
  <p class="small">The system uses this list to avoid unsafe medicines.</p>

  <form method="POST">
    <input type="hidden" name="action" value="add_allergy">
    <input type="text" name="allergy_name" placeholder="e.g., penicillin, ibuprofen..." required style="width:60%;">
    <button type="submit">Add Allergy</button>
  </form>

  <div style="margin-top:10px;">
    <?php if (count($userAllergies) === 0): ?>
      <span class="small">No allergies saved.</span>
    <?php else: ?>
      <?php foreach ($userAllergies as $al): ?>
        <span class="tag"><?= h($al['allergy_name']) ?></span>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ========================= -->
<!-- Feature 1: Symptom Checker -->
<!-- ========================= -->
<div class="box">
  <h2>Symptom Checker</h2>
  <p class="small">Select symptoms to get simple advice and safe medicine suggestions.</p>

  <form method="POST">
    <input type="hidden" name="action" value="get_advice">

    <div class="grid">
      <?php if (count($symptoms) === 0): ?>
        <p class="small">No symptoms found in symptom_catalog.</p>
      <?php else: ?>
        <?php foreach ($symptoms as $s): ?>
          <label>
            <input type="checkbox" name="symptoms[]" value="<?= h($s['symptom_key']) ?>">
            <?= h($s['symptom_name']) ?>
          </label>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <div style="margin-top:12px;">
      <button type="submit">Get Advice</button>
    </div>
  </form>
</div>

<!-- ========================= -->
<!-- Result -->
<!-- ========================= -->
<?php if ($result): ?>
  <div class="box">
    <h2>Result</h2>
    <p><b>Matched rule:</b> <?= h($result['rule_name'] ?? 'N/A') ?></p>
    <p><b>Severity:</b> <?= h($result['severity'] ?? 'low') ?></p>

    <h3>Health Advice</h3>
    <p><?= nl2br(h($result['advice'] ?? '')) ?></p>

    <?php if (!empty($result['red_flags'])): ?>
      <p class="warning"><b>Warning:</b> <?= h($result['red_flags']) ?></p>
    <?php endif; ?>

    <h3>Recommended Medicines (after allergy check)</h3>
    <?php if (count($safeMeds) === 0): ?>
      <p class="small">No safe medicines can be recommended based on your allergies.</p>
    <?php else: ?>
      <?php foreach ($safeMeds as $m): ?>
        <span class="tag"><?= h($m) ?></span>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if (count($warnings) > 0): ?>
      <h3>Medication Warnings</h3>
      <ul>
        <?php foreach ($warnings as $w): ?>
          <li class="note">⚠ <?= h($w) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <p class="small">This is a simple educational prototype, not medical diagnosis.</p>
  </div>
<?php endif; ?>

</body>
</html>
