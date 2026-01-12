<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/openai.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

$user_id = $_SESSION['user_id'] ?? 1;
$today = date('Y-m-d');

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function norm_key($s) {
  $s = strtolower(trim($s));
  $s = preg_replace('/\s+/', '_', $s);
  $s = preg_replace('/[^a-z0-9_]/', '', $s);
  return $s;
}

$msg = "";
$result = null;
$safeMeds = [];
$warnings = [];
$aiExplain = "";

$activeTab = $_GET['tab'] ?? 'alerts';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  if ($action === 'add_period')      $activeTab = 'period';
  elseif ($action === 'add_allergy') $activeTab = 'allergies';
  elseif ($action === 'get_advice')  $activeTab = 'symptoms';
  elseif ($action === 'alert_ai')    $activeTab = 'alerts';
}

$alertAi = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add_allergy') {
    $allergy_name = trim($_POST['allergy_name'] ?? '');
    if ($allergy_name !== '') {
      $allergy_key = norm_key($allergy_name);

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

  if ($action === 'get_advice') {
    $selected = $_POST['symptoms'] ?? [];
    $selected = array_values(array_unique(array_filter($selected)));

    if (count($selected) === 0) {
      $msg = "Please select at least one symptom.";
    } else {
      $rules = $pdo->query("SELECT rule_name, symptoms_csv, severity, advice, red_flags, recommend_meds_csv FROM symptom_rules")
                   ->fetchAll(PDO::FETCH_ASSOC);

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

      $allergyKeysStmt = $pdo->prepare("SELECT allergy_key FROM user_allergies WHERE user_id=?");
      $allergyKeysStmt->execute([$user_id]);
      $allergyKeys = $allergyKeysStmt->fetchAll(PDO::FETCH_COLUMN);

      $recommended = array_filter(array_map('trim', explode(',', strtolower((string)($result['recommend_meds_csv'] ?? '')))));

      $safeMeds = [];
      $warnings = [];

      if (count($recommended) > 0 && count($allergyKeys) > 0) {
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
        $safeMeds = $recommended;
      }

      $aiExplain = openai_symptom_explain($selected, $result, $safeMeds, $warnings);
    }
  }

  if ($action === 'alert_ai') {
    $title = (string)($_POST['title'] ?? '');
    $description = (string)($_POST['description'] ?? '');
    $prevention = (string)($_POST['prevention'] ?? '');
    $start_date = (string)($_POST['start_date'] ?? '');
    $end_date = (string)($_POST['end_date'] ?? '');
    if ($title !== '') {
      $alertAi = gemini_alert_advice($title, $description, $prevention, $start_date, $end_date);
    }
  }
}

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

$userAllergies = [];
try {
  $stmt = $pdo->prepare("SELECT allergy_key, allergy_name FROM user_allergies WHERE user_id=? ORDER BY created_at DESC");
  $stmt->execute([$user_id]);
  $userAllergies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $userAllergies = []; }

$symptoms = [];
try {
  $stmt = $pdo->query("SELECT symptom_key, symptom_name FROM symptom_catalog ORDER BY symptom_name ASC");
  $symptoms = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $symptoms = []; }

$periodInfo = null;
try {
  $stmt = $pdo->prepare("SELECT * FROM menstrual_cycles WHERE user_id=? ORDER BY start_date DESC LIMIT 1");
  $stmt->execute([$user_id]);
  $periodInfo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) { $periodInfo = null; }

$unsafeMeds = [];
try {
  $allKeys = array_map(fn($x)=>$x['allergy_key'], $userAllergies);
  $allKeys = array_values(array_filter($allKeys));
  if (count($allKeys) > 0) {
    $inAll = implode(',', array_fill(0, count($allKeys), '?'));
    $stmt = $pdo->prepare("SELECT med_key, note FROM contraindications WHERE allergy_key IN ($inAll) ORDER BY med_key ASC");
    $stmt->execute($allKeys);
    $unsafeMeds = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
} catch (Exception $e) { $unsafeMeds = []; }

?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Health Assistant</title>
  <style>
:root{--primary:#1463ff;--primary-dark:#0f4fd1;--bg:#f5f7fb;--card:#ffffff;--border:#e7ecf3;--text:#0f172a;--muted:#64748b}
*{box-sizing:border-box}
body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:var(--text)}
.page-wrap{max-width:1100px;margin:0 auto;padding:28px 24px 56px;text-align:center}
h1{margin:8px 0 6px;font-size:44px;line-height:1.1;font-weight:800;letter-spacing:-.02em}
.small{color:var(--muted);margin:0 0 18px;font-size:14px}
.box{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:18px;box-shadow:0 6px 18px rgba(15,23,42,.06);text-align:left}
.box + .box{margin-top:16px}
.tabbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:center;margin:14px 0 14px}
.tab-btn{appearance:none;border:1px solid var(--border);background:transparent;color:var(--text);padding:8px 14px;border-radius:999px;font-weight:700;cursor:pointer;line-height:1}
.tab-btn:hover{border-color:#cfd8e6}
.tab-btn.active{background:var(--primary);border-color:var(--primary);color:#fff;box-shadow:0 8px 18px rgba(20,99,255,.20)}
.tab-content{display:none}
.tab-content.active{display:block}
input[type="text"],input[type="date"],input[type="number"],textarea,select{width:100%;border:1px solid var(--border);border-radius:12px;padding:10px 12px;background:#fff;outline:none}
input[type="text"]:focus,input[type="date"]:focus,input[type="number"]:focus,textarea:focus,select:focus{border-color:rgba(20,99,255,.55);box-shadow:0 0 0 3px rgba(20,99,255,.12)}
textarea{min-height:90px;resize:vertical}
button{border:0;background:var(--primary);color:#fff;padding:10px 14px;border-radius:12px;font-weight:800;cursor:pointer}
button:hover{background:var(--primary-dark)}
button:disabled{opacity:.6;cursor:not-allowed}
.pill{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--border);background:#fff;padding:6px 10px;border-radius:999px;margin:6px 6px 0 0;font-size:13px}
.alert{padding:12px 12px;border-radius:14px;border:1px solid var(--border);background:#fff}
.alert + .alert{margin-top:10px}
.ai-box,.ai-output{border:1px solid rgba(20,99,255,.28);background:rgba(20,99,255,.05);border-radius:14px;padding:12px;white-space:pre-wrap;word-break:break-word}
</style>
</head>
<body>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Mysihat/partials/nav.php'; ?>
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/Mysihat/partials/header.php'; ?>
<div class="page-wrap">

<h1>Health Assistant</h1>
<p class="small">Rule-based symptom advice + outbreak alerts + allergy safety + period reminder.</p>

<?php if ($msg !== ""): ?>
  <div class="box" style="border-color:#99c2ff;background:#eef6ff;">
    <?= h($msg) ?>
  </div>
<?php endif; ?>

<div class="tabbar" id="healthTabs">
  <button class="tab-btn" data-tab="alerts">Alerts</button>
  <button class="tab-btn" data-tab="period">Period</button>
  <button class="tab-btn" data-tab="allergies">Allergies</button>
  <button class="tab-btn" data-tab="symptoms">Symptoms</button>
</div>

<div class="box tab-content" id="tab-alerts">
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

        <form method="POST" style="margin-top:10px;">
          <input type="hidden" name="action" value="alert_ai">
          <input type="hidden" name="title" value="<?= h($a['title']) ?>">
          <input type="hidden" name="description" value="<?= h($a['description']) ?>">
          <input type="hidden" name="prevention" value="<?= h($a['prevention_tips']) ?>">
          <input type="hidden" name="start_date" value="<?= h($a['start_date']) ?>">
          <input type="hidden" name="end_date" value="<?= h($a['end_date']) ?>">
          <button type="submit">Generate AI Tips</button>
        </form>

        <?php if ($alertAi !== "" && $activeTab === 'alerts'): ?>
        <div class="ai-box"><?= nl2br(h($alertAi)) ?></div>

        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="box tab-content" id="tab-period">
  <h2>Period Tracker</h2>
  <p class="small">Save your period dates. The system shows a simple reminder (not diagnosis).</p>

  <form method="POST">
    <input type="hidden" name="action" value="add_period">
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
      <div>
        <div class="small">Start date</div>
        <input type="date" name="start_date" required>
      </div>
      <div>
        <div class="small">End date (optional)</div>
        <input type="date" name="end_date">
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
      if (!empty($periodInfo['end_date'])) echo "<p><b>Last end:</b> " . h($periodInfo['end_date']) . "</p>";
      echo "<p><b>Cycle length:</b> " . h($cycleLen) . " days</p>";
      echo "<p><b>Estimated next period:</b> " . h($nextStart) . "</p>";

      $todayTs = strtotime($today);
      $startTs = strtotime($lastStart);
      $endTs = !empty($periodInfo['end_date']) ? strtotime($periodInfo['end_date']) : strtotime($lastStart . " +5 days");

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

      $daysToNext = (strtotime($nextStart) - strtotime($today)) / 86400;
      if ($daysToNext <= 3 && $daysToNext >= 0) echo "<p class='note'><b>Reminder:</b> Next period may start soon.</p>";
      elseif ($daysToNext < 0 && $daysToNext >= -5) echo "<p class='warning'><b>Note:</b> Estimated date already passed (possible delay).</p>";
      else echo "<p class='small'>Tip: keep tracking for better reminders.</p>";

      if (!empty($periodInfo['notes'])) echo "<p><b>Notes:</b> " . h($periodInfo['notes']) . "</p>";
    ?>
  <?php endif; ?>
</div>

<div class="box tab-content" id="tab-allergies">
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

  <div style="margin-top:16px;">
    <h3>Potentially unsafe medicines (based on your allergies)</h3>
    <?php if (count($unsafeMeds) === 0): ?>
      <p class="small">No contraindication records found for your saved allergies.</p>
    <?php else: ?>
      <ul style="margin:8px 0 0 18px;">
        <?php foreach ($unsafeMeds as $u): ?>
          <li><b><?= h($u['med_key']) ?></b> — <span class="small"><?= h($u['note'] ?? '') ?></span></li>
        <?php endforeach; ?>
      </ul>
      <p class="small">Tip: Add more allergies to make filtering more accurate.</p>
    <?php endif; ?>
  </div>
</div>

<div class="box tab-content" id="tab-symptoms">
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

  <?php if ($result): ?>
    <div class="box" style="margin-top:16px;">
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

      <h3>AI Explanation</h3>
      <?php
        $aiText = trim((string)$aiExplain);
        if ($aiText === '') $aiText = 'AI ERROR: empty output (no request sent)';
      ?>
      <div class="ai-box"><?= h($aiText) ?></div>

      <p class="small">This is a simple educational prototype, not medical diagnosis.</p>
    </div>
  <?php endif; ?>
</div>

<script>
(function(){
  const defaultTab = <?php echo json_encode($activeTab); ?>;
  const buttons = document.querySelectorAll('#healthTabs .tab-btn');
  const panels = {
    alerts: document.getElementById('tab-alerts'),
    period: document.getElementById('tab-period'),
    allergies: document.getElementById('tab-allergies'),
    symptoms: document.getElementById('tab-symptoms'),
  };
  function setTab(tab){
    buttons.forEach(b => b.classList.toggle('active', b.dataset.tab === tab));
    Object.keys(panels).forEach(k => panels[k].classList.toggle('active', k === tab));
  }
  buttons.forEach(b => b.addEventListener('click', () => setTab(b.dataset.tab)));
  setTab(defaultTab);
})();
</script>
</div>

</body>
</html>
