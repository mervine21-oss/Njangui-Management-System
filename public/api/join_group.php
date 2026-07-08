<?php
// API endpoint: join a user to a group and initialize wallets
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$group_id = isset($input['group_id']) ? (int)$input['group_id'] : (int)($_POST['group_id'] ?? 0);
if (!$group_id) {
  http_response_code(400);
  echo json_encode(['error' => 'Missing group_id']);
  exit;
}

try {
  // Check group exists
  $stmt = $pdo->prepare('SELECT capacity FROM tontine_groups WHERE id = ? LIMIT 1');
  $stmt->execute([$group_id]);
  $group = $stmt->fetch();
  if (!$group) {
    http_response_code(404);
    echo json_encode(['error' => 'Group not found']);
    exit;
  }

  // Check if already member
  $mStmt = $pdo->prepare('SELECT id FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1');
  $mStmt->execute([$group_id, $userId]);
  if ($mStmt->fetch()) {
    echo json_encode(['ok' => true, 'message' => 'Already a member']);
    exit;
  }

  // Count active members
  $countStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM group_members WHERE group_id = ? AND status = 'active'");
  $countStmt->execute([$group_id]);
  $cnt = (int)$countStmt->fetchColumn();
  if ($cnt >= (int)$group['capacity']) {
    http_response_code(409);
    echo json_encode(['error' => 'Group is full']);
    exit;
  }

  // Insert membership
  $insert = $pdo->prepare('INSERT INTO group_members (group_id, user_id, is_admin, status, is_new_member) VALUES (?,?,?,?,1)');
  $insert->execute([$group_id, $userId, 0, 'active']);

  // Initialize wallet row if not exists
  $wStmt = $pdo->prepare('SELECT id FROM wallets WHERE group_id = ? AND user_id = ? LIMIT 1');
  $wStmt->execute([$group_id, $userId]);
  if (!$wStmt->fetch()) {
    $createW = $pdo->prepare('INSERT INTO wallets (group_id, user_id, rotational_balance, savings_balance) VALUES (?,?,0.00,0.00)');
    $createW->execute([$group_id, $userId]);
  }

  // Return success with updated seats remaining
  $countStmt->execute([$group_id]);
  $newCnt = (int)$countStmt->fetchColumn();
  $seatsRemaining = max(0, (int)$group['capacity'] - $newCnt);

  echo json_encode(['ok' => true, 'message' => 'Joined group', 'seats_remaining' => $seatsRemaining]);
  exit;

} catch (Exception $e) {
  http_response_code(500);
  error_log('join_group error: ' . $e->getMessage());
  echo json_encode(['error' => 'Server error']);
  exit;
}
