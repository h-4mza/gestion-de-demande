<?php
// ==========================================
// process/admin/delete_type.php
// ==========================================
session_start();
header('Content-Type: application/json');

require_once '../../config/Database.php';
require_once '../../classes/TypeBesoin.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'administrateur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $database = new Database();
    $db = $database->getConnection();
    $type = new TypeBesoin($db);
    
    $type->id = $data['id'];
    
    if($type->delete()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur de suppression']);
    }
}
?>