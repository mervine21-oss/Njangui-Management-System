<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$userRole = $_SESSION['user_role'] ?? 'user';
if (!in_array($userRole, ['admin','super_admin'])) {
  header('Location: dashboard.php');
  exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
  <div class="mb-5">
    <h2 class="mb-2">Admin Dashboard</h2>
    <p class="text-muted">Access administrative tools to manage groups, users and platform operations.</p>
  </div>

  <div class="row gy-4">
    <div class="col-12 col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title">Manage Groups</h5>
          <p class="card-text">Review pending tontine groups, approve or reject submissions, and inspect group details.</p>
          <a href="admin/manage_groups.php" class="btn btn-cta">Open Group Management</a>
        </div>
      </div>
    </div>

    <div class="col-12 col-md-6">
      <div class="card shadow-sm h-100">
        <div class="card-body">
          <h5 class="card-title">Manage Users</h5>
          <p class="card-text">View registered users and manage account roles when you are a super administrator.</p>
          <a href="admin/manage_users.php" class="btn btn-cta">Open User Management</a>
        </div>
      </div>
    </div>
  </div>

  <?php if ($userRole === 'super_admin'): ?>
    <div class="mt-5 alert alert-info">
      <strong>Super Admin</strong> access granted. You can edit user roles and review platform-wide data.
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
