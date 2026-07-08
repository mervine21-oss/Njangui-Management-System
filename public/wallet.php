<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$userId = $_SESSION['user_id'];
$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;

// If group specified, show only that group's wallet
if ($groupId) {
  // Check membership
  $memStmt = $pdo->prepare('SELECT 1 FROM group_members WHERE group_id = ? AND user_id = ?');
  $memStmt->execute([$groupId, $userId]);
  if (!$memStmt->fetch()) {
    echo '<div class="container py-5"><div class="alert alert-danger">Access denied.</div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
  }

  $groupStmt = $pdo->prepare('SELECT name FROM tontine_groups WHERE id = ?');
  $groupStmt->execute([$groupId]);
  $group = $groupStmt->fetch();

  $walletStmt = $pdo->prepare('SELECT * FROM wallets WHERE group_id = ? AND user_id = ?');
  $walletStmt->execute([$groupId, $userId]);
  $wallet = $walletStmt->fetch();

  $memberStmt = $pdo->prepare('SELECT is_admin FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1');
  $memberStmt->execute([$groupId, $userId]);
  $memberInfo = $memberStmt->fetch();
  $memberIsAdmin = $memberInfo && $memberInfo['is_admin'];

  $txStmt = $pdo->prepare('SELECT * FROM transactions WHERE user_id = ? AND group_id = ? ORDER BY created_at DESC');
  $txStmt->execute([$userId, $groupId]);
  $transactions = $txStmt->fetchAll();
?>

<div class="container py-5">
  <h3 class="mb-4">Wallet — <?php echo htmlspecialchars($group['name'], ENT_QUOTES); ?></h3>

  <!-- Dual Wallet Cards -->
  <div class="row mb-4">
    <div class="col-12 col-md-6">
      <div class="card p-4 shadow-sm" style="border: 2px solid var(--primary-purple)">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="text-muted mb-0">Rotational Round Balance</h6>
          <button type="button" class="btn btn-sm btn-outline-secondary balance-toggle-btn"><i class="bi bi-eye"></i></button>
        </div>
        <h2 class="text-primary mb-3 balance-wrapper"><span class="maskable-value" data-value="<?php echo number_format((float)($wallet['rotational_balance'] ?? 0), 2); ?>">••••••</span> XAF</h2>
        <p class="small text-muted mb-3">This balance is used for rotation payouts. Funds rotate through members each cycle.</p>
        <div class="d-grid gap-2">
          <button id="payContributionBtn" type="button" class="btn btn-primary btn-sm">Pay Next Round via MoMo</button>
          <button class="btn btn-outline-secondary btn-sm" disabled>Deposit to Rotation</button>
        </div>
        <div id="contributionForm" class="mt-4">
          <h6 class="mb-3">Contribution Payment</h6>
          <form id="contributionPaymentForm">
            <div class="row g-2 align-items-end">
              <div class="col-md-4">
                <label class="form-label">Network</label>
                <select name="network" class="form-select" required>
                  <option value="">Choose network</option>
                  <option value="MTN">MTN</option>
                  <option value="ORANGE">ORANGE</option>
                  <option value="OTHER">OTHER</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Phone Number</label>
                <input type="text" name="msisdn" class="form-control" placeholder="+237650000000" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Amount</label>
                <input type="text" name="amount" class="form-control" value="<?php echo number_format((float)$group['contribution_amount'], 2); ?>" readonly>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-success">Submit Contribution</button>
              </div>
            </div>
            <div id="contributionMessage" class="mt-3"></div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6">
      <div class="card p-4 shadow-sm" style="border: 2px solid var(--accent-amber)">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="text-muted mb-0">Standalone Savings</h6>
          <button type="button" class="btn btn-sm btn-outline-secondary balance-toggle-btn"><i class="bi bi-eye"></i></button>
        </div>
        <h2 class="mb-3" style="color: var(--accent-amber)"><span class="maskable-value" data-value="<?php echo number_format((float)($wallet['savings_balance'] ?? 0), 2); ?>">••••••</span> XAF</h2>
        <p class="small text-muted mb-3">Voluntary extra deposits. Locked until group termination, never mixed into payouts.</p>
        <div class="d-grid gap-2">
          <button class="btn btn-outline-warning btn-sm" disabled>Deposit Extra Funds</button>
          <button class="btn btn-outline-secondary btn-sm" disabled>View Terms</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Transaction History -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-3">
    <h5 class="mb-0">Transaction History</h5>
    <?php if ($memberIsAdmin): ?>
      <a href="group_report.php?group_id=<?php echo $groupId; ?>" class="btn btn-outline-primary btn-sm">View Group Report</a>
    <?php endif; ?>
  </div>
  <?php if (empty($transactions)): ?>
    <div class="alert alert-info">No transactions yet.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Network</th>
            <th>Phone</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Ref</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($transactions as $tx): ?>
            <tr>
              <td><?php echo date('d/m/Y H:i', strtotime($tx['created_at'])); ?></td>
              <td><?php echo htmlspecialchars($tx['network'], ENT_QUOTES); ?></td>
              <td><code><?php echo htmlspecialchars($tx['msisdn'], ENT_QUOTES); ?></code></td>
              <td><?php echo number_format($tx['amount'], 2); ?> XAF</td>
              <td>
                <span class="badge bg-<?php echo ($tx['status'] === 'success') ? 'success' : (($tx['status'] === 'pending') ? 'warning' : 'danger'); ?>">
                  <?php echo ucfirst($tx['status']); ?>
                </span>
              </td>
              <td>
                <small><?php echo htmlspecialchars(substr($tx['gateway_ref'], 0, 8), ENT_QUOTES); ?>...</small>
                <?php if ($tx['status'] === 'success'): ?>
                  <div class="mt-1"><a href="download_receipt.php?tx_id=<?php echo $tx['id']; ?>" class="btn btn-sm btn-outline-secondary">Receipt</a></div>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <p class="mt-3"><a href="dashboard.php">← Back to dashboard</a> | <a href="transactions.php">View all transactions</a></p>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const payBtn = document.getElementById('payContributionBtn');
  const contributionForm = document.getElementById('contributionForm');
  const contributionPaymentForm = document.getElementById('contributionPaymentForm');
  const contributionMessage = document.getElementById('contributionMessage');

  if (contributionForm) {
    contributionForm.style.display = 'none';
  }

  if (payBtn && contributionForm) {
    payBtn.addEventListener('click', function() {
      contributionForm.style.display = contributionForm.style.display === 'none' ? 'block' : 'none';
    });
  }

  if (contributionPaymentForm) {
    contributionPaymentForm.addEventListener('submit', function(event) {
      event.preventDefault();
      if (!contributionMessage) return;

      contributionMessage.textContent = '';
      contributionMessage.className = '';

      const formData = new FormData(this);
      const payload = {
        group_id: <?php echo $groupId; ?>,
        amount: formData.get('amount'),
        network: formData.get('network'),
        msisdn: formData.get('msisdn')
      };

      fetch('api/pay_contribution.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(response => response.json())
      .then(data => {
        if (data.ok) {
          contributionMessage.className = 'alert alert-success';
          contributionMessage.textContent = data.message + ' Reloading transaction list...';
          setTimeout(() => window.location.reload(), 1200);
        } else {
          contributionMessage.className = 'alert alert-danger';
          contributionMessage.textContent = data.message || 'Payment failed.';
        }
      })
      .catch(() => {
        contributionMessage.className = 'alert alert-danger';
        contributionMessage.textContent = 'Network error. Please try again.';
      });
    });
  }
});
</script>

<?php
} else {
  // Show all wallets across groups
  $groupsStmt = $pdo->prepare("
    SELECT tg.id, tg.name, gm.is_admin
    FROM tontine_groups tg
    JOIN group_members gm ON gm.group_id = tg.id
    WHERE gm.user_id = ? AND gm.status = 'active'
    ORDER BY tg.name
  ");
  $groupsStmt->execute([$userId]);
  $myGroups = $groupsStmt->fetchAll();

  // Get all wallets
  $walletsStmt = $pdo->prepare("
    SELECT w.*, tg.name, tg.contribution_amount
    FROM wallets w
    JOIN tontine_groups tg ON tg.id = w.group_id
    WHERE w.user_id = ?
    ORDER BY tg.name
  ");
  $walletsStmt->execute([$userId]);
  $wallets = $walletsStmt->fetchAll();

  $totalRotational = 0;
  $totalSavings = 0;
  foreach ($wallets as $w) {
    $totalRotational += (float)($w['rotational_balance'] ?? 0);
    $totalSavings += (float)($w['savings_balance'] ?? 0);
  }
?>

<div class="container py-5">
  <h3 class="mb-4">My Wallets</h3>

  <!-- Global Totals -->
  <div class="row mb-4">
    <div class="col-12 col-md-6">
      <div class="card p-4 shadow-sm" style="border-left: 5px solid var(--primary-purple)">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="text-muted mb-0">Total Rotational</h6>
          <button type="button" class="btn btn-sm btn-outline-secondary balance-toggle-btn"><i class="bi bi-eye"></i></button>
        </div>
        <h2 class="text-primary balance-wrapper"><span class="maskable-value" data-value="<?php echo number_format($totalRotational, 2); ?>">••••••</span> XAF</h2>
        <small class="text-muted">Across all groups</small>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="card p-4 shadow-sm" style="border-left: 5px solid var(--accent-amber)">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <h6 class="text-muted mb-0">Total Savings</h6>
          <button type="button" class="btn btn-sm btn-outline-secondary balance-toggle-btn"><i class="bi bi-eye"></i></button>
        </div>
        <h2 style="color: var(--accent-amber)"><span class="maskable-value" data-value="<?php echo number_format($totalSavings, 2); ?>">••••••</span> XAF</h2>
        <small class="text-muted">Across all groups</small>
      </div>
    </div>
  </div>

  <!-- List of Group Wallets -->
  <h5 class="mb-3">Group Wallets</h5>
  <?php if (empty($wallets)): ?>
    <div class="alert alert-info">You don't have any wallets yet.</div>
  <?php else: ?>
    <div class="list-group">
      <?php foreach ($wallets as $w): ?>
        <a href="wallet.php?group_id=<?php echo $w['id']; ?>" class="list-group-item list-group-item-action">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="mb-1"><?php echo htmlspecialchars($w['name'], ENT_QUOTES); ?></h6>
              <small class="text-muted">Contribution: <?php echo number_format($w['contribution_amount'], 2); ?> XAF</small>
            </div>
            <div class="text-end">
              <div class="text-primary fw-bold"><?php echo number_format((float)($w['rotational_balance'] ?? 0), 2); ?> XAF</div>
              <small class="text-muted">Rotational</small>
              <br>
              <div style="color: var(--accent-amber); font-weight: bold;"><?php echo number_format((float)($w['savings_balance'] ?? 0), 2); ?> XAF</div>
              <small class="text-muted">Savings</small>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <p class="mt-4"><a href="dashboard.php">← Back to dashboard</a></p>
</div>

<?php
}
require_once __DIR__ . '/../includes/footer.php';
?>
