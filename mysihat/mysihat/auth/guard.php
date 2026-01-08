<?php
// Simple login guard: redirect to login if not signed in
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (!isset($_SESSION["user_id"])) {
  header("Location: /mysihat/auth/login.php");
  exit();
}
