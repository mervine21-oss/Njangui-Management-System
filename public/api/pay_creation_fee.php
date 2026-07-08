<?php
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
  http_response_code(401); echo json_encode(['error'=>'Unauthorized']); exit;
}
$userId = (int)$_SESSION['user_id'];
$groupId = (int)($_POST['group_id'] ?? 0);
if (!$groupId) { http_response_code(400); echo json_encode(['error'=>'Missing group_id']); exit; }

// simple fee amount (could be configurable)
$fee = 5000.00;
try {
  // verify group exists and is owned by user and pending
  $g = $pdo->prepare('SELECT id, admin_user_id, status, creation_paid FROM tontine_groups WHERE id = ? LIMIT 1');
  $g->execute([$groupId]);
  $group = $g->fetch();
  if (!$group) { http_response_code(404); echo json_encode(['error'=>'Group not found']); exit; }
  if ($group['admin_user_id'] != $userId) { http_response_code(403); echo json_encode(['error'=>'Not group owner']); exit; }
  if ($group['creation_paid']) { http_response_code(400); echo json_encode(['error'=>'Creation fee already paid']); exit; }

  // simulate payment: insert transaction
  $gatewayRef = 'CREATION_' . time() . '_' . bin2hex(random_bytes(4));
  $tx = $pdo->prepare('INSERT INTO transactions (gateway_ref, network, msisdn, user_id, group_id, amount, status, metadata) VALUES (?,?,?,?,?,?,"success",NULL)');
  $tx->execute([$gatewayRef, 'OTHER', '', $userId, $groupId, number_format($fee,2,'.','')]);

  // mark group as creation_paid
  $u = $pdo->prepare('UPDATE tontine_groups SET creation_paid = 1 WHERE id = ?');
  $u->execute([$groupId]);

  // If request came from a browser form (not fetch), redirect back with flash
  if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') === false) {
    $_SESSION['flash'] = 'Payment received. Your group is active.';
    header('Location: ../group-details.php?id=' . $groupId);
    exit;
  }

  echo json_encode(['ok'=>true,'message'=>'Payment received. Your group is active.']);
} catch (PDOException $e) {
  http_response_code(500); echo json_encode(['error'=>'DB error','detail'=>$e->getMessage()]);
}
