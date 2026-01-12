<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

$user_id = (int)$_SESSION["user_id"];
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
// 1. Daily Check-in Logic
// ========================
$checkin_stmt = $pdo->prepare("SELECT 1 FROM daily_checkins WHERE user_id = ? AND checkin_date = ?");
$checkin_stmt->execute([$user_id, $today]);
$already_checked_in = $checkin_stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkin']) && !$already_checked_in) {
    try {
        $pdo->beginTransaction();

        // Insert check-in record (10 points)
        $insert_checkin = $pdo->prepare("INSERT INTO daily_checkins (user_id, checkin_date, points_earned) VALUES (?, ?, 10)");
        $insert_checkin->execute([$user_id, $today]);

        // Add points to ledger
        $insert_ledger = $pdo->prepare("INSERT INTO points_ledger (user_id, points_earned, reason, point_date) VALUES (?, 10, 'Daily check-in bonus', ?)");
        $insert_ledger->execute([$user_id, $today]);

        // Update streak
        $user_stmt = $pdo->prepare("SELECT last_checkin_date, checkin_streak FROM users WHERE id = ?");
        $user_stmt->execute([$user_id]);
        $user = $user_stmt->fetch();

        $yesterday = date("Y-m-d", strtotime("-1 day"));
        $new_streak = ($user['last_checkin_date'] == $yesterday) ? ($user['checkin_streak'] + 1) : 1;

        $update_user = $pdo->prepare("UPDATE users SET checkin_streak = ?, last_checkin_date = ? WHERE id = ?");
        $update_user->execute([$new_streak, $today, $user_id]);

        $pdo->commit();
        $message = "Check-in successful! You earned 10 points! 🎉 Current streak: {$new_streak} days";
        $already_checked_in = true;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = "Check-in failed. Please try again.";
    }
}

// Get current streak
$streak_stmt = $pdo->prepare("SELECT checkin_streak FROM users WHERE id = ?");
$streak_stmt->execute([$user_id]);
$current_streak = (int)$streak_stmt->fetchColumn();

// Build 30-day check-in calendar
$calendar_days = 30;
$start_date = date("Y-m-d", strtotime("-$calendar_days days"));

$checkins = $pdo->prepare("
    SELECT checkin_date
    FROM daily_checkins
    WHERE user_id = ? AND checkin_date >= ?
");
$checkins->execute([$user_id, $start_date]);
$checkin_dates = $checkins->fetchAll(PDO::FETCH_COLUMN);

$calendar = [];
$current_date = new DateTime($start_date);
$today_obj = new DateTime();

for ($i = 0; $i <= $calendar_days; $i++) {
    $date_str = $current_date->format('Y-m-d');
    $is_today = $date_str === $today;
    $is_checked = in_array($date_str, $checkin_dates);

    $calendar[] = [
        'date' => $date_str,
        'day' => $current_date->format('j'),
        'month' => $current_date->format('M'),
        'is_today' => $is_today,
        'is_checked' => $is_checked
    ];

    $current_date->modify('+1 day');
}

// ========================
// 2. Generate points from steps
// ========================
if (($_POST["action"] ?? "") === "generate_points") {
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? "")) {
        $error_message = "Invalid operation. Please refresh the page and try again.";
    } else {
        $stmt = $pdo->prepare("
            SELECT steps FROM daily_steps
            WHERE user_id = :user_id AND step_date = :day
            LIMIT 1
        ");
        $stmt->execute([":user_id" => $user_id, ":day" => $today]);
        $row = $stmt->fetch();
        $today_steps = $row ? (int)$row["steps"] : 0;

        $earned_points = intdiv($today_steps, 100);

        $pdo->prepare("
            DELETE FROM points_ledger
            WHERE user_id = :user_id
              AND point_date = :day
              AND reason = 'Auto points from steps'
        ")->execute([":user_id" => $user_id, ":day" => $today]);

        if ($earned_points > 0) {
            $pdo->prepare("
                INSERT INTO points_ledger
                (user_id, point_date, points_earned, points_spent, reason)
                VALUES (:user_id, :day, :earned, 0, 'Auto points from steps')
            ")->execute([
                ":user_id" => $user_id,
                ":day" => $today,
                ":earned" => $earned_points
            ]);
        }

        if ($earned_points > 0) {
            $message = "Points generated! You earned {$earned_points} points from today's steps.";
        } else {
            $message = "No points from steps today. Keep walking!";
        }
    }
}

// ========================
// Total points, level, steps, trend, history
// ========================
$total_points = (int)$pdo->query("
    SELECT COALESCE(SUM(points_earned - points_spent), 0)
    FROM points_ledger WHERE user_id = $user_id
")->fetchColumn();

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

$today_steps_display = (int)$pdo->query("
    SELECT COALESCE(steps, 0) FROM daily_steps
    WHERE user_id = $user_id AND step_date = '$today'
")->fetchColumn();

$trend_data = array_reverse($pdo->query("
    SELECT point_date, SUM(points_earned - points_spent) AS daily
    FROM points_ledger WHERE user_id = $user_id
    GROUP BY point_date ORDER BY point_date DESC LIMIT 7
")->fetchAll());

$dates = [];
$points_trend = [];
foreach ($trend_data as $t) {
    $dates[] = date("M j", strtotime($t["point_date"]));
    $points_trend[] = (int)$t["daily"];
}

$ledger_stmt = $pdo->prepare("
    SELECT point_date, points_earned, points_spent, reason, created_at
    FROM points_ledger WHERE user_id = :user_id
    ORDER BY created_at DESC LIMIT 20
");
$ledger_stmt->execute([":user_id" => $user_id]);
$ledger = $ledger_stmt->fetchAll();
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<div class="container py-4" style="max-width:960px">
    <h3 class="fw-bold mb-4">Points Center</h3>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php elseif ($error_message): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <!-- Check-in + Calendar -->
    <div class="row g-4 mb-5">
        <div class="col-lg-5">
            <div class="card shadow-sm p-4 text-center h-100">
                <i class="bi bi-calendar-check fs-1 text-primary mb-3"></i>
                <h4>Daily Check-in</h4>
                <p class="text-muted">Today: <?= date("l, F j, Y") ?></p>

                <?php if ($already_checked_in): ?>
                    <div class="alert alert-info">
                        <strong>Checked in today!</strong><br>
                        Current streak: <strong><?= $current_streak ?></strong> days 🔥
                    </div>
                <?php else: ?>
                    <p class="lead mb-4">Check in to earn <strong>10 points</strong>!</p>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <button type="submit" name="checkin" class="btn btn-primary btn-lg px-5">Check In Now</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm p-4 h-100">
                <h5 class="fw-bold mb-3">Check-in Calendar (Last 30 Days)</h5>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                    <?php foreach ($calendar as $day): ?>
                        <div class="text-center">
                            <div class="rounded p-2 <?= $day['is_checked'] ? 'bg-success text-white' : 'bg-light' ?>
                                <?= $day['is_today'] ? 'border border-primary border-3' : '' ?>"
                                style="width: 50px; height: 60px;">
                                <div class="small fw-bold"><?= $day['day'] ?></div>
                                <div class="small"><?= $day['month'] ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3 small text-muted">
                    <span class="d-inline-block rounded bg-success text-white px-2 py-1 me-3">Checked in</span>
                    <span class="d-inline-block rounded bg-light px-2 py-1">Missed</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Points Summary -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card p-4 shadow-sm text-center">
                <div class="small text-muted">Total Points</div>
                <div class="fs-1 fw-bold"><?= number_format($total_points) ?></div>
                <div class="mt-2">Level: <span class="fw-bold <?= $level_class ?>"><?= $level ?></span></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 shadow-sm text-center">
                <div class="small text-muted">Today's Steps</div>
                <div class="fs-2 fw-bold"><?= number_format($today_steps_display) ?></div>
                <div class="small text-muted">(100 steps = 1 point)</div>
                <form method="post" class="mt-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="action" value="generate_points">
                    <button class="btn btn-success w-100">Generate Points</button>
                </form>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 shadow-sm">
                <div class="fw-semibold mb-3">7-Day Points Trend</div>
                <canvas id="pointsChart" height="120"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent History -->
    <h5 class="fw-semibold mb-3">Recent Point History</h5>
    <div class="table-responsive card shadow-sm p-3">
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Earned</th>
                    <th>Spent</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ledger as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l["point_date"]) ?></td>
                        <td class="text-success">+<?= $l["points_earned"] ?></td>
                        <td class="text-danger">-<?= $l["points_spent"] ?></td>
                        <td><?= htmlspecialchars($l["reason"]) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    new Chart(document.getElementById('pointsChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Points',
                data: <?= json_encode($points_trend) ?>,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>
