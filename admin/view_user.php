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

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$userId) {
  header('Location: manage_users.php');
  exit;
}

$stmt = $pdo->prepare('SELECT id, first_name, last_name, email, phone, role, created_at FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
  header('Location: manage_users.php');
  exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
  <div class="row">
    <div class="col-lg-8">
      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h4>User Details</h4>
          <dl class="row mt-4">
            <dt class="col-sm-4">ID</dt>
            <dd class="col-sm-8"><?php echo $user['id']; ?></dd>

            <dt class="col-sm-4">Name</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES); ?></dd>

            <dt class="col-sm-4">Email</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($user['email'], ENT_QUOTES); ?></dd>

            <dt class="col-sm-4">Phone</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($user['phone'], ENT_QUOTES); ?></dd>

            <dt class="col-sm-4">Role</dt>
            <dd class="col-sm-8"><?php echo htmlspecialchars($user['role'], ENT_QUOTES); ?></dd>

            <dt class="col-sm-4">Registered</dt>
            <dd class="col-sm-8"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></dd>
          </dl>
          <a href="manage_users.php" class="btn btn-outline-secondary">← Back to users</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
