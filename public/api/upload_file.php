<?php
/**
 * API: Upload file to group message
 */
require_once '../../config/database.php';
require_once '../../includes/auth-check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$groupId = $_POST['group_id'] ?? null;
$message = $_POST['message'] ?? '';

if (!$groupId || !isset($_FILES['file'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing group_id or file']);
  exit;
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
    echo json_encode(['error' => 'Not a member of this group']);
    exit;
  }

  $file = $_FILES['file'];
  $maxSize = 50 * 1024 * 1024; // 50MB
  $allowedTypes = [
    'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'video' => ['video/mp4', 'video/webm', 'video/quicktime'],
    'pdf' => ['application/pdf'],
    'document' => ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'],
  ];

  // Validate file
  if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'File upload error']);
    exit;
  }

  if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large (max 50MB)']);
    exit;
  }

  // Detect file type
  $mimeType = mime_content_type($file['tmp_name']);
  $fileType = 'other';
  foreach ($allowedTypes as $type => $mimes) {
    if (in_array($mimeType, $mimes)) {
      $fileType = $type;
      break;
    }
  }

  if ($fileType === 'other') {
    http_response_code(400);
    echo json_encode(['error' => 'File type not allowed']);
    exit;
  }

  // Create upload directory
  $uploadDir = __DIR__ . '/../../uploads/group_' . $groupId;
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  // Generate unique filename
  $fileName = $file['name'];
  $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
  $uniqueName = uniqid('file_') . '_' . time() . '.' . $fileExt;
  $filePath = $uploadDir . '/' . $uniqueName;

  // Move uploaded file
  if (!move_uploaded_file($file['tmp_name'], $filePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
  }

  // Save to database
  $stmt = $pdo->prepare(
    'INSERT INTO group_messages (group_id, user_id, message, file_path, file_type, file_name) 
     VALUES (?, ?, ?, ?, ?, ?)'
  );
  
  $relativePath = '../uploads/group_' . $groupId . '/' . $uniqueName;
  $stmt->execute([$groupId, $_SESSION['user_id'], $message, $relativePath, $fileType, $fileName]);

  echo json_encode([
    'success' => true,
    'message_id' => $pdo->lastInsertId(),
    'file_type' => $fileType,
    'file_path' => $relativePath,
  ]);
} catch (Exception $e) {
  error_log('Upload error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['error' => 'Server error']);
}
