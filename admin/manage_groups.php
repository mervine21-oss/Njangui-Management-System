<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// only admin/super_admin
$uid = $_SESSION['user_id'] ?? null;
if (!$uid) { header('Location: ../public/login.php'); exit; }
$r = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1'); $r->execute([$uid]); $role = $r->fetchColumn();
if (!in_array($role, ['admin','super_admin'])) { echo 'Access denied'; exit; }

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

// fetch all groups
$all = $pdo->query('SELECT tg.*, u.first_name AS admin_first, u.last_name AS admin_last FROM tontine_groups tg LEFT JOIN users u ON u.id = tg.admin_user_id ORDER BY tg.created_at DESC')->fetchAll();
?>
<div class="container py-5">
  <h3>Admin — Manage Tontine Groups</h3>
  <p class="text-muted">View platform groups and export reports.</p>

  <h5 class="mt-4">All Groups</h5>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Name</th><th>Admin</th><th>Status</th><th>Created</th><th>Report</th></tr></thead>
      <tbody>
        <?php foreach ($all as $g): ?>
          <tr>
            <td><?php echo $g['id']; ?></td>
            <td><?php echo htmlspecialchars($g['name']); ?></td>
            <td><?php echo htmlspecialchars($g['admin_first'] . ' ' . $g['admin_last']); ?></td>
            <td><?php echo htmlspecialchars($g['status']); ?><?php if ($g['creation_paid']) echo ' • Paid'; ?></td>
            <td><?php echo $g['created_at']; ?></td>
            <td>
              <?php if ($g['status'] === 'active'): ?>
                <a href="../public/group_report.php?group_id=<?php echo $g['id']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">Export report</a>
              <?php else: ?>
                <span class="text-muted">N/A</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
