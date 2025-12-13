<?php
// process/logout_process.php - Déconnexion
session_start();
session_destroy();
header('Location: ../login.php?success=Déconnexion réussie');
exit();
?>