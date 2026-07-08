<?php
// Repair script - fix orphaned data and integrity issues
require_once __DIR__ . '/config/database.php';

echo "<h1>🔧 DigiTon Data Repair Tool</h1>";
echo "<p>This tool will fix data integrity issues.</p>";

// Step 1: Check if there are orphaned groups
echo "<h2>Step 1: Check for orphaned groups</h2>";
$orphaned = $pdo->query("
  SELECT tg.id, tg.name, tg.admin_user_id 
  FROM tontine_groups tg
  LEFT JOIN users u ON tg.admin_user_id = u.id
  WHERE u.id IS NULL
")->fetchAll();

if (!empty($orphaned)) {
  echo "<p>❌ Found " . count($orphaned) . " groups with invalid admin_user_id</p>";
  echo "<table border='1' cellpadding='5'>";
  echo "<tr><th>Group ID</th><th>Group Name</th><th>Invalid Admin ID</th><th>Action</th></tr>";
  foreach ($orphaned as $row) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
    echo "<td>" . $row['admin_user_id'] . "</td>";
    echo "<td>";
    echo "<a href='?delete_group=" . $row['id'] . "' onclick=\"return confirm('Delete group?')\">Delete</a>";
    echo "</td>";
    echo "</tr>";
  }
  echo "</table>";
  
  // Process deletion if requested
  if (!empty($_GET['delete_group'])) {
    $groupId = (int)$_GET['delete_group'];
    try {
      $pdo->beginTransaction();
      
      // Delete in proper order (foreign keys)
      $pdo->prepare("DELETE FROM wallets WHERE group_id = ?")->execute([$groupId]);
      $pdo->prepare("DELETE FROM group_members WHERE group_id = ?")->execute([$groupId]);
      $pdo->prepare("DELETE FROM invites WHERE group_id = ?")->execute([$groupId]);
      $pdo->prepare("DELETE FROM transactions WHERE group_id = ?")->execute([$groupId]);
      $pdo->prepare("DELETE FROM tontine_groups WHERE id = ?")->execute([$groupId]);
      
      $pdo->commit();
      echo "<p style='color: green;'>✅ Group " . $groupId . " deleted successfully</p>";
      echo "<p><a href='repair.php'>← Back to repair tool</a></p>";
    } catch (Exception $e) {
      $pdo->rollBack();
      echo "<p style='color: red;'>❌ Error deleting group: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    exit;
  }
} else {
  echo "<p>✅ No orphaned groups found</p>";
}

// Step 2: Check for orphaned group members
echo "<h2>Step 2: Check for orphaned group members</h2>";
$orphanedMembers = $pdo->query("
  SELECT gm.id, gm.group_id, gm.user_id
  FROM group_members gm
  LEFT JOIN users u ON gm.user_id = u.id
  LEFT JOIN tontine_groups tg ON gm.group_id = tg.id
  WHERE u.id IS NULL OR tg.id IS NULL
")->fetchAll();

if (!empty($orphanedMembers)) {
  echo "<p>❌ Found " . count($orphanedMembers) . " orphaned group memberships</p>";
  try {
    foreach ($orphanedMembers as $member) {
      $pdo->prepare("DELETE FROM group_members WHERE id = ?")->execute([$member['id']]);
    }
    echo "<p style='color: green;'>✅ Deleted " . count($orphanedMembers) . " orphaned memberships</p>";
  } catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error deleting memberships: " . htmlspecialchars($e->getMessage()) . "</p>";
  }
} else {
  echo "<p>✅ No orphaned group members found</p>";
}

// Step 3: Check for orphaned wallets
echo "<h2>Step 3: Check for orphaned wallets</h2>";
$orphanedWallets = $pdo->query("
  SELECT w.id, w.group_id, w.user_id
  FROM wallets w
  LEFT JOIN users u ON w.user_id = u.id
  LEFT JOIN tontine_groups tg ON w.group_id = tg.id
  WHERE u.id IS NULL OR tg.id IS NULL
")->fetchAll();

if (!empty($orphanedWallets)) {
  echo "<p>❌ Found " . count($orphanedWallets) . " orphaned wallets</p>";
  try {
    foreach ($orphanedWallets as $wallet) {
      $pdo->prepare("DELETE FROM wallets WHERE id = ?")->execute([$wallet['id']]);
    }
    echo "<p style='color: green;'>✅ Deleted " . count($orphanedWallets) . " orphaned wallets</p>";
  } catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error deleting wallets: " . htmlspecialchars($e->getMessage()) . "</p>";
  }
} else {
  echo "<p>✅ No orphaned wallets found</p>";
}

// Step 4: Reset with fresh data
echo "<h2>Step 4: Refresh test data</h2>";
echo "<p><a href='setup.php' class='btn btn-primary'>Run setup.php to create fresh test data</a></p>";

// Final status
echo "<h2>✅ Repair complete!</h2>";
echo "<p><a href='debug.php'>← View debug report</a> | <a href='public/'>← Go to app</a></p>";
?>
