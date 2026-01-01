<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

$user_id = (int)($_SESSION["user_id"] ?? 0);
$today = date("Y-m-d");

$message = "";
$error_message = "";

// ========================
// CSRF Token
// ========================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ========================
// 1. Generate points from today's steps
// ========================
if (($_POST["action"] ?? "") === "generate_points") {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? "")) {
        $error_message = "Invalid operation. Please refresh the page and try again.";
    } else {
        // Fetch today's steps
        $stmt = $pdo->prepare("
            SELECT steps FROM daily_steps
            WHERE user_id = :user_id AND step_date = :day
            LIMIT 1
        ");
        $stmt->execute([":user_id" => $user_id, ":day" => $today]);
        $row = $stmt->fetch();
        $today_steps = $row ? (int)$row["steps"] : 0;

        // Calculate points: 1 point per 100 steps, no daily cap
        $earned_points = intdiv($today_steps, 100);

        // Remove any previous auto-generated points for today (to keep it idempotent)
        $pdo->prepare("
            DELETE FROM points_ledger
            WHERE user_id = :user_id
              AND point_date = :day
              AND reason = 'Auto points from steps'
        ")->execute([":user_id" => $user_id, ":day" => $today]);

        // Insert new points record
        $pdo->prepare("
            INSERT INTO points_ledger
            (user_id, point_date, points_earned, points_spent, reason)
            VALUES (:user_id, :day, :earned, 0, 'Auto points from steps')
        ")->execute([
            ":user_id" => $user_id,
            ":day" => $today,
            ":earned" => $earned_points
        ]);

        if ($earned_points > 0) {
            $message = "Points generated successfully! You earned <strong>{$earned_points}</strong> points from today's steps.";
        } else {
            $message = "No points earned today. You walked <strong>" . number_format($today_steps) . "</strong> steps (100 steps = 1 point). Keep walking!";
        }
    }
}

// ========================
// 2. Redeem reward
// ========================
if (($_POST["action"] ?? "") === "redeem_reward") {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? "")) {
        $error_message = "Invalid operation. Please refresh the page and try again.";
    } else {
        $reward_id = (int)($_POST["reward_id"] ?? 0);

        $reward_stmt = $pdo->prepare("
            SELECT reward_name, points_required
            FROM rewards
            WHERE id = :id
        ");
        $reward_stmt->execute([":id" => $reward_id]);
        $reward = $reward_stmt->fetch();

        if (!$reward) {
            $error_message = "Reward not found.";
        } else {
            $cost = (int)$reward["points_required"];

            // Calculate current points balance
            $balance_stmt = $pdo->prepare("
                SELECT COALESCE(SUM(points_earned - points_spent), 0) AS total
                FROM points_ledger
                WHERE user_id = :user_id
            ");
            $balance_stmt->execute([":user_id" => $user_id]);
            $balance = (int)$balance_stmt->fetch()["total"];

            if ($balance >= $cost) {
                $pdo->prepare("
                    INSERT INTO points_ledger
                    (user_id, point_date, points_earned, points_spent, reason)
                    VALUES (:user_id, :day, 0, :spent, :reason)
                ")->execute([
                    ":user_id" => $user_id,
                    ":day" => $today,
                    ":spent" => $cost,
                    ":reason" => "Redeemed: " . $reward["reward_name"]
                ]);

                $message = "Congratulations! You successfully redeemed \"{$reward["reward_name"]}\"!";
            } else {
                $error_message = "Not enough points. You have {$balance}, but need {$cost}.";
            }
        }
    }
}

// ========================
// 3. Total points and level
// ========================
$total_stmt = $pdo->prepare("
    SELECT COALESCE(SUM(points_earned - points_spent), 0) AS total
    FROM points_ledger
    WHERE user_id = :user_id
");
$total_stmt->execute([":user_id" => $user_id]);
$total_points = (int)$total_stmt->fetch()["total"];

// Determine user level
if ($total_points >= 600) {
    $level = "Pro";
    $level_class = "text-danger";
} elseif ($total_points >= 300) {
    $level = "Advanced";
    $level_class = "text-primary";
} elseif ($total_points >= 100) {
    $level = "Active";
    $level_class = "text-success";
} else {
    $level = "Beginner";
    $level_class = "text-muted";
}

// ========================
// 4. Today's steps for display (fixed duplicate fetch bug)
// ========================
$step_stmt = $pdo->prepare("
    SELECT steps FROM daily_steps
    WHERE user_id = :user_id AND step_date = :day
    LIMIT 1
");
$step_stmt->execute([":user_id" => $user_id, ":day" => $today]);
$step_row = $step_stmt->fetch();
$today_steps_display = $step_row ? (int)$step_row["steps"] : 0;

// ========================
// 5. 7-day points trend
// ========================
$trend_stmt = $pdo->prepare("
    SELECT point_date, SUM(points_earned - points_spent) AS daily
    FROM points_ledger
    WHERE user_id = :user_id
    GROUP BY point_date
    ORDER BY point_date DESC
    LIMIT 7
");
$trend_stmt->execute([":user_id" => $user_id]);
$trend_data = array_reverse($trend_stmt->fetchAll());

$dates = [];
$points = [];
foreach ($trend_data as $t) {
    $dates[] = date("M j", strtotime($t["point_date"]));
    $points[] = (int)$t["daily"];
}

// ========================
// 6. Available rewards
// ========================
$rewards_stmt = $pdo->prepare("
    SELECT id, reward_name, description, points_required
    FROM rewards
    ORDER BY points_required ASC
");
$rewards_stmt->execute();
$rewards = $rewards_stmt->fetchAll();

// ========================
// 7. Recent point history (last 20 entries)
// ========================
$ledger_stmt = $pdo->prepare("
    SELECT point_date, points_earned, points_spent, reason, created_at
    FROM points_ledger
    WHERE user_id = :user_id
    ORDER BY created_at DESC
    LIMIT 20
");
$ledger_stmt->execute([":user_id" => $user_id]);
$ledger = $ledger_stmt->fetchAll();
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<div class="container py-4" style="max-width:960px">
    <h3 class="fw-bold mb-3">Points Center</h3>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <!-- Total Points + Trend Chart -->
    <div class="row g-4 mb-5">
        <div class="col-md-6">
            <div class="card p-4 shadow-sm">
                <div class="small text-muted">Total Points</div>
                <div class="fs-1 fw-bold"><?= number_format($total_points) ?></div>
                <div class="mt-2">
                    Level: <span class="fw-bold <?= $level_class ?>"><?= $level ?></span>
                </div>
                <div class="small text-muted mt-3">
                    Today's steps: <strong><?= number_format($today_steps_display) ?></strong>
                    <span class="text-muted">(100 steps = 1 point)</span>
                </div>
                <form method="post" class="mt-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="generate_points">
                    <button class="btn btn-primary w-100">
                        Generate Points from Today's Steps
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card p-4 shadow-sm">
                <div class="fw-semibold mb-3">7-Day Points Trend</div>
                <canvas id="pointsChart" height="180"></canvas>
            </div>
        </div>
    </div>

    <!-- Available Rewards -->
    <h5 class="fw-semibold mb-3">Available Rewards</h5>
    <?php if (empty($rewards)): ?>
        <div class="text-center text-muted py-5">No rewards available yet. Stay tuned!</div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($rewards as $r): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body p-4">
                            <h6 class="fw-bold"><?= htmlspecialchars($r["reward_name"]) ?></h6>
                            <p class="text-muted small"><?= htmlspecialchars($r["description"]) ?></p>
                            <div class="mt-3">
                                Required: <strong><?= number_format($r["points_required"]) ?></strong> points
                            </div>
                            <div class="mt-4">
                                <?php if ($total_points >= $r["points_required"]): ?>
                                    <form method="post">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="action" value="redeem_reward">
                                        <input type="hidden" name="reward_id" value="<?= $r["id"] ?>">
                                        <button class="btn btn-success w-100">Redeem Now</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-100" disabled>
                                        Insufficient points (need <?= $r["points_required"] - $total_points ?> more)
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Recent Point History -->
    <h5 class="fw-semibold mt-5 mb-3">Recent Point History (Last 20)</h5>
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Earned</th>
                    <th>Spent</th>
                    <th>Reason</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ledger)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">No records yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ledger as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars($l["point_date"]) ?></td>
                            <td class="text-success fw-bold">+<?= $l["points_earned"] ?></td>
                            <td class="text-danger fw-bold">-<?= $l["points_spent"] ?></td>
                            <td><?= htmlspecialchars($l["reason"]) ?></td>
                            <td class="text-muted small"><?= htmlspecialchars($l["created_at"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('pointsChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Daily Net Points',
                data: <?= json_encode($points) ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });
</script>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>
