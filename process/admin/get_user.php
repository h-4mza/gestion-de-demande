<?php
// ==========================================
// process/admin/get_user.php
// ==========================================
session_start();
header('Content-Type: application/json');

require_once '../../config/Database.php';
require_once '../../classes/User.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'administrateur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if(isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $user->id = $_GET['id'];
    $userData = $user->readOne();
    
    if($userData) {
        echo json_encode(['success' => true, 'user' => $userData]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
}
?>