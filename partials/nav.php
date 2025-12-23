<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
$current_name = $_SESSION["user_name"] ?? "User";
?>
<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/mysihat/pages/dashboard.php">
      <span class="brand-dot"></span>MySihat AI
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
      aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/mysihat/pages/dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="/mysihat/pages/steps.php"><i class="bi bi-person-walking"></i> Steps</a></li>
        <li class="nav-item"><a class="nav-link" href="/mysihat/pages/diet.php"><i class="bi bi-egg-fried"></i> Diet</a></li>
        <li class="nav-item"><a class="nav-link" href="/mysihat/pages/points.php"><i class="bi bi-stars"></i> Points</a></li>
        <li class="nav-item"><a class="nav-link" href="/mysihat/pages/leaderboard.php"><i class="bi bi-trophy"></i> Leaderboard</a></li>
        <li class="nav-item"><a class="nav-link" href="/mysihat/pages/courses.php"><i class="bi bi-collection-play"></i> Courses</a></li>
      </ul>

      <div class="d-flex align-items-center gap-2">
        <span class="small-muted">Signed in as <span class="fw-semibold"><?php echo htmlspecialchars($current_name); ?></span></span>
        <a class="btn btn-outline-danger btn-sm" href="/mysihat/auth/logout.php">Logout</a>
      </div>
    </div>
  </div>
</nav>
