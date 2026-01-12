<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

$user_id = (int)($_SESSION["user_id"] ?? 0);
$favorite_message = "";

// Duration buckets
$duration_ranges = [
  "0-10" => ["label" => "0-10 minutes", "min" => 0, "max" => 10],
  "10-20" => ["label" => "10-20 minutes", "min" => 10, "max" => 20],
  "20-40" => ["label" => "20-40 minutes", "min" => 20, "max" => 40],
  "40+" => ["label" => "40+ minutes", "min" => 40, "max" => null],
];

// Handle favorite toggle
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["favorite_action"], $_POST["course_id"])) {
  if ($user_id === 0) {
    $favorite_message = "Please login to manage favorites.";
  } else {
    $course_id = (int)$_POST["course_id"];
    if ($_POST["favorite_action"] === "add") {
      $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, course_id) VALUES (:uid, :cid)");
      $stmt->execute([":uid" => $user_id, ":cid" => $course_id]);
      $favorite_message = "Added to favorites.";
    } elseif ($_POST["favorite_action"] === "remove") {
      $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = :uid AND course_id = :cid");
      $stmt->execute([":uid" => $user_id, ":cid" => $course_id]);
      $favorite_message = "Removed from favorites.";
    }
  }
}

// Filters
$q = trim($_GET["q"] ?? "");
$category_slug = trim($_GET["category"] ?? "");
$difficulty = trim($_GET["difficulty"] ?? "");
$duration_key = trim($_GET["duration"] ?? "");
$page = max(1, (int)($_GET["page"] ?? 1));
$per_page = 9;
$offset = ($page - 1) * $per_page;

// Category list for dropdown
$categories = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name ASC")->fetchAll();

// Build dynamic WHERE
$where = [];
$params = [];

if ($q !== "") {
  $where[] = "(c.title LIKE :q OR c.description LIKE :q OR c.goals LIKE :q)";
  $params[":q"] = "%" . $q . "%";
}

if ($category_slug !== "") {
  $where[] = "cat.slug = :category";
  $params[":category"] = $category_slug;
}

if (in_array($difficulty, ["Beginner", "Intermediate", "Advanced"], true)) {
  $where[] = "c.difficulty = :difficulty";
  $params[":difficulty"] = $difficulty;
}

if ($duration_key !== "" && isset($duration_ranges[$duration_key])) {
  $range = $duration_ranges[$duration_key];
  if ($range["min"] !== null) {
    $where[] = "c.total_minutes >= :dmin";
    $params[":dmin"] = $range["min"];
  }
  if ($range["max"] !== null) {
    $where[] = "c.total_minutes <= :dmax";
    $params[":dmax"] = $range["max"];
  }
}

$where_sql = count($where) > 0 ? ("WHERE " . implode(" AND ", $where)) : "";

// Total count
$count_sql = "
  SELECT COUNT(*) AS total
  FROM courses c
  JOIN categories cat ON cat.id = c.category_id
  $where_sql
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_rows = (int)$count_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_rows / $per_page));

// Fetch courses
$courses_sql = "
  SELECT
    c.*,
    cat.name AS category_name,
    cat.slug AS category_slug,
    (
      SELECT l.id FROM lessons l
      WHERE l.course_id = c.id
      ORDER BY l.lesson_no ASC, l.id ASC
      LIMIT 1
    ) AS first_lesson_id
  FROM courses c
  JOIN categories cat ON cat.id = c.category_id
  $where_sql
  ORDER BY c.created_at DESC
  LIMIT :limit OFFSET :offset
";
$courses_stmt = $pdo->prepare($courses_sql);
foreach ($params as $k => $v) {
  $courses_stmt->bindValue($k, $v);
}
$courses_stmt->bindValue(":limit", $per_page, PDO::PARAM_INT);
$courses_stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
$courses_stmt->execute();
$courses = $courses_stmt->fetchAll();

// Favorites set
$favorite_ids = [];
if ($user_id > 0) {
  $fav_stmt = $pdo->prepare("SELECT course_id FROM favorites WHERE user_id = :uid");
  $fav_stmt->execute([":uid" => $user_id]);
  $favorite_ids = array_column($fav_stmt->fetchAll(), "course_id");
}

function difficultyBadge(string $difficulty): string {
  $map = [
    "Beginner" => "success",
    "Intermediate" => "warning",
    "Advanced" => "danger",
  ];
  $variant = $map[$difficulty] ?? "secondary";
  return '<span class="badge bg-' . $variant . '">' . htmlspecialchars($difficulty) . '</span>';
}

function build_page_link(int $page): string {
  $query = $_GET;
  $query["page"] = $page;
  return "?" . http_build_query($query);
}
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <div>
      <h3 class="fw-bold mb-1">Courses</h3>
      <div class="small-muted">Find the right program by category, difficulty, or duration.</div>
    </div>
    <form class="d-flex" method="get" style="min-width: 260px;">
      <input class="form-control" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search courses...">
    </form>
  </div>

  <?php if ($favorite_message !== ""): ?>
    <div class="alert alert-info py-2"><?php echo htmlspecialchars($favorite_message); ?></div>
  <?php endif; ?>

  <div class="row g-3">
    <div class="col-12 col-md-3">
      <div class="card card-metric p-3 sticky-top" style="top: 88px;">
        <div class="fw-semibold mb-2">Filters</div>
        <form method="get" class="vstack gap-2">
          <div>
            <label class="form-label small mb-1">Category</label>
            <select class="form-select" name="category">
              <option value="">All</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat["slug"]); ?>" <?php echo $cat["slug"] === $category_slug ? "selected" : ""; ?>>
                  <?php echo htmlspecialchars($cat["name"]); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="form-label small mb-1">Difficulty</label>
            <select class="form-select" name="difficulty">
              <option value="">All</option>
              <?php foreach (["Beginner","Intermediate","Advanced"] as $diff): ?>
                <option value="<?php echo $diff; ?>" <?php echo $diff === $difficulty ? "selected" : ""; ?>>
                  <?php echo $diff; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="form-label small mb-1">Duration</label>
            <select class="form-select" name="duration">
              <option value="">Any</option>
              <?php foreach ($duration_ranges as $key => $data): ?>
                <option value="<?php echo $key; ?>" <?php echo $key === $duration_key ? "selected" : ""; ?>>
                  <?php echo htmlspecialchars($data["label"]); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="d-grid pt-1">
            <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Apply</button>
            <a class="btn btn-outline-secondary mt-2" href="courses.php">Reset</a>
          </div>
        </form>
      </div>
    </div>

    <div class="col-12 col-md-9">
      <?php if ($total_rows === 0): ?>
        <div class="alert alert-light border">No courses found. Try adjusting filters.</div>
      <?php endif; ?>

      <div class="row g-3">
        <?php foreach ($courses as $course): ?>
          <div class="col-12 col-md-4">
            <div class="card h-100 card-metric">
              <img src="<?php echo htmlspecialchars($course["thumbnail_url"] ?? "https://via.placeholder.com/640x360?text=Course"); ?>" class="card-img-top" alt="thumbnail">
              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                  <span class="badge bg-secondary"><?php echo htmlspecialchars($course["category_name"]); ?></span>
                  <?php echo difficultyBadge($course["difficulty"]); ?>
                </div>
                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($course["title"]); ?></h6>
                <div class="small-muted mb-2">
                  <?php echo htmlspecialchars((string)$course["lessons_count"]); ?> lessons ·
                  <?php echo htmlspecialchars((string)$course["total_minutes"]); ?> mins
                </div>
                <p class="small text-muted flex-grow-1"><?php echo htmlspecialchars($course["description"] ?? ""); ?></p>

                <div class="d-grid gap-2 mt-2">
                  <a class="btn btn-outline-primary btn-sm" href="course.php?id=<?php echo $course["id"]; ?>">
                    <i class="bi bi-eye"></i> View
                  </a>
                  <a class="btn btn-primary btn-sm <?php echo $course["first_lesson_id"] ? "" : "disabled"; ?>"
                     href="<?php echo $course["first_lesson_id"] ? ("player.php?lesson_id=" . $course["first_lesson_id"]) : "#"; ?>">
                    <i class="bi bi-play-fill"></i> Start
                  </a>
                  <form method="post" class="d-grid">
                    <input type="hidden" name="course_id" value="<?php echo $course["id"]; ?>">
                    <?php if (in_array($course["id"], $favorite_ids, true)): ?>
                      <input type="hidden" name="favorite_action" value="remove">
                      <button class="btn btn-outline-danger btn-sm" type="submit">
                        <i class="bi bi-heart-fill"></i> Remove Favorite
                      </button>
                    <?php else: ?>
                      <input type="hidden" name="favorite_action" value="add">
                      <button class="btn btn-outline-secondary btn-sm" type="submit" <?php echo $user_id === 0 ? "disabled" : ""; ?>>
                        <i class="bi bi-heart"></i> Add Favorite
                      </button>
                    <?php endif; ?>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($total_pages > 1): ?>
        <nav aria-label="Courses pagination" class="mt-3">
          <ul class="pagination">
            <li class="page-item <?php echo $page <= 1 ? "disabled" : ""; ?>">
              <a class="page-link" href="<?php echo build_page_link(max(1, $page - 1)); ?>">Prev</a>
            </li>
            <?php for ($p = 1; $p <= $total_pages; $p++): ?>
              <li class="page-item <?php echo $p === $page ? "active" : ""; ?>">
                <a class="page-link" href="<?php echo build_page_link($p); ?>"><?php echo $p; ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $total_pages ? "disabled" : ""; ?>">
              <a class="page-link" href="<?php echo build_page_link(min($total_pages, $page + 1)); ?>">Next</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>