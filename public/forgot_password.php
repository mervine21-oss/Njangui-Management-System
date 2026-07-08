<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/mailer.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
$success = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('SELECT id, first_name FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $update = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?');
                $update->execute([$token, $expiresAt, $user['id']]);

                $resetLink = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/reset_password.php?token=' . urlencode($token);
                send_password_reset_email($email, $user['first_name'], $resetLink);
            }

            $success = 'If an account exists for that email, a password reset link has been sent.';
        } catch (PDOException $e) {
            error_log('Forgot password error: ' . $e->getMessage());
            $errors[] = 'A database error occurred. Please try again later.';
        }
    }
}

define('NO_SIDEBAR', true);
require_once __DIR__ . '/../includes/header.php';
?>

<style>
/* Reuse auth page styling from login/register */
body { background: linear-gradient(135deg, #f3e8ff 0%, #faf5ff 100%) !important; }
.auth-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
.auth-wrapper { width: 100%; max-width: 900px; background: white; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 60px rgba(124, 58, 237, 0.15); }
.auth-left-side { flex: 1; background: linear-gradient(135deg, #a21caf 0%, #7c3aed 100%); color: white; padding: 60px 40px; display: flex; flex-direction: column; justify-content: center; position: relative; }
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
      <h2 class="auth-title">Forgot Password?</h2>
      <p class="auth-subtitle">Enter your email and we’ll send you a secure link to reset your password.</p>
      <ul class="auth-benefits">
        <li><span class="benefit-icon">✓</span> Secure reset link</li>
        <li><span class="benefit-icon">✓</span> One-hour expiry</li>
        <li><span class="benefit-icon">✓</span> No password disclosure</li>
      </ul>
    </div>
    <div class="auth-right-side col-12 col-lg-7">
      <div class="auth-form-card">
        <div class="auth-form-header">
          <h3>Reset Your Password</h3>
          <p>We’ll send a reset link to your email address.</p>
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
          <div class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES); ?></div>
        <?php endif; ?>

        <form method="post" action="forgot_password.php" novalidate>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>" required>
          </div>
          <button type="submit" class="btn-auth-primary">Send Reset Link</button>
          <div class="auth-divider"><span>or</span></div>
          <a href="login.php" class="btn-auth-secondary">Return to Login</a>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
