<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Simulate current user ID
// If you already have a login system, replace this with $_SESSION['user_id']
$user_id = $_SESSION['user_id'] ?? 1;

// 1️⃣ Get symptoms selected by the user
$selectedSymptoms = $_POST['symptoms'] ?? [];

if (empty($selectedSymptoms)) {
    echo "<p>Please select at least one symptom.</p>";
    exit;
}

// Convert symptoms to a string format (for comparison logic)
$symptomString = implode(',', $selectedSymptoms);

// 2️⃣ Retrieve all symptom rules from database
$sql = "SELECT * FROM symptom_rules";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rules = $stmt->fetchAll(PDO::FETCH_ASSOC);

$matchedRule = null;

// Match user-selected symptoms with symptom rules
foreach ($rules as $rule) {
    $ruleSymptoms = explode(',', $rule['symptoms_csv']);
    if (count(array_intersect($selectedSymptoms, $ruleSymptoms)) > 0) {
        $matchedRule = $rule;
        break;
    }
}

if (!$matchedRule) {
    echo "<p>No advice found for the selected symptoms.</p>";
    exit;
}

// 3️⃣ Extract recommended medications from matched rule
$recommendedMeds = [];
if (!empty($matchedRule['recommend_meds_csv'])) {
    $recommendedMeds = array_map(
        'trim',
        explode(',', strtolower($matchedRule['recommend_meds_csv']))
    );
}

// 4️⃣ Retrieve user allergies
$sql = "SELECT allergy_key FROM user_allergies WHERE user_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$userAllergies = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 5️⃣ Check contraindications between allergies and recommended medications
$unsafeMeds = [];

if (!empty($userAllergies) && !empty($recommendedMeds)) {
    $medPlaceholders = implode(',', array_fill(0, count($recommendedMeds), '?'));
    $allergyPlaceholders = implode(',', array_fill(0, count($userAllergies), '?'));

    $sql = "
        SELECT med_key, note
        FROM contraindications
        WHERE allergy_key IN ($allergyPlaceholders)
        AND med_key IN ($medPlaceholders)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($userAllergies, $recommendedMeds));
    $unsafeMeds = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 6️⃣ Remove unsafe medications and collect warning messages
$finalMeds = $recommendedMeds;
$warnings = [];

foreach ($unsafeMeds as $item) {
    $med = strtolower($item['med_key']);
    $finalMeds = array_diff($finalMeds, [$med]);
    $warnings[] = "⚠ {$med}: {$item['note']}";
}

// 7️⃣ Output results (rendered back to health.php)
?>

<h3>Health Advice</h3>
<p><?= htmlspecialchars($matchedRule['advice']) ?></p>

<?php if (!empty($matchedRule['red_flags'])): ?>
    <p style="color:red;">
        <strong>Warning:</strong>
        <?= htmlspecialchars($matchedRule['red_flags']) ?>
    </p>
<?php endif; ?>

<h3>Recommended Medicines</h3>
<?php if (!empty($finalMeds)): ?>
    <ul>
        <?php foreach ($finalMeds as $med): ?>
            <li><?= htmlspecialchars($med) ?></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No safe medications can be recommended based on your allergies.</p>
<?php endif; ?>

<?php if (!empty($warnings)): ?>
    <h3>Medication Warnings</h3>
    <ul>
        <?php foreach ($warnings as $warning): ?>
            <li style="color:orange;">
                <?= htmlspecialchars($warning) ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
