<?php
// process/get_stats.php - Récupérer les statistiques
session_start();
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../classes/Demande.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$demande = new Demande($db);

$stats = $demande->getStatistics($_SESSION['user_id'], $_SESSION['user_role']);

echo json_encode([
    'success' => true,
    'stats' => $stats
]);
?>