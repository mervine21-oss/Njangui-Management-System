<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$uid = $_SESSION['user_id'] ?? null;
if (!$uid) { header('Location: ../public/login.php'); exit; }

$r = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
$r->execute([$uid]);
$role = $r->fetchColumn();
if ($role !== 'super_admin') {
  echo 'Access denied';
  exit;
}

$errors = [];
$old = ['first_name'=>'','last_name'=>'','email'=>'','phone'=>'','role'=>'user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $first_name = trim($_POST['first_name'] ?? '');
  $last_name = trim($_POST['last_name'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $phone = trim($_POST['phone'] ?? '');
  $password = $_POST['password'] ?? '';
  $userRole = $_POST['role'] ?? 'user';

  $old = [
    'first_name' => htmlspecialchars($first_name, ENT_QUOTES),
    'last_name' => htmlspecialchars($last_name, ENT_QUOTES),
    'email' => htmlspecialchars($email, ENT_QUOTES),
    'phone' => htmlspecialchars($phone, ENT_QUOTES),
    'role' => $userRole,
  ];

  if ($first_name === '' || $last_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
    $errors[] = 'All fields except phone are required.';
  }
  if (!in_array($userRole, ['user','admin','super_admin'], true)) {
    $errors[] = 'Invalid role selected.';
  }

  if (empty($errors)) {
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->execute([$email]);
    if ($check->fetch()) {
      $errors[] = 'A user with this email already exists.';
    } else {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $stmt = $pdo->prepare('INSERT INTO users (first_name,last_name,email,password_hash,phone,role) VALUES (?,?,?,?,?,?)');
      $stmt->execute([$first_name, $last_name, $email, $hash, $phone, $userRole]);
      $_SESSION['flash'] = 'User created successfully.';
      header('Location: manage_users.php');
      exit;
    }
  }
}

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card shadow-sm">
        <div class="card-body">
          <h3 class="mb-3">Create System User</h3>
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $error) { echo '<li>' . htmlspecialchars($error, ENT_QUOTES) . '</li>'; } ?></ul></div>
          <?php endif; ?>
          <form method="post">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control" value="<?php echo $old['first_name']; ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="<?php echo $old['last_name']; ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo $old['email']; ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="<?php echo $old['phone']; ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Role</label>
                <select name="role" class="form-select">
                  <option value="user"<?php echo $old['role'] === 'user' ? ' selected' : ''; ?>>User</option>
                  <option value="admin"<?php echo $old['role'] === 'admin' ? ' selected' : ''; ?>>Admin</option>
                  <option value="super_admin"<?php echo $old['role'] === 'super_admin' ? ' selected' : ''; ?>>Super Admin</option>
                </select>
              </div>
            </div>
            <div class="mt-4 d-flex gap-2">
              <button class="btn btn-cta">Create User</button>
              <a href="manage_users.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
