<?php
// Entry point: if signed in, go to dashboard; otherwise go to login
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (isset($_SESSION["user_id"])) {
  header("Location: /mysihat/pages/dashboard.php");
  exit();
}

header("Location: /mysihat/auth/login.php");
exit();
