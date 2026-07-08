<?php
/**
 * API: Serve uploaded files with proper MIME types
 */
require_once '../../config/database.php';
require_once '../../includes/auth-check.php';

$groupId = $_GET['group_id'] ?? null;
$filename = $_GET['file'] ?? null;

if (!$groupId || !$filename) {
  http_response_code(400);
  die('Missing parameters');
}

$groupId = (int)$groupId;

try {
  // Verify user is member of group
  $memberCheck = $pdo->prepare(
    'SELECT id FROM group_members WHERE group_id = ? AND user_id = ?'
  );
  $memberCheck->execute([$groupId, $_SESSION['user_id']]);
  if (!$memberCheck->fetch()) {
    http_response_code(403);
    die('Access denied');
  }

  // Verify file belongs to this group
  $fileCheck = $pdo->prepare(
    'SELECT file_type, file_name FROM group_messages 
     WHERE group_id = ? AND file_path LIKE ? LIMIT 1'
  );
  $fileCheck->execute([$groupId, '%' . $filename]);
  $file = $fileCheck->fetch();
  
  if (!$file) {
    http_response_code(404);
    die('File not found');
  }

  $filePath = __DIR__ . '/../../uploads/group_' . $groupId . '/' . $filename;
  
  if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found on disk');
  }

  // Set appropriate MIME type
  $mimeTypes = [
    'image' => 'image/jpeg',
    'video' => 'video/mp4',
    'pdf' => 'application/pdf',
    'document' => 'application/octet-stream',
    'other' => 'application/octet-stream',
  ];

  header('Content-Type: ' . ($mimeTypes[$file['file_type']] ?? 'application/octet-stream'));
  header('Content-Disposition: inline; filename="' . basename($file['file_name']) . '"');
  header('Content-Length: ' . filesize($filePath));
  
  readfile($filePath);
} catch (Exception $e) {
  http_response_code(500);
  error_log('File serve error: ' . $e->getMessage());
  die('Server error');
}
