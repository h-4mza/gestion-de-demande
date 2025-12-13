<?php
class Notification {

    private $conn;
    private $table = "notifications";

    public $id;
    public $user_id;
    public $demande_id;
    public $message;
    public $lu;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // ===============================
    // 1) Créer une notification
    // ===============================
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (user_id, demande_id, message, lu, created_at)
                  VALUES (:user_id, :demande_id, :message, 0, NOW())";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':demande_id', $this->demande_id);
        $stmt->bindParam(':message', $this->message);

        return $stmt->execute();
    }

    // ===============================
    // 2) Récupérer toutes les notifications d’un user
    // ===============================
    public function getByUser($user_id) {
        $query = "SELECT * FROM " . $this->table . "
                  WHERE user_id = :user_id
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        return $stmt;
    }

    // ===============================
    // 3) Marquer une notification comme lue
    // ===============================
    public function markAsRead($id) {
        $query = "UPDATE " . $this->table . "
                  SET lu = 1
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }
}
?>
