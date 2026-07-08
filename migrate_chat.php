<?php
/**
 * Migration script: Remove direct_messages table and update group_messages
 * Access: http://localhost/DigiTon/migrate_chat.php
 */
require_once __DIR__ . '/config/database.php';

try {
  // Drop direct_messages table if exists
  $pdo->exec('DROP TABLE IF EXISTS direct_messages');

  // Alter group_messages to add file support
  $pdo->exec('
    ALTER TABLE group_messages 
    ADD COLUMN IF NOT EXISTS file_path VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS file_type ENUM("image","video","pdf","document","other") NULL,
    ADD COLUMN IF NOT EXISTS file_name VARCHAR(255) NULL
  ');

  // Create uploads directory if it doesn't exist
  if (!is_dir(__DIR__ . '/uploads')) {
    mkdir(__DIR__ . '/uploads', 0755, true);
  }

  echo '
  <html>
  <head>
    <title>Chat Migration - DigiTon</title>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="p-5">
  <div class="container">
    <div class="alert alert-success">
      <h4>✅ Migration completed successfully!</h4>
      <p>Changes made:</p>
      <ul>
        <li>✓ Dropped <code>direct_messages</code> table</li>
        <li>✓ Added file support to <code>group_messages</code> (file_path, file_type, file_name)</li>
        <li>✓ Created uploads directory</li>
      </ul>
    </div>
    <div class="mt-4">
      <a href="public/chats.php" class="btn btn-primary">Go to Chats</a>
      <a href="public/dashboard.php" class="btn btn-secondary">Dashboard</a>
    </div>
  </div>
  </body>
  </html>
  ';
} catch (Exception $e) {
  echo '
  <html>
  <head>
    <title>Chat Migration - Error</title>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="p-5">
  <div class="container">
    <div class="alert alert-danger">
      <h4>❌ Error</h4>
      <p>' . htmlspecialchars($e->getMessage()) . '</p>
    </div>
  </div>
  </body>
  </html>
  ';
  error_log('Chat migration error: ' . $e->getMessage());
}
