<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$group_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

$stmt = $pdo->prepare('SELECT * FROM tontine_groups WHERE id = ?');
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
  echo '<div class="container py-5"><div class="alert alert-warning">Group not found.</div></div>';
  require_once __DIR__ . '/../includes/footer.php';
  exit;
}

// count active members
$countStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM group_members WHERE group_id = ? AND status = 'active'");
$countStmt->execute([$group_id]);
$countRow = $countStmt->fetch();
$membersCount = (int)$countRow['cnt'];
$seatsRemaining = max(0, $group['capacity'] - $membersCount);

// check if current user is member and admin
$userId = $_SESSION['user_id'] ?? null;
$isMember = false;
$isAdmin = false;
if ($userId) {
  $mStmt = $pdo->prepare('SELECT is_admin FROM group_members WHERE group_id = ? AND user_id = ? LIMIT 1');
  $mStmt->execute([$group_id, $userId]);
  $member = $mStmt->fetch();
  $isMember = (bool)$member;
  $isAdmin = $member && $member['is_admin'];
}

// Get invitation code (for admins)
$inviteCode = null;
if ($isAdmin) {
  $invStmt = $pdo->prepare('SELECT token FROM invites WHERE group_id = ? ORDER BY created_at DESC LIMIT 1');
  $invStmt->execute([$group_id]);
  $invite = $invStmt->fetch();
  $inviteCode = $invite ? $invite['token'] : null;
}
?>

<div class="container py-5">
  <div class="row">
    <div class="col-12 col-md-8">
      <h2 class="mb-2"><?php echo htmlspecialchars($group['name'], ENT_QUOTES); ?></h2>
      <p class="text-muted">Contribution: <?php echo number_format($group['contribution_amount'],2); ?> XAF — Cycle: <?php echo htmlspecialchars(ucfirst($group['cycle_interval'])); ?></p>
      <div class="mb-3">
        <strong>Members:</strong> <?php echo $membersCount; ?> of <?php echo $group['capacity']; ?> (<?php echo $seatsRemaining; ?> seats remaining)
      </div>
      <?php if ($isMember): ?>
        <div class="alert alert-success">You are a member of this group.</div>
      <?php else: ?>
        <?php if ($seatsRemaining > 0 && (!isset($group['status']) || $group['status'] === 'active')): ?>
          <button id="joinBtn" class="btn btn-cta" data-group-id="<?php echo $group_id; ?>">Join Group</button>
        <?php else: ?>
          <div class="alert alert-warning">This group is full.</div>
        <?php endif; ?>
      <?php endif; ?>

    </div>
    <div class="col-12 col-md-4">
      <div class="card p-3 shadow-sm">
        <h6>Group Info</h6>
        <p class="mb-1"><strong>Collateral reserve:</strong> <?php echo number_format($group['collateral_reserve_percent'],2); ?>%</p>
        <p class="mb-0"><strong>Created:</strong> <?php echo $group['created_at']; ?></p>
      </div>

      <?php if ($isAdmin): ?>
        <?php if ($inviteCode): ?>
        <div class="card p-3 shadow-sm mt-3" style="background: linear-gradient(135deg, rgba(243,232,255,0.4), rgba(237,233,254,0.4));">
          <h6 style="color: var(--primary-purple); margin-bottom: 1rem;">📨 Invitation Code</h6>
          <p style="font-size: 0.85rem; color: #666; margin-bottom: 0.75rem;">Share this code with others to invite them to join:</p>
          <div style="display: flex; gap: 0.5rem;">
            <input type="text" id="inviteCodeInput" value="<?php echo htmlspecialchars($inviteCode); ?>" class="form-control" readonly style="font-family: monospace; font-size: 0.9rem; background: white;">
            <button class="btn" style="background: var(--accent-amber); border: none; color: white; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s;" onclick="copyInviteCode()" title="Copy to clipboard">
              📋 Copy
            </button>
          </div>
        </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function copyInviteCode() {
  const input = document.getElementById('inviteCodeInput');
  input.select();
  document.execCommand('copy');
  
  // Visual feedback
  const btn = event.target;
  const originalText = btn.textContent;
  btn.textContent = '✅ Copied!';
  btn.style.background = '#10b981';
  
  setTimeout(() => {
    btn.textContent = originalText;
    btn.style.background = 'var(--accent-amber)';
  }, 2000);
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
