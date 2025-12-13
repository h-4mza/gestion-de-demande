<?php
// classes/Validation.php
class Validation {
    private $conn;
    private $table = "validations";

    public $id;
    public $demande_id;
    public $validateur_id;
    public $action;       // 'validee' ou 'rejetee'
    public $commentaire;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Créer une nouvelle validation
    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (demande_id, validateur_id, action, commentaire, created_at)
                  VALUES (:demande_id, :validateur_id, :action, :commentaire, NOW())";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':demande_id', $this->demande_id, PDO::PARAM_INT);
        $stmt->bindParam(':validateur_id', $this->validateur_id, PDO::PARAM_INT);
        $stmt->bindParam(':action', $this->action, PDO::PARAM_STR);
        $stmt->bindParam(':commentaire', $this->commentaire, PDO::PARAM_STR);

        return $stmt->execute();
    }

    // Récupérer l'historique des validations d'une demande
    public function getByDemande($demande_id) {
        $query = "SELECT v.*, u.nom as validateur_nom, u.prenom as validateur_prenom 
                  FROM " . $this->table . " v 
                  LEFT JOIN users u ON v.validateur_id = u.id 
                  WHERE v.demande_id = :demande_id 
                  ORDER BY v.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':demande_id', $demande_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }
}
?>
