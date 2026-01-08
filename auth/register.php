<?php
require_once __DIR__ . "/../config/db.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$error_message = "";
// Removed $success_message because we now redirect immediately

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $password_plain = $_POST["password"] ?? "";

  if ($name === "" || $email === "" || $password_plain === "") {
    $error_message = "Please fill in name, email, and password.";
  } else {
    // Check if email already exists
    $statement = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $statement->execute([":email" => $email]);
    $existing = $statement->fetch();

    if ($existing) {
      $error_message = "This email is already registered. Please login instead.";
    } else {
      $password_hash = password_hash($password_plain, PASSWORD_DEFAULT);

      $insert = $pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)");
      $insert->execute([
        ":name" => $name,
        ":email" => $email,
        ":password_hash" => $password_hash
      ]);

      // === SUCCESS: Auto-login and redirect to profile completion ===
      $new_user_id = $pdo->lastInsertId();  // Get the newly created user ID

      // Set session variables (same as in login.php)
      $_SESSION["user_id"] = (int)$new_user_id;
      $_SESSION["user_name"] = $name;
      $_SESSION["user_email"] = $email;

      // Redirect to profile page with parameter to trigger the completion modal
      header("Location: /mysihat/pages/profile.php?complete_profile=1");
      exit();
      // ==============================================================
    }
  }
}
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>

<div class="container py-5" style="max-width: 520px;">
  <div class="card card-metric p-4">
    <h3 class="fw-bold mb-1">Create account</h3>
    <p class="small-muted mb-4">MySihat AI (Web version)</p>

    <?php if ($error_message !== ""): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="mb-3">
        <label class="form-label">Name</label>
        <input class="form-control" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required placeholder="Your name">
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" name="email" type="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required placeholder="name@example.com">
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input class="form-control" name="password" type="password" required placeholder="At least 6 characters">
      </div>

      <button class="btn btn-primary w-100" type="submit">Register</button>
    </form>

    <div class="mt-3 small-muted">
      Already have an account? <a href="/mysihat/auth/login.php">Login</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>
