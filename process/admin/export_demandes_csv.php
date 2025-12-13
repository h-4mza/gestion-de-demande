<?php
// ==========================================
// process/admin/export_demandes_csv.php
// ==========================================
session_start();

require_once '../../config/Database.php';
require_once '../../classes/Demande.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'administrateur') {
    die('Non autorisé');
}

$database = new Database();
$db = $database->getConnection();
$demande = new Demande($db);

$stmt = $demande->readAll();
$demandes = $stmt->fetchAll();

// Headers pour téléchargement
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=demandes_' . date('Y-m-d') . '.csv');

$output = fopen('php://output', 'w');

// En-têtes CSV
fputcsv($output, ['ID', 'Demandeur', 'Type', 'Description', 'Urgence', 'Statut', 'Service', 'Date création']);

// Données
foreach($demandes as $d) {
    fputcsv($output, [
        $d['id'],
        $d['demandeur_nom'] . ' ' . $d['demandeur_prenom'],
        $d['type_nom'],
        $d['description'],
        $d['urgence'],
        $d['statut'],
        $d['service_affecte'] ?? 'Non affecté',
        $d['created_at']
    ]);
}

fclose($output);
exit();
?>