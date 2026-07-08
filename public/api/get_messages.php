<?php
/**
 * API: Get group messages with file attachments
 */
require_once '../../config/database.php';
require_once '../../includes/auth-check.php';

header('Content-Type: application/json');

$groupId = $_GET['group_id'] ?? null;
$limit = min((int)($_GET['limit'] ?? 50), 500);
$offset = (int)($_GET['offset'] ?? 0);

if (!$groupId) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing group_id']);
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

  // Get group messages with file attachments
  $stmt = $pdo->prepare(
    'SELECT 
      gm.id,
      gm.message,
      gm.created_at,
      gm.user_id,
      gm.file_path,
      gm.file_type,
      gm.file_name,
      CONCAT(u.first_name, " ", u.last_name) as sender_name,
      u.avatar
    FROM group_messages gm
    JOIN users u ON gm.user_id = u.id
    WHERE gm.group_id = ?
    ORDER BY gm.created_at DESC
    LIMIT ? OFFSET ?'
  );
  $stmt->execute([$groupId, $limit, $offset]);
  $messages = array_reverse($stmt->fetchAll());
  
  echo json_encode([
    'success' => true,
    'messages' => $messages,
  ]);
} catch (Exception $e) {
  error_log('Get messages error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['error' => 'Server error']);
}
