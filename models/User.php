<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "usuarios";
    private $cachedHasEquipoIdColumn = null;
    private $cachedHasArbitroIdColumn = null;

    public $id;
    public $username;
    public $password;
    public $role;
    public $equipo_id;
    public $arbitro_id;
    public $lastError;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    private function hasEquipoIdColumn() {
        if ($this->cachedHasEquipoIdColumn !== null) {
            return $this->cachedHasEquipoIdColumn;
        }
        $query = "SELECT COUNT(*) AS total
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'equipo_id'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->cachedHasEquipoIdColumn = ((int)($row['total'] ?? 0)) > 0;
        return $this->cachedHasEquipoIdColumn;
    }

    private function hasArbitroIdColumn() {
        if ($this->cachedHasArbitroIdColumn !== null) {
            return $this->cachedHasArbitroIdColumn;
        }
        $query = "SELECT COUNT(*) AS total
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'arbitro_id'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->cachedHasArbitroIdColumn = ((int)($row['total'] ?? 0)) > 0;
        return $this->cachedHasArbitroIdColumn;
    }

    public function getUserByUsername($username) {
        $cols = "id, username, password, role";
        if ($this->hasEquipoIdColumn()) {
            $cols .= ", equipo_id";
        }
        if ($this->hasArbitroIdColumn()) {
            $cols .= ", arbitro_id";
        }
        $query = "SELECT $cols FROM " . $this->table_name . " WHERE username = :username";

        $stmt = $this->conn->prepare($query);

        $username = htmlspecialchars(strip_tags($username));
        $stmt->bindParam(':username', $username);

        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !isset($row['equipo_id'])) {
            $row['equipo_id'] = null;
        }
        if ($row && !isset($row['arbitro_id'])) {
            $row['arbitro_id'] = null;
        }
        return $row;
    }

    public function getAll() {
        $cols = "u.id, u.username, u.role, u.created_at";
        $join = "";
        if ($this->hasEquipoIdColumn()) {
            $cols .= ", u.equipo_id, e.nombre AS equipo_nombre";
            $join .= " LEFT JOIN equipos e ON e.id = u.equipo_id";
        } else {
            $cols .= ", NULL AS equipo_id, NULL AS equipo_nombre";
        }
        if ($this->hasArbitroIdColumn()) {
            $cols .= ", u.arbitro_id, ar.nombre AS arbitro_nombre";
            $join .= " LEFT JOIN arbitros ar ON ar.id = u.arbitro_id";
        } else {
            $cols .= ", NULL AS arbitro_id, NULL AS arbitro_nombre";
        }
        $query = "SELECT $cols FROM " . $this->table_name . " u $join ORDER BY u.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $cols = "id, username, role, created_at";
        if ($this->hasEquipoIdColumn()) {
            $cols .= ", equipo_id";
        }
        if ($this->hasArbitroIdColumn()) {
            $cols .= ", arbitro_id";
        }
        $query = "SELECT $cols FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && !isset($row['equipo_id'])) {
            $row['equipo_id'] = null;
        }
        if ($row && !isset($row['arbitro_id'])) {
            $row['arbitro_id'] = null;
        }
        return $row;
    }

    public function create() {
        $fields = ['username', 'password', 'role'];
        $ph = [':username', ':password', ':role'];
        if ($this->hasEquipoIdColumn()) {
            $fields[] = 'equipo_id';
            $ph[] = ':equipo_id';
        }
        if ($this->hasArbitroIdColumn()) {
            $fields[] = 'arbitro_id';
            $ph[] = ':arbitro_id';
        }
        $query = "INSERT INTO " . $this->table_name . " (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $ph) . ")";
        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        $this->role = htmlspecialchars(strip_tags($this->role));

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $this->password);
        $stmt->bindParam(":role", $this->role);
        if ($this->hasEquipoIdColumn()) {
            $eq = null;
            if (($this->role === 'director_tecnico') && isset($this->equipo_id) && (int)$this->equipo_id > 0) {
                $eq = (int)$this->equipo_id;
            }
            $stmt->bindValue(":equipo_id", $eq, $eq === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        }
        if ($this->hasArbitroIdColumn()) {
            $ar = null;
            if (($this->role === 'arbitro') && isset($this->arbitro_id) && (int)$this->arbitro_id > 0) {
                $ar = (int)$this->arbitro_id;
            }
            $stmt->bindValue(":arbitro_id", $ar, $ar === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        }

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function update($update_password = false) {
        $set = "username = :username, role = :role";
        if ($this->hasEquipoIdColumn()) {
            $set .= ", equipo_id = :equipo_id";
        }
        if ($this->hasArbitroIdColumn()) {
            $set .= ", arbitro_id = :arbitro_id";
        }
        if ($update_password) {
            $set .= ", password = :password";
        }
        $query = "UPDATE " . $this->table_name . " SET $set WHERE id = :id";
        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":role", $this->role);
        $stmt->bindParam(":id", $this->id);

        if ($this->hasEquipoIdColumn()) {
            $eq = null;
            if (($this->role === 'director_tecnico') && isset($this->equipo_id) && (int)$this->equipo_id > 0) {
                $eq = (int)$this->equipo_id;
            }
            $stmt->bindValue(":equipo_id", $eq, $eq === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        }
        if ($this->hasArbitroIdColumn()) {
            $ar = null;
            if (($this->role === 'arbitro') && isset($this->arbitro_id) && (int)$this->arbitro_id > 0) {
                $ar = (int)$this->arbitro_id;
            }
            $stmt->bindValue(":arbitro_id", $ar, $ar === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        }

        if ($update_password) {
            $this->password = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(":password", $this->password);
        }

        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }
}
?>
