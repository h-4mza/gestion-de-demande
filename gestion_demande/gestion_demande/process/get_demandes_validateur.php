<?php
// process/get_demandes_validateur.php
session_start();
header('Content-Type: application/json');

require_once '../config/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'validateur') {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

$database = new Database();
$db       = $database->getConnection();

/**
 * Logique validateur :
 *  - Demandes des utilisateurs dont u.chef_id = validateur.id
 *  - On exclut les urgentes (elles vont direct à l'admin)
 *  - Statut encore chez lui : en_attente, en_cours_de_validation
 */
$sql = "
    SELECT 
        d.*, 
        t.nom AS type_nom,
        u.nom AS demandeur_nom,
        u.prenom AS demandeur_prenom
    FROM demandes d
    JOIN users u ON d.demandeur_id = u.id
    LEFT JOIN types_besoins t ON d.type_id = t.id
    WHERE 
        u.chef_id = :chef_id
        AND d.urgence <> 'urgente'
        AND d.statut IN ('en_attente', 'en_cours_de_validation')
    ORDER BY d.created_at DESC
";

$stmt = $db->prepare($sql);
$stmt->bindParam(':chef_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$demandes = $stmt->fetchAll();

echo json_encode([
    'success'  => true,
    'demandes' => $demandes
]);
?>
