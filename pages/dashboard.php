<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";
// 引入 training 模块的计划服务
require_once __DIR__ . "/../training/lib/plan_service.php"; 

$user_id = (int)($_SESSION["user_id"] ?? 0);
$today = date("Y-m-d");
$plan_service = new PlanService($pdo);

// 1. 获取步数及进度计算
$steps_statement = $pdo->prepare("SELECT steps FROM daily_steps WHERE user_id = :user_id AND step_date = :step_date LIMIT 1");
$steps_statement->execute([":user_id" => $user_id, ":step_date" => $today]);
$steps_row = $steps_statement->fetch();
$today_steps = $steps_row ? (int)$steps_row["steps"] : 0;

$step_goal = 10000;
$step_percentage = min(100, round(($today_steps / $step_goal) * 100));

// 2. 获取今日净积分
$points_statement = $pdo->prepare("
  SELECT COALESCE(SUM(points_earned - points_spent), 0) AS net_points
  FROM points_ledger
  WHERE user_id = :user_id AND point_date = :day
");
$points_statement->execute([":user_id" => $user_id, ":day" => $today]);
$today_points = (int)($points_statement->fetch()["net_points"] ?? 0);

// 3. 获取真实的今日训练任务
$stmt = $pdo->prepare("
  SELECT pd.plan_day_id, pd.date, pd.estimated_minutes
  FROM plan_days pd
  JOIN plans p ON pd.plan_id = p.plan_id
  WHERE p.user_id = ? AND pd.date = ?
  LIMIT 1
");
$stmt->execute([$user_id, $today]);
$plan_day = $stmt->fetch(PDO::FETCH_ASSOC);

$today_exercises = [];
if ($plan_day) {
    $stmt = $pdo->prepare("
      SELECT e.name AS exercise_name, pi.target_sets, pi.target_reps
      FROM plan_items pi
      JOIN exercises e ON e.exercise_id = pi.exercise_id
      WHERE pi.plan_day_id = ?
      ORDER BY pi.order_no ASC
    ");
    $stmt->execute([(int)$plan_day['plan_day_id']]);
    $today_exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 4. 获取真实的课程推荐 (联动 courses.php 逻辑)
// 获取最新的 6 个课程作为推荐
$courses_stmt = $pdo->prepare("
  SELECT c.id, c.title, c.difficulty, cat.name AS category_name, c.thumbnail_url 
  FROM courses c
  JOIN categories cat ON cat.id = c.category_id
  ORDER BY c.created_at DESC
  LIMIT 6
");
$courses_stmt->execute();
$recommended_courses = $courses_stmt->fetchAll();

$tip_text = "Tip: Try to reach 6,000–10,000 steps today. Small walk after meals helps.";
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<style>
    .card-metric { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); border-radius: 1rem; transition: transform 0.2s; background: #fff; }
    .card-metric:hover { transform: translateY(-3px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1); }
    .progress-ring__circle { transition: stroke-dashoffset 0.8s ease-in-out; transform: rotate(-90deg); transform-origin: 50% 50%; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    .course-card { min-width: 220px; border-radius: 1rem; overflow: hidden; border: 1px solid #eee; }
    .course-card img { height: 120px; object-fit: cover; }
    .small-muted { font-size: 0.85rem; color: #6c757d; }
    .exercise-item { border-left: 4px solid #0d6efd; background: #f8f9fa; transition: background 0.2s; }
</style>

<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <h3 class="fw-bold mb-0">Dashboard</h3>
        
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card card-metric p-3 h-100 d-flex flex-row align-items-center justify-content-around">
                <div class="text-start">
                    <div class="small-muted mb-1">Today's Steps</div>
                    <div class="fs-2 fw-bold"><?php echo number_format($today_steps); ?></div>
                    <div class="small text-primary">Goal: 10,000</div>
                </div>
                <div class="position-relative" style="width: 80px; height: 80px;">
                    <svg width="80" height="80">
                        <circle stroke="#f0f0f0" stroke-width="6" fill="transparent" r="35" cx="40" cy="40"/>
                        <circle class="progress-ring__circle" stroke="#0d6efd" stroke-width="6" stroke-dasharray="219.9" stroke-dashoffset="<?php echo 219.9 * (1 - $step_percentage/100); ?>" stroke-linecap="round" fill="transparent" r="35" cx="40" cy="40"/>
                    </svg>
                    <div class="position-absolute top-50 start-50 translate-middle fw-bold small"><?php echo $step_percentage; ?>%</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card card-metric p-3 h-100">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small-muted">Total Net Points</div>
                        <div class="fs-2 fw-bold text-primary"><?php echo $today_points; ?></div>
                    </div>
                    <div class="fs-1 text-primary"><i class="bi bi-stars"></i></div>
                </div>
                <div class="mt-auto pt-2">
                    <div class="small-muted fst-italic">Complete training to earn points!</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-7">
            <div class="card card-metric p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar2-check me-2 text-primary"></i>Today's Exercises</h6>
                    <?php if ($plan_day): ?>
                        <span class="badge bg-light text-primary border fw-normal"><?php echo (int)$plan_day['estimated_minutes']; ?> min</span>
                    <?php endif; ?>
                </div>

                <div class="d-flex flex-column gap-2">
                    <?php if (!empty($today_exercises)): ?>
                        <?php foreach($today_exercises as $ex): ?>
                        <div class="d-flex align-items-center justify-content-between p-2 rounded exercise-item">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle me-2 text-primary opacity-50"></i>
                                <span class="fw-medium small"><?php echo htmlspecialchars($ex['exercise_name']); ?></span>
                            </div>
                            <span class="badge bg-white text-muted border fw-normal">
                                <?php echo (int)$ex['target_sets']; ?>x<?php echo (int)$ex['target_reps']; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <div class="mt-2 text-end">
                            <a href="../training/today.php" class="small text-primary text-decoration-none fw-bold">Start Workout <i class="bi bi-arrow-right"></i></a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-cup-hot text-muted fs-2"></i>
                            <p class="small-muted mt-2">Rest day. Take a break!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-5">
            <div class="card card-metric p-3 h-100 d-flex flex-column">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 me-3">
                        <i class="bi bi-cpu text-primary fs-4"></i>
                    </div>
                    <h6 class="fw-bold mb-0">AI Training System</h6>
                </div>
                <p class="small-muted flex-grow-1">Your personal AI coach adjusts your plan based on recovery and progress.</p>
                <div class="mt-3">
                    <a href="../training/index.php" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="bi bi-activity me-1"></i> Open System
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-bold mb-0"><i class="bi bi-collection-play me-2 text-danger"></i>Recommend for you</h6>
            <a href="courses.php" class="small text-decoration-none">View All</a>
        </div>
        <div class="d-flex flex-nowrap overflow-auto gap-3 pb-2 no-scrollbar">
            <?php foreach($recommended_courses as $course): ?>
            <div class="card course-card bg-white shadow-sm flex-shrink-0">
                <img src="<?php echo htmlspecialchars($course["thumbnail_url"] ?? "https://via.placeholder.com/640x360?text=Course"); ?>" class="card-img-top" alt="thumbnail">
                <div class="p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="badge bg-light text-dark fw-normal border" style="font-size: 0.7rem;"><?php echo htmlspecialchars($course["category_name"]); ?></span>
                        <span class="text-primary fw-bold" style="font-size: 0.7rem;"><?php echo htmlspecialchars($course["difficulty"]); ?></span>
                    </div>
                    <div class="fw-bold small text-truncate mb-2" title="<?php echo htmlspecialchars($course["title"]); ?>">
                        <?php echo htmlspecialchars($course["title"]); ?>
                    </div>
                    <a href="course.php?id=<?php echo $course["id"]; ?>" class="btn btn-sm btn-outline-primary w-100" style="font-size: 0.75rem;">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="card card-metric p-3 border-0 bg-light">
        <div class="d-flex align-items-center gap-3">
            <div class="text-warning fs-4"><i class="bi bi-lightbulb"></i></div>
            <div class="small text-muted italic"><?php echo htmlspecialchars($tip_text); ?></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>