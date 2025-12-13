<?php
// process/update_demande.php
session_start();
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../classes/Demande.php';

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. Récupération des données
    $id = $_POST['id'] ?? null;
    $type_id = $_POST['type_id'] ?? null;
    $description = $_POST['description'] ?? null;
    $urgence = $_POST['urgence'] ?? null;

    if(!$id || !$type_id || !$description || !$urgence) {
        echo json_encode(['success' => false, 'message' => 'Champs manquants']);
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();
    $demande = new Demande($db);

    // 2. Vérification de sécurité (Propriétaire + Statut)
    // On vérifie que la demande appartient bien à l'utilisateur et qu'elle est "en_attente"
    $checkQuery = "SELECT demandeur_id, statut FROM demandes WHERE id = :id";
    $stmt = $db->prepare($checkQuery);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$row) {
        echo json_encode(['success' => false, 'message' => 'Demande introuvable']);
        exit();
    }

    if($row['demandeur_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit();
    }

    if($row['statut'] !== 'en_attente') {
        echo json_encode(['success' => false, 'message' => 'Impossible de modifier : la demande est déjà traitée ou en cours de validation']);
        exit();
    }

    // 3. Mise à jour
    // On utilise la méthode update() existante dans votre classe Demande
    // (Voir classes/Demande.php qui filtre déjà sur "en_attente")
    $demande->id = $id;
    $demande->type_id = $type_id;
    $demande->description = $description;
    $demande->urgence = $urgence;

    if($demande->update()) {
        echo json_encode(['success' => true, 'message' => 'Demande mise à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>