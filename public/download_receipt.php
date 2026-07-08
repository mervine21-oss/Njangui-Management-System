<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/pdf_helper.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$userId = $_SESSION['user_id'] ?? null;
$txId = isset($_GET['tx_id']) ? (int)$_GET['tx_id'] : 0;

if (!$userId || !$txId) {
  header('HTTP/1.1 401 Unauthorized');
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Unauthorized: please log in to download the receipt.';
  exit;
}

$stmt = $pdo->prepare('SELECT t.*, tg.name as group_name FROM transactions t LEFT JOIN tontine_groups tg ON tg.id = t.group_id WHERE t.id = ? AND t.user_id = ? LIMIT 1');
$stmt->execute([$txId, $userId]);
$tx = $stmt->fetch();
if (!$tx || $tx['status'] !== 'success') {
  header('HTTP/1.1 404 Not Found');
  header('Content-Type: text/plain; charset=utf-8');
  echo 'Receipt not found.';
  exit;
}

$lines = [
  'DigiTon Payment Receipt',
  '-----------------------',
  'Receipt ID: ' . $tx['id'],
  'Gateway Ref: ' . $tx['gateway_ref'],
  'Date: ' . date('d/m/Y H:i', strtotime($tx['created_at'])),
  'User ID: ' . $tx['user_id'],
  'Group: ' . ($tx['group_name'] ?? 'N/A'),
  'Network: ' . $tx['network'],
  'Phone: ' . $tx['msisdn'],
  'Amount: ' . number_format($tx['amount'], 2) . ' XAF',
  'Status: ' . ucfirst($tx['status']),
  'Thank you for your contribution.',
];

pdf_send('receipt_' . $tx['id'] . '.pdf', $lines);
