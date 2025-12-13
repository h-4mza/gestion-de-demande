<?php
/**
 * index.php - Point d'entrée de l'application
 * Redirige automatiquement selon le statut de connexion et le rôle
 */

session_start();

// Si l'utilisateur n'est pas connecté, rediriger vers login
if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Rediriger selon le rôle
switch($_SESSION['user_role']) {
    case 'administrateur':
        header('Location: pages/admin/dashboard.php');
        break;
        
    case 'validateur':
        header('Location: pages/validateur/dashboard.php');
        break;
        
    case 'demandeur':
        header('Location: pages/demandeur/dashboard.php');
        break;
        
    default:
        // Rôle invalide, déconnecter
        session_destroy();
        header('Location: login.php?error=invalid_role');
        break;
}

exit();
?>