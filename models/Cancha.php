<?php
require_once 'config/database.php';

class Cancha {
    private $conn;
    private $table = 'canchas';

    public $id;
    public $liga_id;
    public $nombre;
    public $direccion;
    public $notas;
    public $activa;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function tableExists() {
        try {
            $q = "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME = 'canchas'";
            $stmt = $this->conn->prepare($q);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int)($row['total'] ?? 0)) > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAllByLiga($ligaId) {
        $lid = (int)$ligaId;
        if ($lid <= 0 || !$this->tableExists()) {
            return [];
        }
        $q = "SELECT * FROM {$this->table} WHERE liga_id = ? ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($q);
        $stmt->bindParam(1, $lid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getById($id) {
        if (!$this->tableExists()) {
            return null;
        }
        $q = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($q);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create() {
        if (!$this->tableExists()) {
            return false;
        }
        $q = "INSERT INTO {$this->table} (liga_id, nombre, direccion, notas, activa)
              VALUES (:liga_id, :nombre, :direccion, :notas, :activa)";
        $stmt = $this->conn->prepare($q);
        $this->nombre = htmlspecialchars(strip_tags((string)$this->nombre));
        $this->direccion = $this->direccion !== null && $this->direccion !== ''
            ? htmlspecialchars(strip_tags((string)$this->direccion)) : null;
        $this->notas = $this->notas !== null && $this->notas !== ''
            ? htmlspecialchars(strip_tags((string)$this->notas)) : null;
        $ligaId = (int)$this->liga_id;
        $activa = !empty($this->activa) ? 1 : 0;
        $stmt->bindValue(':liga_id', $ligaId, PDO::PARAM_INT);
        $stmt->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindValue(':direccion', $this->direccion, $this->direccion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':notas', $this->notas, $this->notas === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':activa', $activa, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function update() {
        if (!$this->tableExists()) {
            return false;
        }
        $q = "UPDATE {$this->table} SET nombre = :nombre, direccion = :direccion, notas = :notas, activa = :activa WHERE id = :id";
        $stmt = $this->conn->prepare($q);
        $this->nombre = htmlspecialchars(strip_tags((string)$this->nombre));
        $this->direccion = $this->direccion !== null && $this->direccion !== ''
            ? htmlspecialchars(strip_tags((string)$this->direccion)) : null;
        $this->notas = $this->notas !== null && $this->notas !== ''
            ? htmlspecialchars(strip_tags((string)$this->notas)) : null;
        $activa = !empty($this->activa) ? 1 : 0;
        $id = (int)$this->id;
        $stmt->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindValue(':direccion', $this->direccion, $this->direccion === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':notas', $this->notas, $this->notas === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindValue(':activa', $activa, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function delete($id) {
        if (!$this->tableExists()) {
            return false;
        }
        $q = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->conn->prepare($q);
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
