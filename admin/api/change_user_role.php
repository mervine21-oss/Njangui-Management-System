<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$uid = $_SESSION['user_id'] ?? null;
if (!$uid) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }

$r = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
$r->execute([$uid]);
$role = $r->fetchColumn();
if ($role !== 'super_admin') { http_response_code(403); echo json_encode(['error' => 'Access denied']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$newRole = trim($input['role'] ?? '');
$validRoles = ['user', 'admin', 'super_admin'];

if (!$userId || !in_array($newRole, $validRoles, true)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid request']);
  exit;
}

if ($userId === $uid) {
  http_response_code(400);
  echo json_encode(['error' => 'You cannot change your own role']);
  exit;
}

try {
  $update = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
  $update->execute([$newRole, $userId]);
  echo json_encode(['ok' => true]);
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['error' => 'Database error']);
}
