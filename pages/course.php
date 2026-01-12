<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

$user_id = (int)($_SESSION["user_id"] ?? 0);
$course_id = (int)($_GET["id"] ?? 0);

if ($course_id <= 0) {
  http_response_code(404);
  echo "Course not found.";
  exit();
}

// Fetch course
$course_stmt = $pdo->prepare("
  SELECT c.*, cat.name AS category_name, cat.slug AS category_slug
  FROM courses c
  JOIN categories cat ON cat.id = c.category_id
  WHERE c.id = :id
  LIMIT 1
");
$course_stmt->execute([":id" => $course_id]);
$course = $course_stmt->fetch();

if (!$course) {
  http_response_code(404);
  echo "Course not found.";
  exit();
}

$error_message = "";
$success_message = "";

// Handle review submit
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "review") {
  $rating = (int)($_POST["rating"] ?? 0);
  $comment = trim($_POST["comment"] ?? "");

  if ($rating < 1 || $rating > 5) {
    $error_message = "Rating must be between 1 and 5.";
  } elseif ($comment === "") {
    $error_message = "Comment cannot be empty.";
  } elseif ($user_id === 0) {
    $error_message = "Please login to submit a review.";
  } else {
    $insert = $pdo->prepare("
      INSERT INTO reviews (course_id, user_id, rating, comment)
      VALUES (:cid, :uid, :rating, :comment)
    ");
    $insert->execute([
      ":cid" => $course_id,
      ":uid" => $user_id,
      ":rating" => $rating,
      ":comment" => $comment
    ]);
    $success_message = "Review submitted. Thank you!";
  }
}

// Lessons
$lesson_stmt = $pdo->prepare("
  SELECT id, title, lesson_no, duration_minutes, youtube_url, notes
  FROM lessons
  WHERE course_id = :cid
  ORDER BY lesson_no ASC, id ASC
");
$lesson_stmt->execute([":cid" => $course_id]);
$lessons = $lesson_stmt->fetchAll();

// Reviews listing + average
$reviews_stmt = $pdo->prepare("
  SELECT rating, comment, created_at
  FROM reviews
  WHERE course_id = :cid
  ORDER BY created_at DESC
");
$reviews_stmt->execute([":cid" => $course_id]);
$reviews = $reviews_stmt->fetchAll();

$avg_stmt = $pdo->prepare("
  SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS count_rating
  FROM reviews
  WHERE course_id = :cid
");
$avg_stmt->execute([":cid" => $course_id]);
$rating_row = $avg_stmt->fetch();
$avg_rating = $rating_row["avg_rating"] ?? null;
$rating_count = (int)($rating_row["count_rating"] ?? 0);

function difficultyBadge(string $difficulty): string {
  $map = [
    "Beginner" => "success",
    "Intermediate" => "warning",
    "Advanced" => "danger",
  ];
  $variant = $map[$difficulty] ?? "secondary";
  return '<span class="badge bg-' . $variant . '">' . htmlspecialchars($difficulty) . '</span>';
}
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

<div class="container py-4">
  <div class="row g-3">
    <div class="col-12 col-lg-8">
      <div class="card card-metric mb-3">
        <img src="<?php echo htmlspecialchars($course["thumbnail_url"] ?? "https://via.placeholder.com/640x360?text=Course"); ?>" class="card-img-top" alt="thumbnail">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-secondary"><?php echo htmlspecialchars($course["category_name"]); ?></span>
              <?php echo difficultyBadge($course["difficulty"]); ?>
            </div>
            <div class="small-muted">
              <?php echo htmlspecialchars((string)$course["lessons_count"]); ?> lessons ·
              <?php echo htmlspecialchars((string)$course["total_minutes"]); ?> mins
              <?php if (!empty($course["coach"])): ?>
                · Coach: <?php echo htmlspecialchars($course["coach"]); ?>
              <?php endif; ?>
            </div>
          </div>
          <h3 class="fw-bold mb-2"><?php echo htmlspecialchars($course["title"]); ?></h3>
          <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($course["description"] ?? "")); ?></p>
        </div>
      </div>

      <div class="card card-metric mb-3">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="fw-semibold mb-0">Lessons</h5>
            <div class="small-muted">Ordered playlist</div>
          </div>
          <?php if (count($lessons) === 0): ?>
            <div class="alert alert-light border mb-0">No lessons yet.</div>
          <?php else: ?>
            <div class="list-group">
              <?php foreach ($lessons as $lesson): ?>
                <div class="list-group-item d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                  <div>
                    <div class="fw-semibold">#<?php echo htmlspecialchars((string)$lesson["lesson_no"]); ?> · <?php echo htmlspecialchars($lesson["title"]); ?></div>
                    <div class="small-muted"><?php echo htmlspecialchars((string)$lesson["duration_minutes"]); ?> mins</div>
                  </div>
                  <a class="btn btn-outline-primary btn-sm" href="player.php?lesson_id=<?php echo $lesson["id"]; ?>">
                    <i class="bi bi-play-fill"></i> Play
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="card card-metric">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="fw-semibold mb-0">Reviews</h5>
            <div class="small-muted">
              <?php if ($rating_count > 0): ?>
                Avg <?php echo htmlspecialchars((string)$avg_rating); ?> (<?php echo htmlspecialchars((string)$rating_count); ?>)
              <?php else: ?>
                No ratings yet
              <?php endif; ?>
            </div>
          </div>

          <?php if ($success_message !== ""): ?>
            <div class="alert alert-success py-2"><?php echo htmlspecialchars($success_message); ?></div>
          <?php endif; ?>
          <?php if ($error_message !== ""): ?>
            <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error_message); ?></div>
          <?php endif; ?>

          <form method="post" class="row g-2 mb-3">
            <input type="hidden" name="action" value="review">
            <div class="col-12 col-md-3">
              <label class="form-label small mb-1">Rating</label>
              <select class="form-select" name="rating" required>
                <option value="">Select</option>
                <?php for ($r = 5; $r >= 1; $r--): ?>
                  <option value="<?php echo $r; ?>"><?php echo $r; ?> star<?php echo $r > 1 ? "s" : ""; ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-12 col-md-9">
              <label class="form-label small mb-1">Comment</label>
              <textarea class="form-control" name="comment" rows="2" required placeholder="Share your feedback..."></textarea>
            </div>
            <div class="col-12">
              <button class="btn btn-primary" type="submit" <?php echo $user_id === 0 ? "disabled" : ""; ?>>
                <i class="bi bi-send"></i> Submit review
              </button>
              <?php if ($user_id === 0): ?>
                <span class="small-muted ms-2">Login to submit.</span>
              <?php endif; ?>
            </div>
          </form>

          <?php if (count($reviews) === 0): ?>
            <div class="alert alert-light border mb-0">No reviews yet.</div>
          <?php else: ?>
            <div class="vstack gap-2">
              <?php foreach ($reviews as $rev): ?>
                <div class="border rounded p-2">
                  <div class="d-flex justify-content-between small mb-1">
                    <span class="fw-semibold"><?php echo htmlspecialchars((string)$rev["rating"]); ?> ★</span>
                    <span class="text-muted"><?php echo htmlspecialchars($rev["created_at"]); ?></span>
                  </div>
                  <div><?php echo nl2br(htmlspecialchars($rev["comment"] ?? "")); ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card card-metric mb-3">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Course info</h6>
          <div class="small-muted mb-1"><strong>Goals:</strong><br><?php echo nl2br(htmlspecialchars($course["goals"] ?? "-")); ?></div>
          <div class="small-muted mb-1"><strong>Equipment:</strong><br><?php echo nl2br(htmlspecialchars($course["equipment"] ?? "-")); ?></div>
          <div class="small-muted"><strong>Precautions:</strong><br><?php echo nl2br(htmlspecialchars($course["precautions"] ?? "-")); ?></div>
        </div>
      </div>

      <div class="card card-metric">
        <div class="card-body">
          <h6 class="fw-semibold mb-2">Quick actions</h6>
          <?php if (count($lessons) > 0): ?>
            <a class="btn btn-primary w-100 mb-2" href="player.php?lesson_id=<?php echo $lessons[0]["id"]; ?>">
              <i class="bi bi-play-fill"></i> Start first lesson
            </a>
          <?php endif; ?>
          <a class="btn btn-outline-secondary w-100" href="courses.php"><i class="bi bi-arrow-left"></i> Back to courses</a>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>

