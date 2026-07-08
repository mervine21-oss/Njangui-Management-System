<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

// Get user's groups
$groupStmt = $pdo->prepare("
  SELECT tg.*, gm.is_admin, (SELECT COUNT(*) FROM group_members WHERE group_id = tg.id AND status = 'active') as member_count
  FROM tontine_groups tg
  JOIN group_members gm ON gm.group_id = tg.id
  WHERE gm.user_id = ? AND gm.status = 'active'
  ORDER BY tg.created_at DESC
");
$groupStmt->execute([$userId]);
$groups = $groupStmt->fetchAll();

// Get total wallets balance
$walletStmt = $pdo->prepare("
  SELECT SUM(rotational_balance) as total_rotational, SUM(savings_balance) as total_savings
  FROM wallets
  WHERE user_id = ?
");
$walletStmt->execute([$userId]);
$totals = $walletStmt->fetch();
$totalRotational = (float)($totals['total_rotational'] ?? 0);
$totalSavings = (float)($totals['total_savings'] ?? 0);

// Get recent transactions
$txStmt = $pdo->prepare("
  SELECT * FROM transactions
  WHERE user_id = ?
  ORDER BY created_at DESC
  LIMIT 5
");
$txStmt->execute([$userId]);
$recentTx = $txStmt->fetchAll();
?>

<div class="container py-5 dashboard-hero">
  <div class="row align-items-center mb-4">
    <div class="col-12 col-lg-8">
      <div class="p-4 bg-white rounded-3 shadow-sm">
        <div class="d-flex align-items-center gap-3">
          <div>
            <h2 class="dashboard-title mb-1">Welcome back, <?php echo htmlspecialchars($userName, ENT_QUOTES); ?> 👋</h2>
            <p class="dashboard-sub mb-0">Manage your tontine groups, wallets and contributions</p>
          </div>
          <div class="ms-auto d-none d-md-block">
            <a href="create_group.php" class="btn btn-cta me-2">+ Create Group</a>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#joinGroupModal">+ Join Group</button>
          </div>
        </div>

        <!-- Search removed as requested -->
      </div>
    </div>
    <div class="col-12 col-lg-4 mt-3 mt-lg-0 d-lg-none">
      <div class="d-flex gap-2 justify-content-end">
        <a href="create_group.php" class="btn btn-cta">+ Create Group</a>
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#joinGroupModal">+ Join Group</button>
      </div>
    </div>
  </div>

  <!-- Wallet Summary -->
  <div class="row mb-4">
    <div class="col-12 col-md-6">
      <div class="stat-card animated-card">
        <div class="icon-circle"><i class="bi bi-arrow-repeat" style="font-size:1.1rem"></i></div>
        <div class="stat-content">
          <div class="d-flex align-items-center justify-content-between">
            <div class="stat-label">Rotational Balance</div>
            <button type="button" class="btn btn-sm btn-outline-secondary balance-toggle-btn"><i class="bi bi-eye"></i></button>
          </div>
          <div class="stat-value"><span class="maskable-value" data-value="<?php echo (float)$totalRotational; ?>">••••••</span> XAF</div>
          <small class="text-muted">Across all groups</small>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-6">
      <div class="stat-card animated-card">
        <div class="icon-circle" style="background:linear-gradient(135deg,var(--accent-amber),#f7b733)"><i class="bi bi-piggy-bank" style="font-size:1.1rem"></i></div>
        <div class="stat-content">
          <div class="d-flex align-items-center justify-content-between">
            <div class="stat-label">Savings Balance</div>
            <button type="button" class="btn btn-sm btn-outline-secondary balance-toggle-btn"><i class="bi bi-eye"></i></button>
          </div>
          <div class="stat-value" style="color:var(--accent-amber)"><span class="maskable-value" data-value="<?php echo (float)$totalSavings; ?>">••••••</span> XAF</div>
          <small class="text-muted">Locked until group termination</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Groups Section -->
  <h4 class="mb-3">Your Groups</h4>
  <?php if (empty($groups)): ?>
    <div class="alert alert-info">
      You're not part of any groups yet. <a href="create_group.php">Create one</a> or wait for an invitation.
    </div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($groups as $group): 
        $seatsUsed = (int)$group['member_count'];
        $seatsRemaining = max(0, (int)$group['capacity'] - $seatsUsed);
        
        // Get wallet for this group
        $wStmt = $pdo->prepare('SELECT rotational_balance, savings_balance FROM wallets WHERE group_id = ? AND user_id = ?');
        $wStmt->execute([$group['id'], $userId]);
        $wallet = $wStmt->fetch();
        $wRotational = (float)($wallet['rotational_balance'] ?? 0);
        $wSavings = (float)($wallet['savings_balance'] ?? 0);
      ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card shadow-sm h-100 group-card animated-card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h5 class="card-title"><?php echo htmlspecialchars($group['name'], ENT_QUOTES); ?></h5>
                  <p class="card-text text-muted small group-meta">
                    <?php echo ucfirst($group['cycle_interval']); ?> • <?php echo htmlspecialchars($seatsUsed . '/' . $group['capacity'] . ' members', ENT_QUOTES); ?>
                  </p>
                </div>
                <?php if ($group['is_admin']): ?>
                  <span class="badge bg-warning text-dark">Admin</span>
                <?php endif; ?>
              </div>
              <div class="row mb-3">
                <div class="col-6">
                  <small class="text-muted">Contribution</small>
                  <div class="fw-bold"><?php echo number_format($group['contribution_amount'], 2); ?> XAF</div>
                </div>
                <div class="col-6">
                  <small class="text-muted">Collateral</small>
                  <div class="fw-bold"><?php echo number_format($group['collateral_reserve_percent'], 2); ?>%</div>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-6">
                  <small class="text-muted">My Rotational</small>
                  <div class="text-primary"><span class="counter" data-target="<?php echo (float)$wRotational; ?>">0</span> XAF</div>
                </div>
                <div class="col-6">
                  <small class="text-muted">My Savings</small>
                  <div style="color: var(--accent-amber);"><span class="counter" data-target="<?php echo (float)$wSavings; ?>">0</span> XAF</div>
                </div>
              </div>
              <div class="d-flex gap-2 mt-2">
                <button class="btn btn-sm btn-outline-primary view-group-btn" data-id="<?php echo $group['id']; ?>">View Details</button>
                <a href="wallet.php?group_id=<?php echo $group['id']; ?>" class="btn btn-sm btn-outline-secondary">Manage</a>
                <button type="button" class="btn btn-sm btn-success pay-btn" data-group-id="<?php echo $group['id']; ?>" data-amount="<?php echo (float)$group['contribution_amount']; ?>">Pay</button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Recent Transactions -->
  <h4 class="mt-5 mb-3">Recent Transactions</h4>
  <?php if (empty($recentTx)): ?>
    <p class="text-muted">No transactions yet.</p>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover">
        <thead class="table-light">
          <tr>
            <th>Date</th>
            <th>Network</th>
            <th>Amount</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentTx as $tx): ?>
            <tr>
              <td><small><?php echo date('d/m/Y H:i', strtotime($tx['created_at'])); ?></small></td>
              <td><?php echo htmlspecialchars($tx['network'], ENT_QUOTES); ?></td>
              <td><?php echo number_format($tx['amount'], 2); ?> XAF</td>
              <td>
                <span class="badge bg-<?php echo ($tx['status'] === 'success') ? 'success' : (($tx['status'] === 'pending') ? 'warning' : 'danger'); ?>">
                  <?php echo ucfirst($tx['status']); ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="text-center"><a href="wallet.php">View all transactions →</a></p>
  <?php endif; ?>
</div>

<!-- Modal: Join Group -->
<div class="modal fade" id="joinGroupModal" tabindex="-1" aria-labelledby="joinGroupLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title" id="joinGroupLabel">Join a Group</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-3">
        <p class="text-muted mb-4">Enter the invitation code to join an existing group</p>
        
        <form id="joinGroupForm" method="post" action="">
          <div class="form-group mb-3">
            <label class="form-label">Invitation Code</label>
            <input type="text" name="invite_code" class="form-control" id="inviteCodeInput" placeholder="Enter 32-character code" required>
            <small class="form-text text-muted">You can get this code from your group admin</small>
          </div>

          <button type="submit" class="btn btn-cta w-100 mb-2">Join Group</button>
        </form>

        <div class="d-grid">
          <a href="invites.php" class="btn btn-sm btn-outline-secondary" style="display: none;" id="sendCodeLink">
            Send invite code to others (Admins only)
          </a>
        </div>

        <hr class="my-3">
        
        <div class="alert alert-info alert-sm mb-0">
          <strong>Don't have a code?</strong> Ask your group admin to send you an invitation code.
        </div>
      </div>
      <div class="modal-footer border-top-0">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Send Invite Code (for admins) -->
<div class="modal fade" id="sendInviteModal" tabindex="-1" aria-labelledby="sendInviteLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title" id="sendInviteLabel">Send Invitation Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body pt-3">
        <p class="text-muted mb-4">Generate and send an invitation code to add new members to your group</p>
        
        <form id="sendInviteForm" method="post">
          <div class="form-group mb-3">
            <label class="form-label">Select Group</label>
            <select name="group_id" class="form-select" id="adminGroupSelect" required>
              <option value="">Choose a group you admin...</option>
            </select>
          </div>

          <div class="form-group mb-3">
            <label class="form-label">Send to Email (Optional)</label>
            <input type="email" name="send_email" class="form-control" placeholder="recipient@example.com">
            <small class="form-text text-muted">Leave empty to copy code manually</small>
          </div>

          <div class="form-group mb-3">
            <label class="form-label">Code Expiration (days)</label>
            <input type="number" name="expires_days" class="form-control" value="7" min="0" max="365">
            <small class="form-text text-muted">0 = never expires</small>
          </div>

          <button type="submit" class="btn btn-cta w-100">Generate & Send Code</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Group Details (AJAX) -->
<div class="modal fade" id="groupDetailsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header">
        <h5 class="modal-title">Group Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="groupDetailsModalBody">
        <div class="text-center py-4">Loading…</div>
      </div>
    </div>
  </div>
</div>

    <!-- Modal: Pay Contribution -->
    <div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header">
            <h5 class="modal-title" id="payModalLabel">Pay Contribution</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="payForm">
              <input type="hidden" name="group_id" id="payGroupId">
              <div class="mb-3">
                <label class="form-label">Amount (XAF)</label>
                <input type="text" id="payAmount" class="form-control" readonly>
              </div>
              <div class="mb-3">
                <label class="form-label">Network</label>
                <select name="network" id="payNetwork" class="form-select" required>
                  <option value="mtn">MTN Mobile Money</option>
                  <option value="orange">Orange Money</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Phone number (MSISDN)</label>
                <input type="text" name="msisdn" id="payMsisdn" class="form-control" placeholder="e.g., 2376XXXXXXXX" required>
              </div>
              <div class="mb-3 text-muted small">Payments are simulated in this demo. Successful payments will update your rotational wallet.</div>
              <button type="submit" class="btn btn-cta w-100">Pay Now</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script>
// Show admin option if user is admin of any group
document.addEventListener('DOMContentLoaded', function() {
  const adminGroupSelect = document.getElementById('adminGroupSelect');
  const sendCodeLink = document.getElementById('sendCodeLink');
  
  // Check if there are admin groups
  const adminGroupsData = <?php 
    // Get groups where user is admin
    $adminStmt = $pdo->prepare("
      SELECT tg.id, tg.name FROM tontine_groups tg
      JOIN group_members gm ON gm.group_id = tg.id
      WHERE gm.user_id = ? AND gm.is_admin = 1 AND gm.status = 'active'
    ");
    $adminStmt->execute([$userId]);
    $adminGroups = $adminStmt->fetchAll();
    echo json_encode($adminGroups);
  ?>;
  
  if (adminGroupsData.length > 0) {
    sendCodeLink.style.display = 'block';
    adminGroupsData.forEach(group => {
      const option = document.createElement('option');
      option.value = group.id;
      option.textContent = group.name;
      adminGroupSelect.appendChild(option);
    });
  }
  
  // Handle Send Invite Form
  document.getElementById('sendInviteForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('api/send_invite.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.ok) {
        alert('✅ ' + data.message + '\n\nCode: ' + data.code);
        const sendInviteModal = bootstrap.Modal.getInstance(document.getElementById('sendInviteModal'));
        sendInviteModal.hide();
        document.getElementById('sendInviteForm').reset();
      } else {
        alert('❌ Error: ' + data.message);
      }
    })
    .catch(err => {
      console.error('Error:', err);
      alert('❌ An error occurred while generating the code.');
    });
  });

  // Handle Join Group Form
  document.getElementById('joinGroupForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const inviteCode = document.getElementById('inviteCodeInput').value.trim();
    
    if (!inviteCode) {
      alert('Please enter an invitation code');
      return;
    }
    
    fetch('api/join_by_invite.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ invite_code: inviteCode })
    })
    .then(response => response.json())
    .then(data => {
      if (data.ok) {
        alert('✅ ' + data.message);
        location.reload();
      } else {
        alert('❌ ' + data.message);
        document.getElementById('inviteCodeInput').value = '';
      }
    })
    .catch(err => {
      console.error('Error:', err);
      alert('❌ An error occurred. Please try again.');
    });
  });
  
  // Link to send code (for admins)
  sendCodeLink.addEventListener('click', function(e) {
    e.preventDefault();
    const joinModal = bootstrap.Modal.getInstance(document.getElementById('joinGroupModal'));
    joinModal.hide();
    const sendModal = new bootstrap.Modal(document.getElementById('sendInviteModal'));
    sendModal.show();
  });

  // Entrance animations: staggered reveal
  const animated = Array.from(document.querySelectorAll('.animated-card'));
  animated.forEach((el, idx) => {
    setTimeout(() => el.classList.add('in'), 120 + idx * 80);
  });

  // reveal title and subtitle
  setTimeout(() => {
    const title = document.querySelector('.dashboard-title');
    const sub = document.querySelector('.dashboard-sub');
    if (title) title.style.opacity = 1, title.style.transform = 'translateY(0)';
    if (sub) sub.style.opacity = 1, sub.style.transform = 'translateY(0)';
  }, 180);

  // Numeric counters animation
  function animateCounter(el, target) {
    target = Number(target);
    const duration = 900;
    const start = 0;
    const startTime = performance.now();
    function tick(now) {
      const progress = Math.min((now - startTime) / duration, 1);
      const value = Math.floor(progress * (target - start) + start);
      el.textContent = value.toLocaleString('en-US', {minimumFractionDigits: 0, maximumFractionDigits: 2});
      if (progress < 1) requestAnimationFrame(tick);
    }
    requestAnimationFrame(tick);
  }

  document.querySelectorAll('.counter').forEach((el, idx) => {
    const target = el.getAttribute('data-target') || 0;
    setTimeout(() => animateCounter(el, target), 300 + idx * 120);
  });

  // Pay button handling: populate modal
  document.querySelectorAll('.pay-btn').forEach(btn => {
    btn.addEventListener('click', function(){
      const groupId = this.getAttribute('data-group-id');
      const amount = this.getAttribute('data-amount');
      document.getElementById('payGroupId').value = groupId;
      document.getElementById('payAmount').value = Number(amount).toFixed(2);
      const payModal = new bootstrap.Modal(document.getElementById('payModal'));
      payModal.show();
    });
  });

  // Handle pay form submission
  document.getElementById('payForm').addEventListener('submit', function(e){
    e.preventDefault();
    const group_id = document.getElementById('payGroupId').value;
    const amount = document.getElementById('payAmount').value;
    const network = document.getElementById('payNetwork').value;
    const msisdn = document.getElementById('payMsisdn').value.trim();
    if (!msisdn) { alert('Enter phone number'); return; }

    const payload = { group_id: parseInt(group_id), amount: parseFloat(amount), network, msisdn };

    fetch('api/pay_contribution.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(payload)
    })
    .then(r=>r.json())
    .then(data => {
      if (data.ok) {
        alert('✅ ' + data.message);
        location.reload();
      } else {
        alert('❌ ' + data.message);
      }
    })
    .catch(err => { console.error(err); alert('Request failed'); });
  });

  // View group in modal via AJAX
  document.querySelectorAll('.view-group-btn').forEach(btn => {
    btn.addEventListener('click', function(){
      const id = this.getAttribute('data-id');
      const modalEl = document.getElementById('groupDetailsModal');
      const modalBody = document.getElementById('groupDetailsModalBody');
      modalBody.innerHTML = '<div class="text-center py-4">Loading…</div>';
      fetch('partials/group-details-modal.php?id=' + encodeURIComponent(id))
        .then(r => r.text())
        .then(html => {
          modalBody.innerHTML = html;

          // copy invite
          const copyBtn = modalBody.querySelector('.copy-invite');
          if (copyBtn) {
            copyBtn.addEventListener('click', function(){
              const txt = this.getAttribute('data-text') || '';
              if (navigator.clipboard) navigator.clipboard.writeText(txt);
              alert('Copied to clipboard');
            });
          }


          // join button inside modal (delegated)
          const joinBtn = modalBody.querySelector('#joinBtnModal');
          if (joinBtn) {
            joinBtn.addEventListener('click', function(){
              const gid = this.getAttribute('data-group-id');
              const code = prompt('Enter invitation code to join this group');
              if (!code) return;
              fetch('api/join_by_invite.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({invite_code: code})})
                .then(r=>r.json()).then(j=>{ if (j.ok) { alert(j.message); location.reload(); } else alert(j.message || 'Failed'); });
            });
          }

          // show modal
          const modal = new bootstrap.Modal(modalEl);
          modal.show();
        })
        .catch(err => { modalBody.innerHTML = '<div class="p-4 text-danger">Failed to load details.</div>'; console.error(err); });
    });
  });

});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
