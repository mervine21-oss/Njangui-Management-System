<?php
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$uid = $_SESSION['user_id'] ?? null;
if (!$uid) { http_response_code(401); echo json_encode(['ok'=>false, 'message'=>'Unauthorized']); exit; }

$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
if (!$groupId) { http_response_code(400); echo json_encode(['ok'=>false,'message'=>'Missing group_id']); exit; }

$check = $pdo->prepare('SELECT is_admin FROM group_members WHERE group_id = ? AND user_id = ? AND status = "active" LIMIT 1');
$check->execute([$groupId, $uid]);
$member = $check->fetch();
if (!$member || !$member['is_admin']) { http_response_code(403); echo json_encode(['ok'=>false,'message'=>'Access denied']); exit; }

$reportUrl = '../public/group_report.php?group_id=' . $groupId;
echo json_encode(['ok'=>true,'url'=>$reportUrl]);
