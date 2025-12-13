<?php 
// ==========================================
// process/admin/update_demande_status.php
// ==========================================
session_start();
header('Content-Type: application/json');

require_once '../../config/Database.php';
require_once '../../classes/Demande.php';
require_once '../../classes/Notification.php';

// 1. Vérification Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

// 2. Vérification Méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id     = isset($data['id']) ? (int)$data['id'] : 0;
$statut = $data['statut'] ?? '';

if ($id <= 0 || empty($statut)) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit();
}

// 3. Statuts autorisés pour l'Admin (Selon votre nouvelle BDD)
$allowedStatus = ['validee', 'en_cours_de_traitement', 'traitee']; 

if (!in_array($statut, $allowedStatus, true)) {
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit();
}

$database = new Database();
$db       = $database->getConnection();
$demande  = new Demande($db);

// 4. Récupérer les infos actuelles (Service affecté ?)
$query = $db->prepare("SELECT demandeur_id, service_affecte FROM demandes WHERE id = :id");
$query->bindParam(':id', $id, PDO::PARAM_INT);
$query->execute();
$infos = $query->fetch();

if (!$infos) {
    echo json_encode(['success' => false, 'message' => 'Demande introuvable']);
    exit();
}

// --- LOGIQUE DE BLOCAGE ---
// Si on veut passer à "En cours" OU "Traitée", il faut OBLIGATOIREMENT un service
if (in_array($statut, ['en_cours_de_traitement', 'traitee'])) {
    if (empty($infos['service_affecte']) || $infos['service_affecte'] === 'Non affecté') {
        echo json_encode([
            'success' => false, 
            'message' => 'Veuillez d\'abord affecter un service avant de changer le statut.'
        ]);
        exit();
    }
}
// --------------------------

// 5. Mise à jour
$demande->id = $id;
if (!$demande->updateStatut($statut)) {
    echo json_encode(['success' => false, 'message' => 'Erreur technique lors de la mise à jour']);
    exit();
}

// 6. Notification au demandeur
$demandeurId = (int)$infos['demandeur_id'];
$msg = "";

switch ($statut) {
    case 'en_cours_de_traitement':
        $service = $infos['service_affecte'];
        $msg = "Votre demande n°{$id} est maintenant en cours de traitement par le service {$service}.";
        break;
    case 'traitee':
        $msg = "Votre demande n°{$id} a été traitée et clôturée.";
        break;
    case 'validee':
        $msg = "Le statut de votre demande n°{$id} est revenu à 'Validée'.";
        break;
}

if ($msg) {
    $notif = new Notification($db);
    $notif->user_id = $demandeurId;
    $notif->demande_id = $id;
    $notif->message = $msg;
    $notif->create();
}

echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
?>