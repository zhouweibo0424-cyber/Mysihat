<?php
require_once __DIR__ . "/../config/db.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email = trim($_POST["email"] ?? "");
  $password_plain = $_POST["password"] ?? "";

  if ($email === "" || $password_plain === "") {
    $error_message = "Please enter email and password.";
  } else {
    $statement = $pdo->prepare("SELECT id, name, email, password_hash FROM users WHERE email = :email LIMIT 1");
    $statement->execute([":email" => $email]);
    $user = $statement->fetch();

    if (!$user || !password_verify($password_plain, $user["password_hash"])) {
      $error_message = "Invalid email or password.";
    } else {
      $_SESSION["user_id"] = (int)$user["id"];
      $_SESSION["user_name"] = $user["name"];
      $_SESSION["user_email"] = $user["email"];

      header("Location: /mysihat/pages/dashboard.php");
      exit();
    }
  }
}
?>

<?php require_once __DIR__ . "/../partials/header.php"; ?>

<div class="container py-5" style="max-width: 520px;">
  <div class="card card-metric p-4">
    <h3 class="fw-bold mb-1">Login</h3>
    <p class="small-muted mb-4">MySihat AI (Web version)</p>

    <?php if ($error_message !== ""): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" name="email" type="email" required placeholder="name@example.com">
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input class="form-control" name="password" type="password" required placeholder="Your password">
      </div>

      <button class="btn btn-primary w-100" type="submit">Login</button>
    </form>

    <div class="mt-3 small-muted">
      No account yet? <a href="/mysihat/auth/register.php">Register</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . "/../partials/footer.php"; ?>
