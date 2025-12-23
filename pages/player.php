<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

$lesson_id = (int)($_GET["lesson_id"] ?? 0);
if ($lesson_id <= 0) {
  http_response_code(404);
  echo "Lesson not found.";
  exit();
}

// Current lesson + course + category
$lesson_stmt = $pdo->prepare("
  SELECT
    l.*,
    c.id AS course_id,
    c.title AS course_title,
    c.category_id,
    c.difficulty,
    cat.name AS category_name
  FROM lessons l
  JOIN courses c ON c.id = l.course_id
  JOIN categories cat ON cat.id = c.category_id
  WHERE l.id = :id
  LIMIT 1
");
$lesson_stmt->execute([":id" => $lesson_id]);
$lesson = $lesson_stmt->fetch();

if (!$lesson) {
  http_response_code(404);
  echo "Lesson not found.";
  exit();
}

// Extract YouTube ID
function extractYoutubeId(string $url, ?string $fallbackId = null): ?string {
  if ($fallbackId !== null && $fallbackId !== "") {
    return $fallbackId;
  }
  $pattern = '/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|v\/|shorts\/))([A-Za-z0-9_\-]{6,})/';
  if (preg_match($pattern, $url, $matches)) {
    return $matches[1];
  }
  return null;
}

$youtube_id = extractYoutubeId($lesson["youtube_url"], $lesson["youtube_id"]);

// Playlist: other lessons in same category (limit 20)
$playlist_stmt = $pdo->prepare("
  SELECT
    l.id,
    l.title,
    l.lesson_no,
    l.duration_minutes,
    c.title AS course_title,
    c.id AS course_id
  FROM lessons l
  JOIN courses c ON c.id = l.course_id
  WHERE c.category_id = :category_id
  ORDER BY c.title ASC, l.lesson_no ASC, l.id ASC
  LIMIT 20
");
$playlist_stmt->execute([":category_id" => $lesson["category_id"]]);
$playlist = $playlist_stmt->fetchAll();

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
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <div>
              <div class="small-muted"><?php echo htmlspecialchars($lesson["category_name"]); ?> · <?php echo difficultyBadge($lesson["difficulty"]); ?></div>
              <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($lesson["course_title"]); ?></h4>
              <div class="text-muted">Lesson #<?php echo htmlspecialchars((string)$lesson["lesson_no"]); ?> · <?php echo htmlspecialchars($lesson["title"]); ?></div>
            </div>
            <a class="btn btn-outline-secondary btn-sm" href="course.php?id=<?php echo $lesson["course_id"]; ?>"><i class="bi bi-arrow-left"></i> Back to course</a>
          </div>

          <div class="ratio ratio-16x9 mb-3">
            <?php if ($youtube_id): ?>
              <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($youtube_id); ?>" title="YouTube video player" allowfullscreen></iframe>
            <?php else: ?>
              <div class="d-flex align-items-center justify-content-center bg-light text-muted">
                Invalid YouTube link.
              </div>
            <?php endif; ?>
          </div>

          <?php if (!empty($lesson["notes"])): ?>
            <div class="card card-metric border mb-0">
              <div class="card-body">
                <h6 class="fw-semibold mb-2">Notes</h6>
                <div class="text-muted"><?php echo nl2br(htmlspecialchars($lesson["notes"])); ?></div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-12 col-lg-4">
      <div class="card card-metric h-100">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-semibold mb-0">More in <?php echo htmlspecialchars($lesson["category_name"]); ?></h6>
            <span class="small-muted">Category playlist</span>
          </div>
          <?php if (count($playlist) === 0): ?>
            <div class="alert alert-light border mb-0">No other lessons.</div>
          <?php else: ?>
            <div class="vstack gap-2">
              <?php foreach ($playlist as $item): ?>
                <a class="d-block border rounded p-2 text-decoration-none <?php echo $item["id"] == $lesson_id ? 'bg-light' : ''; ?>"
                   href="player.php?lesson_id=<?php echo $item["id"]; ?>">
                  <div class="d-flex justify-content-between">
                    <div class="fw-semibold small"><?php echo htmlspecialchars($item["course_title"]); ?></div>
                    <div class="small-muted">#<?php echo htmlspecialchars((string)$item["lesson_no"]); ?></div>
                  </div>
                  <div class="text-muted small"><?php echo htmlspecialchars($item["title"]); ?> · <?php echo htmlspecialchars((string)$item["duration_minutes"]); ?> mins</div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>

