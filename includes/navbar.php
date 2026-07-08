<?php
// Simple navbar used across public pages
?>
<?php
// Vertical sidebar (replaces horizontal navbar)
// Expects session_start() in header.php
$userId = $_SESSION['user_id'] ?? null;
$userName = $_SESSION['user_name'] ?? 'Member';
$userAvatar = $_SESSION['user_avatar'] ?? null; // optional

// compute initials
$initials = '';
if ($userName) {
  $parts = explode(' ', trim($userName));
  $initials = strtoupper(substr($parts[0],0,1) . (isset($parts[1]) ? substr($parts[1],0,1) : ''));
}

?>

<!-- Desktop Sidebar -->
<aside class="app-sidebar d-none d-lg-flex" id="appSidebar">
  <div class="sidebar-top text-center py-4">
    <?php if ($userAvatar): ?>
      <img src="<?php echo htmlspecialchars($userAvatar); ?>" alt="profile" class="rounded-circle" width="84" height="84">
    <?php else: ?>
      <div class="profile-placeholder rounded-circle d-inline-flex align-items-center justify-content-center" style="width:84px;height:84px;font-weight:700;font-size:1.1rem;background:linear-gradient(135deg,var(--primary-gradient-start),var(--primary-gradient-end));color:#fff"><?php echo $initials; ?></div>
    <?php endif; ?>
    <div class="mt-2 text-white fw-semibold"><?php echo htmlspecialchars($userName); ?></div>
  </div>

  <nav class="sidebar-nav mt-4">
    <ul class="list-unstyled">
      <li><a href="dashboard.php" class="nav-link text-white"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
      <li><a href="wallet.php" class="nav-link text-white"><i class="bi bi-wallet2 me-2"></i> Wallet</a></li>
      <li><a href="create_group.php" class="nav-link text-white"><i class="bi bi-plus-square me-2"></i> Create Group</a></li>
      <li><a href="invites.php" class="nav-link text-white"><i class="bi bi-envelope-paper me-2"></i> Invites</a></li>
      <li><a href="transactions.php" class="nav-link text-white"><i class="bi bi-receipt me-2"></i> Transactions</a></li>
      <li><a href="groups.php" class="nav-link text-white"><i class="bi bi-people me-2"></i> Groups</a></li>
      <li><a href="chats.php" class="nav-link text-white"><i class="bi bi-chat-dots me-2"></i> Chats</a></li>
      <?php if (in_array($_SESSION['user_role'] ?? 'user', ['admin','super_admin'])): ?>
        <li><a href="admin.php" class="nav-link text-white"><i class="bi bi-shield-lock me-2"></i> Admin</a></li>
      <?php endif; ?>
      <li><a href="profile.php" class="nav-link text-white"><i class="bi bi-person-circle me-2"></i> Profile</a></li>
      <li><a href="settings.php" class="nav-link text-white"><i class="bi bi-gear me-2"></i> Settings</a></li>
      <li class="mt-3"><a href="logout.php" class="nav-link text-white"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
    </ul>
  </nav>
</aside>

<!-- Mobile Bottom Navigation -->
<nav class="bottom-nav d-lg-none">
  <a href="dashboard.php" class="bottom-nav-item" title="Dashboard"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
  <a href="wallet.php" class="bottom-nav-item" title="Wallet"><i class="bi bi-wallet2"></i><span>Wallet</span></a>
  <a href="groups.php" class="bottom-nav-item" title="Groups"><i class="bi bi-people"></i><span>Groups</span></a>
  <a href="transactions.php" class="bottom-nav-item" title="Transactions"><i class="bi bi-receipt"></i><span>Transactions</span></a>
  <a href="invites.php" class="bottom-nav-item" title="Invites"><i class="bi bi-envelope-paper"></i><span>Invites</span></a>
  <a href="profile.php" class="bottom-nav-item" title="Profile"><i class="bi bi-person-circle"></i><span>Profile</span></a>
</nav>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="container mt-3">
    <div class="alert alert-info alert-dismissible fade show" role="alert">
      <?php echo $_SESSION['flash']; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  </div>
  <?php unset($_SESSION['flash']); ?>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const toggle = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('appSidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function(){
      sidebar.classList.toggle('open');
    });
  }
});
</script>
<!-- Begin main wrapper: centers content relative to sidebar -->
<main class="app-main">
  <div class="app-content">
<!-- Page content starts here -->
