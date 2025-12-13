<?php
// process/login_process.php
session_start();
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../classes/User.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if(empty($email) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Veuillez remplir tous les champs'
        ]);
        exit();
    }
    
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    
    $result = $user->login($email, $password);
    
    if($result) {
        $_SESSION['user_id'] = $result['id'];
        $_SESSION['user_nom'] = $result['nom'];
        $_SESSION['user_prenom'] = $result['prenom'];
        $_SESSION['user_email'] = $result['email'];
        $_SESSION['user_role'] = $result['role'];
        $_SESSION['user_service'] = $result['service'];
        $_SESSION['logged_in'] = true;
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $result['id'],
                'nom' => $result['nom'],
                'prenom' => $result['prenom'],
                'email' => $result['email'],
                'role' => $result['role'],
                'service' => $result['service']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Email ou mot de passe incorrect'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode non autorisée'
    ]);
}
?>