<?php
// process/create_demande.php - Créer une nouvelle demande
session_start();
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../classes/Demande.php';
require_once '../classes/PieceJointe.php';
require_once '../classes/Notification.php';
require_once '../classes/User.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

try {
    $database = new Database();
    $db       = $database->getConnection();

    // 1. Données du formulaire
    $type_id     = isset($_POST['type_id']) ? (int) $_POST['type_id'] : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $urgence     = isset($_POST['urgence']) ? trim($_POST['urgence']) : '';

    if ($type_id <= 0 || $description === '' || $urgence === '') {
        throw new Exception("Champs obligatoires manquants.");
    }

    // 2. Création de la demande
    $demande = new Demande($db);
    $demande->demandeur_id = (int) $_SESSION['user_id'];
    $demande->type_id      = $type_id;
    $demande->description  = $description;
    $demande->urgence      = $urgence; // 'faible' | 'moyenne' | 'urgente'

    if (!$demande->create()) {
        throw new Exception("Erreur lors de la création de la demande.");
    }

    $demandeId = $demande->id;

    // 3. Pièces jointes (optionnel)
    if (!empty($_FILES['files']) && is_array($_FILES['files']['name'])) {
        $upload_dir = '../assets/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $pieceJointe = new PieceJointe($db);

        foreach ($_FILES['files']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
                continue;
            }

            $originalName = $_FILES['files']['name'][$key];
            $safeName     = time() . '_' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $originalName);
            $file_path    = $upload_dir . $safeName;

            if (move_uploaded_file($tmp_name, $file_path)) {
                $pieceJointe->demande_id     = $demandeId;
                $pieceJointe->nom_fichier    = $originalName;
                $pieceJointe->chemin_fichier = $file_path;
                $pieceJointe->taille         = $_FILES['files']['size'][$key];
                $pieceJointe->type_mime      = $_FILES['files']['type'][$key];
                $pieceJointe->create();
            }
        }
    }

    // 4. Notifications 
    try {
        // 4.1 Notif au DEMANDEUR
        $notif = new Notification($db);
        $notif->user_id    = (int) $_SESSION['user_id'];
        $notif->demande_id = $demandeId;
        $notif->message    = "Votre demande n°{$demandeId} a été créée.";
        $notif->create();

        // Récupérer info du demandeur pour le nom + chef
        $user = new User($db);
        $user->id = (int) $_SESSION['user_id'];
        $demandeur = $user->readOne(); // contient chef_id, nom, prenom, etc.

        // 4.2 Logique NOTIF selon urgence
        if ($urgence === 'urgente') {
            // URGENTE → directement ADMIN(S)
            $stmtAdmins = $db->prepare("SELECT id FROM users WHERE role = 'administrateur'");
            $stmtAdmins->execute();
            $admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);

            foreach ($admins as $admin) {
                $notifAdmin = new Notification($db);
                $notifAdmin->user_id    = (int) $admin['id'];
                $notifAdmin->demande_id = $demandeId;

                $fullName = '';
                if ($demandeur) {
                    $fullName = trim(($demandeur['prenom'] ?? '') . ' ' . ($demandeur['nom'] ?? ''));
                }

                $notifAdmin->message = "Nouvelle demande URGENTE n°{$demandeId} à traiter"
                    . ($fullName ? " (demandeur : {$fullName})" : '')
                    . ".";

                $notifAdmin->create();
            }
        } else {
            // NON URGENTE → VALIDATEUR (chef)
            if ($demandeur && !empty($demandeur['chef_id'])) {
                $validateurId = (int) $demandeur['chef_id'];

                $fullName = trim(($demandeur['prenom'] ?? '') . ' ' . ($demandeur['nom'] ?? ''));

                $notifVal = new Notification($db);
                $notifVal->user_id    = $validateurId;
                $notifVal->demande_id = $demandeId;
                $notifVal->message    = "Nouvelle demande n°{$demandeId} à valider pour {$fullName}.";
                $notifVal->create();
            }
        }
    } catch (Throwable $eNotif) {
       
    }

    // 5. Réponse OK
    echo json_encode([
        'success'    => true,
        'message'    => 'Demande créée avec succès',
        'demande_id' => $demandeId
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
