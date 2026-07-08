<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mailer.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'message' => 'You must be logged in']);
  exit;
}

$userId = $_SESSION['user_id'];
$groupId = (int)($_POST['group_id'] ?? 0);
$sendEmail = trim($_POST['send_email'] ?? '');
$expiresDays = (int)($_POST['expires_days'] ?? 7);

if (!$groupId) {
  echo json_encode(['ok' => false, 'message' => 'Group is required']);
  exit;
}

try {
  // Verify user is admin of this group
  $adminCheck = $pdo->prepare("
    SELECT tg.id, tg.name FROM tontine_groups tg
    JOIN group_members gm ON gm.group_id = tg.id
    WHERE tg.id = ? AND gm.user_id = ? AND gm.is_admin = 1
  ");
  $adminCheck->execute([$groupId, $userId]);
  $group = $adminCheck->fetch();

  if (!$group) {
    echo json_encode(['ok' => false, 'message' => 'You are not an admin of this group']);
    exit;
  }

  // Generate token
  $token = bin2hex(random_bytes(16));
  $expiresAt = $expiresDays > 0 ? date('Y-m-d H:i:s', strtotime("+$expiresDays days")) : null;

  // Insert invite
  $insertInvite = $pdo->prepare("
    INSERT INTO invites (group_id, token, expires_at, created_by)
    VALUES (?, ?, ?, ?)
  ");
  $insertInvite->execute([$groupId, $token, $expiresAt, $userId]);

  // Build invite link
  $inviteLink = "http://" . $_SERVER['HTTP_HOST'] . "/DigiTon/public/join.php?token=" . urlencode($token);

  // Send email if requested
  if (!empty($sendEmail) && filter_var($sendEmail, FILTER_VALIDATE_EMAIL)) {
    $subject = "You're invited to join " . htmlspecialchars($group['name']);
    $body = "
      <h2>Join " . htmlspecialchars($group['name']) . "</h2>
      <p>You've been invited to join a tontine group!</p>
      <p><strong>Invitation Code:</strong> <code>" . htmlspecialchars($token) . "</code></p>
      <p>Or click here to join: <a href='" . htmlspecialchars($inviteLink) . "'>" . htmlspecialchars($inviteLink) . "</a></p>
      <p>This invitation " . ($expiresDays > 0 ? "expires in $expiresDays days" : "never expires") . ".</p>
    ";
    
    send_invite_email($sendEmail, $subject, $body);
  }

  echo json_encode([
    'ok' => true,
    'message' => 'Invitation code generated' . (!empty($sendEmail) ? ' and sent to ' . htmlspecialchars($sendEmail) : ''),
    'code' => $token,
    'link' => $inviteLink,
    'expires' => $expiresAt ? date('d/m/Y', strtotime($expiresAt)) : 'Never'
  ]);

} catch (PDOException $e) {
  error_log('Send invite error: ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Database error']);
}
