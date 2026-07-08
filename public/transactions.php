<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$userId = $_SESSION['user_id'];

// Get all transactions for user
$txStmt = $pdo->prepare("
  SELECT t.*, tg.name as group_name
  FROM transactions t
  LEFT JOIN tontine_groups tg ON tg.id = t.group_id
  WHERE t.user_id = ?
  ORDER BY t.created_at DESC
");
$txStmt->execute([$userId]);
$transactions = $txStmt->fetchAll();

// Get summary statistics
$statsStmt = $pdo->prepare("
  SELECT 
    COUNT(*) as total_tx,
    SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END) as total_success,
    COUNT(CASE WHEN status = 'success' THEN 1 END) as success_count,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count
  FROM transactions
  WHERE user_id = ?
");
$statsStmt->execute([$userId]);
$stats = $statsStmt->fetch();
?>

<div class="container py-5">
  <h3 class="mb-4">Transaction History</h3>

  <!-- Summary Cards -->
  <div class="row mb-4 g-3">
    <div class="col-6 col-md-3">
      <div class="card p-3 text-center">
        <h5 class="text-muted mb-2">Total Transactions</h5>
        <h2 class="text-primary"><?php echo (int)($stats['total_tx'] ?? 0); ?></h2>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card p-3 text-center">
        <h5 class="text-muted mb-2">Successful</h5>
        <h2 class="text-success"><?php echo (int)($stats['success_count'] ?? 0); ?></h2>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card p-3 text-center">
        <h5 class="text-muted mb-2">Pending</h5>
        <h2 class="text-warning"><?php echo (int)($stats['pending_count'] ?? 0); ?></h2>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card p-3 text-center">
        <h5 class="text-muted mb-2">Failed</h5>
        <h2 class="text-danger"><?php echo (int)($stats['failed_count'] ?? 0); ?></h2>
      </div>
    </div>
  </div>

  <?php if (empty($transactions)): ?>
    <div class="alert alert-info">
      No transactions yet. Pay contributions or make deposits to see them here.
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-light">
          <tr>
            <th>Date & Time</th>
            <th>Group</th>
            <th>Network</th>
            <th>Phone Number</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Reference</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $tx): ?>
            <tr>
              <td>
                <strong><?php echo date('d/m/Y', strtotime($tx['created_at'])); ?></strong>
                <br>
                <small class="text-muted"><?php echo date('H:i:s', strtotime($tx['created_at'])); ?></small>
              </td>
              <td><?php echo $tx['group_name'] ? htmlspecialchars($tx['group_name'], ENT_QUOTES) : '<em class="text-muted">N/A</em>'; ?></td>
              <td>
                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($tx['network'], ENT_QUOTES); ?></span>
              </td>
              <td><code><?php echo htmlspecialchars($tx['msisdn'], ENT_QUOTES); ?></code></td>
              <td class="fw-bold"><?php echo number_format($tx['amount'], 2); ?> XAF</td>
              <td>
                <span class="badge bg-<?php echo ($tx['status'] === 'success') ? 'success' : (($tx['status'] === 'pending') ? 'warning' : 'danger'); ?>">
                  <?php echo ucfirst($tx['status']); ?>
                </span>
              </td>
              <td><small><code><?php echo htmlspecialchars(substr($tx['gateway_ref'], 0, 12), ENT_QUOTES); ?></code></small></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="mt-4">
    <a href="wallet.php" class="btn btn-outline-secondary">← Back to Wallet</a>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
