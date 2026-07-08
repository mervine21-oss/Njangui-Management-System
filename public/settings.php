<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['user_id'])) {
  header('Location: login.php'); exit;
}

$userId = (int)$_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first_name = trim($_POST['first_name'] ?? '');
  $last_name = trim($_POST['last_name'] ?? '');
  $phone = trim($_POST['phone'] ?? '');

  if ($first_name === '') $errors[] = 'First name required.';

  // Handle avatar upload
  $avatar_filename = null;
  if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $f = $_FILES['avatar'];
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $f['tmp_name']);
    finfo_close($finfo);
    if (!isset($allowed[$mime])) $errors[] = 'Invalid avatar image type.';
    if ($f['size'] > 2 * 1024 * 1024) $errors[] = 'Avatar too large.';
    if (empty($errors)) {
      $uploadsDir = __DIR__ . '/assets/uploads/avatars';
      if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
      $ext = $allowed[$mime];
      $basename = 'avatar_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
      $destPath = $uploadsDir . '/' . $basename;
      if (!move_uploaded_file($f['tmp_name'], $destPath)) $errors[] = 'Failed to move uploaded avatar.';
      else $avatar_filename = 'assets/uploads/avatars/' . $basename;
    }
  }

  if (empty($errors)) {
    $updateFields = ['first_name' => $first_name, 'last_name' => $last_name, 'phone' => $phone];
    if ($avatar_filename) {
      $updateFields['avatar'] = $avatar_filename;
    }
    $setParts = [];
    $params = [];
    foreach ($updateFields as $k=>$v) { $setParts[] = "$k = ?"; $params[] = $v; }
    $params[] = $userId;
    $sql = 'UPDATE users SET ' . implode(', ', $setParts) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // refresh session avatar/name
    $_SESSION['user_name'] = $first_name;
    if ($avatar_filename) $_SESSION['user_avatar'] = $avatar_filename;

    $_SESSION['flash'] = 'Profile updated.';
    header('Location: profile.php'); exit;
  }
}

$stmt = $pdo->prepare('SELECT first_name,last_name,email,phone,avatar FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) { $_SESSION['flash'] = 'User not found.'; header('Location: logout.php'); exit; }

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card p-4">
        <h4>Settings</h4>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $e): ?><li><?php echo htmlspecialchars($e); ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Change Profile Photo</label>
            <input type="file" name="avatar" accept="image/*" class="form-control">
            <small class="form-text">JPG/PNG/GIF max 2MB.</small>
          </div>

          <button class="btn btn-primary">Save Changes</button>
        </form>

      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
