<?php
// classes/PieceJointe.php - Classe pour les fichiers
class PieceJointe {
    private $conn;
    private $table = "pieces_jointes";
    
    public $id;
    public $demande_id;
    public $nom_fichier;
    public $chemin_fichier;
    public $taille;
    public $type_mime;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (demande_id, nom_fichier, chemin_fichier, taille, type_mime) 
                  VALUES (:demande_id, :nom_fichier, :chemin_fichier, :taille, :type_mime)";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':demande_id', $this->demande_id);
        $stmt->bindParam(':nom_fichier', $this->nom_fichier);
        $stmt->bindParam(':chemin_fichier', $this->chemin_fichier);
        $stmt->bindParam(':taille', $this->taille);
        $stmt->bindParam(':type_mime', $this->type_mime);
        
        return $stmt->execute();
    }
    
   // classes/PieceJointe.php (replace method)
// classes/PieceJointe.php (replace method)
public function getByDemande($demande_id) {
    $query = "SELECT id, demande_id, nom_fichier, chemin_fichier, taille, type_mime
              FROM " . $this->table . " 
              WHERE demande_id = :demande_id
              ORDER BY id ASC";
    $stmt = $this->conn->prepare($query);
    $stmt->bindValue(':demande_id', (int)$demande_id, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC); // <-- return array, not statement
}

}
?>