<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

$user_id = (int)$_SESSION["user_id"];

// Calculate user's total points
$total_points = (int)$pdo->query("
    SELECT COALESCE(SUM(points_earned - points_spent), 0)
    FROM points_ledger
    WHERE user_id = $user_id
")->fetchColumn();

// Auto-unlock achievements based on points only
$new_unlocks = [];

$achs = $pdo->query("
    SELECT id, name, required_points
    FROM achievements
    WHERE required_points > 0
")->fetchAll();

foreach ($achs as $ach) {
    if ($total_points >= $ach['required_points']) {
        $check = $pdo->prepare("SELECT 1 FROM user_achievements WHERE user_id = ? AND achievement_id = ?");
        $check->execute([$user_id, $ach['id']]);
        if (!$check->fetch()) {
            $pdo->prepare("INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)")
                ->execute([$user_id, $ach['id']]);
            $new_unlocks[] = $ach['name'];
        }
    }
}

// Fetch all achievements with unlock status
$achievements = $pdo->query("
    SELECT
        a.id, a.name, a.description, a.icon, a.color_class, a.required_points,
        ua.unlocked_at IS NOT NULL AS is_unlocked,
        ua.unlocked_at
    FROM achievements a
    LEFT JOIN user_achievements ua ON ua.achievement_id = a.id AND ua.user_id = $user_id
    ORDER BY a.sort_order ASC
")->fetchAll();
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<div class="container py-4" style="max-width:960px">
    <h3 class="fw-bold mb-4">Achievement Gallery</h3>

    <?php if (!empty($new_unlocks)): ?>
        <div class="alert alert-success text-center mb-4">
            🎉 Congratulations! You unlocked: <?= htmlspecialchars(implode(', ', $new_unlocks)) ?> 🎉
        </div>
    <?php endif; ?>

    <?php if (empty($achievements)): ?>
        <div class="text-center text-muted py-5">No achievements available yet.</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($achievements as $ach): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card h-100 shadow-sm border-0 <?= $ach['is_unlocked'] ? '' : 'opacity-50' ?>">
                        <div class="card-body text-center p-4">
                            <i class="bi bi-<?= htmlspecialchars($ach['icon'] ?? 'trophy') ?> fs-1 <?= htmlspecialchars($ach['color_class'] ?? 'text-warning') ?> mb-3"></i>
                            <h5 class="fw-bold"><?= htmlspecialchars($ach['name']) ?></h5>
                            <p class="small text-muted mb-3"><?= htmlspecialchars($ach['description']) ?></p>

                            <?php if ($ach['is_unlocked']): ?>
                                <span class="badge bg-success fs-6">Unlocked</span>
                                <div class="small text-muted mt-2">
                                    <?= date("M d, Y", strtotime($ach['unlocked_at'])) ?>
                                </div>
                            <?php else: ?>
                                <span class="badge bg-secondary fs-6">Locked</span>
                                <div class="small text-muted mt-2">
                                    Requires <?= number_format($ach['required_points']) ?> points
                                    (you have <?= number_format($total_points) ?>)
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="text-center mt-5 text-muted small">
        Achievements unlock automatically when you earn enough points!
    </div>
</div>

<!-- Confetti animation for new unlocks -->
<?php if (!empty($new_unlocks)): ?>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
<script>
    confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
    setTimeout(() => {
        confetti({ particleCount: 50, angle: 60, spread: 55, origin: { x: 0 } });
        confetti({ particleCount: 50, angle: 120, spread: 55, origin: { x: 1 } });
    }, 250);
</script>
<?php endif; ?>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>