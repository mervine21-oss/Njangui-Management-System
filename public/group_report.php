<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/pdf_helper.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$userId = $_SESSION['user_id'] ?? null;
$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;

if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
  if (!$userId || !$groupId) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unauthorized: please log in to download the PDF.';
    exit;
  }
}

require_once __DIR__ . '/../includes/auth-check.php';

if (!$userId || !$groupId) {
  header('Location: dashboard.php');
  exit;
}

$roleStmt = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
$roleStmt->execute([$userId]);
$userRole = $roleStmt->fetchColumn();

$check = $pdo->prepare('SELECT 1 FROM group_members gm WHERE gm.group_id = ? AND gm.user_id = ? AND gm.status = "active" LIMIT 1');
$check->execute([$groupId, $userId]);
$member = $check->fetch();
if (!$member) {
  if ($userRole !== 'super_admin') {
    if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
      header('HTTP/1.1 403 Forbidden');
      header('Content-Type: text/plain; charset=utf-8');
      echo 'Forbidden: you do not have permission to download this PDF.';
      exit;
    }
    echo '<div class="container py-5"><div class="alert alert-danger">Access denied.</div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
  }
}

$groupStmt = $pdo->prepare('SELECT * FROM tontine_groups WHERE id = ? LIMIT 1');
$groupStmt->execute([$groupId]);
$group = $groupStmt->fetch();
if (!$group) {
  if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
    header('HTTP/1.1 404 Not Found');
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Group not found.';
    exit;
  }
  header('Location: dashboard.php');
  exit;
}

$summaryStmt = $pdo->prepare('SELECT COUNT(*) as total_payments, SUM(amount) as total_collected, COUNT(CASE WHEN status = "success" THEN 1 END) as success_count, COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_count, COUNT(CASE WHEN status = "pending" THEN 1 END) as pending_count FROM transactions WHERE group_id = ?');
$summaryStmt->execute([$groupId]);
$summary = $summaryStmt->fetch();

$paymentsStmt = $pdo->prepare('SELECT t.*, u.first_name, u.last_name FROM transactions t LEFT JOIN users u ON u.id = t.user_id WHERE t.group_id = ? ORDER BY t.created_at DESC LIMIT 200');
$paymentsStmt->execute([$groupId]);
$payments = $paymentsStmt->fetchAll();

if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
  $lines = [
    'DigiTon Group Report',
    'Group: ' . $group['name'],
    'Generated: ' . date('d/m/Y H:i'),
    'Total Payments: ' . ($summary['total_payments'] ?? 0),
    'Success: ' . ($summary['success_count'] ?? 0),
    'Pending: ' . ($summary['pending_count'] ?? 0),
    'Failed: ' . ($summary['failed_count'] ?? 0),
    'Total Collected: ' . number_format((float)($summary['total_collected'] ?? 0), 2) . ' XAF',
    '---- Payment Details ----',
  ];
  foreach ($payments as $tx) {
    $lines[] = sprintf('%s | %s %s | %s XAF | %s | %s', date('d/m/Y', strtotime($tx['created_at'])), $tx['first_name'], $tx['last_name'], number_format($tx['amount'],2), $tx['status'], $tx['gateway_ref']);
  }
  pdf_send('group_report_' . $groupId . '.pdf', $lines);
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3>Group Report — <?php echo htmlspecialchars($group['name'], ENT_QUOTES); ?></h3>
      <p class="text-muted">Summary of contributions and transaction activity for this group.</p>
    </div>
    <a href="group_report.php?group_id=<?php echo $groupId; ?>&download=pdf" class="btn btn-cta">Download PDF</a>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card p-3 shadow-sm">
        <h6 class="text-muted">Total Payments</h6>
        <h2><?php echo (int)($summary['total_payments'] ?? 0); ?></h2>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 shadow-sm">
        <h6 class="text-muted">Collected</h6>
        <h2><?php echo number_format((float)($summary['total_collected'] ?? 0),2); ?> XAF</h2>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card p-3 shadow-sm">
        <h6 class="text-muted">Success</h6>
        <h2><?php echo (int)($summary['success_count'] ?? 0); ?></h2>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card p-3 shadow-sm">
        <h6 class="text-muted">Pending</h6>
        <h2><?php echo (int)($summary['pending_count'] ?? 0); ?></h2>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card p-3 shadow-sm">
        <h6 class="text-muted">Failed</h6>
        <h2><?php echo (int)($summary['failed_count'] ?? 0); ?></h2>
      </div>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover">
      <thead class="table-light">
        <tr>
          <th>Date</th>
          <th>User</th>
          <th>Amount</th>
          <th>Status</th>
          <th>Network</th>
          <th>Ref</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($payments as $tx): ?>
          <tr>
            <td><?php echo date('d/m/Y H:i', strtotime($tx['created_at'])); ?></td>
            <td><?php echo htmlspecialchars($tx['first_name'] . ' ' . $tx['last_name'], ENT_QUOTES); ?></td>
            <td><?php echo number_format($tx['amount'],2); ?> XAF</td>
            <td><?php echo htmlspecialchars($tx['status'], ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars($tx['network'], ENT_QUOTES); ?></td>
            <td><?php echo htmlspecialchars(substr($tx['gateway_ref'],0,10), ENT_QUOTES); ?>...</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <p class="mt-4"><a href="wallet.php?group_id=<?php echo $groupId; ?>">← Back to wallet</a></p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
