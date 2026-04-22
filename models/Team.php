<?php
require_once 'config/database.php';

class Team {
    private $conn;
    private $table_name = "equipos";

    public $id;
    public $nombre;
    public $ciudad;
    public $entrenador;
    public $logo;
    public $fecha_registro;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (nombre, ciudad, entrenador, logo) VALUES (:nombre, :ciudad, :entrenador, :logo)";
        $stmt = $this->conn->prepare($query);

        // sanitize
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->ciudad = htmlspecialchars(strip_tags($this->ciudad));
        $this->entrenador = htmlspecialchars(strip_tags($this->entrenador));
        $this->logo = htmlspecialchars(strip_tags($this->logo));

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":ciudad", $this->ciudad);
        $stmt->bindParam(":entrenador", $this->entrenador);
        $stmt->bindParam(":logo", $this->logo);

        if($stmt->execute()){
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " SET nombre = :nombre, ciudad = :ciudad, entrenador = :entrenador, logo = :logo WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->ciudad = htmlspecialchars(strip_tags($this->ciudad));
        $this->entrenador = htmlspecialchars(strip_tags($this->entrenador));
        $this->logo = htmlspecialchars(strip_tags($this->logo));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":ciudad", $this->ciudad);
        $stmt->bindParam(":entrenador", $this->entrenador);
        $stmt->bindParam(":logo", $this->logo);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()){
            return true;
        }
        return false;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);

        if($stmt->execute()){
            return true;
        }
        return false;
    }
}
?>
