<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
  header('Location: login.php'); exit;
}

$userId = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT id, first_name, last_name, email, phone, avatar FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
  $_SESSION['flash'] = 'User not found.';
  header('Location: logout.php'); exit;
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
  <div class="row">
    <div class="col-md-4">
      <div class="card p-4 text-center">
        <?php if ($user['avatar']): ?>
          <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="avatar" class="rounded-circle mb-3" width="140" height="140">
        <?php else: ?>
          <div class="rounded-circle mb-3 d-inline-flex align-items-center justify-content-center" style="width:140px;height:140px;background:linear-gradient(135deg,#7c3aed,#a21caf);color:#fff;font-size:2rem;font-weight:700"><?php echo strtoupper(substr($user['first_name'],0,1)); ?></div>
        <?php endif; ?>
        <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
        <p class="text-muted mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
        <p class="text-muted"><?php echo htmlspecialchars($user['phone']); ?></p>
        <a href="settings.php" class="btn btn-outline-primary mt-3">Edit Profile</a>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card p-4">
        <h5>About</h5>
        <p class="text-muted">This is your profile page. You can update your personal information and profile photo in settings.</p>

        <h6 class="mt-3">Memberships</h6>
        <?php
        // detect if 'schedule' column exists in tontine_groups
        $hasSchedule = false;
        try {
          $col = $pdo->query("SHOW COLUMNS FROM tontine_groups LIKE 'schedule'")->fetch();
          if ($col) $hasSchedule = true;
        } catch (PDOException $e) {
          // table may not exist or permission denied — treat as no schedule
          $hasSchedule = false;
        }

        if ($hasSchedule) {
          $m = $pdo->prepare('SELECT g.id,g.name,g.schedule FROM tontine_groups g JOIN group_members gm ON gm.group_id=g.id WHERE gm.user_id = ?');
        } else {
          $m = $pdo->prepare('SELECT g.id,g.name FROM tontine_groups g JOIN group_members gm ON gm.group_id=g.id WHERE gm.user_id = ?');
        }
        $m->execute([$userId]);
        $groups = $m->fetchAll();
        if ($groups):
        ?>
          <ul>
            <?php foreach ($groups as $gr): ?>
              <li>
                <?php echo htmlspecialchars($gr['name']); ?>
                <?php if ($hasSchedule && isset($gr['schedule']) && $gr['schedule'] !== null): ?> — <?php echo htmlspecialchars($gr['schedule']); ?><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <p class="text-muted">Not a member of any groups yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
