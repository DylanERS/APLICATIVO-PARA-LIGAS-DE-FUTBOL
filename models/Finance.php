<?php
require_once 'config/database.php';

class Finance {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    // --- PAGOS ---
    public function getPagos() {
        $query = "SELECT p.*, e.nombre as equipo_nombre 
                  FROM pagos p 
                  JOIN equipos e ON p.equipo_id = e.id 
                  ORDER BY p.fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createPago($equipo_id, $temporada_id, $concepto, $monto) {
        $query = "INSERT INTO pagos (equipo_id, temporada_id, concepto, monto) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        $equipo_id = htmlspecialchars(strip_tags($equipo_id));
        $temporada_id = htmlspecialchars(strip_tags($temporada_id));
        $concepto = htmlspecialchars(strip_tags($concepto));
        $monto = htmlspecialchars(strip_tags($monto));
        
        $stmt->bindParam(1, $equipo_id);
        $stmt->bindParam(2, $temporada_id);
        $stmt->bindParam(3, $concepto);
        $stmt->bindParam(4, $monto);

        return $stmt->execute();
    }

    public function getSeasonsForPayments() {
        $query = "SELECT id, nombre, anio, estado
                  FROM temporadas
                  ORDER BY anio DESC, id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSeasonTeamsMap() {
        $query = "SELECT et.temporada_id, e.id AS equipo_id, e.nombre AS equipo_nombre
                  FROM equipos_temporadas et
                  INNER JOIN equipos e ON e.id = et.equipo_id
                  ORDER BY et.temporada_id DESC, e.nombre ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        $map = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tempId = (int)$row['temporada_id'];
            if (!isset($map[$tempId])) {
                $map[$tempId] = [];
            }
            $map[$tempId][] = [
                'id' => (int)$row['equipo_id'],
                'nombre' => $row['equipo_nombre']
            ];
        }
        return $map;
    }

    public function getMatchesForPayments() {
        $query = "SELECT p.id, p.temporada_id, p.equipo_local_id, p.equipo_visitante_id,
                         t.nombre AS temporada_nombre, t.anio AS temporada_anio,
                         el.nombre AS equipo_local_nombre, ev.nombre AS equipo_visitante_nombre
                  FROM partidos p
                  INNER JOIN temporadas t ON t.id = p.temporada_id
                  INNER JOIN equipos el ON el.id = p.equipo_local_id
                  INNER JOIN equipos ev ON ev.id = p.equipo_visitante_id
                  ORDER BY p.id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMatchById($matchId) {
        $query = "SELECT id, temporada_id, equipo_local_id, equipo_visitante_id
                  FROM partidos
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $safeId = (int)$matchId;
        $stmt->bindParam(':id', $safeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isTeamInSeason($teamId, $seasonId) {
        $query = "SELECT COUNT(*) AS total
                  FROM equipos_temporadas
                  WHERE equipo_id = :equipo_id AND temporada_id = :temporada_id";
        $stmt = $this->conn->prepare($query);
        $safeTeamId = (int)$teamId;
        $safeSeasonId = (int)$seasonId;
        $stmt->bindParam(':equipo_id', $safeTeamId, PDO::PARAM_INT);
        $stmt->bindParam(':temporada_id', $safeSeasonId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ((int)($row['total'] ?? 0)) > 0;
    }
    
    // --- MULTAS ---
    public function getMultas() {
        $query = "SELECT m.*, e.nombre as equipo_nombre 
                  FROM multas m 
                  JOIN equipos e ON m.equipo_id = e.id 
                  ORDER BY m.fecha DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function createMulta($equipo_id, $motivo, $monto) {
        $query = "INSERT INTO multas (equipo_id, motivo, monto) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        $equipo_id = htmlspecialchars(strip_tags($equipo_id));
        $motivo = htmlspecialchars(strip_tags($motivo));
        $monto = htmlspecialchars(strip_tags($monto));
        
        $stmt->bindParam(1, $equipo_id);
        $stmt->bindParam(2, $motivo);
        $stmt->bindParam(3, $monto);

        return $stmt->execute();
    }

    public function getActiveSeasonId() {
        $query = "SELECT id FROM temporadas WHERE estado = 'activa' ORDER BY id ASC OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return $row['id'];
        }

        // Obtener ID de la liga
        $qLiga = "SELECT id FROM ligas ORDER BY id ASC OFFSET 0 ROWS FETCH NEXT 1 ROWS ONLY";
        $stmtLiga = $this->conn->prepare($qLiga);
        $stmtLiga->execute();
        $ligaRow = $stmtLiga->fetch(PDO::FETCH_ASSOC);
        
        $liga_id = 1;
        if($ligaRow) {
            $liga_id = $ligaRow['id'];
        } else {
            $this->conn->exec("INSERT INTO ligas (nombre, descripcion) VALUES ('Liga por Defecto', 'Generada')");
            $stmtLiga->execute();
            $ligaRow = $stmtLiga->fetch(PDO::FETCH_ASSOC);
            $liga_id = $ligaRow['id'];
        }

        $anio = date('Y');
        $nombre = "Temporada " . $anio;
        $insertSeason = "INSERT INTO temporadas (liga_id, nombre, anio, estado) VALUES (?, ?, ?, 'activa')";
        $stmtInsert = $this->conn->prepare($insertSeason);
        $stmtInsert->execute([$liga_id, $nombre, $anio]);
        
        $stmt->execute();
        $newRow = $stmt->fetch(PDO::FETCH_ASSOC);
        return $newRow['id'] ?? 1;
    }

    public function pagarMulta($id) {
        $query = "UPDATE multas SET estado = 'pagada' WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        return $stmt->execute();
    }
}
?>
