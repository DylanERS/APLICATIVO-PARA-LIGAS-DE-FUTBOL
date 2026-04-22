<?php
require_once 'config/database.php';

class MatchAttendance {
    private $conn;
    private $tableExists = null;
    private $cachedHasFotoAsistencia = null;
    private $cachedHasValidacionArbitro = null;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function tableReady() {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }
        $query = "SELECT COUNT(*) AS total
                  FROM INFORMATION_SCHEMA.TABLES
                  WHERE TABLE_NAME = 'partido_jugadores_presentes'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->tableExists = ((int)($row['total'] ?? 0)) > 0;
        return $this->tableExists;
    }

    public function hasFotoAsistenciaColumn() {
        if ($this->cachedHasFotoAsistencia !== null) {
            return $this->cachedHasFotoAsistencia;
        }
        if (!$this->tableReady()) {
            $this->cachedHasFotoAsistencia = false;
            return false;
        }
        $query = "SELECT COUNT(*) AS total
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'partido_jugadores_presentes' AND COLUMN_NAME = 'foto_asistencia'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->cachedHasFotoAsistencia = ((int)($row['total'] ?? 0)) > 0;
        return $this->cachedHasFotoAsistencia;
    }

    public function hasValidacionArbitroColumn() {
        if ($this->cachedHasValidacionArbitro !== null) {
            return $this->cachedHasValidacionArbitro;
        }
        if (!$this->tableReady()) {
            $this->cachedHasValidacionArbitro = false;
            return false;
        }
        $query = "SELECT COUNT(*) AS total
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'partido_jugadores_presentes' AND COLUMN_NAME = 'validacion_arbitro'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->cachedHasValidacionArbitro = ((int)($row['total'] ?? 0)) > 0;
        return $this->cachedHasValidacionArbitro;
    }

    public function countPresent($partidoId, $equipoId) {
        if (!$this->tableReady()) {
            return 0;
        }
        $query = "SELECT COUNT(*) AS total
                  FROM partido_jugadores_presentes
                  WHERE partido_id = :partido_id AND equipo_id = :equipo_id";
        $stmt = $this->conn->prepare($query);
        $pid = (int)$partidoId;
        $eid = (int)$equipoId;
        $stmt->bindParam(':partido_id', $pid, PDO::PARAM_INT);
        $stmt->bindParam(':equipo_id', $eid, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function getPresentPlayerIds($partidoId, $equipoId) {
        if (!$this->tableReady()) {
            return [];
        }
        $query = "SELECT jugador_id FROM partido_jugadores_presentes
                  WHERE partido_id = :partido_id AND equipo_id = :equipo_id";
        $stmt = $this->conn->prepare($query);
        $pid = (int)$partidoId;
        $eid = (int)$equipoId;
        $stmt->bindParam(':partido_id', $pid, PDO::PARAM_INT);
        $stmt->bindParam(':equipo_id', $eid, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    /**
     * Detalle por jugador: foto_asistencia, validacion_arbitro (claves int jugador_id).
     */
    public function getPresentDetailsByTeam($partidoId, $equipoId) {
        if (!$this->tableReady()) {
            return [];
        }
        $fotoSel = $this->hasFotoAsistenciaColumn() ? 'pjp.foto_asistencia' : 'CAST(NULL AS VARCHAR(255)) AS foto_asistencia';
        $valSel = $this->hasValidacionArbitroColumn() ? 'pjp.validacion_arbitro' : 'CAST(NULL AS VARCHAR(20)) AS validacion_arbitro';
        $query = "SELECT pjp.jugador_id, $fotoSel, $valSel
                  FROM partido_jugadores_presentes pjp
                  WHERE pjp.partido_id = :partido_id AND pjp.equipo_id = :equipo_id";
        $stmt = $this->conn->prepare($query);
        $pid = (int)$partidoId;
        $eid = (int)$equipoId;
        $stmt->bindParam(':partido_id', $pid, PDO::PARAM_INT);
        $stmt->bindParam(':equipo_id', $eid, PDO::PARAM_INT);
        $stmt->execute();
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $jid = (int)$row['jugador_id'];
            $out[$jid] = [
                'foto_asistencia' => $row['foto_asistencia'] ?? null,
                'validacion_arbitro' => $row['validacion_arbitro'] ?? null
            ];
        }
        return $out;
    }

    /**
     * Nómina completa del partido para el árbitro (ambos equipos).
     */
    public function getPresentRosterForMatch($partidoId) {
        if (!$this->tableReady()) {
            return [];
        }
        $fotoSel = $this->hasFotoAsistenciaColumn() ? 'pjp.foto_asistencia' : 'CAST(NULL AS VARCHAR(255)) AS foto_asistencia';
        $valSel = $this->hasValidacionArbitroColumn() ? 'pjp.validacion_arbitro' : 'CAST(NULL AS VARCHAR(20)) AS validacion_arbitro';
        $query = "SELECT pjp.partido_id, pjp.jugador_id, pjp.equipo_id, j.nombre AS jugador_nombre, j.numero, j.foto AS jugador_foto,
                         e.nombre AS equipo_nombre, $fotoSel, $valSel
                  FROM partido_jugadores_presentes pjp
                  INNER JOIN jugadores j ON j.id = pjp.jugador_id
                  INNER JOIN equipos e ON e.id = pjp.equipo_id
                  WHERE pjp.partido_id = :partido_id
                  ORDER BY pjp.equipo_id, j.numero, j.nombre";
        $stmt = $this->conn->prepare($query);
        $pid = (int)$partidoId;
        $stmt->bindParam(':partido_id', $pid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * $fotoByJugador: jugador_id => ruta relativa (ej. assets/img/asistencia/...) o null para omitir en INSERT legacy.
     * $requireFoto: si true y columna existe, cada jugador debe tener foto (nueva o preservada en $preserveFromPrevious).
     */
    public function saveTeamRoster($partidoId, $equipoId, $jugadorIds, array $fotoByJugador = [], array $preserveFromPrevious = [], $requireFoto = false) {
        if (!$this->tableReady()) {
            return false;
        }
        $jugadorIds = array_values(array_unique(array_map('intval', $jugadorIds)));
        $jugadorIds = array_filter($jugadorIds, function ($id) {
            return $id > 0;
        });

        $hasFoto = $this->hasFotoAsistenciaColumn();
        $hasVal = $this->hasValidacionArbitroColumn();

        $previous = [];
        if ($hasFoto || $hasVal) {
            $qPrev = "SELECT jugador_id" .
                ($hasFoto ? ", foto_asistencia" : "") .
                ($hasVal ? ", validacion_arbitro" : "") .
                " FROM partido_jugadores_presentes WHERE partido_id = :p AND equipo_id = :e";
            $st = $this->conn->prepare($qPrev);
            $pid = (int)$partidoId;
            $eid = (int)$equipoId;
            $st->bindParam(':p', $pid, PDO::PARAM_INT);
            $st->bindParam(':e', $eid, PDO::PARAM_INT);
            $st->execute();
            while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                $previous[(int)$row['jugador_id']] = $row;
            }
        }

        foreach ($jugadorIds as $jid) {
            $foto = $fotoByJugador[$jid] ?? null;
            if ($foto === null || $foto === '') {
                $foto = $preserveFromPrevious[$jid] ?? null;
            }
            if ($requireFoto && $hasFoto && ($foto === null || $foto === '')) {
                return false;
            }
        }

        try {
            $this->conn->beginTransaction();

            $del = "DELETE FROM partido_jugadores_presentes WHERE partido_id = :p AND equipo_id = :e";
            $stmtDel = $this->conn->prepare($del);
            $pid = (int)$partidoId;
            $eid = (int)$equipoId;
            $stmtDel->bindParam(':p', $pid, PDO::PARAM_INT);
            $stmtDel->bindParam(':e', $eid, PDO::PARAM_INT);
            $stmtDel->execute();

            if ($hasFoto) {
                $ins = "INSERT INTO partido_jugadores_presentes (partido_id, jugador_id, equipo_id, foto_asistencia" .
                    ($hasVal ? ", validacion_arbitro" : "") . ")
                    VALUES (:p, :j, :e, :foto" . ($hasVal ? ", :val" : "") . ")";
            } else {
                $ins = "INSERT INTO partido_jugadores_presentes (partido_id, jugador_id, equipo_id) VALUES (:p, :j, :e)";
            }
            $stmtIns = $this->conn->prepare($ins);

            foreach ($jugadorIds as $jid) {
                $foto = $fotoByJugador[$jid] ?? null;
                if ($foto === null || $foto === '') {
                    $foto = $preserveFromPrevious[$jid] ?? null;
                }

                $val = null;
                if ($hasVal && $hasFoto) {
                    $prevFoto = $previous[$jid]['foto_asistencia'] ?? null;
                    if ($prevFoto !== null && $prevFoto !== '' && $prevFoto === $foto) {
                        $val = $previous[$jid]['validacion_arbitro'] ?? null;
                    }
                }

                $stmtIns->bindParam(':p', $pid, PDO::PARAM_INT);
                $stmtIns->bindParam(':j', $jid, PDO::PARAM_INT);
                $stmtIns->bindParam(':e', $eid, PDO::PARAM_INT);
                if ($hasFoto) {
                    if ($foto === null || $foto === '') {
                        $stmtIns->bindValue(':foto', null, PDO::PARAM_NULL);
                    } else {
                        $stmtIns->bindValue(':foto', $foto, PDO::PARAM_STR);
                    }
                    if ($hasVal) {
                        if ($val === null || $val === '') {
                            $stmtIns->bindValue(':val', null, PDO::PARAM_NULL);
                        } else {
                            $stmtIns->bindValue(':val', $val, PDO::PARAM_STR);
                        }
                    }
                }
                $stmtIns->execute();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    public function validateJugadoresBelongToTeam($jugadorIds, $equipoId) {
        if (empty($jugadorIds)) {
            return true;
        }
        $jugadorIds = array_values(array_unique(array_map('intval', $jugadorIds)));
        $placeholders = [];
        foreach ($jugadorIds as $idx => $_) {
            $placeholders[] = ':id_' . $idx;
        }
        $query = "SELECT COUNT(*) AS total FROM jugadores
                  WHERE equipo_id = :equipo_id AND id IN (" . implode(',', $placeholders) . ")";
        $stmt = $this->conn->prepare($query);
        $eid = (int)$equipoId;
        $stmt->bindParam(':equipo_id', $eid, PDO::PARAM_INT);
        foreach ($jugadorIds as $idx => $jid) {
            $stmt->bindValue(':id_' . $idx, $jid, PDO::PARAM_INT);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0) === count($jugadorIds);
    }

    public function addPresentPlayerIfMissing($partidoId, $equipoId, $jugadorId) {
        if (!$this->tableReady()) {
            return false;
        }
        $pid = (int)$partidoId;
        $eid = (int)$equipoId;
        $jid = (int)$jugadorId;
        if ($jid <= 0 || $eid <= 0 || $pid <= 0) {
            return false;
        }
        if (!$this->validateJugadoresBelongToTeam([$jid], $eid)) {
            return false;
        }
        try {
            $q = "SELECT 1 AS ok FROM partido_jugadores_presentes WHERE partido_id = :p AND jugador_id = :j";
            $stmt = $this->conn->prepare($q);
            $stmt->bindParam(':p', $pid, PDO::PARAM_INT);
            $stmt->bindParam(':j', $jid, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return true;
            }
            if ($this->hasFotoAsistenciaColumn()) {
                $hasVal = $this->hasValidacionArbitroColumn();
                if ($hasVal) {
                    $ins = "INSERT INTO partido_jugadores_presentes (partido_id, jugador_id, equipo_id, foto_asistencia, validacion_arbitro)
                            VALUES (:p, :j, :e, NULL, NULL)";
                } else {
                    $ins = "INSERT INTO partido_jugadores_presentes (partido_id, jugador_id, equipo_id, foto_asistencia)
                            VALUES (:p, :j, :e, NULL)";
                }
            } else {
                $ins = "INSERT INTO partido_jugadores_presentes (partido_id, jugador_id, equipo_id) VALUES (:p, :j, :e)";
            }
            $stmtIns = $this->conn->prepare($ins);
            $stmtIns->bindParam(':p', $pid, PDO::PARAM_INT);
            $stmtIns->bindParam(':j', $jid, PDO::PARAM_INT);
            $stmtIns->bindParam(':e', $eid, PDO::PARAM_INT);
            return $stmtIns->execute();
        } catch (Exception $e) {
            return false;
        }
    }

    public function updateFotoAsistencia($partidoId, $jugadorId, $relativePath) {
        if (!$this->tableReady() || !$this->hasFotoAsistenciaColumn()) {
            return false;
        }
        $pid = (int)$partidoId;
        $jid = (int)$jugadorId;
        if ($pid <= 0 || $jid <= 0 || $relativePath === null || $relativePath === '') {
            return false;
        }
        $hasVal = $this->hasValidacionArbitroColumn();
        $sql = $hasVal
            ? "UPDATE partido_jugadores_presentes SET foto_asistencia = :f, validacion_arbitro = NULL WHERE partido_id = :p AND jugador_id = :j"
            : "UPDATE partido_jugadores_presentes SET foto_asistencia = :f WHERE partido_id = :p AND jugador_id = :j";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':f', $relativePath, PDO::PARAM_STR);
        $stmt->bindParam(':p', $pid, PDO::PARAM_INT);
        $stmt->bindParam(':j', $jid, PDO::PARAM_INT);
        return $stmt->execute() && $stmt->rowCount() > 0;
    }

    /**
     * Jugadores en nómina con validación distinta de "confirmado" (incluye NULL, pendiente, rechazado).
     * @return int -1 si no existe columna de validación
     */
    public function countPresentWithoutConfirmacion($partidoId) {
        if (!$this->tableReady()) {
            return 0;
        }
        if (!$this->hasValidacionArbitroColumn()) {
            return -1;
        }
        $pid = (int)$partidoId;
        $sql = "SELECT COUNT(*) AS total FROM partido_jugadores_presentes
                WHERE partido_id = :p
                  AND (validacion_arbitro IS NULL
                       OR LTRIM(RTRIM(LOWER(validacion_arbitro))) <> 'confirmado')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':p', $pid, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    public function setValidacionArbitro($partidoId, $jugadorId, $estado) {
        if (!$this->tableReady() || !$this->hasValidacionArbitroColumn()) {
            return false;
        }
        $estado = strtolower(trim((string)$estado));
        if (!in_array($estado, ['confirmado', 'rechazado', 'pendiente'], true)) {
            return false;
        }
        $pid = (int)$partidoId;
        $jid = (int)$jugadorId;
        $val = $estado === 'pendiente' ? null : $estado;
        $sql = "UPDATE partido_jugadores_presentes SET validacion_arbitro = :v WHERE partido_id = :p AND jugador_id = :j";
        $stmt = $this->conn->prepare($sql);
        if ($val === null) {
            $stmt->bindValue(':v', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':v', $val, PDO::PARAM_STR);
        }
        $stmt->bindParam(':p', $pid, PDO::PARAM_INT);
        $stmt->bindParam(':j', $jid, PDO::PARAM_INT);
        return $stmt->execute();
    }
}
?>
