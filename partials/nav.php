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
        <li class="nav-item">
          <a class="nav-link" href="/mysihat/pages/dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/mysihat/pages/steps.php">
            <i class="bi bi-person-walking"></i> Steps
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/mysihat/pages/health.php">
            <i class="bi bi-heart-pulse"></i> Health
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/mysihat/pages/diet.php">
            <i class="bi bi-egg-fried"></i> Diet
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/mysihat/pages/points.php">
            <i class="bi bi-stars"></i> Points
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/mysihat/pages/leaderboard.php">
            <i class="bi bi-trophy"></i> Leaderboard
          </a>
        </li>

        <!-- ✅ Keep both modules' entries -->
        <li class="nav-item">
          <a class="nav-link" href="/mysihat/pages/achievements.php">
            <i class="bi bi-award"></i> Achievements
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/mysihat/pages/courses.php">
            <i class="bi bi-collection-play"></i> Courses
          </a>
        </li>
      </ul>

      <!-- User Dropdown -->
      <div class="d-flex align-items-center">
        <div class="dropdown">
          <a class="d-flex align-items-center text-decoration-none text-dark dropdown-toggle"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle fs-4 me-2"></i>
            <?php echo htmlspecialchars($current_name); ?>
          </a>

          <ul class="dropdown-menu dropdown-menu-end shadow">
            <li>
              <a class="dropdown-item" href="/mysihat/pages/profile.php?complete_profile=1">
                <i class="bi bi-pencil-square me-2"></i> Edit Profile
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item text-danger" href="/mysihat/auth/logout.php">
                <i class="bi bi-box-arrow-right me-2"></i> Logout
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>
