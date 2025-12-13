<?php
// ==========================================
// process/admin/update_user.php
// ==========================================
session_start();
header('Content-Type: application/json');

require_once '../../config/Database.php';
require_once '../../classes/User.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'administrateur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $user->id = $_POST['id'];
    $user->nom = $_POST['nom'];
    $user->prenom = $_POST['prenom'];
    $user->email = $_POST['email'];
    $user->role = $_POST['role'];
    $user->service = $_POST['service'];
    $user->statut = $_POST['statut'];
    $user->chef_id = null;
    
    // Mot de passe optionnel
    if(!empty($_POST['password'])) {
        $user->password = $_POST['password'];
    }
    
    if($user->update()) {
        echo json_encode(['success' => true, 'message' => 'Utilisateur mis à jour']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur de mise à jour']);
    }
}
?>