<?php
// ==========================================
// process/admin/get_top_requesters.php
// ==========================================
session_start();
header('Content-Type: application/json');

require_once '../../config/Database.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'administrateur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$query = "SELECT u.nom, u.prenom, COUNT(d.id) as total 
          FROM demandes d 
          JOIN users u ON d.demandeur_id = u.id 
          GROUP BY u.id 
          ORDER BY total DESC 
          LIMIT 5";

$stmt = $db->prepare($query);
$stmt->execute();
$requesters = $stmt->fetchAll();

echo json_encode(['success' => true, 'requesters' => $requesters]);
?>