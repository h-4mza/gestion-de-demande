<?php
// process/get_current_user.php - Retourne l'utilisateur courant
session_start();
header('Content-Type: application/json');

if (isset($_SESSION['user_id']) && !empty($_SESSION['logged_in'])) {
    echo json_encode([
        'success' => true,
        'user' => [
            'id'      => $_SESSION['user_id'],
            'nom'     => $_SESSION['user_nom'],
            'prenom'  => $_SESSION['user_prenom'],
            'email'   => $_SESSION['user_email'],
            'role'    => $_SESSION['user_role'],
            'service' => $_SESSION['user_service']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Non authentifié'
    ]);
}
?>
