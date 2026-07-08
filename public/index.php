<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>
<main class="container-fluid px-0">
  <section class="auth-hero d-flex align-items-stretch">
    <div class="hero-left d-none d-md-flex flex-column justify-content-center p-5 text-white">
      <h1 class="display-5 fw-bold">DigiTon</h1>
      <p class="lead">Multi-tenant digital tontine (ROSCA) management — secure, transparent, and simple.</p>
      <ul class="feature-list mt-4">
        <li>Create & manage tontine groups</li>
        <li>Dual wallets: Rotational & Savings</li>
        <li>Invite members via secure links</li>
        <li>Simulated payment integrations (MTN/Orange)</li>
      </ul>
      <div class="mt-4">
        <a href="register.php" class="btn btn-cta me-2">Get Started</a>
        <a href="login.php" class="btn btn-outline-light">Sign In</a>
      </div>
    </div>

    <div class="hero-right d-flex align-items-center justify-content-center p-4">
      <div class="card auth-card shadow-sm">
        <div class="card-body text-center">
          <h3 class="card-title mb-2">Welcome to DigiTon</h3>
          <p class="text-muted">Manage your group's contributions, payouts, and compliance from one beautiful dashboard.</p>
      <div class="d-grid gap-2 d-md-block mt-4">
            <a href="register.php" class="btn btn-primary me-2">Create Account</a>
            <a href="login.php" class="btn btn-outline-secondary">Already have an account?</a>
            <br><br>
            <small class="text-muted d-block"><a href="test-workflow.php">🧪 View test workflow</a></small>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
