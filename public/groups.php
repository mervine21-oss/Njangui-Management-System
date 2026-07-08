<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'User';

// Get user's groups
$groupStmt = $pdo->prepare("SELECT tg.*, gm.is_admin, (SELECT COUNT(*) FROM group_members WHERE group_id = tg.id AND status = 'active') as member_count FROM tontine_groups tg JOIN group_members gm ON gm.group_id = tg.id WHERE gm.user_id = ? AND gm.status = 'active' ORDER BY tg.created_at DESC");
$groupStmt->execute([$userId]);
$groups = $groupStmt->fetchAll();

?>

<div class="container py-5">
  <div class="row align-items-center mb-4">
    <div class="col-md-8">
      <h2 class="mb-2">Your groups</h2>
      <p class="text-muted mb-0">Browse active tontine groups and open details in a modal window.</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
      <a href="create_group.php" class="btn btn-cta me-2">Create Group</a>
      <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#joinGroupModal">Join Group</button>
    </div>
  </div>


  <?php if (empty($groups)): ?>
    <div class="alert alert-info">You are not a member of any active groups yet. Create one or join with an invitation code.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($groups as $group):
        $seatsUsed = (int)$group['member_count'];
        $seatsRemaining = max(0, (int)$group['capacity'] - $seatsUsed);
      ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card shadow-sm h-100 group-card">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                  <h5 class="card-title"><?php echo htmlspecialchars($group['name'], ENT_QUOTES); ?></h5>
                  <p class="card-text text-muted small"><?php echo ucfirst($group['cycle_interval']); ?> • <?php echo htmlspecialchars($seatsUsed . '/' . $group['capacity'] . ' members', ENT_QUOTES); ?></p>
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
              <div class="d-flex gap-2 mt-2">
                <button class="btn btn-sm btn-outline-primary view-group-btn" data-id="<?php echo $group['id']; ?>">View Details</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="location.href='wallet.php?group_id=<?php echo $group['id']; ?>'">Manage</button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
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
          <a href="#" class="btn btn-sm btn-outline-secondary" style="display: none;" id="sendCodeLink">Send invite code to others (Admins only)</a>
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

<script>
function showGroupDetailsModal(id) {
  const modalEl = document.getElementById('groupDetailsModal');
  const modalBody = document.getElementById('groupDetailsModalBody');
  modalBody.innerHTML = '<div class="text-center py-4">Loading…</div>';
  fetch('partials/group-details-modal.php?id=' + encodeURIComponent(id))
    .then(r => r.text())
    .then(html => {
      modalBody.innerHTML = html;
      const copyBtn = modalBody.querySelector('.copy-invite');
      if (copyBtn) {
        copyBtn.addEventListener('click', function() {
          const text = this.getAttribute('data-text') || '';
          navigator.clipboard.writeText(text).then(() => alert('Copied to clipboard'));
        });
      }
      const joinBtn = modalBody.querySelector('#joinBtnModal');
      if (joinBtn) {
        joinBtn.addEventListener('click', function() {
          const code = prompt('Enter invitation code to join this group');
          if (!code) return;
          fetch('api/join_by_invite.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({invite_code: code})})
            .then(r=>r.json()).then(j=>{ if (j.ok) { alert(j.message); location.reload(); } else alert(j.message || 'Failed'); });
        });
      }
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    })
    .catch(err => {
      modalBody.innerHTML = '<div class="p-4 text-danger">Failed to load details.</div>';
      console.error(err);
    });
}

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('.view-group-btn').forEach(btn => btn.addEventListener('click', function(){
    showGroupDetailsModal(this.getAttribute('data-id'));
  }));

  const joinGroupForm = document.getElementById('joinGroupForm');
  if (joinGroupForm) {
    joinGroupForm.addEventListener('submit', function(e){
      e.preventDefault();
      const inviteCode = document.getElementById('inviteCodeInput').value.trim();
      if (!inviteCode) { alert('Please enter an invitation code'); return; }
      fetch('api/join_by_invite.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({invite_code: inviteCode})})
        .then(r=>r.json()).then(data => { if (data.ok) { alert('✅ ' + data.message); location.reload(); } else { alert('❌ ' + data.message); } })
        .catch(() => alert('❌ An error occurred.'));
    });
  }

  const adminGroupSelect = document.getElementById('adminGroupSelect');
  const sendCodeLink = document.getElementById('sendCodeLink');
  if (adminGroupSelect && sendCodeLink) {
    const adminGroupsData = <?php
      $adminStmt = $pdo->prepare("SELECT tg.id, tg.name FROM tontine_groups tg JOIN group_members gm ON gm.group_id = tg.id WHERE gm.user_id = ? AND gm.is_admin = 1 AND gm.status = 'active'");
      $adminStmt->execute([$userId]);
      echo json_encode($adminStmt->fetchAll());
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
    document.getElementById('sendInviteForm')?.addEventListener('submit', function(e){
      e.preventDefault();
      const formData = new FormData(this);
      fetch('api/send_invite.php', {method:'POST', body: formData})
        .then(r=>r.json()).then(data => {
          if (data.ok) {
            alert('✅ ' + data.message + '\n\nCode: ' + data.code);
            bootstrap.Modal.getInstance(document.getElementById('sendInviteModal')).hide();
            this.reset();
          } else {
            alert('❌ Error: ' + data.message);
          }
        }).catch(err => { console.error(err); alert('❌ An error occurred while generating the code.'); });
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
