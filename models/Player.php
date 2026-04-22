<?php
require_once 'config/database.php';

class Player {
    private $conn;
    private $table_name = "jugadores";

    public $id;
    public $equipo_id;
    public $nombre;
    public $edad;
    public $posicion;
    public $numero;
    public $foto;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAllWithTeam() {
        $query = "SELECT j.*, e.nombre as equipo_nombre 
                  FROM " . $this->table_name . " j
                  LEFT JOIN equipos e ON j.equipo_id = e.id
                  ORDER BY j.nombre ASC";
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

    public function getByEquipoId($equipoId) {
        $query = "SELECT * FROM " . $this->table_name . "
                  WHERE equipo_id = :equipo_id
                  ORDER BY numero ASC, nombre ASC";
        $stmt = $this->conn->prepare($query);
        $eid = (int)$equipoId;
        $stmt->bindParam(':equipo_id', $eid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " (equipo_id, nombre, edad, posicion, numero, foto) 
                  VALUES (:equipo_id, :nombre, :edad, :posicion, :numero, :foto)";
        $stmt = $this->conn->prepare($query);

        $this->equipo_id = htmlspecialchars(strip_tags($this->equipo_id));
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->edad = htmlspecialchars(strip_tags($this->edad));
        $this->posicion = htmlspecialchars(strip_tags($this->posicion));
        $this->numero = htmlspecialchars(strip_tags($this->numero));
        $this->foto = htmlspecialchars(strip_tags($this->foto));

        $stmt->bindParam(":equipo_id", $this->equipo_id);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":edad", $this->edad);
        $stmt->bindParam(":posicion", $this->posicion);
        $stmt->bindParam(":numero", $this->numero);
        $stmt->bindParam(":foto", $this->foto);

        if($stmt->execute()){
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET equipo_id = :equipo_id, nombre = :nombre, edad = :edad, posicion = :posicion, numero = :numero, foto = :foto 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $this->equipo_id = htmlspecialchars(strip_tags($this->equipo_id));
        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->edad = htmlspecialchars(strip_tags($this->edad));
        $this->posicion = htmlspecialchars(strip_tags($this->posicion));
        $this->numero = htmlspecialchars(strip_tags($this->numero));
        $this->foto = htmlspecialchars(strip_tags($this->foto));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":equipo_id", $this->equipo_id);
        $stmt->bindParam(":nombre", $this->nombre);
        $stmt->bindParam(":edad", $this->edad);
        $stmt->bindParam(":posicion", $this->posicion);
        $stmt->bindParam(":numero", $this->numero);
        $stmt->bindParam(":foto", $this->foto);
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
