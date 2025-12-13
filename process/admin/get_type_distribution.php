<?php
// ==========================================
// process/admin/get_type_distribution.php
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

$query = "SELECT t.nom as type_nom, COUNT(d.id) as total 
          FROM demandes d 
          JOIN types_besoins t ON d.type_id = t.id 
          GROUP BY t.id 
          ORDER BY total DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$distribution = $stmt->fetchAll();

echo json_encode(['success' => true, 'distribution' => $distribution]);
?>