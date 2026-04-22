<?php
require_once 'config/database.php';

class Referee {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll() {
        $query = "SELECT id, nombre, telefono, created_at
                  FROM arbitros
                  ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($nombre, $telefono = null) {
        $query = "INSERT INTO arbitros (nombre, telefono) VALUES (:nombre, :telefono)";
        $stmt = $this->conn->prepare($query);

        $safeNombre = htmlspecialchars(strip_tags($nombre));
        $safeTelefono = $telefono !== null ? htmlspecialchars(strip_tags($telefono)) : null;

        $stmt->bindParam(':nombre', $safeNombre);
        if ($safeTelefono === null || $safeTelefono === '') {
            $stmt->bindValue(':telefono', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':telefono', $safeTelefono);
        }

        return $stmt->execute();
    }

    public function getById($id) {
        $query = "SELECT id, nombre, telefono, created_at FROM arbitros WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $i = (int)$id;
        $stmt->bindParam(':id', $i, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
?>
