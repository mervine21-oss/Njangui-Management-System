<?php
// Debug script - check database and session state
session_start();
require_once __DIR__ . '/config/database.php';

echo "<h1>🔍 DigiTon Debug Report</h1>";

// 1. Check database connection
echo "<h3>1. Database Connection</h3>";
try {
  $result = $pdo->query("SELECT 1");
  echo "<p>✅ Database connection: OK</p>";
} catch (Exception $e) {
  echo "<p>❌ Database connection: FAILED</p>";
  echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
  exit;
}

// 2. Check users table
echo "<h3>2. Users Table</h3>";
$users = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch();
echo "<p>Total users: <strong>" . $users['cnt'] . "</strong></p>";

if ($users['cnt'] > 0) {
  $userList = $pdo->query("SELECT id, email, first_name, last_name FROM users LIMIT 10");
  echo "<table border='1' cellpadding='5'>";
  echo "<tr><th>ID</th><th>Email</th><th>Name</th></tr>";
  foreach ($userList as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . htmlspecialchars($user['email']) . "</td>";
    echo "<td>" . htmlspecialchars($user['first_name'] . " " . $user['last_name']) . "</td>";
    echo "</tr>";
  }
  echo "</table>";
} else {
  echo "<p>⚠️ No users found! Run setup.php first.</p>";
}

// 3. Check session
echo "<h3>3. Current Session</h3>";
if (!empty($_SESSION['user_id'])) {
  echo "<p>Logged in as: <strong>User ID " . $_SESSION['user_id'] . "</strong></p>";
  
  // Check if user exists in DB
  $check = $pdo->prepare("SELECT id, email, first_name FROM users WHERE id = ?");
  $check->execute([$_SESSION['user_id']]);
  $user = $check->fetch();
  
  if ($user) {
    echo "<p>✅ User found in database: " . htmlspecialchars($user['email']) . "</p>";
  } else {
    echo "<p>❌ User ID " . $_SESSION['user_id'] . " NOT found in database!</p>";
    echo "<p><strong>This is the problem!</strong> Session has invalid user_id.</p>";
    echo "<p><a href='public/logout.php'>Logout</a> and try logging in again.</p>";
  }
} else {
  echo "<p>⚠️ Not logged in</p>";
}

// 4. Check groups table
echo "<h3>4. Tontine Groups</h3>";
$groups = $pdo->query("SELECT COUNT(*) as cnt FROM tontine_groups")->fetch();
echo "<p>Total groups: <strong>" . $groups['cnt'] . "</strong></p>";

if ($groups['cnt'] > 0) {
  $groupList = $pdo->query("SELECT id, name, admin_user_id FROM tontine_groups LIMIT 10");
  echo "<table border='1' cellpadding='5'>";
  echo "<tr><th>ID</th><th>Name</th><th>Admin User ID</th><th>Admin Exists?</th></tr>";
  foreach ($groupList as $group) {
    $adminCheck = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $adminCheck->execute([$group['admin_user_id']]);
    $adminExists = $adminCheck->fetch() ? "✅ Yes" : "❌ No";
    
    echo "<tr>";
    echo "<td>" . $group['id'] . "</td>";
    echo "<td>" . htmlspecialchars($group['name']) . "</td>";
    echo "<td>" . $group['admin_user_id'] . "</td>";
    echo "<td>" . $adminExists . "</td>";
    echo "</tr>";
  }
  echo "</table>";
}

// 5. Check for orphaned groups (admin_user_id doesn't exist)
echo "<h3>5. Integrity Check</h3>";
$orphaned = $pdo->query("
  SELECT tg.id, tg.name, tg.admin_user_id 
  FROM tontine_groups tg
  LEFT JOIN users u ON tg.admin_user_id = u.id
  WHERE u.id IS NULL
")->fetchAll();

if (!empty($orphaned)) {
  echo "<p>❌ Found " . count($orphaned) . " groups with invalid admin_user_id:</p>";
  echo "<table border='1' cellpadding='5'>";
  echo "<tr><th>Group ID</th><th>Group Name</th><th>Invalid Admin ID</th></tr>";
  foreach ($orphaned as $row) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . $row['admin_user_id'] . "</td>";
    echo "</tr>";
  }
  echo "</table>";
  echo "<p><strong>Fix:</strong> Delete these groups or ensure admin_user_id exists in users table.</p>";
} else {
  echo "<p>✅ All groups have valid admin_user_id</p>";
}

// 6. Recommendations
echo "<h3>6. Recommendations</h3>";
echo "<ol>";
if ($users['cnt'] === 0) {
  echo "<li>❌ <strong>Run setup.php</strong> to create test users and groups</li>";
  echo "<li><a href='setup.php'>→ Click here to run setup.php</a></li>";
}
if (!empty($_SESSION['user_id'])) {
  $userCheck = $pdo->prepare("SELECT id FROM users WHERE id = ?");
  $userCheck->execute([$_SESSION['user_id']]);
  if (!$userCheck->fetch()) {
    echo "<li>❌ <strong>Your session is invalid</strong> - user_id doesn't exist in DB</li>";
    echo "<li><a href='public/logout.php'>Logout</a> and login again</li>";
  }
}
echo "<li>✅ Once fixed, try creating a group: <a href='public/create_group.php'>→ Create Group</a></li>";
echo "</ol>";

?>
<hr>
<p><small>Debug script - delete before production</small></p>
