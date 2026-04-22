<?php
require_once 'config/database.php';

class Season {
    private $conn;
    private $hasArbitroColumn = null;
    private $cachedHasAsistenciaTokenColumn = null;
    private $cachedHasAsistenciaDtHabilitadaColumn = null;
    private $cachedHasInicioFinReal = null;
    private $cachedHasCanchaColumn = null;
    private $cachedHasFaseColumn = null;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createWithTeams($leagueId, $nombre, $anio, $fechaInicio, $fechaFin, $diasJuego, $horaInicio, $horaFin, $teamIds) {
        try {
            $this->conn->beginTransaction();

            $querySeason = "INSERT INTO temporadas (liga_id, nombre, anio, fecha_inicio, fecha_fin, dias_juego, hora_inicio, hora_fin, estado)
                            VALUES (:liga_id, :nombre, :anio, :fecha_inicio, :fecha_fin, :dias_juego, :hora_inicio, :hora_fin, 'activa')";
            $stmtSeason = $this->conn->prepare($querySeason);

            $safeNombre = htmlspecialchars(strip_tags($nombre));
            $safeAnio = (int)$anio;
            $safeLeagueId = (int)$leagueId;
            $safeFechaInicio = $fechaInicio;
            $safeFechaFin = trim((string)$fechaFin) === '' ? null : $fechaFin;
            $safeHoraInicio = trim((string)$horaInicio) === '' ? null : $horaInicio;
            $safeHoraFin = trim((string)$horaFin) === '' ? null : $horaFin;
            $safeDiasJuego = implode(',', $diasJuego);

            $stmtSeason->bindParam(':liga_id', $safeLeagueId, PDO::PARAM_INT);
            $stmtSeason->bindParam(':nombre', $safeNombre);
            $stmtSeason->bindParam(':anio', $safeAnio, PDO::PARAM_INT);
            $stmtSeason->bindParam(':fecha_inicio', $safeFechaInicio);
            $this->bindNullableValue($stmtSeason, ':fecha_fin', $safeFechaFin);
            $stmtSeason->bindParam(':dias_juego', $safeDiasJuego);
            $this->bindNullableValue($stmtSeason, ':hora_inicio', $safeHoraInicio);
            $this->bindNullableValue($stmtSeason, ':hora_fin', $safeHoraFin);
            $stmtSeason->execute();

            $seasonId = $this->conn->lastInsertId();
            if (!$seasonId) {
                $seasonIdQuery = "SELECT TOP 1 id FROM temporadas WHERE liga_id = :liga_id ORDER BY id DESC";
                $stmtSeasonId = $this->conn->prepare($seasonIdQuery);
                $stmtSeasonId->bindParam(':liga_id', $safeLeagueId, PDO::PARAM_INT);
                $stmtSeasonId->execute();
                $row = $stmtSeasonId->fetch(PDO::FETCH_ASSOC);
                $seasonId = $row['id'] ?? 0;
            }

            if (!$seasonId) {
                $this->conn->rollBack();
                return false;
            }

            $queryPivot = "INSERT INTO equipos_temporadas (equipo_id, temporada_id) VALUES (:equipo_id, :temporada_id)";
            $stmtPivot = $this->conn->prepare($queryPivot);

            foreach ($teamIds as $teamId) {
                $safeTeamId = (int)$teamId;
                $safeSeasonId = (int)$seasonId;
                $stmtPivot->bindParam(':equipo_id', $safeTeamId, PDO::PARAM_INT);
                $stmtPivot->bindParam(':temporada_id', $safeSeasonId, PDO::PARAM_INT);
                $stmtPivot->execute();
            }

            // Genera calendario de partidos (una sola vuelta, sin repetir cruces).
            $this->generateMatchesForSeason($safeSeasonId, $teamIds, $diasJuego, $safeHoraInicio);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return false;
        }
    }

    public function getByLeagueWithTeams($leagueId) {
        $query = "SELECT t.id, t.nombre, t.anio, t.fecha_inicio, t.fecha_fin, t.dias_juego, t.hora_inicio, t.hora_fin, t.estado,
                         COUNT(et.equipo_id) AS total_equipos,
                         STRING_AGG(e.nombre, ', ') AS equipos
                  FROM temporadas t
                  LEFT JOIN equipos_temporadas et ON et.temporada_id = t.id
                  LEFT JOIN equipos e ON e.id = et.equipo_id
                  WHERE t.liga_id = :liga_id
                  GROUP BY t.id, t.nombre, t.anio, t.fecha_inicio, t.fecha_fin, t.dias_juego, t.hora_inicio, t.hora_fin, t.estado
                  ORDER BY t.anio DESC, t.id DESC";

        $stmt = $this->conn->prepare($query);
        $safeLeagueId = (int)$leagueId;
        $stmt->bindParam(':liga_id', $safeLeagueId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveSeasonsByLeague($leagueId) {
        $query = "SELECT id, nombre, anio, estado
                  FROM temporadas
                  WHERE liga_id = :liga_id AND estado = 'activa'
                  ORDER BY anio DESC, id DESC";
        $stmt = $this->conn->prepare($query);
        $lid = (int)$leagueId;
        $stmt->bindParam(':liga_id', $lid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deactivateById($seasonId, $leagueId) {
        $safeSeasonId = (int)$seasonId;
        $safeLeagueId = (int)$leagueId;

        try {
            $query = "UPDATE temporadas
                      SET estado = 'inactiva'
                      WHERE id = :id AND liga_id = :liga_id AND estado <> 'inactiva'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $safeSeasonId, PDO::PARAM_INT);
            $stmt->bindParam(':liga_id', $safeLeagueId, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Exception $e) {
            // Compatibilidad con esquemas antiguos que usan 'finalizada' en vez de 'inactiva'.
            $queryFallback = "UPDATE temporadas
                              SET estado = 'finalizada'
                              WHERE id = :id AND liga_id = :liga_id AND estado <> 'finalizada'";
            $stmtFallback = $this->conn->prepare($queryFallback);
            $stmtFallback->bindParam(':id', $safeSeasonId, PDO::PARAM_INT);
            $stmtFallback->bindParam(':liga_id', $safeLeagueId, PDO::PARAM_INT);
            return $stmtFallback->execute();
        }
    }

    public function getByIdWithTeams($seasonId, $leagueId) {
        $query = "SELECT id, liga_id, nombre, anio, fecha_inicio, fecha_fin, dias_juego, hora_inicio, hora_fin, estado
                  FROM temporadas
                  WHERE id = :id AND liga_id = :liga_id";

        $stmt = $this->conn->prepare($query);
        $safeSeasonId = (int)$seasonId;
        $safeLeagueId = (int)$leagueId;
        $stmt->bindParam(':id', $safeSeasonId, PDO::PARAM_INT);
        $stmt->bindParam(':liga_id', $safeLeagueId, PDO::PARAM_INT);
        $stmt->execute();
        $season = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$season) {
            return null;
        }

        $queryTeams = "SELECT equipo_id FROM equipos_temporadas WHERE temporada_id = :temporada_id";
        $stmtTeams = $this->conn->prepare($queryTeams);
        $stmtTeams->bindParam(':temporada_id', $safeSeasonId, PDO::PARAM_INT);
        $stmtTeams->execute();
        $teams = $stmtTeams->fetchAll(PDO::FETCH_COLUMN);

        $season['equipos_ids'] = array_map('intval', $teams ?: []);
        return $season;
    }

    public function updateWithTeams($seasonId, $leagueId, $nombre, $anio, $fechaInicio, $fechaFin, $diasJuego, $horaInicio, $horaFin, $teamIds) {
        try {
            $this->conn->beginTransaction();

            $querySeason = "UPDATE temporadas
                            SET nombre = :nombre,
                                anio = :anio,
                                fecha_inicio = :fecha_inicio,
                                fecha_fin = :fecha_fin,
                                dias_juego = :dias_juego,
                                hora_inicio = :hora_inicio,
                                hora_fin = :hora_fin
                            WHERE id = :id AND liga_id = :liga_id";
            $stmtSeason = $this->conn->prepare($querySeason);

            $safeNombre = htmlspecialchars(strip_tags($nombre));
            $safeAnio = (int)$anio;
            $safeLeagueId = (int)$leagueId;
            $safeSeasonId = (int)$seasonId;
            $safeFechaFin = trim((string)$fechaFin) === '' ? null : $fechaFin;
            $safeHoraInicio = trim((string)$horaInicio) === '' ? null : $horaInicio;
            $safeHoraFin = trim((string)$horaFin) === '' ? null : $horaFin;
            $safeDiasJuego = implode(',', $diasJuego);

            $stmtSeason->bindParam(':nombre', $safeNombre);
            $stmtSeason->bindParam(':anio', $safeAnio, PDO::PARAM_INT);
            $stmtSeason->bindParam(':fecha_inicio', $fechaInicio);
            $this->bindNullableValue($stmtSeason, ':fecha_fin', $safeFechaFin);
            $stmtSeason->bindParam(':dias_juego', $safeDiasJuego);
            $this->bindNullableValue($stmtSeason, ':hora_inicio', $safeHoraInicio);
            $this->bindNullableValue($stmtSeason, ':hora_fin', $safeHoraFin);
            $stmtSeason->bindParam(':id', $safeSeasonId, PDO::PARAM_INT);
            $stmtSeason->bindParam(':liga_id', $safeLeagueId, PDO::PARAM_INT);
            $stmtSeason->execute();

            $deletePivot = "DELETE FROM equipos_temporadas WHERE temporada_id = :temporada_id";
            $stmtDelete = $this->conn->prepare($deletePivot);
            $stmtDelete->bindParam(':temporada_id', $safeSeasonId, PDO::PARAM_INT);
            $stmtDelete->execute();

            $insertPivot = "INSERT INTO equipos_temporadas (equipo_id, temporada_id) VALUES (:equipo_id, :temporada_id)";
            $stmtInsert = $this->conn->prepare($insertPivot);

            foreach ($teamIds as $teamId) {
                $safeTeamId = (int)$teamId;
                $stmtInsert->bindParam(':equipo_id', $safeTeamId, PDO::PARAM_INT);
                $stmtInsert->bindParam(':temporada_id', $safeSeasonId, PDO::PARAM_INT);
                $stmtInsert->execute();
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

    public function hasPartidoInicioFinRealColumns() {
        if ($this->cachedHasInicioFinReal !== null) {
            return $this->cachedHasInicioFinReal;
        }
        try {
            $q = "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'partidos' AND COLUMN_NAME IN ('inicio_real', 'fin_real')";
            $stmt = $this->conn->prepare($q);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->cachedHasInicioFinReal = ((int)($row['total'] ?? 0)) >= 2;
        } catch (\Exception $e) {
            $this->cachedHasInicioFinReal = false;
        }
        return $this->cachedHasInicioFinReal;
    }

    public function setMatchInicioReal($matchId, $seasonId, $leagueId) {
        if (!$this->hasPartidoInicioFinRealColumns()) {
            return false;
        }
        $mid = (int)$matchId;
        $sid = (int)$seasonId;
        $lid = (int)$leagueId;
        $sql = "UPDATE partidos SET inicio_real = GETDATE()
                WHERE id = :id AND temporada_id = :sid
                  AND temporada_id IN (SELECT id FROM temporadas WHERE liga_id = :lid)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $mid, PDO::PARAM_INT);
        $stmt->bindParam(':sid', $sid, PDO::PARAM_INT);
        $stmt->bindParam(':lid', $lid, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function setMatchFinReal($matchId, $seasonId, $leagueId) {
        if (!$this->hasPartidoInicioFinRealColumns()) {
            return false;
        }
        $mid = (int)$matchId;
        $sid = (int)$seasonId;
        $lid = (int)$leagueId;
        $sql = "UPDATE partidos SET fin_real = GETDATE()
                WHERE id = :id AND temporada_id = :sid
                  AND temporada_id IN (SELECT id FROM temporadas WHERE liga_id = :lid)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $mid, PDO::PARAM_INT);
        $stmt->bindParam(':sid', $sid, PDO::PARAM_INT);
        $stmt->bindParam(':lid', $lid, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getMatchesBySeason($seasonId, $leagueId) {
        $faseSelect = $this->hasFaseColumn()
            ? ', p.fase'
            : ", 'regular' AS fase";
        $adtSelect = $this->hasAsistenciaDtHabilitadaColumn()
            ? ', ISNULL(p.asistencia_dt_habilitada, 0) AS asistencia_dt_habilitada'
            : ', CAST(0 AS BIT) AS asistencia_dt_habilitada';

        $timeSelect = $this->hasPartidoInicioFinRealColumns()
            ? ', p.inicio_real, p.fin_real'
            : ', CAST(NULL AS DATETIME) AS inicio_real, CAST(NULL AS DATETIME) AS fin_real';
        $canchaSelect = $this->hasCanchaColumn()
            ? ', p.cancha_id, c.nombre AS cancha_nombre'
            : ', CAST(NULL AS INT) AS cancha_id, CAST(NULL AS NVARCHAR(150)) AS cancha_nombre';
        $canchaJoin = $this->hasCanchaColumn()
            ? 'LEFT JOIN canchas c ON c.id = p.cancha_id'
            : '';

        $orderBy = $this->hasPartidoInicioFinRealColumns()
            ? "ORDER BY CASE WHEN p.estado = 'finalizado' THEN 0 ELSE 1 END ASC,
                     CASE WHEN p.estado = 'finalizado' THEN ISNULL(p.fin_real, p.fecha_hora) END DESC,
                     CASE WHEN p.estado <> 'finalizado' THEN p.fecha_hora END ASC,
                     p.id ASC"
            : 'ORDER BY p.fecha_hora ASC, p.id ASC';

        if ($this->hasRefereeColumn()) {
            $query = "SELECT p.id, p.fecha_hora, p.estado
                             $faseSelect,
                             p.equipo_local_id, p.equipo_visitante_id,
                             p.arbitro_id,
                             a.nombre AS arbitro_nombre,
                             el.nombre AS equipo_local_nombre,
                             ev.nombre AS equipo_visitante_nombre,
                             el.logo AS equipo_local_logo,
                             ev.logo AS equipo_visitante_logo,
                             ISNULL(r.goles_local, 0) AS goles_local,
                             ISNULL(r.goles_visitante, 0) AS goles_visitante
                             $adtSelect
                             $timeSelect
                             $canchaSelect
                      FROM partidos p
                      INNER JOIN temporadas t ON t.id = p.temporada_id
                      INNER JOIN equipos el ON el.id = p.equipo_local_id
                      INNER JOIN equipos ev ON ev.id = p.equipo_visitante_id
                      LEFT JOIN arbitros a ON a.id = p.arbitro_id
                      LEFT JOIN resultados r ON r.partido_id = p.id
                      $canchaJoin
                      WHERE p.temporada_id = :temporada_id AND t.liga_id = :liga_id
                      $orderBy";
        } else {
            $query = "SELECT p.id, p.fecha_hora, p.estado
                             $faseSelect,
                             p.equipo_local_id, p.equipo_visitante_id,
                             NULL AS arbitro_id,
                             NULL AS arbitro_nombre,
                             el.nombre AS equipo_local_nombre,
                             ev.nombre AS equipo_visitante_nombre,
                             el.logo AS equipo_local_logo,
                             ev.logo AS equipo_visitante_logo,
                             ISNULL(r.goles_local, 0) AS goles_local,
                             ISNULL(r.goles_visitante, 0) AS goles_visitante
                             $adtSelect
                             $timeSelect
                             $canchaSelect
                      FROM partidos p
                      INNER JOIN temporadas t ON t.id = p.temporada_id
                      INNER JOIN equipos el ON el.id = p.equipo_local_id
                      INNER JOIN equipos ev ON ev.id = p.equipo_visitante_id
                      LEFT JOIN resultados r ON r.partido_id = p.id
                      $canchaJoin
                      WHERE p.temporada_id = :temporada_id AND t.liga_id = :liga_id
                      $orderBy";
        }

        $stmt = $this->conn->prepare($query);
        $safeSeasonId = (int)$seasonId;
        $safeLeagueId = (int)$leagueId;
        $stmt->bindParam(':temporada_id', $safeSeasonId, PDO::PARAM_INT);
        $stmt->bindParam(':liga_id', $safeLeagueId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMatchByIdForSeason($matchId, $seasonId, $leagueId) {
        $canchaSelect = $this->hasCanchaColumn()
            ? ', p.cancha_id, c.nombre AS cancha_nombre'
            : ', CAST(NULL AS INT) AS cancha_id, CAST(NULL AS NVARCHAR(150)) AS cancha_nombre';
        $canchaJoin = $this->hasCanchaColumn()
            ? 'LEFT JOIN canchas c ON c.id = p.cancha_id'
            : '';
        if ($this->hasRefereeColumn()) {
            $query = "SELECT p.*, a.nombre AS arbitro_nombre,
                             el.nombre AS equipo_local_nombre,
                             ev.nombre AS equipo_visitante_nombre
                             $canchaSelect
                      FROM partidos p
                      INNER JOIN temporadas t ON t.id = p.temporada_id
                      INNER JOIN equipos el ON el.id = p.equipo_local_id
                      INNER JOIN equipos ev ON ev.id = p.equipo_visitante_id
                      LEFT JOIN arbitros a ON a.id = p.arbitro_id
                      $canchaJoin
                      WHERE p.id = :id AND p.temporada_id = :temporada_id AND t.liga_id = :liga_id";
        } else {
            $query = "SELECT p.*, NULL AS arbitro_nombre,
                             el.nombre AS equipo_local_nombre,
                             ev.nombre AS equipo_visitante_nombre
                             $canchaSelect
                      FROM partidos p
                      INNER JOIN temporadas t ON t.id = p.temporada_id
                      INNER JOIN equipos el ON el.id = p.equipo_local_id
                      INNER JOIN equipos ev ON ev.id = p.equipo_visitante_id
                      $canchaJoin
                      WHERE p.id = :id AND p.temporada_id = :temporada_id AND t.liga_id = :liga_id";
        }

        $stmt = $this->conn->prepare($query);
        $safeId = (int)$matchId;
        $safeSeasonId = (int)$seasonId;
        $safeLeagueId = (int)$leagueId;
        $stmt->bindParam(':id', $safeId, PDO::PARAM_INT);
        $stmt->bindParam(':temporada_id', $safeSeasonId, PDO::PARAM_INT);
        $stmt->bindParam(':liga_id', $safeLeagueId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateMatch($matchId, $seasonId, $leagueId, $fechaHora, $arbitroId = null, $canchaId = null) {
        $safeMatchId = (int)$matchId;
        $safeSeasonId = (int)$seasonId;
        $safeLeagueId = (int)$leagueId;
        $hasRef = $this->hasRefereeColumn();
        $hasCancha = $this->hasCanchaColumn();

        $setParts = ['fecha_hora = :fecha_hora'];
        if ($hasRef) {
            $setParts[] = 'arbitro_id = :arbitro_id';
        }
        if ($hasCancha) {
            $setParts[] = 'cancha_id = :cancha_id';
        }
        $query = "UPDATE partidos
                  SET " . implode(', ', $setParts) . "
                  WHERE id = :id
                    AND temporada_id = :temporada_id
                    AND temporada_id IN (SELECT id FROM temporadas WHERE liga_id = :liga_id)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fecha_hora', $fechaHora);
        if ($hasRef) {
            if ($arbitroId === null || (int)$arbitroId <= 0) {
                $stmt->bindValue(':arbitro_id', null, PDO::PARAM_NULL);
            } else {
                $safeArbitroId = (int)$arbitroId;
                $stmt->bindValue(':arbitro_id', $safeArbitroId, PDO::PARAM_INT);
            }
        }
        if ($hasCancha) {
            if ($canchaId === null || (int)$canchaId <= 0) {
                $stmt->bindValue(':cancha_id', null, PDO::PARAM_NULL);
            } else {
                $safeCanchaId = (int)$canchaId;
                $stmt->bindValue(':cancha_id', $safeCanchaId, PDO::PARAM_INT);
            }
        }
        $stmt->bindParam(':id', $safeMatchId, PDO::PARAM_INT);
        $stmt->bindParam(':temporada_id', $safeSeasonId, PDO::PARAM_INT);
        $stmt->bindParam(':liga_id', $safeLeagueId, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function updateMatchStatus($matchId, $seasonId, $leagueId, $newStatus, $allowedCurrentStatuses) {
        if (empty($allowedCurrentStatuses)) {
            return false;
        }

        $placeholders = [];
        foreach ($allowedCurrentStatuses as $idx => $value) {
            $placeholders[] = ':status_' . $idx;
        }

        $query = "UPDATE partidos
                  SET estado = :new_status
                  WHERE id = :id
                    AND temporada_id = :temporada_id
                    AND temporada_id IN (SELECT id FROM temporadas WHERE liga_id = :liga_id)
                    AND estado IN (" . implode(', ', $placeholders) . ")";

        $stmt = $this->conn->prepare($query);
        $safeMatchId = (int)$matchId;
        $safeSeasonId = (int)$seasonId;
        $safeLeagueId = (int)$leagueId;

        $stmt->bindParam(':new_status', $newStatus);
        $stmt->bindParam(':id', $safeMatchId, PDO::PARAM_INT);
        $stmt->bindParam(':temporada_id', $safeSeasonId, PDO::PARAM_INT);
        $stmt->bindParam(':liga_id', $safeLeagueId, PDO::PARAM_INT);
        foreach ($allowedCurrentStatuses as $idx => $status) {
            $stmt->bindValue(':status_' . $idx, $status);
        }

        return $stmt->execute();
    }

    public function ensureAttendanceTokenForMatch($matchId) {
        if (!$this->hasAsistenciaTokenColumn()) {
            return null;
        }
        $safeId = (int)$matchId;
        $query = "SELECT id, asistencia_token FROM partidos WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $safeId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        if (!empty($row['asistencia_token'])) {
            return $row['asistencia_token'];
        }
        $token = bin2hex(random_bytes(16));
        $upd = "UPDATE partidos SET asistencia_token = :token WHERE id = :id";
        $stmtUpd = $this->conn->prepare($upd);
        $stmtUpd->bindParam(':token', $token);
        $stmtUpd->bindParam(':id', $safeId, PDO::PARAM_INT);
        $stmtUpd->execute();
        return $token;
    }

    public function getMatchByAttendanceToken($token) {
        if (!$this->hasAsistenciaTokenColumn() || trim((string)$token) === '') {
            return null;
        }
        $query = "SELECT p.*, t.liga_id
                  FROM partidos p
                  INNER JOIN temporadas t ON t.id = p.temporada_id
                  WHERE p.asistencia_token = :token";
        $stmt = $this->conn->prepare($query);
        $safeToken = trim((string)$token);
        $stmt->bindParam(':token', $safeToken);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUpcomingMatchesForTeam($equipoId, $leagueId) {
        $faseSelect = $this->hasFaseColumn()
            ? ', p.fase'
            : ", 'regular' AS fase";
        $adtSelect = $this->hasAsistenciaDtHabilitadaColumn()
            ? ', ISNULL(p.asistencia_dt_habilitada, 0) AS asistencia_dt_habilitada'
            : ', CAST(0 AS BIT) AS asistencia_dt_habilitada';

        $query = "SELECT p.id, p.temporada_id, p.fecha_hora, p.estado,
                         p.equipo_local_id, p.equipo_visitante_id,
                         el.nombre AS equipo_local_nombre,
                         ev.nombre AS equipo_visitante_nombre,
                         t.nombre AS temporada_nombre
                         $faseSelect
                         $adtSelect
                  FROM partidos p
                  INNER JOIN temporadas t ON t.id = p.temporada_id
                  INNER JOIN equipos el ON el.id = p.equipo_local_id
                  INNER JOIN equipos ev ON ev.id = p.equipo_visitante_id
                  WHERE t.liga_id = :liga_id
                    AND p.estado = 'programado'
                    AND (p.equipo_local_id = :equipo_a OR p.equipo_visitante_id = :equipo_b)
                  ORDER BY p.fecha_hora ASC, p.id ASC";
        $stmt = $this->conn->prepare($query);
        $eid = (int)$equipoId;
        $lid = (int)$leagueId;
        $stmt->bindParam(':liga_id', $lid, PDO::PARAM_INT);
        $stmt->bindParam(':equipo_a', $eid, PDO::PARAM_INT);
        $stmt->bindParam(':equipo_b', $eid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMatchesForReferee($arbitroId, $leagueId) {
        $faseSelect = $this->hasFaseColumn()
            ? ', p.fase'
            : ", 'regular' AS fase";
        $adtSelect = $this->hasAsistenciaDtHabilitadaColumn()
            ? ', ISNULL(p.asistencia_dt_habilitada, 0) AS asistencia_dt_habilitada'
            : ', CAST(0 AS BIT) AS asistencia_dt_habilitada';

        $query = "SELECT p.id, p.temporada_id, p.fecha_hora, p.estado, p.arbitro_id,
                         p.equipo_local_id, p.equipo_visitante_id,
                         el.nombre AS equipo_local_nombre,
                         ev.nombre AS equipo_visitante_nombre,
                         t.nombre AS temporada_nombre
                         $faseSelect
                         $adtSelect
                  FROM partidos p
                  INNER JOIN temporadas t ON t.id = p.temporada_id
                  INNER JOIN equipos el ON el.id = p.equipo_local_id
                  INNER JOIN equipos ev ON ev.id = p.equipo_visitante_id
                  WHERE t.liga_id = :liga_id
                    AND p.arbitro_id = :arbitro_id
                    AND p.estado IN ('programado', 'en curso')
                  ORDER BY p.fecha_hora ASC, p.id ASC";
        $stmt = $this->conn->prepare($query);
        $aid = (int)$arbitroId;
        $lid = (int)$leagueId;
        $stmt->bindParam(':liga_id', $lid, PDO::PARAM_INT);
        $stmt->bindParam(':arbitro_id', $aid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function bindNullableValue($stmt, $param, $value) {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($param, $value, PDO::PARAM_STR);
    }

    private function generateMatchesForSeason($seasonId, $teamIds, $diasJuego, $horaInicio) {
        $teams = array_values(array_map('intval', $teamIds));
        if (count($teams) < 2) {
            return;
        }

        $weekdayMap = [
            'domingo' => 0,
            'lunes' => 1,
            'martes' => 2,
            'miercoles' => 3,
            'jueves' => 4,
            'viernes' => 5,
            'sabado' => 6
        ];

        $allowedWeekdays = [];
        foreach ($diasJuego as $dia) {
            if (isset($weekdayMap[$dia])) {
                $allowedWeekdays[] = $weekdayMap[$dia];
            }
        }
        $allowedWeekdays = array_values(array_unique($allowedWeekdays));
        sort($allowedWeekdays);

        if (empty($allowedWeekdays)) {
            return;
        }

        $kickoffTime = trim((string)$horaInicio) !== '' ? trim((string)$horaInicio) : '09:00';
        $currentDate = new DateTime(date('Y-m-d')); // desde la fecha de creación del torneo
        $matchDate = $this->nextAllowedDate($currentDate, $allowedWeekdays);

        $insertMatch = $this->hasFaseColumn()
            ? "INSERT INTO partidos (temporada_id, equipo_local_id, equipo_visitante_id, fecha_hora, estado, fase)
               VALUES (:temporada_id, :equipo_local_id, :equipo_visitante_id, :fecha_hora, 'programado', 'regular')"
            : "INSERT INTO partidos (temporada_id, equipo_local_id, equipo_visitante_id, fecha_hora, estado)
               VALUES (:temporada_id, :equipo_local_id, :equipo_visitante_id, :fecha_hora, 'programado')";
        $stmtMatch = $this->conn->prepare($insertMatch);

        // Todos contra todos a una sola vuelta (cada cruce ocurre una vez).
        $pairs = [];
        $total = count($teams);
        for ($i = 0; $i < $total; $i++) {
            for ($j = $i + 1; $j < $total; $j++) {
                $pairs[] = [$teams[$i], $teams[$j]];
            }
        }

        foreach ($pairs as $pair) {
            $fechaHora = $matchDate->format('Y-m-d') . ' ' . $kickoffTime . ':00';
            $localId = (int)$pair[0];
            $visitanteId = (int)$pair[1];
            $safeSeasonId = (int)$seasonId;

            $stmtMatch->bindParam(':temporada_id', $safeSeasonId, PDO::PARAM_INT);
            $stmtMatch->bindParam(':equipo_local_id', $localId, PDO::PARAM_INT);
            $stmtMatch->bindParam(':equipo_visitante_id', $visitanteId, PDO::PARAM_INT);
            $stmtMatch->bindParam(':fecha_hora', $fechaHora);
            $stmtMatch->execute();

            // Próxima fecha disponible de juego.
            $matchDate->modify('+1 day');
            $matchDate = $this->nextAllowedDate($matchDate, $allowedWeekdays);
        }
    }

    private function nextAllowedDate(DateTime $date, $allowedWeekdays) {
        $candidate = clone $date;
        while (!in_array((int)$candidate->format('w'), $allowedWeekdays, true)) {
            $candidate->modify('+1 day');
        }
        return $candidate;
    }

    public function hasRefereeColumn() {
        if ($this->hasArbitroColumn !== null) {
            return $this->hasArbitroColumn;
        }

        $query = "SELECT COUNT(*) AS total
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'partidos' AND COLUMN_NAME = 'arbitro_id'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->hasArbitroColumn = ((int)($row['total'] ?? 0)) > 0;
        return $this->hasArbitroColumn;
    }

    public function hasAsistenciaTokenColumn() {
        if ($this->cachedHasAsistenciaTokenColumn !== null) {
            return $this->cachedHasAsistenciaTokenColumn;
        }
        $query = "SELECT COUNT(*) AS total
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'partidos' AND COLUMN_NAME = 'asistencia_token'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->cachedHasAsistenciaTokenColumn = ((int)($row['total'] ?? 0)) > 0;
        return $this->cachedHasAsistenciaTokenColumn;
    }

    public function hasAsistenciaDtHabilitadaColumn() {
        if ($this->cachedHasAsistenciaDtHabilitadaColumn !== null) {
            return $this->cachedHasAsistenciaDtHabilitadaColumn;
        }
        $query = "SELECT COUNT(*) AS total
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'partidos' AND COLUMN_NAME = 'asistencia_dt_habilitada'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->cachedHasAsistenciaDtHabilitadaColumn = ((int)($row['total'] ?? 0)) > 0;
        return $this->cachedHasAsistenciaDtHabilitadaColumn;
    }

    public function hasCanchaColumn() {
        if ($this->cachedHasCanchaColumn !== null) {
            return $this->cachedHasCanchaColumn;
        }
        $query = "SELECT COUNT(*) AS total
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'partidos' AND COLUMN_NAME = 'cancha_id'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->cachedHasCanchaColumn = ((int)($row['total'] ?? 0)) > 0;
        return $this->cachedHasCanchaColumn;
    }

    public function hasFaseColumn() {
        if ($this->cachedHasFaseColumn !== null) {
            return $this->cachedHasFaseColumn;
        }
        $query = "SELECT COUNT(*) AS total
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'partidos' AND COLUMN_NAME = 'fase'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->cachedHasFaseColumn = ((int)($row['total'] ?? 0)) > 0;
        return $this->cachedHasFaseColumn;
    }

    public function getKnockoutStageAvailability($seasonId, $leagueId) {
        $sid = (int)$seasonId;
        $lid = (int)$leagueId;
        if ($sid <= 0 || $lid <= 0) {
            return ['enabled' => false, 'label' => null, 'reason' => 'invalid'];
        }

        $season = $this->getByIdWithTeams($sid, $lid);
        if (!$season) {
            return ['enabled' => false, 'label' => null, 'reason' => 'season_not_found'];
        }

        $teamIds = array_values(array_unique(array_map('intval', $season['equipos_ids'] ?? [])));
        $totalTeams = count($teamIds);
        $regularMatches = (int)(($totalTeams * ($totalTeams - 1)) / 2);
        $knockoutTeams = $this->resolveKnockoutTeams($totalTeams);

        if ($regularMatches <= 0 || $knockoutTeams < 2) {
            return ['enabled' => false, 'label' => null, 'reason' => 'insufficient_teams'];
        }

        $countSql = $this->hasFaseColumn()
            ? "SELECT COUNT(*) AS total FROM partidos WHERE temporada_id = :sid AND fase <> 'regular'"
            : "SELECT COUNT(*) AS total FROM partidos WHERE temporada_id = :sid";
        $countStmt = $this->conn->prepare($countSql);
        $countStmt->bindParam(':sid', $sid, PDO::PARAM_INT);
        $countStmt->execute();
        $extraMatches = (int)($countStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        if ($extraMatches > 0) {
            return ['enabled' => false, 'label' => null, 'reason' => 'already_generated'];
        }

        $pendingSql = $this->hasFaseColumn()
            ? "SELECT COUNT(*) AS total
               FROM partidos
               WHERE temporada_id = :sid
                 AND fase = 'regular'
                 AND estado <> 'finalizado'"
            : "SELECT COUNT(*) AS total
               FROM partidos
               WHERE temporada_id = :sid
                 AND estado <> 'finalizado'";
        $pendingStmt = $this->conn->prepare($pendingSql);
        $pendingStmt->bindParam(':sid', $sid, PDO::PARAM_INT);
        $pendingStmt->execute();
        $pending = (int)($pendingStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        if ($pending > 0) {
            return ['enabled' => false, 'label' => null, 'reason' => 'pending_regular_matches'];
        }

        return [
            'enabled' => true,
            'label' => $this->getKnockoutLabelByTeams($knockoutTeams),
            'knockout_teams' => $knockoutTeams,
            'reason' => null
        ];
    }

    public function createInitialKnockoutStage($seasonId, $leagueId) {
        $sid = (int)$seasonId;
        $lid = (int)$leagueId;
        $availability = $this->getKnockoutStageAvailability($sid, $lid);
        if (empty($availability['enabled'])) {
            return ['ok' => false, 'error' => $availability['reason'] ?? 'not_available'];
        }

        $teamsForBracket = (int)($availability['knockout_teams'] ?? 0);
        $standings = $this->buildStandingsForSeason($sid);
        if (count($standings) < $teamsForBracket) {
            return ['ok' => false, 'error' => 'insufficient_ranked_teams'];
        }

        $qualified = array_slice($standings, 0, $teamsForBracket);
        $season = $this->getByIdWithTeams($sid, $lid);
        $kickoff = !empty($season['hora_inicio']) ? substr((string)$season['hora_inicio'], 0, 5) : '09:00';
        if (strlen($kickoff) !== 5) {
            $kickoff = '09:00';
        }

        $nextDate = new DateTime(date('Y-m-d'));
        $nextDate->modify('+1 day');
        $fechaHora = $nextDate->format('Y-m-d') . ' ' . $kickoff . ':00';

        $knockoutPhase = $this->getKnockoutPhaseByTeams($teamsForBracket);
        $insertSql = $this->hasFaseColumn()
            ? "INSERT INTO partidos (temporada_id, equipo_local_id, equipo_visitante_id, fecha_hora, estado, fase)
               VALUES (:sid, :local_id, :visit_id, :fecha_hora, 'programado', :fase)"
            : "INSERT INTO partidos (temporada_id, equipo_local_id, equipo_visitante_id, fecha_hora, estado)
               VALUES (:sid, :local_id, :visit_id, :fecha_hora, 'programado')";
        $insert = $this->conn->prepare($insertSql);

        try {
            $this->conn->beginTransaction();
            $numMatches = (int)($teamsForBracket / 2);
            for ($i = 0; $i < $numMatches; $i++) {
                $local = (int)$qualified[$i]['equipo_id'];
                $visit = (int)$qualified[$teamsForBracket - 1 - $i]['equipo_id'];
                $insert->bindParam(':sid', $sid, PDO::PARAM_INT);
                $insert->bindParam(':local_id', $local, PDO::PARAM_INT);
                $insert->bindParam(':visit_id', $visit, PDO::PARAM_INT);
                $insert->bindParam(':fecha_hora', $fechaHora);
                if ($this->hasFaseColumn()) {
                    $insert->bindParam(':fase', $knockoutPhase);
                }
                $insert->execute();
            }
            $this->conn->commit();
            return ['ok' => true, 'label' => $availability['label']];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['ok' => false, 'error' => 'insert_failed'];
        }
    }

    private function buildStandingsForSeason($seasonId) {
        $sid = (int)$seasonId;
        if ($sid <= 0) {
            return [];
        }

        $teamsStmt = $this->conn->prepare(
            "SELECT e.id AS equipo_id, e.nombre, e.logo
             FROM equipos e
             INNER JOIN equipos_temporadas et ON et.equipo_id = e.id
             WHERE et.temporada_id = :sid"
        );
        $teamsStmt->bindParam(':sid', $sid, PDO::PARAM_INT);
        $teamsStmt->execute();
        $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stats = [];
        foreach ($teams as $team) {
            $id = (int)$team['equipo_id'];
            $stats[$id] = [
                'equipo_id' => $id,
                'nombre' => (string)$team['nombre'],
                'logo' => (string)($team['logo'] ?? ''),
                'gf' => 0,
                'gc' => 0,
                'pts' => 0,
            ];
        }

        $matchesStmt = $this->conn->prepare(
            "SELECT p.equipo_local_id, p.equipo_visitante_id,
                    ISNULL(r.goles_local, 0) AS gl, ISNULL(r.goles_visitante, 0) AS gv
             FROM partidos p
             INNER JOIN resultados r ON r.partido_id = p.id
             WHERE p.temporada_id = :sid AND p.estado = 'finalizado'"
        );
        $matchesStmt->bindParam(':sid', $sid, PDO::PARAM_INT);
        $matchesStmt->execute();

        while ($match = $matchesStmt->fetch(PDO::FETCH_ASSOC)) {
            $localId = (int)$match['equipo_local_id'];
            $visitId = (int)$match['equipo_visitante_id'];
            if (!isset($stats[$localId]) || !isset($stats[$visitId])) {
                continue;
            }
            $gl = (int)$match['gl'];
            $gv = (int)$match['gv'];
            $stats[$localId]['gf'] += $gl;
            $stats[$localId]['gc'] += $gv;
            $stats[$visitId]['gf'] += $gv;
            $stats[$visitId]['gc'] += $gl;

            if ($gl > $gv) {
                $stats[$localId]['pts'] += 3;
            } elseif ($gl < $gv) {
                $stats[$visitId]['pts'] += 3;
            } else {
                $stats[$localId]['pts'] += 1;
                $stats[$visitId]['pts'] += 1;
            }
        }

        $rows = array_values($stats);
        usort($rows, function ($a, $b) {
            if ($a['pts'] !== $b['pts']) {
                return $b['pts'] - $a['pts'];
            }
            $difA = $a['gf'] - $a['gc'];
            $difB = $b['gf'] - $b['gc'];
            if ($difA !== $difB) {
                return $difB - $difA;
            }
            if ($a['gf'] !== $b['gf']) {
                return $b['gf'] - $a['gf'];
            }
            return strcasecmp($a['nombre'], $b['nombre']);
        });

        return $rows;
    }

    private function resolveKnockoutTeams($totalTeams) {
        $total = (int)$totalTeams;
        if ($total >= 16) {
            return 16;
        }
        if ($total >= 8) {
            return 8;
        }
        if ($total >= 4) {
            return 4;
        }
        if ($total >= 2) {
            return 2;
        }
        return 0;
    }

    private function getKnockoutLabelByTeams($teamsInRound) {
        $teams = (int)$teamsInRound;
        if ($teams === 16) {
            return 'Octavos de final';
        }
        if ($teams === 8) {
            return 'Cuartos de final';
        }
        if ($teams === 4) {
            return 'Semifinal';
        }
        if ($teams === 2) {
            return 'Final';
        }
        return 'Eliminatoria';
    }

    private function getKnockoutPhaseByTeams($teamsInRound) {
        $teams = (int)$teamsInRound;
        if ($teams === 16) {
            return 'octavos';
        }
        if ($teams === 8) {
            return 'cuartos';
        }
        if ($teams === 4) {
            return 'semifinal';
        }
        if ($teams === 2) {
            return 'final';
        }
        return 'eliminatoria';
    }

    public function getSeasonFinalAwards($seasonId, $leagueId) {
        $sid = (int)$seasonId;
        $lid = (int)$leagueId;
        if ($sid <= 0 || $lid <= 0) {
            return null;
        }

        $season = $this->getByIdWithTeams($sid, $lid);
        if (!$season) {
            return null;
        }

        $pendingStmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total FROM partidos WHERE temporada_id = :sid AND estado <> 'finalizado'"
        );
        $pendingStmt->bindParam(':sid', $sid, PDO::PARAM_INT);
        $pendingStmt->execute();
        $pending = (int)($pendingStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        if ($pending > 0) {
            return null;
        }

        $totalStmt = $this->conn->prepare(
            "SELECT COUNT(*) AS total FROM partidos WHERE temporada_id = :sid"
        );
        $totalStmt->bindParam(':sid', $sid, PDO::PARAM_INT);
        $totalStmt->execute();
        $totalMatches = (int)($totalStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
        if ($totalMatches <= 0) {
            return null;
        }

        return [
            'champion' => $this->resolveSeasonChampion($sid),
            'top_scorers' => $this->getSeasonTopScorers($sid, 3),
            'clean_team' => $this->getSeasonFairPlayTeam($sid),
            'best_goalkeeper_team' => $this->getSeasonBestGoalkeeperTeam($sid),
        ];
    }

    private function resolveSeasonChampion($seasonId) {
        $sid = (int)$seasonId;
        if ($this->hasFaseColumn()) {
            $finalStmt = $this->conn->prepare(
                "SELECT TOP 1 el.id AS local_id, el.nombre AS local_nombre, el.logo AS local_logo,
                                ev.id AS visit_id, ev.nombre AS visit_nombre, ev.logo AS visit_logo,
                                ISNULL(r.goles_local, 0) AS gl, ISNULL(r.goles_visitante, 0) AS gv
                 FROM partidos p
                 INNER JOIN equipos el ON el.id = p.equipo_local_id
                 INNER JOIN equipos ev ON ev.id = p.equipo_visitante_id
                 LEFT JOIN resultados r ON r.partido_id = p.id
                 WHERE p.temporada_id = :sid
                   AND p.estado = 'finalizado'
                   AND p.fase = 'final'
                 ORDER BY p.id DESC"
            );
            $finalStmt->bindParam(':sid', $sid, PDO::PARAM_INT);
            $finalStmt->execute();
            $final = $finalStmt->fetch(PDO::FETCH_ASSOC);
            if ($final) {
                $gl = (int)($final['gl'] ?? 0);
                $gv = (int)($final['gv'] ?? 0);
                if ($gl > $gv) {
                    return [
                        'id' => (int)$final['local_id'],
                        'name' => (string)$final['local_nombre'],
                        'logo' => (string)($final['local_logo'] ?? ''),
                        'source' => 'final'
                    ];
                }
                if ($gv > $gl) {
                    return [
                        'id' => (int)$final['visit_id'],
                        'name' => (string)$final['visit_nombre'],
                        'logo' => (string)($final['visit_logo'] ?? ''),
                        'source' => 'final'
                    ];
                }
            }
        }

        $standings = $this->buildStandingsForSeason($sid);
        if (empty($standings)) {
            return null;
        }
        return [
            'id' => (int)$standings[0]['equipo_id'],
            'name' => (string)$standings[0]['nombre'],
            'logo' => (string)($standings[0]['logo'] ?? ''),
            'source' => 'tabla_regular'
        ];
    }

    private function getSeasonTopScorers($seasonId, $limit = 3) {
        $sid = (int)$seasonId;
        $lim = max(1, min(10, (int)$limit));
        $stmt = $this->conn->prepare(
            "SELECT TOP " . $lim . " j.nombre AS jugador_nombre,
                    e.nombre AS equipo_nombre,
                    COUNT(g.id) AS goles
             FROM goles g
             INNER JOIN partidos p ON p.id = g.partido_id
             INNER JOIN jugadores j ON j.id = g.jugador_id
             INNER JOIN equipos e ON e.id = j.equipo_id
             WHERE p.temporada_id = :sid
             GROUP BY j.id, j.nombre, e.nombre
             ORDER BY COUNT(g.id) DESC, j.nombre ASC"
        );
        $stmt->bindParam(':sid', $sid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function getSeasonFairPlayTeam($seasonId) {
        $sid = (int)$seasonId;
        $stmt = $this->conn->prepare(
            "SELECT TOP 1 e.nombre AS equipo_nombre,
                    ISNULL(SUM(CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END), 0) AS total_tarjetas
             FROM equipos_temporadas et
             INNER JOIN equipos e ON e.id = et.equipo_id
             LEFT JOIN jugadores j ON j.equipo_id = e.id
             LEFT JOIN tarjetas t ON t.jugador_id = j.id
             LEFT JOIN partidos p ON p.id = t.partido_id AND p.temporada_id = :sid
             WHERE et.temporada_id = :sid2
             GROUP BY e.id, e.nombre
             ORDER BY total_tarjetas ASC, e.nombre ASC"
        );
        $stmt->bindParam(':sid', $sid, PDO::PARAM_INT);
        $stmt->bindParam(':sid2', $sid, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function getSeasonBestGoalkeeperTeam($seasonId) {
        $sid = (int)$seasonId;
        $standings = $this->buildStandingsForSeason($sid);
        if (empty($standings)) {
            return null;
        }
        $best = $standings[0];
        foreach ($standings as $row) {
            if ((int)$row['gc'] < (int)$best['gc']) {
                $best = $row;
            } elseif ((int)$row['gc'] === (int)$best['gc'] && strcasecmp((string)$row['nombre'], (string)$best['nombre']) < 0) {
                $best = $row;
            }
        }
        return [
            'equipo_nombre' => (string)$best['nombre'],
            'goles_recibidos' => (int)$best['gc'],
        ];
    }

    public function setMatchAsistenciaDtHabilitada($matchId, $seasonId, $leagueId, $enabled) {
        if (!$this->hasAsistenciaDtHabilitadaColumn()) {
            return false;
        }
        $query = "UPDATE partidos
                  SET asistencia_dt_habilitada = :hab
                  WHERE id = :id
                    AND temporada_id = :temporada_id
                    AND temporada_id IN (SELECT id FROM temporadas WHERE liga_id = :liga_id)
                    AND estado = 'programado'";
        $stmt = $this->conn->prepare($query);
        $hab = $enabled ? 1 : 0;
        $mid = (int)$matchId;
        $sid = (int)$seasonId;
        $lid = (int)$leagueId;
        $stmt->bindValue(':hab', $hab, PDO::PARAM_INT);
        $stmt->bindParam(':id', $mid, PDO::PARAM_INT);
        $stmt->bindParam(':temporada_id', $sid, PDO::PARAM_INT);
        $stmt->bindParam(':liga_id', $lid, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * Próximo partido programado (desde ahora) en temporada activa donde participa el equipo.
     */
    public function getNextScheduledMatchForTeam($equipoId, $leagueId) {
        $eid = (int)$equipoId;
        $lid = (int)$leagueId;
        if ($eid <= 0 || $lid <= 0) {
            return null;
        }
        $query = "SELECT TOP 1 p.id, p.temporada_id, p.fecha_hora
                  FROM partidos p
                  INNER JOIN temporadas t ON t.id = p.temporada_id
                  WHERE t.liga_id = :liga_id
                    AND t.estado = 'activa'
                    AND p.estado = 'programado'
                    AND (p.equipo_local_id = :equipo_id OR p.equipo_visitante_id = :equipo_id2)
                    AND p.fecha_hora >= GETDATE()
                  ORDER BY p.fecha_hora ASC, p.id ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':liga_id', $lid, PDO::PARAM_INT);
        $stmt->bindParam(':equipo_id', $eid, PDO::PARAM_INT);
        $stmt->bindParam(':equipo_id2', $eid, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
?>
