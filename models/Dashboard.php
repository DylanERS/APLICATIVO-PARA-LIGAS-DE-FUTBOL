<?php
require_once 'config/database.php';

class Dashboard {
    private $conn;
    private $cachedHasPartidoFinReal = null;
    private $cachedHasPartidoFase = null;
    /** @var array<string,bool|null> */
    private $cachedHasTable = [];
    private $cachedTarjetaMotivoColumn = null;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getTotalEquipos() {
        $query = "SELECT COUNT(*) as total FROM equipos";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getTotalJugadores() {
        $query = "SELECT COUNT(*) as total FROM jugadores";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getPartidosJugados() {
        $query = "SELECT COUNT(*) as total FROM partidos WHERE estado = 'finalizado'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getEntradasDinero() {
        $query = "SELECT ISNULL(SUM(monto), 0) as total FROM pagos";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'] ?? 0;
    }

    public function getTemporadaActiva() {
        $query = "SELECT TOP 1 id, nombre, anio, estado
                  FROM temporadas
                  WHERE estado = 'activa'
                  ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $season = $stmt->fetch(PDO::FETCH_ASSOC);
        return $season ?: null;
    }

    public function getProximosPartidos($temporadaId = null, $limit = 5) {
        $query = "SELECT p.*, el.nombre as local_nombre, ev.nombre as visitante_nombre
                  FROM partidos p 
                  JOIN equipos el ON p.equipo_local_id = el.id 
                  JOIN equipos ev ON p.equipo_visitante_id = ev.id 
                  WHERE p.fecha_hora >= GETDATE()
                    AND p.estado IN ('programado', 'en curso')";

        if ($temporadaId !== null) {
            $query .= " AND p.temporada_id = :temporada_id";
        }

        $query .= " ORDER BY p.fecha_hora ASC 
                  OFFSET 0 ROWS FETCH NEXT :limit ROWS ONLY";

        $stmt = $this->conn->prepare($query);
        if ($temporadaId !== null) {
            $safeTemporadaId = (int)$temporadaId;
            $stmt->bindParam(':temporada_id', $safeTemporadaId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Columna partidos.fin_real (migración sqlserver_partidos_inicio_fin_real.sql).
     */
    private function hasPartidoFinRealColumn() {
        if ($this->cachedHasPartidoFinReal !== null) {
            return $this->cachedHasPartidoFinReal;
        }
        try {
            $q = "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'partidos' AND COLUMN_NAME = 'fin_real'";
            $stmt = $this->conn->prepare($q);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->cachedHasPartidoFinReal = ((int)($row['total'] ?? 0)) > 0;
        } catch (\Exception $e) {
            $this->cachedHasPartidoFinReal = false;
        }
        return $this->cachedHasPartidoFinReal;
    }

    /**
     * Columna partidos.fase (regular, octavos, cuartos, semifinal, final...).
     */
    private function hasPartidoFaseColumn() {
        if ($this->cachedHasPartidoFase !== null) {
            return $this->cachedHasPartidoFase;
        }
        try {
            $q = "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'partidos' AND COLUMN_NAME = 'fase'";
            $stmt = $this->conn->prepare($q);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->cachedHasPartidoFase = ((int)($row['total'] ?? 0)) > 0;
        } catch (\Exception $e) {
            $this->cachedHasPartidoFase = false;
        }
        return $this->cachedHasPartidoFase;
    }

    /**
     * Último partido finalizado: por hora real de cierre (fin_real) si existe; si no, por fecha/hora programada.
     */
    public function getUltimoPartidoResultado() {
        $finSel = $this->hasPartidoFinRealColumn()
            ? ', p.fin_real'
            : ', CAST(NULL AS DATETIME) AS fin_real';
        $orderBy = $this->hasPartidoFinRealColumn()
            ? 'ORDER BY ISNULL(p.fin_real, p.fecha_hora) DESC, p.id DESC'
            : 'ORDER BY p.fecha_hora DESC, p.id DESC';

        $query = "SELECT TOP 1 p.id, p.fecha_hora
                         $finSel,
                         el.nombre AS local_nombre,
                         ev.nombre AS visitante_nombre,
                         ISNULL(r.goles_local, 0) AS goles_local,
                         ISNULL(r.goles_visitante, 0) AS goles_visitante
                  FROM partidos p
                  INNER JOIN equipos el ON p.equipo_local_id = el.id
                  INNER JOIN equipos ev ON p.equipo_visitante_id = ev.id
                  LEFT JOIN resultados r ON r.partido_id = p.id
                  WHERE p.estado = 'finalizado'
                  $orderBy";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Todos los partidos finalizados con marcador (todas las temporadas).
     */
    public function getPartidosFinalizadosConResultados() {
        // No anteponer coma a fin_real: va después de temporada_id (evita ", ," en SQL Server).
        $finSel = $this->hasPartidoFinRealColumn()
            ? 'p.fin_real'
            : 'CAST(NULL AS DATETIME) AS fin_real';
        $orderBy = $this->hasPartidoFinRealColumn()
            ? 'ORDER BY ISNULL(p.fin_real, p.fecha_hora) DESC, p.id DESC'
            : 'ORDER BY p.fecha_hora DESC, p.id DESC';

        $query = "SELECT p.id,
                         p.fecha_hora,
                         p.temporada_id,
                         $finSel,
                         ts.nombre AS temporada_nombre,
                         ts.anio AS temporada_anio,
                         el.nombre AS local_nombre,
                         ev.nombre AS visitante_nombre,
                         ISNULL(r.goles_local, 0) AS goles_local,
                         ISNULL(r.goles_visitante, 0) AS goles_visitante
                  FROM partidos p
                  INNER JOIN temporadas ts ON ts.id = p.temporada_id
                  INNER JOIN equipos el ON p.equipo_local_id = el.id
                  INNER JOIN equipos ev ON p.equipo_visitante_id = ev.id
                  LEFT JOIN resultados r ON r.partido_id = p.id
                  WHERE p.estado = 'finalizado'
                  $orderBy";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Marcadores de resultados por partido_id.
     *
     * @param array<int,int> $partidoIds
     * @return array<int,array<string,mixed>>
     */
    public function getResultadosByPartidoIds(array $partidoIds) {
        $ids = array_values(array_unique(array_map('intval', $partidoIds)));
        $ids = array_values(array_filter($ids, function ($id) {
            return $id > 0;
        }));
        if (empty($ids)) {
            return [];
        }

        $placeholders = [];
        foreach ($ids as $idx => $_) {
            $placeholders[] = ':id_' . $idx;
        }

        $sql = "SELECT partido_id,
                       ISNULL(goles_local, 0) AS goles_local,
                       ISNULL(goles_visitante, 0) AS goles_visitante
                FROM resultados
                WHERE partido_id IN (" . implode(',', $placeholders) . ")";
        $stmt = $this->conn->prepare($sql);
        foreach ($ids as $idx => $id) {
            $stmt->bindValue(':id_' . $idx, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['partido_id']] = $r;
        }
        return $out;
    }

    /**
     * Cabecera de un partido finalizado (detalle + timeline).
     */
    public function getPartidoFinalizadoDetalle($partidoId) {
        $pid = (int)$partidoId;
        if ($pid <= 0) {
            return null;
        }
        $finSel = $this->hasPartidoFinRealColumn()
            ? 'p.fin_real'
            : 'CAST(NULL AS DATETIME) AS fin_real';

        $query = "SELECT p.id,
                         p.fecha_hora,
                         p.temporada_id,
                         p.equipo_local_id,
                         p.equipo_visitante_id,
                         $finSel,
                         ts.nombre AS temporada_nombre,
                         ts.anio AS temporada_anio,
                         el.nombre AS local_nombre,
                         ev.nombre AS visitante_nombre,
                         ISNULL(r.goles_local, 0) AS goles_local,
                         ISNULL(r.goles_visitante, 0) AS goles_visitante,
                         r.observaciones AS observaciones
                  FROM partidos p
                  INNER JOIN temporadas ts ON ts.id = p.temporada_id
                  INNER JOIN equipos el ON p.equipo_local_id = el.id
                  INNER JOIN equipos ev ON p.equipo_visitante_id = ev.id
                  LEFT JOIN resultados r ON r.partido_id = p.id
                  WHERE p.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $pid, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function hasTableNamed($name) {
        if (!in_array($name, ['goles', 'tarjetas'], true)) {
            return false;
        }
        if (array_key_exists($name, $this->cachedHasTable) && $this->cachedHasTable[$name] !== null) {
            return $this->cachedHasTable[$name];
        }
        try {
            $q = 'SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?';
            $stmt = $this->conn->prepare($q);
            $stmt->bindParam(1, $name, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->cachedHasTable[$name] = ((int)($row['total'] ?? 0)) > 0;
        } catch (\Exception $e) {
            $this->cachedHasTable[$name] = false;
        }
        return $this->cachedHasTable[$name];
    }

    private function hasTarjetaMotivoColumn() {
        if ($this->cachedTarjetaMotivoColumn !== null) {
            return $this->cachedTarjetaMotivoColumn;
        }
        if (!$this->hasTableNamed('tarjetas')) {
            $this->cachedTarjetaMotivoColumn = false;
            return false;
        }
        try {
            $q = "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = 'tarjetas' AND COLUMN_NAME = 'motivo'";
            $stmt = $this->conn->prepare($q);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->cachedTarjetaMotivoColumn = ((int)($row['total'] ?? 0)) > 0;
        } catch (\Exception $e) {
            $this->cachedTarjetaMotivoColumn = false;
        }
        return $this->cachedTarjetaMotivoColumn;
    }

    /**
     * Eventos ordenados por minuto: goles y tarjetas (línea del tiempo).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPartidoTimeline(
        $partidoId,
        $equipoLocalId,
        $equipoVisitId,
        $localNombre,
        $visitNombre
    ) {
        $pid = (int)$partidoId;
        $lid = (int)$equipoLocalId;
        $vid = (int)$equipoVisitId;
        if ($pid <= 0) {
            return [];
        }

        $events = [];

        if ($this->hasTableNamed('goles')) {
            $stmt = $this->conn->prepare(
                'SELECT g.minuto, g.id AS evento_id, j.nombre AS jugador_nombre, j.numero, j.equipo_id
                 FROM goles g
                 INNER JOIN jugadores j ON j.id = g.jugador_id
                 WHERE g.partido_id = ?
                 ORDER BY g.minuto ASC, g.id ASC'
            );
            $stmt->bindParam(1, $pid, PDO::PARAM_INT);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $eid = (int)$row['equipo_id'];
                $side = ($eid === $lid) ? 'local' : 'visitante';
                $teamName = ($eid === $lid) ? $localNombre : $visitNombre;
                $events[] = [
                    'minuto' => (int)$row['minuto'],
                    'tipo' => 'gol',
                    'sort_key' => 0,
                    'jugador' => $row['jugador_nombre'],
                    'numero' => $row['numero'],
                    'lado' => $side,
                    'equipo_nombre' => $teamName,
                    'tarjeta_tipo' => null,
                    'motivo' => null,
                ];
            }
        }

        if ($this->hasTableNamed('tarjetas')) {
            $motivoSel = $this->hasTarjetaMotivoColumn()
                ? ', t.motivo'
                : ', CAST(NULL AS NVARCHAR(500)) AS motivo';
            $sql = "SELECT t.minuto, t.id AS evento_id, t.tipo AS tarjeta_tipo,
                           j.nombre AS jugador_nombre, j.numero, j.equipo_id
                           $motivoSel
                    FROM tarjetas t
                    INNER JOIN jugadores j ON j.id = t.jugador_id
                    WHERE t.partido_id = ?
                    ORDER BY t.minuto ASC, t.id ASC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(1, $pid, PDO::PARAM_INT);
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $eid = (int)$row['equipo_id'];
                $side = ($eid === $lid) ? 'local' : 'visitante';
                $teamName = ($eid === $lid) ? $localNombre : $visitNombre;
                $events[] = [
                    'minuto' => (int)$row['minuto'],
                    'tipo' => 'tarjeta',
                    'sort_key' => 1,
                    'jugador' => $row['jugador_nombre'],
                    'numero' => $row['numero'],
                    'lado' => $side,
                    'equipo_nombre' => $teamName,
                    'tarjeta_tipo' => $row['tarjeta_tipo'],
                    'motivo' => isset($row['motivo']) ? $row['motivo'] : null,
                ];
            }
        }

        usort($events, function ($a, $b) {
            if ($a['minuto'] !== $b['minuto']) {
                return $a['minuto'] <=> $b['minuto'];
            }
            return ($a['sort_key'] ?? 0) <=> ($b['sort_key'] ?? 0);
        });

        foreach ($events as &$e) {
            unset($e['sort_key']);
        }
        unset($e);

        return $events;
    }

    public function isTeamInSeason($seasonId, $equipoId) {
        $sid = (int)$seasonId;
        $eid = (int)$equipoId;
        if ($sid <= 0 || $eid <= 0) {
            return false;
        }
        $q = "SELECT 1 AS ok FROM equipos_temporadas WHERE temporada_id = ? AND equipo_id = ?";
        $stmt = $this->conn->prepare($q);
        $stmt->bindParam(1, $sid, PDO::PARAM_INT);
        $stmt->bindParam(2, $eid, PDO::PARAM_INT);
        $stmt->execute();
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getStandingsForSeason($seasonId) {
        $sid = (int)$seasonId;
        if ($sid <= 0) {
            return [];
        }

        $teamsStmt = $this->conn->prepare(
            "SELECT e.id AS equipo_id, e.nombre FROM equipos e
             INNER JOIN equipos_temporadas et ON et.equipo_id = e.id
             WHERE et.temporada_id = ?
             ORDER BY e.nombre ASC"
        );
        $teamsStmt->bindParam(1, $sid, PDO::PARAM_INT);
        $teamsStmt->execute();
        $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($teams as $t) {
            $id = (int)$t['equipo_id'];
            $stats[$id] = [
                'equipo_id' => $id,
                'nombre' => $t['nombre'],
                'pj' => 0,
                'pg' => 0,
                'pe' => 0,
                'pp' => 0,
                'gf' => 0,
                'gc' => 0,
                'pts' => 0
            ];
        }

        $phaseFilter = $this->hasPartidoFaseColumn() ? " AND p.fase = 'regular'" : '';
        $mStmt = $this->conn->prepare(
            "SELECT p.equipo_local_id, p.equipo_visitante_id,
                    ISNULL(r.goles_local, 0) AS gl, ISNULL(r.goles_visitante, 0) AS gv
             FROM partidos p
             INNER JOIN resultados r ON r.partido_id = p.id
             WHERE p.temporada_id = ? AND p.estado = 'finalizado'" . $phaseFilter
        );
        $mStmt->bindParam(1, $sid, PDO::PARAM_INT);
        $mStmt->execute();

        while ($m = $mStmt->fetch(PDO::FETCH_ASSOC)) {
            $L = (int)$m['equipo_local_id'];
            $V = (int)$m['equipo_visitante_id'];
            $gl = (int)$m['gl'];
            $gv = (int)$m['gv'];
            if (!isset($stats[$L]) || !isset($stats[$V])) {
                continue;
            }
            $stats[$L]['pj']++;
            $stats[$V]['pj']++;
            $stats[$L]['gf'] += $gl;
            $stats[$L]['gc'] += $gv;
            $stats[$V]['gf'] += $gv;
            $stats[$V]['gc'] += $gl;
            if ($gl > $gv) {
                $stats[$L]['pg']++;
                $stats[$L]['pts'] += 3;
                $stats[$V]['pp']++;
            } elseif ($gl < $gv) {
                $stats[$V]['pg']++;
                $stats[$V]['pts'] += 3;
                $stats[$L]['pp']++;
            } else {
                $stats[$L]['pe']++;
                $stats[$V]['pe']++;
                $stats[$L]['pts']++;
                $stats[$V]['pts']++;
            }
        }

        $rows = array_values($stats);
        usort($rows, function ($a, $b) {
            if ($a['pts'] !== $b['pts']) {
                return $b['pts'] - $a['pts'];
            }
            $difa = $a['gf'] - $a['gc'];
            $difb = $b['gf'] - $b['gc'];
            if ($difa !== $difb) {
                return $difb - $difa;
            }
            if ($a['gf'] !== $b['gf']) {
                return $b['gf'] - $a['gf'];
            }
            return strcasecmp($a['nombre'], $b['nombre']);
        });

        return $rows;
    }

    public function getTeamStandingSummary($seasonId, $equipoId) {
        $rows = $this->getStandingsForSeason($seasonId);
        $total = count($rows);
        $eid = (int)$equipoId;
        foreach ($rows as $i => $row) {
            if ((int)$row['equipo_id'] === $eid) {
                return [
                    'posicion' => $i + 1,
                    'total_equipos' => $total,
                    'tabla' => $rows,
                    'mi_fila' => $row
                ];
            }
        }
        return null;
    }

    public function getTopScorersForTeamInSeason($seasonId, $equipoId, $limit = 8) {
        $sid = (int)$seasonId;
        $eid = (int)$equipoId;
        $lim = max(1, min(25, (int)$limit));
        if ($sid <= 0 || $eid <= 0) {
            return [];
        }
        $query = "SELECT j.id, j.nombre, j.numero, COUNT(g.id) AS goles
                  FROM goles g
                  INNER JOIN jugadores j ON j.id = g.jugador_id
                  INNER JOIN partidos p ON p.id = g.partido_id
                  WHERE p.temporada_id = :tid AND j.equipo_id = :eid
                  GROUP BY j.id, j.nombre, j.numero
                  HAVING COUNT(g.id) > 0
                  ORDER BY COUNT(g.id) DESC, j.nombre ASC
                  OFFSET 0 ROWS FETCH NEXT :lim ROWS ONLY";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tid', $sid, PDO::PARAM_INT);
        $stmt->bindParam(':eid', $eid, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $lim, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTopCardsForTeamInSeason($seasonId, $equipoId, $limit = 8) {
        $sid = (int)$seasonId;
        $eid = (int)$equipoId;
        $lim = max(1, min(25, (int)$limit));
        if ($sid <= 0 || $eid <= 0) {
            return [];
        }
        $query = "SELECT j.id, j.nombre, j.numero,
                         SUM(CASE WHEN t.tipo = 'amarilla' THEN 1 ELSE 0 END) AS amarillas,
                         SUM(CASE WHEN t.tipo = 'roja' THEN 1 ELSE 0 END) AS rojas,
                         COUNT(*) AS total_tarjetas
                  FROM tarjetas t
                  INNER JOIN jugadores j ON j.id = t.jugador_id
                  INNER JOIN partidos p ON p.id = t.partido_id
                  WHERE p.temporada_id = :tid AND j.equipo_id = :eid
                  GROUP BY j.id, j.nombre, j.numero
                  HAVING COUNT(*) > 0
                  ORDER BY total_tarjetas DESC, rojas DESC, amarillas DESC, j.nombre ASC
                  OFFSET 0 ROWS FETCH NEXT :lim ROWS ONLY";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tid', $sid, PDO::PARAM_INT);
        $stmt->bindParam(':eid', $eid, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $lim, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
