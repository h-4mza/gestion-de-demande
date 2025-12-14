<?php


session_start();


if(!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}


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
        
        session_destroy();
        header('Location: login.php?error=invalid_role');
        break;
}

exit();
?>