<?php
require_once __DIR__ . '/../../config/database.php';
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])){
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'Authentication required']);
  exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$groupId = isset($input['group_id']) ? (int)$input['group_id'] : 0;
$amount = isset($input['amount']) ? (float)$input['amount'] : 0.0;
$network = isset($input['network']) ? trim($input['network']) : '';
$msisdn = isset($input['msisdn']) ? trim($input['msisdn']) : '';
$userId = $_SESSION['user_id'];

if (!$groupId || $amount <= 0 || !$network || !$msisdn) {
  http_response_code(400);
  echo json_encode(['ok'=>false,'message'=>'Missing or invalid parameters']);
  exit;
}

try {
  // Verify user is a member of this group
  $mstmt = $pdo->prepare('SELECT * FROM group_members WHERE group_id = ? AND user_id = ? AND status = "active" LIMIT 1');
  $mstmt->execute([$groupId, $userId]);
  $member = $mstmt->fetch();
  if (!$member) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'message'=>'You must be a member of the group to pay contributions']);
    exit;
  }

  // Verify amount matches group's contribution amount
  $gstmt = $pdo->prepare('SELECT contribution_amount FROM tontine_groups WHERE id = ? LIMIT 1');
  $gstmt->execute([$groupId]);
  $group = $gstmt->fetch();
  if (!$group) { http_response_code(404); echo json_encode(['ok'=>false,'message'=>'Group not found']); exit; }
  $expected = (float)$group['contribution_amount'];
  if (abs($expected - $amount) > 0.01) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'message'=>"Contribution amount must be {$expected} XAF"]);
    exit;
  }

  // Begin transaction
  $pdo->beginTransaction();

  // Create transaction record (pending)
  $gatewayRef = bin2hex(random_bytes(10));
  $status = 'pending';
  $meta = json_encode(['note'=>'Simulated payment']);
  $ins = $pdo->prepare('INSERT INTO transactions (gateway_ref, network, msisdn, user_id, group_id, amount, status, metadata) VALUES (?,?,?,?,?,?,?,?)');
  $ins->execute([$gatewayRef, $network, $msisdn, $userId, $groupId, number_format($amount,2,'.',''), $status, $meta]);
  $txId = $pdo->lastInsertId();

  // Simulate payment processing (replace with real gateway integration)
  // For demo, we mark success for supported networks
  $supported = ['mtn','orange'];
  $success = in_array(strtolower($network), $supported);

  if ($success) {
    // Update transaction to success
    $upd = $pdo->prepare('UPDATE transactions SET status = ? WHERE id = ?');
    $upd->execute(['success', $txId]);

    // Credit wallet rotational_balance
    $wstmt = $pdo->prepare('SELECT id, rotational_balance FROM wallets WHERE group_id = ? AND user_id = ? LIMIT 1');
    $wstmt->execute([$groupId, $userId]);
    $wallet = $wstmt->fetch();
    if ($wallet) {
      $newBal = (float)$wallet['rotational_balance'] + $amount;
      $uw = $pdo->prepare('UPDATE wallets SET rotational_balance = ? WHERE id = ?');
      $uw->execute([number_format($newBal,2,'.',''), $wallet['id']]);
    } else {
      $iw = $pdo->prepare('INSERT INTO wallets (group_id, user_id, rotational_balance, savings_balance) VALUES (?,?,?,0.00)');
      $iw->execute([$groupId, $userId, number_format($amount,2,'.','')]);
    }

    $pdo->commit();
    echo json_encode(['ok'=>true,'message'=>'Payment successful and wallet updated','transaction_id'=>$txId,'gateway_ref'=>$gatewayRef]);
    exit;
  } else {
    // mark failed
    $upd = $pdo->prepare('UPDATE transactions SET status = ? WHERE id = ?');
    $upd->execute(['failed', $txId]);
    $pdo->commit();
    echo json_encode(['ok'=>false,'message'=>'Payment failed - unsupported network','transaction_id'=>$txId]);
    exit;
  }

} catch (Exception $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log('pay_contribution error: '.$e->getMessage());
  http_response_code(500);
  echo json_encode(['ok'=>false,'message'=>'Server error']);
  exit;
}
