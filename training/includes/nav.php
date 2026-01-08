<?php
/**
 * Training Module Navigation
 * Shows module title and tab navigation
 */
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$nav_items = [
  'plan' => ['label' => 'Plan Builder', 'icon' => 'bi-calendar-check', 'url' => '/mysihat/training/plan.php'],
  'today' => ['label' => 'Today Workout', 'icon' => 'bi-play-circle', 'url' => '/mysihat/training/today.php'],
  'history' => ['label' => 'History', 'icon' => 'bi-clock-history', 'url' => '/mysihat/training/history.php'],
  'insight' => ['label' => 'Insight', 'icon' => 'bi-graph-up', 'url' => '/mysihat/training/insight.php'],
];
?>
<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/mysihat/pages/dashboard.php">
      <span class="brand-dot"></span>MySihat AI
    </a>
    <span class="navbar-text text-muted">Training System</span>
  </div>
</nav>

<div class="training-nav">
  <div class="container">
    <ul class="nav nav-pills training-nav">
      <?php foreach ($nav_items as $key => $item): ?>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page === $key ? 'active' : ''; ?>" 
             href="<?php echo htmlspecialchars($item['url']); ?>">
            <i class="<?php echo htmlspecialchars($item['icon']); ?>"></i>
            <?php echo htmlspecialchars($item['label']); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<div class="container py-4">

