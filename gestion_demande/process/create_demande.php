<?php 
// process/create_demande.php - Créer une nouvelle demande
session_start();
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../classes/Demande.php';
require_once '../classes/Notification.php'; // <--- nouvel include

if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    $demande = new Demande($db);
    
    $demande->demandeur_id = $_SESSION['user_id'];
    $demande->type_id      = $_POST['type_id'];
    $demande->description  = $_POST['description'];
    $demande->urgence      = $_POST['urgence'];   // 'faible' | 'moyenne' | 'urgente'
    
    if($demande->create()) {

        // 1) NOTIFICATION POUR LE DEMANDEUR
        $notif = new Notification($db);
        $notif->user_id    = $_SESSION['user_id'];
        $notif->demande_id = $demande->id;
        $notif->message    = "Votre demande n°{$demande->id} a été créée.";
        $notif->create();

        // 2) SI DEMANDE URGENTE → NOTIF ADMIN(S)
        if ($demande->urgence === 'urgente') {
            // Ici on notifie tous les utilisateurs ayant le rôle 'administrateur'
            $stmtAdmins = $db->prepare("SELECT id FROM users WHERE role = 'administrateur'");
            $stmtAdmins->execute();
            $admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);

            foreach ($admins as $admin) {
                $notifAdmin = new Notification($db);
                $notifAdmin->user_id    = $admin['id'];
                $notifAdmin->demande_id = $demande->id;
                $notifAdmin->message    = "Nouvelle demande URGENTE n°{$demande->id} à traiter.";
                $notifAdmin->create();
            }
        }

        // 3) Gestion des fichiers uploadés (inchangé)
        if(isset($_FILES['files'])) {
            $upload_dir = '../assets/uploads/';
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            require_once '../classes/PieceJointe.php';
            $pieceJointe = new PieceJointe($db);
            
            foreach($_FILES['files']['tmp_name'] as $key => $tmp_name) {
                if($_FILES['files']['error'][$key] == 0) {
                    $file_name = time() . '_' . $_FILES['files']['name'][$key];
                    $file_path = $upload_dir . $file_name;
                    
                    if(move_uploaded_file($tmp_name, $file_path)) {
                        $pieceJointe->demande_id      = $demande->id;
                        $pieceJointe->nom_fichier     = $_FILES['files']['name'][$key];
                        $pieceJointe->chemin_fichier  = $file_path;
                        $pieceJointe->taille          = $_FILES['files']['size'][$key];
                        $pieceJointe->type_mime       = $_FILES['files']['type'][$key];
                        $pieceJointe->create();
                    }
                }
            }
        }
        
        echo json_encode([
            'success'    => true,
            'message'    => 'Demande créée avec succès',
            'demande_id' => $demande->id
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la création'
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>
