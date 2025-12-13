<?php
// process/get_demande_details.php - Détails d'une demande
session_start();
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../classes/Demande.php';
require_once '../classes/Validation.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID manquant ou invalide']);
    exit();
}

$database   = new Database();
$db         = $database->getConnection();
$demande    = new Demande($db);
$validation = new Validation($db);

$demande->id = $id;
$details     = $demande->readOne();

if (!$details) {
    echo json_encode(['success' => false, 'message' => 'Demande non trouvée']);
    exit();
}

// 1) Contrôle d'accès de base :
//    - le demandeur ne voit que ses propres demandes
if ($_SESSION['user_role'] === 'demandeur') {
    if ((int) $details['demandeur_id'] !== (int) $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Accès refusé']);
        exit();
    }
}

// 2) Si un validateur ouvre une demande encore en_attente,
//    on la passe en en_cours_de_validation
if ($_SESSION['user_role'] === 'validateur') {
    if ($details['statut'] === 'en_attente') {
        $demande->updateStatut('en_cours_de_validation');
        $details['statut'] = 'en_cours_de_validation';
    }
}

// 3) Récupérer l'historique des validations
$stmt        = $validation->getByDemande($id);
$validations = $stmt->fetchAll();

echo json_encode([
    'success'     => true,
    'demande'     => $details,
    'validations' => $validations
]);
?>
