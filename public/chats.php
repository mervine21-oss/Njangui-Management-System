<?php
require_once '../config/database.php';
require_once '../includes/auth-check.php';

// Get user's groups
$groupsStmt = $pdo->prepare(
  'SELECT tg.id, tg.name, gm.is_admin,
    (SELECT COUNT(*) FROM group_messages WHERE group_id = tg.id) as msg_count
   FROM group_members gm
   JOIN tontine_groups tg ON gm.group_id = tg.id
   WHERE gm.user_id = ?
   ORDER BY tg.name'
);
$groupsStmt->execute([$_SESSION['user_id']]);
$groups = $groupsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chats - DigiTon</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
  <link href="assets/css/custom.css?v=20260708-2" rel="stylesheet">
  <style>
    .chat-card {
      border-radius: 16px;
      border: 1px solid rgba(124,58,237,0.12);
      transition: all 0.3s ease;
      cursor: pointer;
      display: block;
      text-decoration: none;
      color: inherit;
    }
    .chat-card:hover {
      box-shadow: 0 16px 40px rgba(124,58,237,0.14);
      transform: translateY(-3px);
    }
    .chat-card-badge {
      position: absolute;
      top: 0;
      right: 0;
      background: var(--accent-amber);
      color: white;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      font-weight: 600;
    }
  </style>
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="app-main">
  <div class="app-content">
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="mb-0">Chats</h2>
        <p class="text-muted mb-0">Communicate with your group members</p>
      </div>
    </div>

    <?php if (empty($groups)): ?>
      <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        You are not a member of any groups yet. 
        <a href="groups.php">Join or create a group</a> to start chatting.
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($groups as $group): ?>
          <div class="col-md-6 col-lg-4">
            <a href="chat.php?group_id=<?= $group['id'] ?>" class="chat-card card p-3 position-relative">
              <div class="d-flex align-items-start gap-3">
                <div class="rounded-circle p-3" style="background: linear-gradient(135deg, #A21CAF, #7C3AED); flex-shrink: 0;">
                  <i class="bi bi-chat-left-text text-white" style="font-size: 1.5rem;"></i>
                </div>
                <div style="flex: 1; min-width: 0;">
                  <h5 class="mb-1"><?= htmlspecialchars($group['name']) ?></h5>
                  <p class="text-muted mb-0" style="font-size: 0.85rem;">
                    <i class="bi bi-chat-dots me-1"></i>
                    <?= $group['msg_count'] ?> messages
                    <?php if ($group['is_admin']): ?>
                      <span class="badge bg-warning text-dark">Admin</span>
                    <?php endif; ?>
                  </p>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
