<?php
/**
 * API: Send group message
 */
require_once '../../config/database.php';
require_once '../../includes/auth-check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

// Accept both JSON and FormData
$input = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST)) {
    $input = $_POST;
  } else {
    $input = json_decode(file_get_contents('php://input'), true);
  }
}

error_log('send_message input: ' . json_encode($input));

if (!isset($input['group_id']) || !isset($input['message'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing group_id or message', 'debug_input' => $input, 'debug_post' => $_POST]);
  exit;
}

$groupId = (int)$input['group_id'];
$message = trim($input['message']);

if (strlen($message) === 0 || strlen($message) > 5000) {
  http_response_code(400);
  echo json_encode(['error' => 'Message must be between 1 and 5000 characters']);
  exit;
}

try {
  // Verify user is member of group
  $memberCheck = $pdo->prepare(
    'SELECT gm.id FROM group_members gm WHERE gm.group_id = ? AND gm.user_id = ?'
  );
  $memberCheck->execute([$groupId, $_SESSION['user_id']]);
  
  if (!$memberCheck->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Not a member of this group']);
    exit;
  }

  // Send group message
  $stmt = $pdo->prepare(
    'INSERT INTO group_messages (group_id, user_id, message) VALUES (?, ?, ?)'
  );
  $stmt->execute([$groupId, $_SESSION['user_id'], $message]);
  
  error_log('Message inserted: group_id=' . $groupId . ', user_id=' . $_SESSION['user_id'] . ', message=' . $message);
  
  http_response_code(200);
  echo json_encode([
    'success' => true,
    'message_id' => $pdo->lastInsertId(),
  ]);
} catch (Exception $e) {
  error_log('Send message error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}
