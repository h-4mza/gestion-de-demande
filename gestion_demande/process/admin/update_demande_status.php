<?php 
// ==========================================
// process/admin/update_demande_status.php
// ==========================================
session_start();
header('Content-Type: application/json');

require_once '../../config/Database.php';
require_once '../../classes/Demande.php';
require_once '../../classes/Notification.php'; // IMPORTANT

// Vérif admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// Vérif méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données envoyées par fetch()
$data = json_decode(file_get_contents('php://input'), true);

$id     = isset($data['id']) ? (int)$data['id'] : 0;
$statut = $data['statut'] ?? '';

if ($id <= 0 || empty($statut)) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
}

// Liste des statuts permis pour l'ADMIN
$allowedStatus = [
    'en_cours_de_traitement',
    'traitee'
];

if (!in_array($statut, $allowedStatus, true)) {
    echo json_encode(['success' => false, 'message' => 'Statut interdit pour un administrateur']);
    exit();
}

$database = new Database();
$db       = $database->getConnection();
$demande  = new Demande($db);

// Vérifier que la demande existe
$query = $db->prepare("SELECT demandeur_id FROM demandes WHERE id = :id");
$query->bindParam(':id', $id, PDO::PARAM_INT);
$query->execute();
$infos = $query->fetch();

if (!$infos) {
    echo json_encode(['success' => false, 'message' => 'Demande introuvable']);
    exit();
}

// Mettre à jour le statut
$demande->id = $id;

if (!$demande->updateStatut($statut)) {
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    exit();
}

// -----------------------------------------------
// CRÉATION DE LA NOTIFICATION POUR LE DEMANDEUR
// -----------------------------------------------
$demandeurId = (int)$infos['demandeur_id'];

switch ($statut) {
    case 'en_cours_de_traitement':
        $msg = "Votre demande n°{$id} est maintenant en cours de traitement.";
        break;

    case 'traitee':
        $msg = "Votre demande n°{$id} a été traitée avec succès.";
        break;

    default:
        $msg = "Le statut de votre demande n°{$id} a été mis à jour.";
        break;
}

$notif = new Notification($db);
$notif->user_id = $demandeurId;  // demandeur
$notif->demande_id = $id;
$notif->message = "Votre demande n°{$id} a été traitée.";
$notif->create();


echo json_encode([
    'success' => true,
    'message' => 'Statut mis à jour et notification envoyée.'
]);
?>
