<?php
// Test guidé - pas besoin d'authentification
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
  <div class="row justify-content-center mb-5">
    <div class="col-12 col-lg-8">
      <h2 class="mb-2">✅ DigiTon Test Workflow</h2>
      <p class="text-muted">Testé suite complète en ~5 minutes</p>
    </div>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      
      <!-- Step 1 -->
      <div class="card mb-3 border-left-primary">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge bg-primary me-2">1</span> Créer données de test
          </h5>
          <p class="mb-3">Générer 3 utilisateurs de test et 1 groupe automatiquement:</p>
          <a href="setup.php" class="btn btn-primary" target="_blank">→ Lancer setup.php</a>
          <p class="mt-2 mb-0">
            <small class="text-muted">
              Crée: alice@, jean@, marie@ + groupe "Chama Alpha"<br>
              Vous recevrez email/mot de passe pour chaque compte
            </small>
          </p>
        </div>
      </div>

      <!-- Step 2 -->
      <div class="card mb-3 border-left-success">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge bg-success me-2">2</span> Première connexion
          </h5>
          <p class="mb-3">Se connecter avec un compte de test:</p>
          <a href="login.php" class="btn btn-success">→ Aller à la connexion</a>
          <p class="mt-2 mb-0">
            <small class="text-muted">
              Utilisez <strong>alice@</strong> (vous êtes admin du groupe)
            </small>
          </p>
        </div>
      </div>

      <!-- Step 3 -->
      <div class="card mb-3 border-left-info">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge bg-info me-2">3</span> Explorer le dashboard
          </h5>
          <p class="mb-3">Voir vos groupes et portefeuilles:</p>
          <a href="dashboard.php" class="btn btn-info">→ Voir dashboard</a>
          <p class="mt-2 mb-0">
            <small class="text-muted">
              Affiche: groupes (cartes), balances totales, transactions récentes
            </small>
          </p>
        </div>
      </div>

      <!-- Step 4 -->
      <div class="card mb-3 border-left-warning">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge bg-warning me-2">4</span> Vérifier le portefeuille
          </h5>
          <p class="mb-3">Voir double portefeuille (Rotational + Savings):</p>
          <a href="wallet.php" class="btn btn-warning">→ Voir portefeuille</a>
          <p class="mt-2 mb-0">
            <small class="text-muted">
              Montre balances par groupe + historique transactions
            </small>
          </p>
        </div>
      </div>

      <!-- Step 5 -->
      <div class="card mb-3 border-left-secondary">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge bg-secondary me-2">5</span> Générer une invitation
          </h5>
          <p class="mb-3">Créer un lien d'invitation pour ajouter membres (admin only):</p>
          <ol class="mb-3 ps-3">
            <li>Allez à <strong>Dashboard</strong></li>
            <li>Cliquez <strong>Manage</strong> sur "Chama Alpha"</li>
            <li>Sélectionnez <strong>Invites</strong></li>
            <li>Cliquez <strong>Create Invite</strong></li>
          </ol>
          <p class="mb-0">
            <small class="text-muted">
              Le lien d'invitation vous permet de partager le groupe avec d'autres
            </small>
          </p>
        </div>
      </div>

      <!-- Step 6 -->
      <div class="card mb-3 border-left-danger">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge bg-danger me-2">6</span> Rejoindre le groupe (2e compte)
          </h5>
          <p class="mb-3">Tester le flux de rejoindre en tant qu'autre utilisateur:</p>
          <ol class="mb-3 ps-3">
            <li><strong>Déconnectez-vous</strong> (Logout)</li>
            <li><strong>Connectez-vous</strong> avec <strong>jean@</strong> (Jean123456)</li>
            <li>Collez le lien d'invitation dans navigateur (ou cliquez depuis email)</li>
            <li>Acceptez <strong>Join Group</strong></li>
          </ol>
          <p class="mb-0">
            <small class="text-muted">
              Jean peut maintenant voir le groupe dans son dashboard
            </small>
          </p>
        </div>
      </div>

      <!-- Step 7 -->
      <div class="card mb-3 border-left-dark">
        <div class="card-body">
          <h5 class="card-title">
            <span class="badge bg-dark me-2">7</span> Voir l'historique complet
          </h5>
          <p class="mb-3">Accédez à la page des transactions:</p>
          <a href="transactions.php" class="btn btn-dark">→ Voir transactions</a>
          <p class="mt-2 mb-0">
            <small class="text-muted">
              Montre toutes les transactions avec stats (succès, en attente, échouées)
            </small>
          </p>
        </div>
      </div>

      <!-- Complets -->
      <div class="alert alert-success mt-4">
        <h6 class="mb-2">🎉 Flux complétés!</h6>
        <p class="mb-0">
          Vous avez testé: <strong>signup → login → dashboard → wallet → transactions → invites → join</strong>
        </p>
      </div>

      <div class="alert alert-info">
        <h6 class="mb-2">💡 Prochaines étapes</h6>
        <ul class="mb-0">
          <li>Intégration paiement MTN MoMo / Orange Money</li>
          <li>Moteur de rotation (assigner payouts automatiquement)</li>
          <li>Système de pénalités (défaut contribution)</li>
          <li>Admin dashboard (vue system admin)</li>
        </ul>
      </div>

      <p class="text-center mt-4">
        <a href="../public/" class="btn btn-outline-secondary">← Retour à l'accueil</a>
      </p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
