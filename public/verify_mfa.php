<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
$success = '';
$pendingUserId = $_SESSION['pending_mfa_user_id'] ?? null;
$pendingOtp = $_SESSION['pending_mfa_otp'] ?? null;
$pendingExpires = $_SESSION['pending_mfa_expires_at'] ?? null;
$userName = $_SESSION['pending_mfa_name'] ?? 'User';

if (!$pendingUserId || !$pendingOtp || !$pendingExpires) {
  header('Location: login.php');
  exit;
}

if (time() > $pendingExpires) {
  unset($_SESSION['pending_mfa_user_id'], $_SESSION['pending_mfa_otp'], $_SESSION['pending_mfa_expires_at'], $_SESSION['pending_mfa_name']);
  $errors[] = 'Your OTP has expired. Please sign in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $otp = trim($_POST['otp'] ?? '');

  if ($otp === '') {
    $errors[] = 'Please enter the 6-digit code.';
  } elseif (!preg_match('/^[0-9]{6}$/', $otp)) {
    $errors[] = 'Invalid code format.';
  }

  if (empty($errors)) {
    if ($otp === $pendingOtp && time() <= $pendingExpires) {
      $stmt = $pdo->prepare('SELECT first_name, avatar, role FROM users WHERE id = ? LIMIT 1');
      $stmt->execute([$pendingUserId]);
      $user = $stmt->fetch();

      if ($user) {
        $_SESSION['user_id'] = $pendingUserId;
        $_SESSION['user_name'] = $user['first_name'];
        $_SESSION['user_role'] = $user['role'] ?? 'user';
        if (!empty($user['avatar'])) {
          $_SESSION['user_avatar'] = $user['avatar'];
        }

        unset($_SESSION['pending_mfa_user_id'], $_SESSION['pending_mfa_otp'], $_SESSION['pending_mfa_expires_at'], $_SESSION['pending_mfa_name']);
        $_SESSION['flash'] = '✅ Welcome back, ' . htmlspecialchars($user['first_name'], ENT_QUOTES) . '!';
        header('Location: dashboard.php');
        exit;
      }

      $errors[] = 'Unable to complete authentication. Please try again.';
    } else {
      $errors[] = 'Incorrect or expired code. Please try again.';
    }
  }
}

define('NO_SIDEBAR', true);
require_once __DIR__ . '/../includes/header.php';
?>

<style>
body { background: linear-gradient(135deg, #f3e8ff 0%, #faf5ff 100%) !important; }
.auth-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
.auth-wrapper { width: 100%; max-width: 700px; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 60px rgba(124, 58, 237, 0.15); }
.auth-right-side { padding: 60px 40px; }
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
.auth-caption { color: #6b7280; margin-bottom: 24px; }
</style>

<div class="auth-container">
  <div class="auth-wrapper">
    <div class="auth-right-side">
      <div class="auth-form-card">
        <div class="auth-form-header">
          <h3>Verify Your Login</h3>
          <p class="auth-caption">Enter the 6-digit code sent to your account.</p>
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

        <form method="post" action="verify_mfa.php" novalidate>
          <div class="form-group">
            <label class="form-label">6-Digit Code</label>
            <input type="text" name="otp" class="form-control" maxlength="6" pattern="\d{6}" placeholder="123456" required>
          </div>
          <button type="submit" class="btn-auth-primary">Verify & Continue</button>
          <div class="auth-divider"><span>or</span></div>
          <a href="login.php" class="btn-auth-secondary">Back to Login</a>
        </form>

        <p class="text-muted small mt-3">Code expires in 5 minutes. For demo purposes, the code is logged in PHP error logs.</p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
