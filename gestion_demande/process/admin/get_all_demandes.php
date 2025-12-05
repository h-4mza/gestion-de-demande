<?php
// ==========================================
// process/admin/get_all_demandes.php
// ==========================================
session_start();
header('Content-Type: application/json');

require_once '../../config/Database.php';
require_once '../../classes/Demande.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'administrateur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

// ---- L’ADMIN NE DOIT VOIR QUE LES DEMANDES QUI LE CONCERNENT ----
$sql = "
SELECT d.*, u.nom AS demandeur_nom, u.prenom AS demandeur_prenom, u.email AS demandeur_email
FROM demandes d
JOIN users u ON d.demandeur_id = u.id
WHERE 
      d.statut IN ('validee', 'en_cours_de_traitement', 'traitee')
   OR (d.urgence = 'urgente')
ORDER BY d.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->execute();
$demandes = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'demandes' => $demandes
]);
?>
