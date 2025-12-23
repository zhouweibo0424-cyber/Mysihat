<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

// Current week start (Monday)
$week_start = date("Y-m-d", strtotime("monday this week"));

$statement = $pdo->prepare("
  SELECT u.name,
         COALESCE(SUM(p.points_earned - p.points_spent), 0) AS weekly_points
  FROM users u
  LEFT JOIN points_ledger p ON p.user_id = u.id AND p.point_date >= :week_start
  GROUP BY u.id
  ORDER BY weekly_points DESC, u.name ASC
  LIMIT 10
");
$statement->execute([":week_start" => $week_start]);
$rows = $statement->fetchAll();
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<div class="container py-4" style="max-width: 960px;">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h3 class="fw-bold mb-0">Leaderboard</h3>
    <span class="small-muted">Week starts: <?php echo htmlspecialchars($week_start); ?></span>
  </div>

  <div class="card card-metric p-3">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width: 90px;">Rank</th>
            <th>Name</th>
            <th style="width: 160px;">Weekly points</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($rows) === 0): ?>
            <tr><td colspan="3" class="small-muted">No data yet.</td></tr>
          <?php endif; ?>

          <?php foreach ($rows as $index => $row): ?>
            <tr>
              <td class="fw-semibold">
                <?php
                  $rank = $index + 1;
                  if ($rank === 1) echo "🥇 1";
                  else if ($rank === 2) echo "🥈 2";
                  else if ($rank === 3) echo "🥉 3";
                  else echo htmlspecialchars((string)$rank);
                ?>
              </td>
              <td><?php echo htmlspecialchars($row["name"]); ?></td>
              <td class="fw-semibold"><?php echo htmlspecialchars((string)$row["weekly_points"]); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="small-muted">
      This leaderboard sums points_earned - points_spent from the current week.
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>
