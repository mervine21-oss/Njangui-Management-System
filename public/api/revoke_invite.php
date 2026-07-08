<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])){
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'Not authenticated']);
  exit;
}
$userId = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$inviteId = isset($input['invite_id']) ? (int)$input['invite_id'] : 0;
if (!$inviteId){ http_response_code(400); echo json_encode(['ok'=>false,'message'=>'invite_id required']); exit; }

try{
  // Verify invite exists and belongs to a group where user is admin
  $stmt = $pdo->prepare("SELECT i.id, i.group_id FROM invites i WHERE i.id = ? LIMIT 1");
  $stmt->execute([$inviteId]);
  $inv = $stmt->fetch();
  if (!$inv){ http_response_code(404); echo json_encode(['ok'=>false,'message'=>'Invite not found']); exit; }

  $checkAdmin = $pdo->prepare("SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ? AND is_admin = 1 AND status = 'active' LIMIT 1");
  $checkAdmin->execute([$inv['group_id'], $userId]);
  if (!$checkAdmin->fetch()){ http_response_code(403); echo json_encode(['ok'=>false,'message'=>'Not an admin of this group']); exit; }

  // Revoke by setting expires_at to now
  $upd = $pdo->prepare("UPDATE invites SET expires_at = NOW() WHERE id = ?");
  $upd->execute([$inviteId]);

  echo json_encode(['ok'=>true,'message'=>'Invite revoked']);
} catch(PDOException $e){ error_log('revoke_invite error: '.$e->getMessage()); http_response_code(500); echo json_encode(['ok'=>false,'message'=>'Server error']); }
