<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'message' => 'You must be logged in']);
  exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$inviteCode = trim($data['invite_code'] ?? '');

if (!$inviteCode) {
  echo json_encode(['ok' => false, 'message' => 'Invitation code is required']);
  exit;
}

try {
  // Find the invite
  $inviteStmt = $pdo->prepare("
    SELECT * FROM invites WHERE token = ?
  ");
  $inviteStmt->execute([$inviteCode]);
  $invite = $inviteStmt->fetch();

  if (!$invite) {
    echo json_encode(['ok' => false, 'message' => 'Invalid invitation code']);
    exit;
  }

  // Check if expired
  if ($invite['expires_at'] && strtotime($invite['expires_at']) < time()) {
    echo json_encode(['ok' => false, 'message' => 'Invitation code has expired']);
    exit;
  }

  $groupId = $invite['group_id'];
  $userId = $_SESSION['user_id'];

  // Check if user already member
  $memberCheck = $pdo->prepare("
    SELECT id FROM group_members WHERE group_id = ? AND user_id = ?
  ");
  $memberCheck->execute([$groupId, $userId]);
  if ($memberCheck->fetch()) {
    echo json_encode(['ok' => false, 'message' => 'You are already a member of this group']);
    exit;
  }

  // Check group capacity
  $groupStmt = $pdo->prepare("SELECT capacity FROM tontine_groups WHERE id = ?");
  $groupStmt->execute([$groupId]);
  $group = $groupStmt->fetch();

  if (!$group) {
    echo json_encode(['ok' => false, 'message' => 'Group not found']);
    exit;
  }

  // Count active members
  $memberCount = $pdo->prepare("
    SELECT COUNT(*) as cnt FROM group_members WHERE group_id = ? AND status = 'active'
  ");
  $memberCount->execute([$groupId]);
  $count = $memberCount->fetch();
  
  if ($count['cnt'] >= $group['capacity']) {
    echo json_encode(['ok' => false, 'message' => 'Group is full']);
    exit;
  }

  // Add member to group
  $pdo->beginTransaction();
  
  $insertMember = $pdo->prepare("
    INSERT INTO group_members (group_id, user_id, is_admin, status, is_new_member)
    VALUES (?, ?, 0, 'active', 1)
  ");
  $insertMember->execute([$groupId, $userId]);

  // Create wallet
  $insertWallet = $pdo->prepare("
    INSERT INTO wallets (group_id, user_id, rotational_balance, savings_balance)
    VALUES (?, ?, 0.00, 0.00)
  ");
  $insertWallet->execute([$groupId, $userId]);

  $pdo->commit();

  $seatsRemaining = $group['capacity'] - $count['cnt'] - 1;

  echo json_encode([
    'ok' => true,
    'message' => 'Successfully joined the group!',
    'seats_remaining' => $seatsRemaining
  ]);

} catch (PDOException $e) {
  $pdo->rollBack();
  error_log('Join by invite error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Database error']);
}
