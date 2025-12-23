<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

session_unset();
session_destroy();

header("Location: /mysihat/auth/login.php");
exit();
