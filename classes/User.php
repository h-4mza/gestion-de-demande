<?php
// classes/User.php
class User {
    private $conn;
    private $table = "users";

    public $id;
    public $nom;
    public $prenom;
    public $email;
    public $password;
    public $role;
    public $service;
    public $chef_id;
    public $statut;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($email, $password) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email AND statut = 'actif' LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            if(password_verify($password, $row['password'])) {
                return $row;
            }
        }
        return false;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (nom, prenom, email, password, role, service, chef_id, statut) 
                  VALUES (:nom, :prenom, :email, :password, :role, :service, :chef_id, :statut)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        
        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':prenom', $this->prenom);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':service', $this->service);
        $stmt->bindParam(':chef_id', $this->chef_id);
        $stmt->bindParam(':statut', $this->statut);

        return $stmt->execute();
    }

    public function readAll() {
        $query = "SELECT u.*, c.nom as chef_nom, c.prenom as chef_prenom 
                  FROM " . $this->table . " u 
                  LEFT JOIN " . $this->table . " c ON u.chef_id = c.id 
                  ORDER BY u.nom, u.prenom";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET nom = :nom, prenom = :prenom, email = :email, 
                      role = :role, service = :service, chef_id = :chef_id, statut = :statut";
        
        if(!empty($this->password)) {
            $query .= ", password = :password";
        }
        
        $query .= " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':prenom', $this->prenom);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':role', $this->role);
        $stmt->bindParam(':service', $this->service);
        $stmt->bindParam(':chef_id', $this->chef_id);
        $stmt->bindParam(':statut', $this->statut);
        $stmt->bindParam(':id', $this->id);
        
        if(!empty($this->password)) {
            $hashed = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(':password', $hashed);
        }

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
?>