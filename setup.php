<?php
/**
 * Setup script — créer les données de test pour DigiTon
 * Accédez à http://localhost/DigiTon/setup.php
 */
require_once __DIR__ . '/config/database.php';

// Vérifier si déjà exécuté
$tableCheck = $pdo->query("SELECT 1 FROM users LIMIT 1");
if ($tableCheck) {
  $existingUser = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
  if ($existingUser > 0) {
    die('Les données de test existent déjà. Supprimez les utilisateurs si vous voulez réinitialiser.');
  }
}

try {
  $pdo->beginTransaction();

  // Créer utilisateurs de test
  $testUsers = [
    [
      'first_name' => 'Alice',
      'last_name' => 'Nkem',
      'email' => 'alice@example.com',
      'password' => 'Alice123456',
      'phone' => '+237650000000',
      'role' => 'super_admin',
    ],
    [
      'first_name' => 'Jean',
      'last_name' => 'Dupont',
      'email' => 'jean@example.com',
      'password' => 'Jean123456',
      'phone' => '+237691111111',
      'role' => 'user',
    ],
    [
      'first_name' => 'Marie',
      'last_name' => 'Martin',
      'email' => 'marie@example.com',
      'password' => 'Marie123456',
      'phone' => '+237692222222',
      'role' => 'user',
    ],
  ];

  foreach ($testUsers as $user) {
    $stmt = $pdo->prepare(
      'INSERT INTO users (first_name,last_name,email,password_hash,phone,role) VALUES (?,?,?,?,?,?)'
    );
    $stmt->execute([
      $user['first_name'],
      $user['last_name'],
      $user['email'],
      password_hash($user['password'], PASSWORD_BCRYPT),
      $user['phone'],
      $user['role'],
    ]);
  }

  // Créer un groupe de test
  $groupStmt = $pdo->prepare(
    'INSERT INTO tontine_groups (name,admin_user_id,capacity,contribution_amount,cycle_interval,collateral_reserve_percent) VALUES (?,?,?,?,?,?)'
  );
  $groupStmt->execute([
    'Chama Alpha',
    1, // alice
    12,
    250000.00,
    'monthly',
    5.00,
  ]);
  $groupId = $pdo->lastInsertId();

  // Ajouter Alice comme admin du groupe
  $memberStmt = $pdo->prepare(
    'INSERT INTO group_members (group_id,user_id,is_admin,status,is_new_member) VALUES (?,?,?,?,?)'
  );
  $memberStmt->execute([$groupId, 1, 1, 'active', 0]);

  // Créer wallet pour Alice
  $walletStmt = $pdo->prepare(
    'INSERT INTO wallets (group_id,user_id,rotational_balance,savings_balance) VALUES (?,?,?,?)'
  );
  $walletStmt->execute([$groupId, 1, 0.00, 0.00]);

  // Générer invite token
  $token = bin2hex(random_bytes(16));
  $inviteStmt = $pdo->prepare(
    'INSERT INTO invites (group_id,token,expires_at,created_by) VALUES (?,?,?,?)'
  );
  $inviteStmt->execute([$groupId, $token, null, 1]);

  $pdo->commit();

  echo '
  <html>
  <head>
    <title>Setup DigiTon</title>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="p-5">
  <div class="container">
    <div class="alert alert-success">
      <h4>✅ Setup completed!</h4>
      <p>Les données de test ont été créées avec succès.</p>
    </div>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>Email</th>
          <th>Mot de passe</th>
          <th>Rôle</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>alice@example.com</td>
          <td>Alice123456</td>
          <td>super_admin</td>
        </tr>
        <tr>
          <td>jean@example.com</td>
          <td>Jean123456</td>
          <td>user</td>
        </tr>
        <tr>
          <td>marie@example.com</td>
          <td>Marie123456</td>
          <td>user</td>
        </tr>
      </tbody>
    </table>
    <p class="mt-4">Groupe de test créé: <strong>Chama Alpha</strong></p>
    <p>Invite token (pour rejoindre): <code>' . htmlspecialchars($token) . '</code></p>
    <div class="mt-4">
      <a href="public/index.php" class="btn btn-primary">Aller à l\'accueil</a>
      <a href="public/login.php" class="btn btn-secondary">Se connecter</a>
    </div>
  </div>
  </body>
  </html>
  ';
} catch (Exception $e) {
  error_log('Setup error: ' . $e->getMessage());
  echo '
  <html>
  <head>
    <title>Setup DigiTon — Erreur</title>
    <meta charset="utf-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="p-5">
  <div class="container">
    <div class="alert alert-danger">
      <h4>❌ Erreur</h4>
      <p>' . htmlspecialchars($e->getMessage()) . '</p>
      <p><strong>Conseil</strong>: Assurez-vous que MySQL est démarré et que la base de données <code>digiton</code> a été importée via <code>sql/schema.sql</code>.</p>
    </div>
  </div>
  </body>
  </html>
  ';
}
