<?php
// process/check_auth.php - Vérifier l'authentification
session_start();
header('Content-Type: application/json');

if(isset($_SESSION['user_id']) && $_SESSION['logged_in']) {
    echo json_encode([
        'authenticated' => true,
        'user' => [
            'id' => $_SESSION['user_id'],
            'nom' => $_SESSION['user_nom'],
            'prenom' => $_SESSION['user_prenom'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'service' => $_SESSION['user_service']
        ]
    ]);
} else {
    echo json_encode(['authenticated' => false]);
}
?>