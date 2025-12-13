<?php
// process/mark_notification_read.php
session_start();
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../classes/Notification.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$id = $_POST['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $notif = new Notification($db);

    // (Optionnel mais recommandé) vérifier que la notif appartient bien à l'utilisateur
    $stmt = $db->prepare("SELECT user_id FROM notifications WHERE id = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Non autorisé']);
        exit();
    }

    if ($notif->markAsRead($id)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
