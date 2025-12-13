<?php
// ==========================================
// process/admin/get_all_users.php
// ==========================================
session_start();
header('Content-Type: application/json');

require_once '../../config/Database.php';
require_once '../../classes/User.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'administrateur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$stmt = $user->readAll();
$users = $stmt->fetchAll();

echo json_encode(['success' => true, 'users' => $users]);
?>