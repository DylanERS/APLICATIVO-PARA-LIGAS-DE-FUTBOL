<?php
require_once 'config/database.php';

class MatchResult {
    private $conn;
    private $cachedGolesTable = null;
    private $cachedTarjetasTable = null;
    private $cachedResultadosTable = null;
    private $cachedTarjetasMotivo = null;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    private function hasTable($name) {
        try {
            $q = "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = :t";
            $stmt = $this->conn->prepare($q);
            $stmt->bindValue(':t', $name, PDO::PARAM_STR);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int)($row['total'] ?? 0)) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function statsTablesReady() {
        if ($this->cachedGolesTable === null) {
            $this->cachedGolesTable = $this->hasTable('goles');
            $this->cachedTarjetasTable = $this->hasTable('tarjetas');
            $this->cachedResultadosTable = $this->hasTable('resultados');
        }
        return $this->cachedGolesTable && $this->cachedTarjetasTable && $this->cachedResultadosTable;
    }

    public function hasTarjetaMotivoColumn() {
        if ($this->cachedTarjetasMotivo !== null) {
            return $this->cachedTarjetasMotivo;
        }
        if (!$this->hasTable('tarjetas')) {
            $this->cachedTarjetasMotivo = false;
            return false;
        }
        $q = "SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_NAME = 'tarjetas' AND COLUMN_NAME = 'motivo'";
        $stmt = $this->conn->prepare($q);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->cachedTarjetasMotivo = ((int)($row['total'] ?? 0)) > 0;
        return $this->cachedTarjetasMotivo;
    }

    private function jugadorEquipoId($jugadorId) {
        $jid = (int)$jugadorId;
        if ($jid <= 0) {
            return null;
        }
        $stmt = $this->conn->prepare('SELECT equipo_id FROM jugadores WHERE id = :id');
        $stmt->bindValue(':id', $jid, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['equipo_id'] : null;
    }

    /**
     * @param array $goles [['jugador_id' => int, 'minuto' => int], ...]
     * @param array $tarjetas [['jugador_id' => int, 'tipo' => 'amarilla'|'roja', 'minuto' => int, 'motivo' => string], ...]
     */
    public function saveAndFinalizeMatch($matchId, $seasonId, $leagueId, $equipoLocalId, $equipoVisitId, array $goles, array $tarjetas, $informeArbitro, $seasonModel) {
        if (!$this->statsTablesReady()) {
            return ['ok' => false, 'error' => 'Faltan tablas goles/tarjetas/resultados en la base de datos.'];
        }

        $mid = (int)$matchId;
        $lid = (int)$equipoLocalId;
        $vid = (int)$equipoVisitId;

        $golesL = 0;
        $golesV = 0;
        $cleanGoles = [];
        foreach ($goles as $g) {
            $jid = (int)($g['jugador_id'] ?? 0);
            if ($jid <= 0) {
                continue;
            }
            $eq = $this->jugadorEquipoId($jid);
            if ($eq === null || ($eq !== $lid && $eq !== $vid)) {
                return ['ok' => false, 'error' => 'Un gol tiene un jugador que no pertenece a los equipos del partido.'];
            }
            $min = max(0, min(130, (int)($g['minuto'] ?? 0)));
            $cleanGoles[] = ['jugador_id' => $jid, 'minuto' => $min];
            if ($eq === $lid) {
                $golesL++;
            } else {
                $golesV++;
            }
        }

        $cleanTarjetas = [];
        $hasMotivoCol = $this->hasTarjetaMotivoColumn();
        foreach ($tarjetas as $t) {
            $jid = (int)($t['jugador_id'] ?? 0);
            if ($jid <= 0) {
                continue;
            }
            $tipo = strtolower(trim((string)($t['tipo'] ?? '')));
            if (!in_array($tipo, ['amarilla', 'roja'], true)) {
                return ['ok' => false, 'error' => 'Tipo de tarjeta no válido.'];
            }
            $eq = $this->jugadorEquipoId($jid);
            if ($eq === null || ($eq !== $lid && $eq !== $vid)) {
                return ['ok' => false, 'error' => 'Una tarjeta tiene un jugador que no pertenece a los equipos del partido.'];
            }
            $motivo = trim((string)($t['motivo'] ?? ''));
            if ($motivo === '') {
                return ['ok' => false, 'error' => 'Cada tarjeta debe incluir la causal o motivo.'];
            }
            if (mb_strlen($motivo) > 500) {
                $motivo = mb_substr($motivo, 0, 500);
            }
            $min = max(0, min(130, (int)($t['minuto'] ?? 0)));
            $cleanTarjetas[] = [
                'jugador_id' => $jid,
                'tipo' => $tipo,
                'minuto' => $min,
                'motivo' => $motivo
            ];
        }

        $informe = trim((string)$informeArbitro);
        if (mb_strlen($informe) > 4000) {
            $informe = mb_substr($informe, 0, 4000);
        }

        try {
            $this->conn->beginTransaction();

            $delG = $this->conn->prepare('DELETE FROM goles WHERE partido_id = :p');
            $delG->bindValue(':p', $mid, PDO::PARAM_INT);
            $delG->execute();

            $delT = $this->conn->prepare('DELETE FROM tarjetas WHERE partido_id = :p');
            $delT->bindValue(':p', $mid, PDO::PARAM_INT);
            $delT->execute();

            $insG = $this->conn->prepare('INSERT INTO goles (partido_id, jugador_id, minuto) VALUES (:p, :j, :m)');
            foreach ($cleanGoles as $g) {
                $insG->bindValue(':p', $mid, PDO::PARAM_INT);
                $insG->bindValue(':j', $g['jugador_id'], PDO::PARAM_INT);
                $insG->bindValue(':m', $g['minuto'], PDO::PARAM_INT);
                $insG->execute();
            }

            if ($hasMotivoCol) {
                $insT = $this->conn->prepare(
                    'INSERT INTO tarjetas (partido_id, jugador_id, tipo, minuto, motivo) VALUES (:p, :j, :tipo, :m, :mot)'
                );
                foreach ($cleanTarjetas as $t) {
                    $insT->bindValue(':p', $mid, PDO::PARAM_INT);
                    $insT->bindValue(':j', $t['jugador_id'], PDO::PARAM_INT);
                    $insT->bindValue(':tipo', $t['tipo'], PDO::PARAM_STR);
                    $insT->bindValue(':m', $t['minuto'], PDO::PARAM_INT);
                    $insT->bindValue(':mot', $t['motivo'], PDO::PARAM_STR);
                    $insT->execute();
                }
            } else {
                $insT = $this->conn->prepare(
                    'INSERT INTO tarjetas (partido_id, jugador_id, tipo, minuto) VALUES (:p, :j, :tipo, :m)'
                );
                foreach ($cleanTarjetas as $t) {
                    $insT->bindValue(':p', $mid, PDO::PARAM_INT);
                    $insT->bindValue(':j', $t['jugador_id'], PDO::PARAM_INT);
                    $insT->bindValue(':tipo', $t['tipo'], PDO::PARAM_STR);
                    $insT->bindValue(':m', $t['minuto'], PDO::PARAM_INT);
                    $insT->execute();
                }
            }

            $obs = $informe;
            if (!$hasMotivoCol && !empty($cleanTarjetas)) {
                $extra = "\n\n[Tarjetas sin columna motivo en BD — ejecute migración sqlserver_tarjetas_motivo.sql]\n";
                foreach ($cleanTarjetas as $t) {
                    $extra .= sprintf(
                        "%s #%d: %s — %s\n",
                        $t['tipo'],
                        $t['jugador_id'],
                        $t['motivo'],
                        $t['minuto'] . "'"
                    );
                }
                $obs = trim($obs . $extra);
            }

            $chk = $this->conn->prepare('SELECT partido_id FROM resultados WHERE partido_id = :p');
            $chk->bindValue(':p', $mid, PDO::PARAM_INT);
            $chk->execute();
            if ($chk->fetch(PDO::FETCH_ASSOC)) {
                $up = $this->conn->prepare(
                    'UPDATE resultados SET goles_local = :gl, goles_visitante = :gv, observaciones = :obs WHERE partido_id = :p'
                );
                $up->bindValue(':gl', $golesL, PDO::PARAM_INT);
                $up->bindValue(':gv', $golesV, PDO::PARAM_INT);
                $up->bindValue(':obs', $obs !== '' ? $obs : null, $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $up->bindValue(':p', $mid, PDO::PARAM_INT);
                $up->execute();
            } else {
                $ins = $this->conn->prepare(
                    'INSERT INTO resultados (partido_id, goles_local, goles_visitante, observaciones) VALUES (:p, :gl, :gv, :obs)'
                );
                $ins->bindValue(':p', $mid, PDO::PARAM_INT);
                $ins->bindValue(':gl', $golesL, PDO::PARAM_INT);
                $ins->bindValue(':gv', $golesV, PDO::PARAM_INT);
                $ins->bindValue(':obs', $obs !== '' ? $obs : null, $obs !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
                $ins->execute();
            }

            $ok = $seasonModel->updateMatchStatus($mid, (int)$seasonId, (int)$leagueId, 'finalizado', ['en curso']);
            if (!$ok) {
                throw new Exception('No se pudo finalizar el partido.');
            }

            $seasonModel->setMatchFinReal($mid, (int)$seasonId, (int)$leagueId);

            $this->conn->commit();
            return ['ok' => true];
        } catch (\Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return ['ok' => false, 'error' => 'Error al guardar: ' . $e->getMessage()];
        }
    }
}
