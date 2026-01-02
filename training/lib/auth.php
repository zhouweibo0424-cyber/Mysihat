<?php
/**
 * Authentication helper for training module
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Get user_id from session, or use demo user_id=1
function get_user_id() {
  return isset($_SESSION["user_id"]) ? (int)$_SESSION["user_id"] : 1;
}

// Verify user owns resource (for security)
function verify_user_owns($pdo, $user_id, $table, $id_column, $resource_id) {
  $stmt = $pdo->prepare("SELECT user_id FROM {$table} WHERE {$id_column} = :id");
  $stmt->execute([":id" => $resource_id]);
  $row = $stmt->fetch();
  return $row && (int)$row["user_id"] === $user_id;
}

