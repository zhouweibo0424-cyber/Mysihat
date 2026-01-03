<?php
require_once __DIR__ . "/../auth/guard.php";
require_once __DIR__ . "/../config/db.php";

$user_id = (int)$_SESSION["user_id"];

$message = "";
$error_message = "";

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fetch all profile data
$profile_stmt = $pdo->prepare("
    SELECT full_name, birth_date, phone, address, country, language,
           height, weight, gender, daily_step_goal, target_weight, expected_weeks
    FROM users
    WHERE id = :user_id
");
$profile_stmt->execute([":user_id" => $user_id]);
$profile = $profile_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

// Show modal if first time or explicitly requested
$is_first_time = (!empty($_GET['complete_profile']) || $profile['height'] === null || $profile['weight'] === null);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['skip'])) {
        // User clicked "Skip for now" — just close modal
        $is_first_time = false;
    } else {
        if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? "")) {
            $error_message = "Invalid request. Please try again.";
        } else {
            $full_name       = trim($_POST['full_name'] ?? '');
            $birth_date      = $_POST['birth_date'] ?? null;
            $phone           = trim($_POST['phone'] ?? '');
            $address         = trim($_POST['address'] ?? '');
            $country         = trim($_POST['country'] ?? '');
            $language        = $_POST['language'] ?? 'en';
            $height          = !empty($_POST['height']) ? (float)$_POST['height'] : null;
            $weight          = !empty($_POST['weight']) ? (float)$_POST['weight'] : null;
            $gender          = $_POST['gender'] ?? null;
            $daily_step_goal = !empty($_POST['daily_step_goal']) ? (int)$_POST['daily_step_goal'] : 10000;
            $target_weight   = !empty($_POST['target_weight']) ? (float)$_POST['target_weight'] : null;
            $expected_weeks  = !empty($_POST['expected_weeks']) ? (int)$_POST['expected_weeks'] : null;

            if ($height !== null && ($height < 50 || $height > 250)) {
                $error_message = "Height must be between 50 and 250 cm.";
            } elseif ($weight !== null && ($weight < 20 || $weight > 300)) {
                $error_message = "Weight must be between 20 and 300 kg.";
            } elseif ($target_weight !== null && $weight !== null && $target_weight >= $weight) {
                $error_message = "Target weight must be less than current weight.";
            } else {
                $update_stmt = $pdo->prepare("
                    UPDATE users SET
                        full_name = ?, birth_date = ?, phone = ?, address = ?, country = ?, language = ?,
                        height = ?, weight = ?, gender = ?, daily_step_goal = ?,
                        target_weight = ?, expected_weeks = ?
                    WHERE id = ?
                ");
                $update_stmt->execute([
                        $full_name ?: null, $birth_date ?: null, $phone ?: null, $address ?: null,
                        $country ?: null, $language, $height, $weight, $gender, $daily_step_goal,
                        $target_weight, $expected_weeks, $user_id
                ]);

                $message = "Profile saved successfully!";
                $is_first_time = false;

                $profile_stmt->execute([":user_id" => $user_id]);
                $profile = $profile_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            }
        }
    }
}
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>
<?php require_once __DIR__ . "/../partials/nav.php"; ?>

    <div class="container py-4" style="max-width:960px">
        <h3 class="fw-bold mb-4">My Profile</h3>

        <?php if ($message && !$is_first_time): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

<?php if (!$is_first_time): ?>
    <div class="card shadow-sm p-4">
        <h5 class="fw-bold mb-4">Your Profile Information</h5>

        <div class="row">
            <!-- Left column: Personal & Health -->
            <div class="col-md-6">
                <p><strong>Full Name:</strong>
                    <?= htmlspecialchars($profile['full_name'] ?? 'Not set') ?>
                </p>

                <p><strong>Email:</strong>
                    <?= htmlspecialchars($_SESSION['user_email'] ?? 'Not set') ?>
                </p>

                <p><strong>Gender:</strong>
                    <?= htmlspecialchars(ucfirst($profile['gender'] ?? 'Not set')) ?>
                </p>

                <p><strong>Birth Date:</strong>
                    <?= htmlspecialchars($profile['birth_date'] ?? 'Not set') ?>
                </p>

                <hr>

                <p><strong>Height:</strong>
                    <?= htmlspecialchars($profile['height'] ?? 'Not set') ?> cm
                </p>

                <p><strong>Current Weight:</strong>
                    <?= htmlspecialchars($profile['weight'] ?? 'Not set') ?> kg
                </p>

                <p><strong>Target Weight:</strong>
                    <?= htmlspecialchars($profile['target_weight'] ?? 'Not set') ?> kg
                </p>

                <p><strong>Expected Weeks:</strong>
                    <?= htmlspecialchars($profile['expected_weeks'] ?? 'Not set') ?>
                </p>
            </div>

            <!-- Right column: Contact & Preferences -->
            <div class="col-md-6">
                <p><strong>Phone:</strong>
                    <?= htmlspecialchars($profile['phone'] ?? 'Not set') ?>
                </p>

                <p><strong>Address:</strong>
                    <?= htmlspecialchars($profile['address'] ?? 'Not set') ?>
                </p>

                <p><strong>Country:</strong>
                    <?= htmlspecialchars($profile['country'] ?? 'Not set') ?>
                </p>

                <p><strong>Language:</strong>
                    <?= htmlspecialchars(strtoupper($profile['language'] ?? 'Not set')) ?>
                </p>

                <hr>

                <p><strong>Daily Step Goal:</strong>
                    <?= htmlspecialchars($profile['daily_step_goal'] ?? '10000') ?> steps
                </p>
            </div>
        </div>

        <a href="?complete_profile=1" class="btn btn-primary mt-4">
            Edit Profile
        </a>
    </div>
<?php endif; ?>



<?php if ($is_first_time): ?>
<div class="modal fade show" id="completeProfileModal" tabindex="-1" style="display: block;" aria-modal="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header d-flex justify-content-between align-items-center border-0">
                <h5 class="modal-title fw-bold">Complete Your Profile</h5>
                <form method="post" class="d-inline">
                    <button type="submit" name="skip" value="1" class="btn-close" aria-label="Skip for now"></button>
                </form>
            </div>

            <div class="modal-body">
                <p class="text-muted">Please provide your basic info for personalized health tracking. You can skip and fill later.</p>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <!-- Basic Information -->
                    <h6 class="fw-bold mb-3">Basic Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name (optional)</label>
                            <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Birth Date (optional)</label>
                            <input type="date" class="form-control" name="birth_date" value="<?= htmlspecialchars($profile['birth_date'] ?? '') ?>" lang="en">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone (optional)</label>
                            <input type="tel" class="form-control" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Address (optional)</label>
                            <input type="text" class="form-control" name="address" value="<?= htmlspecialchars($profile['address'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country (optional)</label>
                            <input type="text" class="form-control" name="country" value="<?= htmlspecialchars($profile['country'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Language</label>
                            <select class="form-select" name="language">
                                <option value="en" <?= ($profile['language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English</option>
                                <option value="zh" <?= ($profile['language'] ?? '') === 'zh' ? 'selected' : '' ?>>Chinese</option>
                                <option value="ms" <?= ($profile['language'] ?? '') === 'ms' ? 'selected' : '' ?>>Bahasa Malaysia</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Health Information -->
                    <h6 class="fw-bold mb-3">Health Information</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Height (cm) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="height" value="<?= htmlspecialchars($profile['height'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Current Weight (kg) <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" class="form-control" name="weight" value="<?= htmlspecialchars($profile['weight'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender (optional)</label>
                            <select class="form-select" name="gender">
                                <option value="">Select...</option>
                                <option value="male" <?= ($profile['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                <option value="female" <?= ($profile['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                <option value="other" <?= ($profile['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Daily Step Goal (optional)</label>
                            <input type="number" class="form-control" name="daily_step_goal" value="<?= htmlspecialchars($profile['daily_step_goal'] ?? '10000') ?>">
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Weight Loss Goal -->
                    <h6 class="fw-bold mb-3">Weight Loss Goal (optional)</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Target Weight (kg)</label>
                            <input type="number" step="0.001" class="form-control" name="target_weight" value="<?= htmlspecialchars($profile['target_weight'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">In how many weeks?</label>
                            <input type="number" min="1" max="52" class="form-control" name="expected_weeks" value="<?= htmlspecialchars($profile['expected_weeks'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="d-flex justify-content-between mt-5">
                        <form method="post">
                            <button type="submit" name="skip" value="1" class="btn btn-outline-secondary btn-lg">Skip for now</button>
                        </form>
                        <button type="submit" class="btn btn-primary btn-lg">Save & Continue</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
<?php endif; ?>


<?php require_once __DIR__ . "/../partials/footer.php"; ?>