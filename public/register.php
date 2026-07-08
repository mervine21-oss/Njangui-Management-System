<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$errors = [];
$old = ['full_name'=>'','profession'=>'','phone'=>'','email'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $full_name = trim($_POST['full_name'] ?? '');
  $profession = trim($_POST['profession'] ?? '');
  $phone = trim($_POST['phone'] ?? '');
  $email = strtolower(trim($_POST['email'] ?? ''));
  $password = $_POST['password'] ?? '';

  $old['full_name'] = htmlspecialchars($full_name, ENT_QUOTES);
  $old['profession'] = htmlspecialchars($profession, ENT_QUOTES);
  $old['phone'] = htmlspecialchars($phone, ENT_QUOTES);
  $old['email'] = htmlspecialchars($email, ENT_QUOTES);

  if ($full_name === '') {
    $errors[] = 'Please provide your full name.';
  }
  if ($profession === '') {
    $errors[] = 'Please provide your profession.';
  }
  if ($phone === '') {
    $errors[] = 'Please provide your phone number.';
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please provide a valid email address.';
  }
  if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
  }

  if (empty($errors)) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
      $errors[] = 'An account with that email already exists.';
    } else {
      try {
        // Ensure avatar column exists
        $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'")->fetch();
        if (!$col) {
          $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER phone");
        }

        // Handle avatar upload if provided
        $avatar_filename = null;
        if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
          $f = $_FILES['avatar'];
          $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif'];
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          $mime = finfo_file($finfo, $f['tmp_name']);
          finfo_close($finfo);
          if (!isset($allowed[$mime])) {
            throw new Exception('Invalid avatar image type. Use JPG/PNG/GIF.');
          }
          if ($f['size'] > 2 * 1024 * 1024) {
            throw new Exception('Avatar must be smaller than 2MB.');
          }
          $uploadsDir = __DIR__ . '/assets/uploads/avatars';
          if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
          $ext = $allowed[$mime];
          $basename = 'avatar_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
          $destPath = $uploadsDir . '/' . $basename;
          if (!move_uploaded_file($f['tmp_name'], $destPath)) {
            throw new Exception('Failed to move uploaded avatar.');
          }
          // store web-accessible path
          $avatar_filename = 'assets/uploads/avatars/' . $basename;
        }
        $name_parts = explode(' ', trim($full_name), 2);
        $first_name = $name_parts[0];
        $last_name = $name_parts[1] ?? '';
        $role = 'user';
        
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        if ($avatar_filename) {
          $insert = $pdo->prepare('INSERT INTO users (first_name,last_name,email,password_hash,phone,avatar,role) VALUES (?,?,?,?,?,?,?)');
          $insert->execute([$first_name, $last_name, $email, $password_hash, $phone, $avatar_filename, $role]);
        } else {
          $insert = $pdo->prepare('INSERT INTO users (first_name,last_name,email,password_hash,phone,role) VALUES (?,?,?,?,?,?)');
          $insert->execute([$first_name, $last_name, $email, $password_hash, $phone, $role]);
        }
        $userId = $pdo->lastInsertId();

        if (!$userId) {
          throw new Exception('Failed to retrieve inserted user ID');
        }

        $verify = $pdo->prepare('SELECT id FROM users WHERE id = ?');
        $verify->execute([$userId]);
        if (!$verify->fetch()) {
          throw new Exception('User creation verification failed');
        }

        $_SESSION['flash'] = '✅ Account created successfully! Please log in.';
        header('Location: login.php');
        exit;
      } catch (PDOException $e) {
        error_log('Register error: ' . $e->getMessage());
        $errors[] = 'Database error during registration: ' . $e->getMessage();
      } catch (Exception $e) {
        error_log('Register verification error: ' . $e->getMessage());
        $errors[] = 'Registration error: ' . $e->getMessage();
      }
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
        <h2 class="auth-title">Join DigiTon</h2>
        <p class="auth-subtitle">Start managing your tontine group today</p>
        <ul class="auth-benefits">
          <li><span class="benefit-icon">✓</span> Create & manage groups</li>
          <li><span class="benefit-icon">✓</span> Secure payments</li>
          <li><span class="benefit-icon">✓</span> Track contributions</li>
          <li><span class="benefit-icon">✓</span> Real-time reporting</li>
        </ul>
      </div>
    </div>

    <div class="auth-right-side">
      <div class="auth-form-card">
        <div class="auth-form-header">
          <h3>Create Account</h3>
          <p>Sign up to get started</p>
        </div>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <strong>Errors:</strong>
            <ul class="mb-0 mt-2">
              <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="register.php" novalidate enctype="multipart/form-data">
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" placeholder="Enter your full name" value="<?php echo $old['full_name']; ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Profession</label>
            <input type="text" name="profession" class="form-control" placeholder="e.g., Software Engineer" value="<?php echo $old['profession']; ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Phone Number</label>
            <input type="tel" name="phone" class="form-control" placeholder="+237 XXX XXX XXX" value="<?php echo $old['phone']; ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Profile Photo (optional)</label>
            <input type="file" name="avatar" accept="image/*" class="form-control">
            <small class="form-text">JPG, PNG or GIF. Max 2MB.</small>
          </div>

          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?php echo $old['email']; ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Password</label>
            <div class="password-input-wrapper">
              <input type="password" name="password" class="form-control password-input" id="passwordInput" placeholder="At least 8 characters" required>
              <button type="button" class="password-toggle" onclick="togglePasswordVisibility('passwordInput')">
                <svg class="eye-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </button>
            </div>
            <small class="form-text">Minimum 8 characters</small>
          </div>

          <button type="submit" class="btn-auth-primary">Create Account</button>

          <div class="auth-divider">
            <span>Already have an account?</span>
          </div>

          <a href="login.php" class="btn-auth-secondary">Sign In</a>
        </form>

        <div class="auth-footer">
          <p class="text-center text-muted small">By signing up, you agree to our Terms & Privacy Policy</p>
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
