<?php
// PDO connection helper for DigiTon
// Update DB constants with your environment values
define('DB_HOST', 'localhost');
define('DB_NAME', 'digiton');
define('DB_USER', 'root');
define('DB_PASS', '');

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
  $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, $options);
  // Ensure `avatar` column exists in `users` table (helps avoid fatal errors on older schemas)
  try {
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'")->fetch();
    if (!$col) {
      $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER phone");
    }
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_token'")->fetch();
    if (!$col) {
      $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL AFTER avatar");
    }
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'reset_token_expires_at'")->fetch();
    if (!$col) {
      $pdo->exec("ALTER TABLE users ADD COLUMN reset_token_expires_at DATETIME NULL AFTER reset_token");
    }
  } catch (PDOException $e) {
    // If users table doesn't exist yet or ALTER fails, log and continue silently.
    error_log('DB avatar/reset token column check/creation skipped: ' . $e->getMessage());
  }
  // Ensure `status` and `creation_paid` columns exist in `tontine_groups`
  try {
    $col = $pdo->query("SHOW COLUMNS FROM tontine_groups LIKE 'status'")->fetch();
    if (!$col) {
      $pdo->exec("ALTER TABLE tontine_groups ADD COLUMN status ENUM('pending','active','rejected') NOT NULL DEFAULT 'active' AFTER created_at");
    }
    $col2 = $pdo->query("SHOW COLUMNS FROM tontine_groups LIKE 'creation_paid'")->fetch();
    if (!$col2) {
      $pdo->exec("ALTER TABLE tontine_groups ADD COLUMN creation_paid TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
    }
  } catch (PDOException $e) {
    error_log('DB tontine_groups migration skipped: ' . $e->getMessage());
  }
} catch (PDOException $e) {
  // In production, do not reveal details. Log and show friendly message.
  error_log('Database connection error: ' . $e->getMessage());
  die('Database connection failed. Check logs.');
}
