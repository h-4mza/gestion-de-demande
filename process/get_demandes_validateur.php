<?php
// process/get_demandes_validateur.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/Database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'validateur') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$database = new Database();
$db       = $database->getConnection();

try {
    
    $sql = "
        SELECT 
            d.*,
            t.nom AS type_nom,
            u.nom AS demandeur_nom,
            u.prenom AS demandeur_prenom,
            (SELECT COUNT(*) FROM pieces_jointes pj WHERE pj.demande_id = d.id) AS attachments_count
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

    
    $stmt->bindValue(':chef_id', (int) $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();

    $demandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // normalize attachments flag
    foreach ($demandes as &$d) {
        $d['has_attachments'] = (isset($d['attachments_count']) && (int)$d['attachments_count'] > 0) ? 1 : 0;
    }
    unset($d); // good practice after reference loop

    echo json_encode([
        'success'  => true,
        'demandes' => $demandes
    ]);
    exit;

} catch (PDOException $e) {
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'message' => 'Erreur base de données',
        'detail'  => $e->getMessage()
    ]);
    exit;
}
