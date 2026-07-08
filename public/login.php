<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
$old_email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';
  $old_email = htmlspecialchars($email, ENT_QUOTES);

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email.';
  }
  if ($password === '') {
    $errors[] = 'Please enter your password.';
  }

  if (empty($errors)) {
    try {
      $stmt = $pdo->prepare('SELECT id, password_hash, first_name FROM users WHERE email = ? LIMIT 1');
      $stmt->execute([$email]);
      $user = $stmt->fetch();
      
      if ($user && password_verify($password, $user['password_hash'])) {
        $verify = $pdo->prepare('SELECT id FROM users WHERE id = ?');
        $verify->execute([$user['id']]);
        if ($verify->fetch()) {
          $otp = random_int(100000, 999999);
          $_SESSION['pending_mfa_user_id'] = (int)$user['id'];
          $_SESSION['pending_mfa_expires_at'] = time() + 300;
          $_SESSION['pending_mfa_otp'] = (string)$otp;
          $_SESSION['pending_mfa_name'] = $user['first_name'];

          // Optionally log OTP for local testing
          error_log('DigiTon MFA OTP for user ' . $user['id'] . ': ' . $otp);

          header('Location: verify_mfa.php');
          exit;
        } else {
          $errors[] = 'User verification failed. Please contact support.';
        }
      } else {
        $errors[] = 'Wrong credentials. Please check your email and password.';
      }
    } catch (PDOException $e) {
      error_log('Login database error: ' . $e->getMessage());
      $errors[] = 'Database error during login.';
    }
  }
}

define('NO_SIDEBAR', true);
require_once __DIR__ . '/../includes/header.php';
?>

<style>
body { background: linear-gradient(135deg, #f3e8ff 0%, #faf5ff 100%) !important; }

.auth-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: linear-gradient(135deg, #f3e8ff 0%, #faf5ff 100%);
}

.auth-wrapper {
  display: flex;
  width: 100%;
  max-width: 1200px;
  background: white;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(124, 58, 237, 0.15);
  min-height: 600px;
}

.auth-left-side {
  flex: 1;
  background: linear-gradient(135deg, #a21caf 0%, #7c3aed 100%);
  color: white;
  padding: 60px 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.auth-decoration {
  position: absolute;
  top: 0;
  right: 0;
  width: 100%;
  height: 100%;
  opacity: 0.1;
}

.auth-decoration-circle {
  position: absolute;
  border-radius: 50%;
  background: white;
}

.auth-circle-1 { width: 300px; height: 300px; top: -100px; right: -100px; }
.auth-circle-2 { width: 200px; height: 200px; bottom: -50px; right: 50px; }
.auth-circle-3 { width: 150px; height: 150px; top: 50%; left: -50px; }

.auth-info {
  position: relative;
  z-index: 2;
}

.auth-title {
  font-size: 36px;
  font-weight: 700;
  margin-bottom: 12px;
  letter-spacing: -0.5px;
}

.auth-subtitle {
  font-size: 16px;
  opacity: 0.9;
  margin-bottom: 40px;
}

.auth-benefits {
  list-style: none;
  padding: 0;
  margin: 0;
}

.auth-benefits li {
  display: flex;
  align-items: center;
  margin-bottom: 16px;
  font-size: 15px;
}

.benefit-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 50%;
  margin-right: 12px;
  font-weight: bold;
}

.auth-right-side {
  flex: 1;
  padding: 60px 40px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.auth-form-card {
  width: 100%;
}

.auth-form-header {
  margin-bottom: 32px;
}

.auth-form-header h3 {
  font-size: 28px;
  font-weight: 700;
  margin-bottom: 8px;
  color: #1f2937;
}

.auth-form-header p {
  color: #6b7280;
  margin: 0;
}

.form-group {
  position: relative;
  margin-bottom: 20px;
}

.form-label {
  font-size: 13px;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  display: block;
}

.form-control {
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 12px 16px;
  font-size: 14px;
  transition: all 0.3s ease;
  background: #f9fafb;
  width: 100%;
}

.form-control:focus {
  border-color: #7c3aed;
  background: white;
  box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
  outline: none;
}

.password-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.password-input {
  padding-right: 40px !important;
}

.password-toggle {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  padding: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
  z-index: 10;
}

.password-toggle:hover {
  color: #7c3aed;
}

.eye-icon {
  width: 20px;
  height: 20px;
}

.btn-auth-primary {
  background: linear-gradient(135deg, #a21caf 0%, #7c3aed 100%);
  border: none;
  color: white;
  font-weight: 600;
  padding: 12px 24px;
  border-radius: 12px;
  transition: all 0.3s ease;
  font-size: 14px;
  letter-spacing: 0.5px;
  cursor: pointer;
  width: 100%;
}

.btn-auth-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(124, 58, 237, 0.3);
  color: white;
  text-decoration: none;
}

.btn-auth-primary:active {
  transform: translateY(0);
}

.btn-auth-secondary {
  background: white;
  border: 2px solid #e5e7eb;
  color: #7c3aed;
  font-weight: 600;
  padding: 12px 24px;
  border-radius: 12px;
  transition: all 0.3s ease;
  font-size: 14px;
  letter-spacing: 0.5px;
  cursor: pointer;
  width: 100%;
  text-decoration: none;
  display: inline-block;
  text-align: center;
}

.btn-auth-secondary:hover {
  border-color: #7c3aed;
  background: #f3e8ff;
  color: #7c3aed;
  text-decoration: none;
}

.auth-divider {
  text-align: center;
  margin: 24px 0;
  color: #9ca3af;
  font-size: 13px;
  position: relative;
}

.auth-divider span {
  background: white;
  padding: 0 12px;
  position: relative;
  z-index: 1;
}

.auth-divider::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  height: 1px;
  background: #e5e7eb;
}

.auth-footer {
  margin-top: 32px;
}

.form-text {
  font-size: 12px;
  margin-top: 6px;
  display: block;
  color: #9ca3af;
}

.alert {
  border-radius: 12px;
  border: none;
  padding: 16px 20px;
  margin-bottom: 24px;
  font-size: 14px;
}

.alert-danger {
  background: #fee2e2;
  color: #dc2626;
}

.alert-danger ul {
  margin-bottom: 0;
}

.remember-forgot {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  font-size: 14px;
}

.remember-forgot a {
  color: #7c3aed;
  text-decoration: none;
  transition: color 0.2s;
}

.remember-forgot a:hover {
  color: #a21caf;
}

.form-check {
  margin: 0;
  display: flex;
  align-items: center;
}

.form-check-input {
  border: 2px solid #e5e7eb;
  border-radius: 6px;
  margin-right: 8px;
  cursor: pointer;
  transition: all 0.2s;
}

.form-check-input:checked {
  background: #7c3aed;
  border-color: #7c3aed;
}

.form-check-label {
  cursor: pointer;
  margin-bottom: 0;
  color: #6b7280;
  font-size: 14px;
}

@media (max-width: 768px) {
  .auth-wrapper {
    flex-direction: column;
    min-height: auto;
  }

  .auth-left-side {
    padding: 40px 20px;
    min-height: 300px;
  }

  .auth-right-side {
    padding: 40px 20px;
  }

  .auth-title { font-size: 28px; }
  .auth-form-header h3 { font-size: 24px; }
}
</style>

<div class="auth-container">
  <div class="auth-wrapper">
    <div class="auth-left-side">
      <div class="auth-decoration">
        <div class="auth-decoration-circle auth-circle-1"></div>
        <div class="auth-decoration-circle auth-circle-2"></div>
        <div class="auth-decoration-circle auth-circle-3"></div>
      </div>
      <div class="auth-info">
        <h2 class="auth-title">Welcome Back</h2>
        <p class="auth-subtitle">Manage your tontine group efficiently</p>
        <ul class="auth-benefits">
          <li><span class="benefit-icon">✓</span> Secure login protection</li>
          <li><span class="benefit-icon">✓</span> Real-time updates</li>
          <li><span class="benefit-icon">✓</span> Instant notifications</li>
          <li><span class="benefit-icon">✓</span> 24/7 Access</li>
        </ul>
      </div>
    </div>

    <div class="auth-right-side">
      <div class="auth-form-card">
        <div class="auth-form-header">
          <h3>Sign In</h3>
          <p>Access your account</p>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <strong>Login Error:</strong>
            <ul class="mb-0 mt-2">
              <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="login.php" novalidate>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?php echo $old_email; ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Password</label>
            <div class="password-input-wrapper">
              <input type="password" name="password" class="form-control password-input" id="passwordInput" placeholder="Enter your password" required>
              <button type="button" class="password-toggle" onclick="togglePasswordVisibility('passwordInput')">
                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
          </div>

          <div class="remember-forgot">
            <label class="form-check">
              <input type="checkbox" class="form-check-input" name="remember">
              <span class="form-check-label">Remember me</span>
            </label>
            <a href="forgot_password.php">Forgot password?</a>
          </div>

          <button type="submit" class="btn-auth-primary">Sign In</button>

          <div class="auth-divider">
            <span>Don't have an account?</span>
          </div>

          <a href="register.php" class="btn-auth-secondary">Create Account</a>
        </form>

        <div class="auth-footer">
          <p class="text-center text-muted small">By signing in, you agree to our Terms & Privacy Policy</p>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function togglePasswordVisibility(inputId) {
  const input = document.getElementById(inputId);
  if (input.type === 'password') {
    input.type = 'text';
  } else {
    input.type = 'password';
  }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
