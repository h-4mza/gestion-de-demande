<?php
// process/validation_process.php - Validation/Rejet par le validateur
session_start();
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../classes/Demande.php';
require_once '../classes/Validation.php';
require_once '../classes/Notification.php';

$response = ['success' => false, 'message' => 'Erreur inconnue'];

try {
    // Sécurité : rôle
    if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'validateur') {
        throw new Exception('Non autorisé');
    }

    // Méthode HTTP
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée');
    }

    // Données envoyées par fetch() ou formulaire
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }

    $id          = isset($data['id']) ? (int) $data['id'] : 0;
    $action      = $data['action'] ?? '';       // 'valider' ou 'rejeter'
    $commentaire = $data['commentaire'] ?? '';

    if ($id <= 0 || !in_array($action, ['valider', 'rejeter'], true)) {
        throw new Exception('Paramètres invalides');
    }

    $database = new Database();
    $db       = $database->getConnection();
    $demande  = new Demande($db);

    // Vérifier que la demande existe ET appartient bien à l’équipe du validateur
    $sql = "SELECT d.demandeur_id, d.statut
            FROM demandes d
            JOIN users u ON d.demandeur_id = u.id
            WHERE d.id = :id AND u.chef_id = :chef_id";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':chef_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row) {
        throw new Exception('Demande introuvable ou non autorisée');
    }

    // On ne permet la décision que si la demande est en attente / en cours de validation
    if (!in_array($row['statut'], ['en_attente', 'en_cours_de_validation'], true)) {
        throw new Exception('Statut actuel incompatible avec une validation');
    }

    // Déterminer le nouveau statut
    if ($action === 'valider') {
        $nouveauStatut    = 'validee';
        $validationAction = 'validee';
    } else { // rejeter
        $nouveauStatut    = 'rejetee';
        $validationAction = 'rejetee';
    }

    // Mise à jour du statut de la demande
    $demande->id = $id;
    if (!$demande->updateStatut($nouveauStatut)) {
        throw new Exception('Erreur lors de la mise à jour du statut');
    }

    // Enregistrement dans la table validations
    $validation = new Validation($db);
    $validation->demande_id    = $id;
    $validation->validateur_id = $_SESSION['user_id'];
    $validation->action        = $validationAction;
    $validation->commentaire   = $commentaire;
    $validation->create();

    // Notification au demandeur
    $demandeurId = (int) $row['demandeur_id'];

    $notif = new Notification($db);
    $notif->user_id    = $demandeurId;
    $notif->demande_id = $id;

    if ($nouveauStatut === 'validee') {
        $notif->message = "Votre demande n°{$id} a été validée par votre supérieur hiérarchique.";
    } else {
        $notif->message = "Votre demande n°{$id} a été rejetée : " . ($commentaire ?: 'sans commentaire');
    }

    $notif->create();

        // Si la demande est VALIDÉE → notifier les ADMINISTRATEURS
    if ($nouveauStatut === 'validee') {
        try {
            $stmtAdmins = $db->prepare("SELECT id FROM users WHERE role = 'administrateur'");
            $stmtAdmins->execute();
            $admins = $stmtAdmins->fetchAll(PDO::FETCH_ASSOC);

            foreach ($admins as $admin) {
                $notifAdmin = new Notification($db);
                $notifAdmin->user_id    = (int) $admin['id'];
                $notifAdmin->demande_id = $id;
                $notifAdmin->message    = "Une demande n°{$id} a été validée et doit être traitée.";
                $notifAdmin->create();
            }
        } catch (Throwable $eNotifAdmin) {
            // On ignore les erreurs de notif admin pour ne pas casser la réponse
        }
    }


    $response['success'] = true;
    $response['message'] = 'Décision enregistrée avec succès.';

} catch (Throwable $e) {
    $response['success'] = false;
    // pour le debug on garde le vrai message
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>
