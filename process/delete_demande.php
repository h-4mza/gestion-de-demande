<?php
// process/delete_demande.php - Supprimer une demande
session_start();
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../classes/Demande.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if(isset($data['id'])) {
        $database = new Database();
        $db = $database->getConnection();
        $demande = new Demande($db);
        
        $demande->id = $data['id'];
        
        if($demande->delete()) {
            echo json_encode([
                'success' => true,
                'message' => 'Demande supprimée avec succès'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Impossible de supprimer la demande'
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ID manquant']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>