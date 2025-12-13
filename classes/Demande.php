<?php
// classes/Demande.php
class Demande {
    private $conn;
    private $table = "demandes";

    public $id;
    public $demandeur_id;
    public $type_id;
    public $description;
    public $urgence;
    public $statut;
    public $service_affecte;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (demandeur_id, type_id, description, urgence, statut) 
                  VALUES (:demandeur_id, :type_id, :description, :urgence, 'en_attente')";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':demandeur_id', $this->demandeur_id);
        $stmt->bindParam(':type_id', $this->type_id);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':urgence', $this->urgence);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readByUser($user_id) {
        $query = "SELECT d.*, t.nom as type_nom, 
                  u.nom as demandeur_nom, u.prenom as demandeur_prenom 
                  FROM " . $this->table . " d 
                  LEFT JOIN types_besoins t ON d.type_id = t.id 
                  LEFT JOIN users u ON d.demandeur_id = u.id 
                  WHERE d.demandeur_id = :user_id 
                  ORDER BY d.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        return $stmt;
    }

    public function readByChef($chef_id) {
        $query = "SELECT d.*, t.nom as type_nom, 
                  u.nom as demandeur_nom, u.prenom as demandeur_prenom 
                  FROM " . $this->table . " d 
                  LEFT JOIN types_besoins t ON d.type_id = t.id 
                  LEFT JOIN users u ON d.demandeur_id = u.id 
                  WHERE u.chef_id = :chef_id 
                  ORDER BY d.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':chef_id', $chef_id);
        $stmt->execute();
        return $stmt;
    }

    public function readAll() {
        $query = "SELECT d.*, t.nom as type_nom, 
                  u.nom as demandeur_nom, u.prenom as demandeur_prenom,
                  u.service as demandeur_service
                  FROM " . $this->table . " d 
                  LEFT JOIN types_besoins t ON d.type_id = t.id 
                  LEFT JOIN users u ON d.demandeur_id = u.id 
                  ORDER BY d.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT d.*, t.nom as type_nom, t.description as type_description,
                  u.nom as demandeur_nom, u.prenom as demandeur_prenom, u.email as demandeur_email,
                  u.service as demandeur_service
                  FROM " . $this->table . " d 
                  LEFT JOIN types_besoins t ON d.type_id = t.id 
                  LEFT JOIN users u ON d.demandeur_id = u.id 
                  WHERE d.id = :id 
                  LIMIT 1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET type_id = :type_id, description = :description, 
                      urgence = :urgence 
                  WHERE id = :id AND statut = 'en_attente'";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':type_id', $this->type_id);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':urgence', $this->urgence);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

public function updateStatut($statut = null, $service = null) {
    // 1. Rien à mettre à jour ?
    if ($statut === null && $service === null) {
        return false;
    }

    // 2. Contrôle des statuts autorisés (si on en passe un)
    if ($statut !== null) {
        $allowed = [
            'en_attente',
            'en_cours_de_validation',
            'validee',
            'rejetee',
            'en_cours_de_traitement',
            'traitee'
        ];

        if (!in_array($statut, $allowed, true)) {
            // statut invalide => on refuse
            return false;
        }
    }

    // 3. Construction dynamique des champs à mettre à jour
    $fields = [];
    $params = [':id' => $this->id];

    if ($statut !== null) {
        $fields[] = "statut = :statut";
        $params[':statut'] = $statut;
    }

    if ($service !== null) {
        $fields[] = "service_affecte = :service";
        $params[':service'] = $service;
    }

    // On met aussi à jour la date de modification
    $fields[] = "updated_at = NOW()";

    $query = "UPDATE " . $this->table . " 
              SET " . implode(', ', $fields) . " 
              WHERE id = :id";

    $stmt = $this->conn->prepare($query);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    return $stmt->execute();
}


    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id AND statut = 'en_attente'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

public function getStatistics($user_id = null, $role = null) {

        if ($role === 'demandeur') {
            $query = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) AS en_attente,
                SUM(CASE WHEN statut = 'en_cours_de_validation' THEN 1 ELSE 0 END) AS en_cours_de_validation,
                SUM(CASE WHEN statut = 'validee' THEN 1 ELSE 0 END) AS validee,
                SUM(CASE WHEN statut = 'rejetee' THEN 1 ELSE 0 END) AS rejetee,
                SUM(CASE WHEN statut = 'en_cours_de_traitement' THEN 1 ELSE 0 END) AS en_cours_de_traitement,
                SUM(CASE WHEN statut = 'traitee' THEN 1 ELSE 0 END) AS traitee
            FROM demandes
            WHERE demandeur_id = :uid";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':uid', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();

        } else if ($role === 'validateur') {
            $query = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN d.statut = 'en_attente' THEN 1 ELSE 0 END) AS en_attente,
                SUM(CASE WHEN d.statut = 'en_cours_de_validation' THEN 1 ELSE 0 END) AS en_cours_de_validation,
                SUM(CASE WHEN d.statut = 'validee' THEN 1 ELSE 0 END) AS validee,
                SUM(CASE WHEN d.statut = 'rejetee' THEN 1 ELSE 0 END) AS rejetee
            FROM demandes d
            JOIN users u ON d.demandeur_id = u.id
            WHERE u.chef_id = :chef_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':chef_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch();

        } else if ($role === 'administrateur') {
            // --- CORRECTION MAJEURE ICI ---
            // On renomme les colonnes pour qu'elles correspondent EXACTEMENT au tableau $stats du dashboard
            $query = "
            SELECT 
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END), 0) AS en_attente,
                COALESCE(SUM(CASE WHEN statut = 'en_cours_de_validation' THEN 1 ELSE 0 END), 0) AS en_cours_de_validation,
                COALESCE(SUM(CASE WHEN statut = 'validee' THEN 1 ELSE 0 END), 0) AS validee,
                COALESCE(SUM(CASE WHEN statut = 'rejetee' THEN 1 ELSE 0 END), 0) AS rejetee,
                COALESCE(SUM(CASE WHEN statut = 'traitee' THEN 1 ELSE 0 END), 0) AS traitee,
                COALESCE(SUM(CASE WHEN statut = 'en_cours_de_traitement' THEN 1 ELSE 0 END), 0) AS en_cours_de_traitement
            FROM demandes";
            
            $stmt = $this->conn->query($query);
            return $stmt->fetch();
        }
        return null;
    }

}
?>