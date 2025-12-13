<?php
// process/get_demandes.php - Récupérer les demandes de l'utilisateur
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

$stmt = $demande->readByUser($_SESSION['user_id']);
$demandes = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'demandes' => $demandes
]);
?>