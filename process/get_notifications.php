<?php
// process/get_notifications.php
session_start();
header('Content-Type: application/json');

require_once '../config/Database.php';
require_once '../classes/Notification.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$notif = new Notification($db);
$stmt  = $notif->getByUser($_SESSION['user_id']);

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success'       => true,
    'notifications' => $notifications
]);
?>
