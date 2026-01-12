<?php
require_once __DIR__ . "/../config/db.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$error_message = "";
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $password_plain = $_POST["password"] ?? "";

  if ($name === "" || $email === "" || $password_plain === "") {
    $error_message = "Please fill in name, email, and password.";
  } else {
    // Check existing email
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

      $success_message = "Registration successful. You can login now.";
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

    <?php if ($success_message !== ""): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="mb-3">
        <label class="form-label">Name</label>
        <input class="form-control" name="name" required placeholder="Your name">
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" name="email" type="email" required placeholder="name@example.com">
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
