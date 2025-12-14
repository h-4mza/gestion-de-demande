<?php
// process/get_attachments.php
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/PieceJointe.php';

$demandeId = isset($_GET['demande_id']) ? (int)$_GET['demande_id'] : 0;
if (!$demandeId) {
    echo json_encode(['success' => true, 'attachments' => []]);
    exit;
}

$db = (new Database())->getConnection();
$pj = new PieceJointe($db);

try {
    $rows = $pj->getByDemande($demandeId);
    
    if ($rows instanceof PDOStatement) {
        $rows = $rows->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    echo json_encode(['success' => true, 'attachments' => []]);
    exit;
}

$attachments = [];
foreach ($rows as $r) {
    // determine the stored path
    $rawPath = $r['chemin_fichier'] ?? $r['filename'] ?? $r['file_name'] ?? null;

    
    $filenameWeb = null;
    if ($rawPath) {
        // normalize slashes
        $rp = str_replace('\\', '/', $rawPath);
        // if contains assets/uploads, extract starting at that folder
        $pos = strpos($rp, 'assets/uploads');
        if ($pos !== false) {
            $filenameWeb = substr($rp, $pos); 
        } else {
            // fallback to basename
            $base = basename($rp);
            $filenameWeb = 'assets/uploads/' . $base;
        }
    }

    $attachments[] = [
        'id'            => isset($r['id']) ? (int)$r['id'] : null,
        'filename'      => $filenameWeb,               // normalized relative path (or null)
        'original_name' => $r['nom_fichier'] ?? $r['original_name'] ?? null,
        'mime'          => $r['type_mime'] ?? $r['mime'] ?? null,
        'size'          => isset($r['taille']) ? (int)$r['taille'] : (isset($r['size']) ? (int)$r['size'] : null),
    ];
}

echo json_encode(['success' => true, 'attachments' => $attachments]);
exit;
