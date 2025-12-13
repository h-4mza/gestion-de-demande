<?php
// ==========================================
// process/admin/get_all_types.php
// ==========================================
session_start();
header('Content-Type: application/json');

require_once '../../config/Database.php';
require_once '../../classes/TypeBesoin.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'administrateur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$type = new TypeBesoin($db);

$stmt = $type->readAll();
$types = $stmt->fetchAll();

echo json_encode(['success' => true, 'types' => $types]);
?>