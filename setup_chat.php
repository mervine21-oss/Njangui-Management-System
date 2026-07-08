<?php
/**
 * Setup script for chat tables
 * Access: http://localhost/DigiTon/setup_chat.php
 */
require_once __DIR__ . '/config/database.php';

try {
  // Create group_messages table
  $pdo->exec('
    CREATE TABLE IF NOT EXISTS group_messages (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      group_id INT UNSIGNED NOT NULL,
      user_id INT UNSIGNED NOT NULL,
      message TEXT NOT NULL,
      file_path VARCHAR(255) NULL,
      file_type ENUM("image","video","pdf","document","other") NULL,
      file_name VARCHAR(255) NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (group_id) REFERENCES tontine_groups(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      INDEX idx_group_messages (group_id,created_at)
    ) ENGINE=InnoDB;
  ');

  // Create uploads directory
  $uploadsDir = __DIR__ . '/uploads';
  if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
  }

  echo '
  <html>
  <head>
    <title>Chat Setup - DigiTon</title>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="p-5">
  <div class="container">
    <div class="alert alert-success">
      <h4>✅ Chat system set up successfully!</h4>
      <p>The following have been created:</p>
      <ul>
        <li><code>group_messages</code> table - Group chat with file attachment support</li>
        <li><code>uploads/</code> directory - For storing uploaded files</li>
      </ul>
      <p style="margin-top: 1rem; color: #666;"><strong>Supported files:</strong> Photos (JPG, PNG, GIF, WebP), Videos (MP4, WebM, MOV), PDFs, Documents (Word, Text)</p>
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
    <title>Chat Setup - Error</title>
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
  error_log('Chat setup error: ' . $e->getMessage());
}
