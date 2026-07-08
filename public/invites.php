<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$userId = $_SESSION['user_id'];

// Fetch admin groups
$adminStmt = $pdo->prepare("SELECT tg.id, tg.name FROM tontine_groups tg
  JOIN group_members gm ON gm.group_id = tg.id
  WHERE gm.user_id = ? AND gm.is_admin = 1 AND gm.status = 'active'");
$adminStmt->execute([$userId]);
$adminGroups = $adminStmt->fetchAll();

// Fetch invites for admin groups
$invites = [];
if (!empty($adminGroups)) {
  $groupIds = array_column($adminGroups, 'id');
  $placeholders = implode(',', array_fill(0, count($groupIds), '?'));
  $invStmt = $pdo->prepare("SELECT i.id, i.group_id, i.token, i.expires_at, i.created_at, u.first_name, u.last_name, tg.name as group_name
    FROM invites i
    LEFT JOIN users u ON u.id = i.created_by
    JOIN tontine_groups tg ON tg.id = i.group_id
    WHERE i.group_id IN ($placeholders)
    ORDER BY i.created_at DESC");
  $invStmt->execute($groupIds);
  $invites = $invStmt->fetchAll();
}
?>

<div class="container py-5">
  <div class="row">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="mb-3">Manage Invitation Codes</h4>
          <?php if (empty($adminGroups)): ?>
            <div class="alert alert-warning">You are not an admin of any group. Create a group to generate invites.</div>
          <?php else: ?>

            <form id="sendInviteForm" class="mb-4">
              <div class="row g-2">
                <div class="col-md-4">
                  <label class="form-label">Select Group</label>
                  <select name="group_id" id="groupSelect" class="form-select" required>
                    <option value="">Choose a group...</option>
                    <?php foreach ($adminGroups as $g): ?>
                      <option value="<?php echo $g['id']; ?>"><?php echo htmlspecialchars($g['name']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Send to Email (optional)</label>
                  <input type="email" name="send_email" class="form-control" placeholder="recipient@example.com">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Expires (days)</label>
                  <input type="number" name="expires_days" class="form-control" value="7" min="0" max="365">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button class="btn btn-cta w-100" type="submit">Generate & Send</button>
                </div>
              </div>
            </form>

            <h6 class="mb-3">Recent Invites</h6>
            <?php if (empty($invites)): ?>
              <div class="alert alert-info">No invitation codes generated yet for your groups.</div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Group</th>
                      <th>Token</th>
                      <th>Expires</th>
                      <th>Created</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($invites as $inv): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($inv['group_name']); ?></td>
                        <td><code id="token-<?php echo $inv['id']; ?>"><?php echo htmlspecialchars($inv['token']); ?></code></td>
                        <td><?php echo $inv['expires_at'] ? date('d/m/Y', strtotime($inv['expires_at'])) : 'Never'; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($inv['created_at'])); ?></td>
                        <td>
                          <button class="btn btn-sm btn-outline-secondary" onclick="copyToken('<?php echo $inv['id']; ?>')">Copy</button>
                          <button class="btn btn-sm btn-danger" onclick="revokeInvite('<?php echo $inv['id']; ?>')">Revoke</button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function copyToken(id){
  const el = document.getElementById('token-' + id);
  if (!el) return;
  const text = el.textContent.trim();
  navigator.clipboard.writeText(text).then(()=>{
    alert('Copied token to clipboard');
  }).catch(()=>{ alert('Copy failed'); });
}

function revokeInvite(id){
  if (!confirm('Revoke this invitation?')) return;
  fetch('api/revoke_invite.php', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({invite_id: id})
  }).then(r=>r.json()).then(data=>{
    if (data.ok) location.reload(); else alert('Error: ' + data.message);
  }).catch(err=>{console.error(err);alert('Request failed');});
}

// Send invite form
document.getElementById('sendInviteForm')?.addEventListener('submit', function(e){
  e.preventDefault();
  const form = new FormData(this);
  fetch('api/send_invite.php', {method:'POST', body: form})
  .then(r=>r.json()).then(data=>{
    if (data.ok){
      alert('Invitation generated: ' + data.code);
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  }).catch(err=>{console.error(err);alert('Request failed');});
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
