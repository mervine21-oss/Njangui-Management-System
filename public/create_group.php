<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';

$errors = [];
$old = ['name'=>'','capacity'=>12,'contribution_amount'=>'250000.00','cycle_interval'=>'monthly','collateral_reserve_percent'=>'5.00'];
$intervals = ['weekly','monthly','bimonthly','annually'];

// Verify user exists in database
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  $_SESSION['flash'] = '❌ Authentication error. Please log in again.';
  header('Location: login.php');
  exit;
}

$userCheck = $pdo->prepare('SELECT id FROM users WHERE id = ?');
$userCheck->execute([$userId]);
if (!$userCheck->fetch()) {
  $_SESSION['flash'] = '❌ User account not found. Please log in again.';
  header('Location: logout.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $capacity = (int)($_POST['capacity'] ?? 0);
  $contribution = str_replace(',', '', $_POST['contribution_amount'] ?? '0');
  $cycle = $_POST['cycle_interval'] ?? '';
  $collateral = str_replace(',', '', $_POST['collateral_reserve_percent'] ?? '0');

  $old['name'] = htmlspecialchars($name, ENT_QUOTES);
  $old['capacity'] = $capacity;
  $old['contribution_amount'] = htmlspecialchars($contribution, ENT_QUOTES);
  $old['cycle_interval'] = $cycle;
  $old['collateral_reserve_percent'] = htmlspecialchars($collateral, ENT_QUOTES);

  if ($name === '') $errors[] = 'Please provide a group name.';
  if ($capacity < 2) $errors[] = 'Capacity must be at least 2.';
  if (!is_numeric($contribution) || (float)$contribution <= 0) $errors[] = 'Contribution amount must be a positive number.';
  if (!in_array($cycle, $intervals)) $errors[] = 'Invalid cycle interval.';
  if (!is_numeric($collateral) || (float)$collateral < 0 || (float)$collateral > 100) $errors[] = 'Collateral percent must be between 0 and 100.';

  if (empty($errors)) {
    try {
      $pdo->beginTransaction();

      // Double-check user exists before insert
      $verifyStmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
      $verifyStmt->execute([$userId]);
      if (!$verifyStmt->fetch()) {
        throw new Exception('User account not found in database.');
      }

      // Insert group as active immediately. Admin is added as the first member.
      $stmt = $pdo->prepare('INSERT INTO tontine_groups (name, admin_user_id, capacity, contribution_amount, cycle_interval, collateral_reserve_percent, status, creation_paid) VALUES (?,?,?,?,?,?,?,0)');
      $stmt->execute([$name, $userId, $capacity, number_format((float)$contribution,2,'.',''), $cycle, number_format((float)$collateral,2,'.',''), 'active']);
      $groupId = $pdo->lastInsertId();

      $memberStmt = $pdo->prepare('INSERT IGNORE INTO group_members (group_id,user_id,is_admin,status,is_new_member) VALUES (?,?,?,?,0)');
      $memberStmt->execute([$groupId, $userId, 1, 'active']);
      $walletStmt = $pdo->prepare('INSERT IGNORE INTO wallets (group_id,user_id,rotational_balance,savings_balance) VALUES (?,?,0.00,0.00)');
      $walletStmt->execute([$groupId, $userId]);

      $pdo->commit();

      // After group creation: optional immediate payment
      $payNow = isset($_POST['pay_now']) && $_POST['pay_now'] === '1';
      $paymentNetwork = $_POST['payment_network'] ?? 'OTHER';
      $msisdn = trim($_POST['msisdn'] ?? '');

      if ($payNow) {
        // basic msisdn validation
        if ($msisdn === '') {
          $_SESSION['flash'] = 'Group created. Payment failed: missing MSISDN.';
          header('Location: group-details.php?id=' . (int)$groupId);
          exit;
        }
        try {
          $fee = 5000.00;
          $gatewayRef = 'CREATION_' . time() . '_' . bin2hex(random_bytes(4));
          $tx = $pdo->prepare('INSERT INTO transactions (gateway_ref, network, msisdn, user_id, group_id, amount, status, metadata) VALUES (?,?,?,?,?,?,"success",NULL)');
          $tx->execute([$gatewayRef, strtoupper($paymentNetwork), $msisdn, $userId, $groupId, number_format($fee,2,'.','')]);

          // mark group as creation_paid
          $u = $pdo->prepare('UPDATE tontine_groups SET creation_paid = 1 WHERE id = ?');
          $u->execute([$groupId]);

          $_SESSION['flash'] = '✅ Group created and creation fee paid. Your group is active.';
          header('Location: group-details.php?id=' . (int)$groupId);
          exit;
        } catch (PDOException $e) {
          error_log('Payment during create_group failed: ' . $e->getMessage());
          $_SESSION['flash'] = 'Group created but payment failed. Please pay from group details.';
          header('Location: group-details.php?id=' . (int)$groupId);
          exit;
        }
      }

      $_SESSION['flash'] = '✅ Group created. You can pay the creation fee later from group details.';
      header('Location: group-details.php?id=' . (int)$groupId);
      exit;
    } catch (PDOException $e) {
      $pdo->rollBack();
      error_log('Database error in create_group: ' . $e->getMessage());
      if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        $errors[] = 'Database error: User account issue. Please log out and log in again.';
      } else {
        $errors[] = 'Database error while creating group: ' . $e->getMessage();
      }
    } catch (Exception $e) {
      $pdo->rollBack();
      error_log('create_group error: ' . $e->getMessage());
      $errors[] = $e->getMessage();
    }
  }
}

?>

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
      <div class="card auth-card shadow-sm">
        <div class="card-body">
          <h4 class="mb-3">Create new Tontine Group</h4>
          <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . htmlspecialchars($e,ENT_QUOTES) . '</li>';?></ul></div>
          <?php endif; ?>
          <form method="post" action="create_group.php">
            <div class="mb-3">
              <label class="form-label">Group Name</label>
              <input type="text" name="name" class="form-control" value="<?php echo $old['name']; ?>" required>
              <small class="form-text text-muted">e.g., Chama Alpha, Women's Savings Group</small>
            </div>
            <div class="row">
              <div class="mb-3 col-6">
                <label class="form-label">Capacity (max members)</label>
                <input type="number" name="capacity" class="form-control" value="<?php echo $old['capacity']; ?>" min="2" required>
              </div>
              <div class="mb-3 col-6">
                <label class="form-label">Contribution Amount (XAF)</label>
                <input type="text" name="contribution_amount" class="form-control" value="<?php echo $old['contribution_amount']; ?>" required>
              </div>
            </div>
            <div class="row">
              <div class="mb-3 col-6">
                <label class="form-label">Cycle Interval</label>
                <select name="cycle_interval" class="form-select">
                  <?php foreach ($intervals as $i): ?>
                    <option value="<?php echo $i; ?>" <?php echo ($old['cycle_interval'] === $i) ? 'selected' : ''; ?>><?php echo ucfirst($i); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="mb-3 col-6">
                <label class="form-label">Collateral Reserve (%)</label>
                <input type="text" name="collateral_reserve_percent" class="form-control" value="<?php echo $old['collateral_reserve_percent']; ?>">
                <small class="form-text text-muted">Security buffer (0-100%)</small>
              </div>
            </div>

              <hr>
              <h6>Creation Fee</h6>
              <p class="small text-muted">A small creation fee is required to publish the group. You can pay now or later from your dashboard.</p>
              <div class="mb-3">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="pay_now" id="payNowNo" value="0" checked>
                  <label class="form-check-label" for="payNowNo">Pay later</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="pay_now" id="payNowYes" value="1">
                  <label class="form-check-label" for="payNowYes">Pay now</label>
                </div>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <label class="form-label">Payment Network</label>
                  <select name="payment_network" class="form-select">
                    <option value="MTN">MTN</option>
                    <option value="ORANGE">ORANGE</option>
                    <option value="OTHER">Other</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">MSISDN (for payment)</label>
                  <input type="text" name="msisdn" class="form-control" placeholder="e.g., +237650000000">
                </div>
              </div>
            <div class="d-grid">
              <button class="btn btn-cta">Create Group</button>
            </div>
          </form>
          <p class="mt-3 text-center"><a href="dashboard.php">← Back to dashboard</a></p>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
