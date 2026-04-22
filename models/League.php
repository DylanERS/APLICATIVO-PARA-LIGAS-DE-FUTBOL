<?php
require_once 'config/database.php';

class League {
    private $conn;
    private $table_name = 'ligas';
    /** @var array<string,bool> */
    private $cachedLigasColumns = [];

    public $id;
    public $nombre;
    public $descripcion;
    public $min_jugadores_partido;
    public $duracion_partido_minutos;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getMainLeague() {
        $query = 'SELECT * FROM ' . $this->table_name . ' ORDER BY id ASC OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $league = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$league) {
            $insert = 'INSERT INTO ' . $this->table_name . " (nombre, descripcion) VALUES ('Mi Liga de Fútbol', 'Descripción de la liga')";
            $this->conn->exec($insert);

            $stmt->execute();
            $league = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $league;
    }

    public function getMinJugadoresPartido() {
        $league = $this->getMainLeague();
        if (!empty($league['min_jugadores_partido'])) {
            return max(1, (int)$league['min_jugadores_partido']);
        }
        return 7;
    }

    /**
     * Duración oficial del partido en minutos (p. ej. 90). Por defecto 90 si no hay columna o valor.
     */
    public function getDuracionPartidoMinutos() {
        $league = $this->getMainLeague();
        $d = (int)($league['duracion_partido_minutos'] ?? 0);
        if ($d <= 0) {
            $d = 90;
        }
        return max(15, min(150, $d));
    }

    public function hasDuracionPartidoColumn() {
        return $this->hasLigasColumn('duracion_partido_minutos');
    }

    public function update() {
        $sets = ['nombre = :nombre', 'descripcion = :descripcion'];
        $hasMin = $this->hasLigasColumn('min_jugadores_partido');
        $hasDur = $this->hasLigasColumn('duracion_partido_minutos');
        if ($hasMin) {
            $sets[] = 'min_jugadores_partido = :min_jugadores';
        }
        if ($hasDur) {
            $sets[] = 'duracion_partido_minutos = :duracion';
        }
        $query = 'UPDATE ' . $this->table_name . ' SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->conn->prepare($query);

        $this->nombre = htmlspecialchars(strip_tags($this->nombre));
        $this->descripcion = htmlspecialchars(strip_tags($this->descripcion));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $stmt->bindValue(':descripcion', $this->descripcion, PDO::PARAM_STR);
        $stmt->bindValue(':id', (int)$this->id, PDO::PARAM_INT);
        if ($hasMin) {
            $min = isset($this->min_jugadores_partido) ? (int)$this->min_jugadores_partido : 7;
            $min = max(1, min(50, $min));
            $stmt->bindValue(':min_jugadores', $min, PDO::PARAM_INT);
        }
        if ($hasDur) {
            $dur = isset($this->duracion_partido_minutos) ? (int)$this->duracion_partido_minutos : 90;
            $dur = max(15, min(150, $dur));
            $stmt->bindValue(':duracion', $dur, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    /**
     * SQL Server: COL_LENGTH / INFORMATION_SCHEMA para columnas opcionales en ligas.
     */
    private function hasLigasColumn($column) {
        if (isset($this->cachedLigasColumns[$column])) {
            return $this->cachedLigasColumns[$column];
        }
        $ok = false;
        try {
            $sqlAttempts = [
                "SELECT COL_LENGTH('dbo.ligas', '" . str_replace("'", "''", $column) . "') AS len",
                "SELECT COL_LENGTH('ligas', '" . str_replace("'", "''", $column) . "') AS len",
            ];
            foreach ($sqlAttempts as $sql) {
                $stmt = $this->conn->query($sql);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $len = $row['len'] ?? null;
                if ($len !== null && (int)$len > 0) {
                    $ok = true;
                    break;
                }
            }
            if (!$ok) {
                $q = 'SELECT COUNT(*) AS total
                      FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_SCHEMA = \'dbo\' AND TABLE_NAME = \'ligas\' AND COLUMN_NAME = ?';
                $stmt = $this->conn->prepare($q);
                $stmt->bindParam(1, $column, PDO::PARAM_STR);
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $ok = ((int)($row['total'] ?? 0)) > 0;
            }
        } catch (Exception $e) {
            $ok = false;
        }
        $this->cachedLigasColumns[$column] = $ok;
        return $ok;
    }
}

