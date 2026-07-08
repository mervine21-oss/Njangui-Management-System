<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$uid = $_SESSION['user_id'] ?? null;
if (!$uid) { header('Location: ../public/login.php'); exit; }

$r = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
$r->execute([$uid]);
$role = $r->fetchColumn();
if (!in_array($role, ['admin','super_admin'])) {
  echo 'Access denied';
  exit;
}

$search = trim($_GET['search'] ?? '');
$params = [];
$sql = 'SELECT id, first_name, last_name, email, phone, role, created_at FROM users';
if ($search !== '') {
  $sql .= ' WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ?';
  $term = '%' . $search . '%';
  $params = [$term, $term, $term];
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3 mb-4">
    <div>
      <h3>Admin — Manage Users</h3>
      <p class="text-muted">Search users, view roles, and change access levels for the platform.</p>
    </div>
    <?php if ($role === 'super_admin'): ?>
      <a href="create_user.php" class="btn btn-cta">Create System User</a>
    <?php endif; ?>
  </div>

  <form class="row g-3 mb-4" method="get" action="manage_users.php">
    <div class="col-auto flex-grow-1">
      <input type="search" class="form-control" name="search" placeholder="Search by name or email" value="<?php echo htmlspecialchars($search, ENT_QUOTES); ?>">
    </div>
    <div class="col-auto">
      <button class="btn btn-outline-primary">Search</button>
    </div>
  </form>

  <?php if (empty($users)): ?>
    <div class="alert alert-info">No users found.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><?php echo $user['id']; ?></td>
              <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES); ?></td>
              <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></td>
              <td><?php echo htmlspecialchars($user['phone'], ENT_QUOTES); ?></td>
              <td><?php echo htmlspecialchars($user['role'], ENT_QUOTES); ?></td>
              <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
              <td>
                <a href="view_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-secondary">View</a>
                <?php if ($role === 'super_admin' && $user['id'] !== $uid): ?>
                  <button class="btn btn-sm btn-outline-primary" onclick="openRoleModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['role'], ENT_QUOTES); ?>')">Change Role</button>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Change User Role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="roleForm">
          <input type="hidden" name="user_id" id="roleUserId">
          <div class="mb-3">
            <label class="form-label">New role</label>
            <select id="roleSelect" name="role" class="form-select">
              <option value="user">User</option>
              <option value="admin">Admin</option>
              <option value="super_admin">Super Admin</option>
            </select>
          </div>
          <div class="text-muted">Changing a user role affects access to admin functionality.</div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-cta" onclick="submitRoleChange()">Save Role</button>
      </div>
    </div>
  </div>
</div>

<script>
function openRoleModal(userId, currentRole) {
  document.getElementById('roleUserId').value = userId;
  document.getElementById('roleSelect').value = currentRole;
  new bootstrap.Modal(document.getElementById('roleModal')).show();
}

function submitRoleChange() {
  const userId = document.getElementById('roleUserId').value;
  const role = document.getElementById('roleSelect').value;
  fetch('api/change_user_role.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({user_id: parseInt(userId, 10), role})
  }).then(r => r.json()).then(data => {
    if (data.ok) {
      location.reload();
    } else {
      alert(data.message || 'Failed to update role');
    }
  }).catch(err => {
    console.error(err);
    alert('Request failed');
  });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
