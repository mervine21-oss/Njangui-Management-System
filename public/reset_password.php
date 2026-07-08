<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
$success = '';
$token = $_GET['token'] ?? '';
$token = trim($token);
$userId = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($token === '') {
        $errors[] = 'Invalid or missing reset token.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT id, email FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW() LIMIT 1');
            $stmt->execute([$token]);
            $user = $stmt->fetch();

            if (!$user) {
                $errors[] = 'Reset link is invalid or has expired.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $update = $pdo->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?');
                $update->execute([$passwordHash, $user['id']]);
                $success = 'Your password has been updated. You can now <a href="login.php">sign in</a>.';
            }
        } catch (PDOException $e) {
            error_log('Reset password error: ' . $e->getMessage());
            $errors[] = 'A database error occurred. Please try again later.';
        }
    }
} else {
    if ($token !== '') {
        $stmt = $pdo->prepare('SELECT email FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW() LIMIT 1');
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if ($user) {
            $email = $user['email'];
        } else {
            $errors[] = 'Reset link is invalid or has expired.';
        }
    } else {
        $errors[] = 'Reset token is required.';
    }
}

define('NO_SIDEBAR', true);
require_once __DIR__ . '/../includes/header.php';
?>

<style>
body { background: linear-gradient(135deg, #f3e8ff 0%, #faf5ff 100%) !important; }
.auth-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
.auth-wrapper { width: 100%; max-width: 900px; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 60px rgba(124, 58, 237, 0.15); }
.auth-left-side { flex: 1; background: linear-gradient(135deg, #a21caf 0%, #7c3aed 100%); color: white; padding: 60px 40px; display: flex; flex-direction: column; justify-content: center; }
.auth-right-side { flex: 1; padding: 60px 40px; }
.auth-form-card { width: 100%; }
.auth-form-header h3 { font-size: 28px; font-weight: 700; margin-bottom: 8px; color: #1f2937; }
.form-group { margin-bottom: 20px; }
.form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #374151; text-transform: uppercase; font-size: 13px; }
.form-control { width: 100%; padding: 12px 16px; border-radius: 12px; border: 2px solid #e5e7eb; background: #f9fafb; }
.btn-auth-primary, .btn-auth-secondary { width: 100%; border-radius: 12px; padding: 12px 24px; font-weight: 600; font-size: 14px; }
.btn-auth-primary { background: linear-gradient(135deg, #a21caf 0%, #7c3aed 100%); color: white; border: none; }
.btn-auth-secondary { background: white; border: 2px solid #e5e7eb; color: #7c3aed; text-decoration: none; display: inline-block; text-align: center; }
.alert { border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; }
.alert-danger { background: #fee2e2; color: #dc2626; }
.alert-success { background: #d1fae5; color: #065f46; }
</style>

<div class="auth-container">
  <div class="auth-wrapper d-flex flex-wrap">
    <div class="auth-left-side col-12 col-lg-5">
      <h2 class="auth-title">Set a New Password</h2>
      <p class="auth-subtitle">Choose a strong password to secure your account.</p>
      <ul class="auth-benefits">
        <li><span class="benefit-icon">✓</span> Strong, bcrypt-hashed passwords</li>
        <li><span class="benefit-icon">✓</span> Token-based reset link</li>
        <li><span class="benefit-icon">✓</span> One-time use URL</li>
      </ul>
    </div>
    <div class="auth-right-side col-12 col-lg-7">
      <div class="auth-form-card">
        <div class="auth-form-header">
          <h3>Reset Password</h3>
          <p>Enter your new password below.</p>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error, ENT_QUOTES); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
          <form method="post" action="reset_password.php" novalidate>
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES); ?>">
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>" disabled>
            </div>
            <div class="form-group">
              <label class="form-label">New Password</label>
              <input type="password" name="password" class="form-control" placeholder="At least 8 characters" required>
            </div>
            <div class="form-group">
              <label class="form-label">Confirm Password</label>
              <input type="password" name="confirm_password" class="form-control" placeholder="Retype your password" required>
            </div>
            <button type="submit" class="btn-auth-primary">Update Password</button>
            <div class="auth-divider"><span>or</span></div>
            <a href="login.php" class="btn-auth-secondary">Back to Login</a>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
