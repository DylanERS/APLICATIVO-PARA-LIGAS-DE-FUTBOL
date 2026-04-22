<?php
require_once 'controllers/Controller.php';
require_once 'models/League.php';
require_once 'models/Season.php';
require_once 'models/Player.php';
require_once 'models/MatchAttendance.php';
require_once 'models/Team.php';

class MatchAttendanceController extends Controller {

    private static function attendanceQrSignature($matchId, $equipoId, $jugadorId) {
        return substr(hash_hmac('sha256', (int)$matchId . '|' . (int)$equipoId . '|' . (int)$jugadorId, ATTENDANCE_QR_SECRET), 0, 16);
    }

    private static function attendanceFotoSignature($matchId, $equipoId, $jugadorId) {
        return substr(hash_hmac('sha256', (int)$matchId . '|' . (int)$equipoId . '|' . (int)$jugadorId . '|foto', ATTENDANCE_QR_SECRET), 0, 16);
    }

    /**
     * URL firmada para marcar asistencia por QR (misma lógica que en la lista de asistencia del partido).
     */
    public static function buildQrMarcarAsistenciaUrl($matchId, $seasonId, $equipoId, $jugadorId) {
        $sig = self::attendanceQrSignature((int)$matchId, (int)$equipoId, (int)$jugadorId);
        return BASE_URL . 'partido/asistencia/qr-marcar?' . http_build_query([
            'season_id' => (int)$seasonId,
            'match_id' => (int)$matchId,
            'equipo_id' => (int)$equipoId,
            'jugador_id' => (int)$jugadorId,
            'sig' => $sig,
        ]);
    }

    /**
     * URL firmada para que el árbitro confirme presencia escaneando QR desde la ficha del DT.
     */
    public static function buildQrConfirmacionArbitroUrl($matchId, $seasonId, $equipoId, $jugadorId) {
        $sig = self::attendanceQrSignature((int)$matchId, (int)$equipoId, (int)$jugadorId);
        return BASE_URL . 'partido/arbitro/confirmar-asistencia-qr?' . http_build_query([
            'season_id' => (int)$seasonId,
            'match_id' => (int)$matchId,
            'equipo_id' => (int)$equipoId,
            'jugador_id' => (int)$jugadorId,
            'sig' => $sig,
        ]);
    }

    private function requireLogin($redirectAfterLogin) {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login?redirect=' . rawurlencode($redirectAfterLogin));
            exit;
        }
    }

    private static function isAllowedAttendanceImage(array $file) {
        if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        if ((int)$file['size'] > 5 * 1024 * 1024) {
            return false;
        }
        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            return false;
        }
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);
                $ok = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                return in_array($mime, $ok, true);
            }
        }
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }

    /**
     * @return string|null ruta relativa tipo assets/img/asistencia/{matchId}/archivo.ext
     */
    private function storeAttendancePhoto($matchId, array $file) {
        if (!self::isAllowedAttendanceImage($file)) {
            return null;
        }
        $dir = 'assets/img/asistencia/' . (int)$matchId . '/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }
        $name = 'a_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $path = $dir . $name;
        if (move_uploaded_file($file['tmp_name'], $path)) {
            return $path;
        }
        return null;
    }

    public function asistencia() {
        $token = trim($_GET['token'] ?? '');
        $seasonIdParam = (int)($_GET['season_id'] ?? 0);
        $matchIdParam = (int)($_GET['match_id'] ?? 0);
        $redirectPath = 'partido/asistencia';
        if ($token !== '') {
            $redirectPath .= '?token=' . rawurlencode($token);
        } elseif ($seasonIdParam > 0 && $matchIdParam > 0) {
            $redirectPath .= '?season_id=' . $seasonIdParam . '&match_id=' . $matchIdParam;
        }
        $this->requireLogin($redirectPath);

        $leagueModel = new League();
        $seasonModel = new Season();
        $playerModel = new Player();
        $attendanceModel = new MatchAttendance();
        $teamModel = new Team();

        $league = $leagueModel->getMainLeague();
        $match = null;
        if ($token !== '') {
            if (!$seasonModel->hasAsistenciaTokenColumn()) {
                http_response_code(503);
                echo 'Nomina por token no disponible: agregue la columna asistencia_token en partidos (ver database.sql).';
                exit;
            }
            $match = $seasonModel->getMatchByAttendanceToken($token);
            if (!$match || (int)$match['liga_id'] !== (int)$league['id']) {
                http_response_code(404);
                echo 'Partido no encontrado.';
                exit;
            }
        } elseif ($seasonIdParam > 0 && $matchIdParam > 0) {
            $match = $seasonModel->getMatchByIdForSeason($matchIdParam, $seasonIdParam, (int)$league['id']);
            if (!$match) {
                http_response_code(404);
                echo 'Partido no encontrado.';
                exit;
            }
        } else {
            http_response_code(400);
            echo 'Debe indicar token o parametros season_id y match_id.';
            exit;
        }

        $seasonId = (int)$match['temporada_id'];
        $matchId = (int)$match['id'];
        $localId = (int)$match['equipo_local_id'];
        $visitId = (int)$match['equipo_visitante_id'];
        $role = $_SESSION['role'] ?? '';
        $userEquipo = isset($_SESSION['equipo_id']) ? (int)$_SESSION['equipo_id'] : 0;

        $dtPuedeMarcar = true;
        if ($seasonModel->hasAsistenciaDtHabilitadaColumn()) {
            $dtPuedeMarcar = (int)($match['asistencia_dt_habilitada'] ?? 0) === 1;
        }

        $isAdminViewer = ($role === 'admin');
        $canLocal = ($role === 'director_tecnico' && $userEquipo === $localId && $dtPuedeMarcar);
        $canVisit = ($role === 'director_tecnico' && $userEquipo === $visitId && $dtPuedeMarcar);

        if ($role === 'director_tecnico' && !$dtPuedeMarcar) {
            http_response_code(403);
            echo 'El administrador aun no habilito el registro de asistencia para este partido.';
            exit;
        }

        if (!$isAdminViewer && !$canLocal && !$canVisit) {
            http_response_code(403);
            echo 'No tienes permiso para ver la nomina de este partido.';
            exit;
        }

        $asistenciaUrl = ($token !== '')
            ? ('partido/asistencia?token=' . rawurlencode($token))
            : ('partido/asistencia?season_id=' . $seasonId . '&match_id=' . $matchId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($role === 'admin') {
                header('Location: ' . BASE_URL . $asistenciaUrl . '&msg=forbidden');
                exit;
            }

            $equipoSeleccionado = (int)($_POST['equipo_id'] ?? 0);
            $jugadores = $_POST['jugadores'] ?? [];
            if (!is_array($jugadores)) {
                $jugadores = [];
            }

            if ($equipoSeleccionado !== $localId && $equipoSeleccionado !== $visitId) {
                header('Location: ' . BASE_URL . $asistenciaUrl . '&msg=team_invalid');
                exit;
            }
            if ($equipoSeleccionado === $localId && !$canLocal) {
                header('Location: ' . BASE_URL . $asistenciaUrl . '&msg=forbidden');
                exit;
            }
            if ($equipoSeleccionado === $visitId && !$canVisit) {
                header('Location: ' . BASE_URL . $asistenciaUrl . '&msg=forbidden');
                exit;
            }

            if (!$attendanceModel->validateJugadoresBelongToTeam($jugadores, $equipoSeleccionado)) {
                header('Location: ' . BASE_URL . $asistenciaUrl . '&msg=players_invalid');
                exit;
            }

            $fotoByJugador = [];
            $preserveFromPrevious = [];
            $requireFoto = $attendanceModel->hasFotoAsistenciaColumn();

            if ($requireFoto) {
                $prev = $attendanceModel->getPresentDetailsByTeam($matchId, $equipoSeleccionado);
                foreach ($prev as $jid => $row) {
                    if (!empty($row['foto_asistencia'])) {
                        $preserveFromPrevious[(int)$jid] = $row['foto_asistencia'];
                    }
                }

                if (!empty($_FILES['foto_jugador']) && is_array($_FILES['foto_jugador']['name'])) {
                    $files = $_FILES['foto_jugador'];
                    foreach ($files['name'] as $jidKey => $_name) {
                        $jid = (int)$jidKey;
                        if ($jid <= 0 || !in_array($jid, array_map('intval', $jugadores), true)) {
                            continue;
                        }
                        if ((int)($files['error'][$jidKey] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                            continue;
                        }
                        $one = [
                            'name' => $files['name'][$jidKey],
                            'type' => $files['type'][$jidKey] ?? '',
                            'tmp_name' => $files['tmp_name'][$jidKey] ?? '',
                            'error' => (int)($files['error'][$jidKey] ?? 0),
                            'size' => (int)($files['size'][$jidKey] ?? 0)
                        ];
                        $stored = $this->storeAttendancePhoto($matchId, $one);
                        if ($stored !== null) {
                            $fotoByJugador[$jid] = $stored;
                        } else {
                            header('Location: ' . BASE_URL . $asistenciaUrl . '&msg=foto_invalida');
                            exit;
                        }
                    }
                }
            }

            if ($attendanceModel->saveTeamRoster($matchId, $equipoSeleccionado, $jugadores, $fotoByJugador, $preserveFromPrevious, $requireFoto)) {
                header('Location: ' . BASE_URL . $asistenciaUrl . '&msg=saved');
                exit;
            }

            header('Location: ' . BASE_URL . $asistenciaUrl . '&msg=' . ($requireFoto ? 'foto_requerida' : 'error'));
            exit;
        }

        $season = $seasonModel->getByIdWithTeams($seasonId, (int)$league['id']);
        $localTeam = $teamModel->getById($localId);
        $visitTeam = $teamModel->getById($visitId);
        $playersLocal = $playerModel->getByEquipoId($localId);
        $playersVisit = $playerModel->getByEquipoId($visitId);
        $presentLocal = $attendanceModel->getPresentPlayerIds($matchId, $localId);
        $presentVisit = $attendanceModel->getPresentPlayerIds($matchId, $visitId);
        $presentDetailsLocal = $attendanceModel->getPresentDetailsByTeam($matchId, $localId);
        $presentDetailsVisit = $attendanceModel->getPresentDetailsByTeam($matchId, $visitId);
        $minJug = $leagueModel->getMinJugadoresPartido();

        $this->render('matches/asistencia', [
            'pageTitle' => 'Nomina del Partido',
            'token' => $token,
            'asistenciaUrl' => BASE_URL . $asistenciaUrl,
            'match' => $match,
            'season' => $season,
            'localTeam' => $localTeam,
            'visitTeam' => $visitTeam,
            'playersLocal' => $playersLocal,
            'playersVisit' => $playersVisit,
            'presentLocal' => $presentLocal,
            'presentVisit' => $presentVisit,
            'presentDetailsLocal' => $presentDetailsLocal,
            'presentDetailsVisit' => $presentDetailsVisit,
            'fotoAsistenciaReady' => $attendanceModel->hasFotoAsistenciaColumn(),
            'canLocal' => $canLocal,
            'canVisit' => $canVisit,
            'isAdminViewer' => $isAdminViewer,
            'dtPuedeMarcar' => $dtPuedeMarcar,
            'minJugadores' => $minJug,
            'countLocal' => $attendanceModel->countPresent($matchId, $localId),
            'countVisit' => $attendanceModel->countPresent($matchId, $visitId)
        ]);
    }

    /**
     * Vista independiente del catálogo de fichas QR para validación del árbitro.
     */
    public function asistenciaCatalogo() {
        $seasonId = (int)($_GET['season_id'] ?? 0);
        $matchId = (int)($_GET['match_id'] ?? 0);
        $equipoId = (int)($_GET['equipo_id'] ?? 0);
        $modo = trim((string)($_GET['modo'] ?? 'grid'));
        if (!in_array($modo, ['grid', 'carrusel', 'fullscreen'], true)) {
            $modo = 'grid';
        }
        $redirect = 'partido/asistencia/catalogo?season_id=' . $seasonId . '&match_id=' . $matchId . '&equipo_id=' . $equipoId . '&modo=' . rawurlencode($modo);
        $this->requireLogin($redirect);

        if (($_SESSION['role'] ?? '') !== 'director_tecnico') {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        if ($seasonId <= 0 || $matchId <= 0 || $equipoId <= 0) {
            header('Location: ' . BASE_URL . 'partido/mis-partidos');
            exit;
        }

        $leagueModel = new League();
        $seasonModel = new Season();
        $playerModel = new Player();
        $attendanceModel = new MatchAttendance();
        $teamModel = new Team();
        $league = $leagueModel->getMainLeague();
        $match = $seasonModel->getMatchByIdForSeason($matchId, $seasonId, (int)$league['id']);

        if (!$match || ($match['estado'] ?? '') !== 'programado') {
            header('Location: ' . BASE_URL . 'partido/mis-partidos');
            exit;
        }

        $localId = (int)$match['equipo_local_id'];
        $visitId = (int)$match['equipo_visitante_id'];
        if ($equipoId !== $localId && $equipoId !== $visitId) {
            header('Location: ' . BASE_URL . 'partido/mis-partidos');
            exit;
        }

        $userEquipo = (int)($_SESSION['equipo_id'] ?? 0);
        if ($userEquipo !== $equipoId) {
            header('Location: ' . BASE_URL . 'partido/mis-partidos');
            exit;
        }

        $dtPuedeMarcar = true;
        if ($seasonModel->hasAsistenciaDtHabilitadaColumn()) {
            $dtPuedeMarcar = (int)($match['asistencia_dt_habilitada'] ?? 0) === 1;
        }
        if (!$dtPuedeMarcar) {
            header('Location: ' . BASE_URL . 'partido/mis-partidos');
            exit;
        }

        $presentIds = $attendanceModel->getPresentPlayerIds($matchId, $equipoId);
        $minJug = $leagueModel->getMinJugadoresPartido();
        if (count($presentIds) < $minJug) {
            header('Location: ' . BASE_URL . 'partido/asistencia?season_id=' . $seasonId . '&match_id=' . $matchId . '&msg=min_no_cumplido');
            exit;
        }

        $players = $playerModel->getByEquipoId($equipoId);
        $playersPresentes = [];
        $presentSet = array_fill_keys($presentIds, true);
        foreach ($players as $p) {
            $jid = (int)($p['id'] ?? 0);
            if ($jid > 0 && isset($presentSet[$jid])) {
                $playersPresentes[] = $p;
            }
        }

        $team = $teamModel->getById($equipoId);
        $season = $seasonModel->getByIdWithTeams($seasonId, (int)$league['id']);

        $this->render('matches/asistencia_catalogo', [
            'pageTitle' => 'Catálogo de asistencia',
            'season' => $season,
            'match' => $match,
            'team' => $team,
            'players' => $playersPresentes,
            'mode' => $modo,
            'minJugadores' => $minJug
        ]);
    }

    public function asistenciaFotoJugador() {
        $seasonId = (int)($_GET['season_id'] ?? 0);
        $matchId = (int)($_GET['match_id'] ?? 0);
        $equipoId = (int)($_GET['equipo_id'] ?? 0);
        $jugadorId = (int)($_GET['jugador_id'] ?? 0);
        $sig = trim($_GET['sig'] ?? '');
        $q = [
            'season_id' => $seasonId,
            'match_id' => $matchId,
            'equipo_id' => $equipoId,
            'jugador_id' => $jugadorId,
            'sig' => $sig
        ];
        $redirectLogin = 'partido/asistencia/foto-jugador?' . http_build_query($q);
        $this->requireLogin($redirectLogin);

        if ($seasonId <= 0 || $matchId <= 0 || $equipoId <= 0 || $jugadorId <= 0 || strlen($sig) !== 16) {
            http_response_code(400);
            echo 'Parametros invalidos.';
            exit;
        }
        $expected = self::attendanceFotoSignature($matchId, $equipoId, $jugadorId);
        if (!hash_equals($expected, $sig)) {
            http_response_code(403);
            echo 'Enlace no valido.';
            exit;
        }

        $leagueModel = new League();
        $seasonModel = new Season();
        $playerModel = new Player();
        $teamModel = new Team();
        $attendanceModel = new MatchAttendance();
        $league = $leagueModel->getMainLeague();
        $match = $seasonModel->getMatchByIdForSeason($matchId, $seasonId, (int)$league['id']);

        if (!$match || ($match['estado'] ?? '') !== 'programado') {
            http_response_code(404);
            echo 'Partido no disponible.';
            exit;
        }

        $localId = (int)$match['equipo_local_id'];
        $visitId = (int)$match['equipo_visitante_id'];
        if ($equipoId !== $localId && $equipoId !== $visitId) {
            http_response_code(400);
            echo 'Equipo no valido.';
            exit;
        }

        $dtPuedeMarcar = true;
        if ($seasonModel->hasAsistenciaDtHabilitadaColumn()) {
            $dtPuedeMarcar = (int)($match['asistencia_dt_habilitada'] ?? 0) === 1;
        }
        $role = $_SESSION['role'] ?? '';
        $userEquipo = isset($_SESSION['equipo_id']) ? (int)$_SESSION['equipo_id'] : 0;
        if ($role !== 'director_tecnico' || $userEquipo !== $equipoId || !$dtPuedeMarcar) {
            http_response_code(403);
            echo 'No tiene permiso.';
            exit;
        }

        if (!$attendanceModel->hasFotoAsistenciaColumn()) {
            http_response_code(503);
            echo 'Foto de asistencia no configurada en base de datos.';
            exit;
        }

        $presentIds = $attendanceModel->getPresentPlayerIds($matchId, $equipoId);
        if (!in_array($jugadorId, $presentIds, true)) {
            http_response_code(400);
            echo 'El jugador no esta en la nomina. Marque primero con QR o casilla.';
            exit;
        }

        $player = $playerModel->getById($jugadorId);
        $equipo = $teamModel->getById($equipoId);
        $backUrl = BASE_URL . 'partido/asistencia?season_id=' . $seasonId . '&match_id=' . $matchId;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_FILES['foto']) || (int)($_FILES['foto']['error'] ?? 0) !== UPLOAD_ERR_OK) {
                header('Location: ' . BASE_URL . 'partido/asistencia/foto-jugador?' . http_build_query($q) . '&msg=sin_foto');
                exit;
            }
            $stored = $this->storeAttendancePhoto($matchId, $_FILES['foto']);
            if ($stored === null || !$attendanceModel->updateFotoAsistencia($matchId, $jugadorId, $stored)) {
                header('Location: ' . BASE_URL . 'partido/asistencia/foto-jugador?' . http_build_query($q) . '&msg=error');
                exit;
            }
            header('Location: ' . $backUrl . '&msg=foto_ok');
            exit;
        }

        $this->render('matches/asistencia_foto_jugador', [
            'pageTitle' => 'Foto del jugador',
            'seasonId' => $seasonId,
            'matchId' => $matchId,
            'equipoId' => $equipoId,
            'jugadorId' => $jugadorId,
            'sig' => $sig,
            'player' => $player,
            'equipo' => $equipo,
            'backUrl' => $backUrl
        ]);
    }

    public function qrMarcarAsistencia() {
        $seasonId = (int)($_GET['season_id'] ?? 0);
        $matchId = (int)($_GET['match_id'] ?? 0);
        $equipoId = (int)($_GET['equipo_id'] ?? 0);
        $jugadorId = (int)($_GET['jugador_id'] ?? 0);
        $sig = trim($_GET['sig'] ?? '');
        $back = 'partido/asistencia?season_id=' . $seasonId . '&match_id=' . $matchId;

        $this->requireLogin('partido/asistencia/qr-marcar?' . http_build_query([
            'season_id' => $seasonId,
            'match_id' => $matchId,
            'equipo_id' => $equipoId,
            'jugador_id' => $jugadorId,
            'sig' => $sig
        ]));

        if (($_SESSION['role'] ?? '') === 'admin') {
            header('Location: ' . BASE_URL . $back . '&msg=qr_forbidden');
            exit;
        }

        if ($seasonId <= 0 || $matchId <= 0 || $equipoId <= 0 || $jugadorId <= 0 || strlen($sig) !== 16) {
            header('Location: ' . BASE_URL . $back . '&msg=qr_invalid');
            exit;
        }

        $expected = self::attendanceQrSignature($matchId, $equipoId, $jugadorId);
        if (!hash_equals($expected, $sig)) {
            header('Location: ' . BASE_URL . $back . '&msg=qr_invalid');
            exit;
        }

        $leagueModel = new League();
        $seasonModel = new Season();
        $attendanceModel = new MatchAttendance();
        $league = $leagueModel->getMainLeague();
        $match = $seasonModel->getMatchByIdForSeason($matchId, $seasonId, (int)$league['id']);

        if (!$match || ($match['estado'] ?? '') !== 'programado') {
            header('Location: ' . BASE_URL . $back . '&msg=qr_invalid');
            exit;
        }

        $localId = (int)$match['equipo_local_id'];
        $visitId = (int)$match['equipo_visitante_id'];
        if ($equipoId !== $localId && $equipoId !== $visitId) {
            header('Location: ' . BASE_URL . $back . '&msg=qr_invalid');
            exit;
        }

        $dtPuedeMarcar = true;
        if ($seasonModel->hasAsistenciaDtHabilitadaColumn()) {
            $dtPuedeMarcar = (int)($match['asistencia_dt_habilitada'] ?? 0) === 1;
        }

        $role = $_SESSION['role'] ?? '';
        $userEquipo = isset($_SESSION['equipo_id']) ? (int)$_SESSION['equipo_id'] : 0;
        if ($role !== 'director_tecnico' || $userEquipo !== $equipoId || !$dtPuedeMarcar) {
            header('Location: ' . BASE_URL . $back . '&msg=qr_denied');
            exit;
        }

        if ($attendanceModel->addPresentPlayerIfMissing($matchId, $equipoId, $jugadorId)) {
            if ($attendanceModel->hasFotoAsistenciaColumn()) {
                $det = $attendanceModel->getPresentDetailsByTeam($matchId, $equipoId);
                $needFoto = empty($det[$jugadorId]['foto_asistencia']);
                if ($needFoto) {
                    $fsig = self::attendanceFotoSignature($matchId, $equipoId, $jugadorId);
                    $url = BASE_URL . 'partido/asistencia/foto-jugador?' . http_build_query([
                        'season_id' => $seasonId,
                        'match_id' => $matchId,
                        'equipo_id' => $equipoId,
                        'jugador_id' => $jugadorId,
                        'sig' => $fsig
                    ]);
                    header('Location: ' . $url);
                    exit;
                }
            }
            header('Location: ' . BASE_URL . $back . '&msg=qr_ok');
            exit;
        }

        header('Location: ' . BASE_URL . $back . '&msg=qr_error');
        exit;
    }

    public function myMatches() {
        $this->requireLogin('partido/mis-partidos');

        if (($_SESSION['role'] ?? '') !== 'director_tecnico') {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        $equipoId = (int)($_SESSION['equipo_id'] ?? 0);
        if ($equipoId <= 0) {
            $this->render('matches/mis_partidos', [
                'pageTitle' => 'Mis Partidos',
                'matches' => [],
                'equipo' => null,
                'error' => 'Tu usuario no tiene un equipo asignado. Contacta al administrador.'
            ]);
            return;
        }

        $leagueModel = new League();
        $seasonModel = new Season();
        $teamModel = new Team();
        $attendanceModel = new MatchAttendance();

        $league = $leagueModel->getMainLeague();
        $equipo = $teamModel->getById($equipoId);
        $matches = $seasonModel->getUpcomingMatchesForTeam($equipoId, (int)$league['id']);

        foreach ($matches as &$m) {
            $m['count_local'] = $attendanceModel->countPresent((int)$m['id'], (int)$m['equipo_local_id']);
            $m['count_visit'] = $attendanceModel->countPresent((int)$m['id'], (int)$m['equipo_visitante_id']);
        }
        unset($m);

        $this->render('matches/mis_partidos', [
            'pageTitle' => 'Mis Partidos',
            'matches' => $matches,
            'equipo' => $equipo,
            'minJugadores' => $leagueModel->getMinJugadoresPartido(),
            'asistenciaDtColumnReady' => $seasonModel->hasAsistenciaDtHabilitadaColumn(),
            'error' => null
        ]);
    }

    public function refereeMyMatches() {
        $this->requireLogin('partido/arbitro/mis-partidos');

        if (($_SESSION['role'] ?? '') !== 'arbitro') {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        $arbitroId = (int)($_SESSION['arbitro_id'] ?? 0);
        if ($arbitroId <= 0) {
            $this->render('matches/arbitro_mis_partidos', [
                'pageTitle' => 'Mis partidos (árbitro)',
                'matches' => [],
                'referee' => null,
                'error' => 'Tu usuario no tiene un árbitro asignado. Contacta al administrador.'
            ]);
            return;
        }

        require_once 'models/Referee.php';
        $leagueModel = new League();
        $seasonModel = new Season();
        $refereeModel = new Referee();
        $league = $leagueModel->getMainLeague();
        $referee = $refereeModel->getById($arbitroId);
        $matches = $seasonModel->getMatchesForReferee($arbitroId, (int)$league['id']);

        $this->render('matches/arbitro_mis_partidos', [
            'pageTitle' => 'Mis partidos (árbitro)',
            'matches' => $matches,
            'referee' => $referee,
            'error' => null
        ]);
    }

    public function refereeValidarAsistencia() {
        $seasonId = (int)($_GET['season_id'] ?? 0);
        $matchId = (int)($_GET['match_id'] ?? 0);
        $redirectPath = 'partido/arbitro/validar-asistencia?season_id=' . $seasonId . '&match_id=' . $matchId;
        $this->requireLogin($redirectPath);

        if (($_SESSION['role'] ?? '') !== 'arbitro') {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        $arbitroId = (int)($_SESSION['arbitro_id'] ?? 0);
        if ($arbitroId <= 0 || $seasonId <= 0 || $matchId <= 0) {
            http_response_code(400);
            echo 'Solicitud invalida.';
            exit;
        }

        $leagueModel = new League();
        $seasonModel = new Season();
        $teamModel = new Team();
        $attendanceModel = new MatchAttendance();
        $league = $leagueModel->getMainLeague();
        $match = $seasonModel->getMatchByIdForSeason($matchId, $seasonId, (int)$league['id']);

        if (!$match || (int)($match['arbitro_id'] ?? 0) !== $arbitroId) {
            http_response_code(403);
            echo 'No es su partido asignado.';
            exit;
        }

        $season = $seasonModel->getByIdWithTeams($seasonId, (int)$league['id']);
        $roster = $attendanceModel->getPresentRosterForMatch($matchId);
        $localTeam = $teamModel->getById((int)$match['equipo_local_id']);
        $visitTeam = $teamModel->getById((int)$match['equipo_visitante_id']);

        $this->render('matches/arbitro_validar_asistencia', [
            'pageTitle' => 'Validar nómina',
            'match' => $match,
            'season' => $season,
            'localTeam' => $localTeam,
            'visitTeam' => $visitTeam,
            'roster' => $roster,
            'validacionReady' => $attendanceModel->hasValidacionArbitroColumn(),
            'fotoReady' => $attendanceModel->hasFotoAsistenciaColumn()
        ]);
    }

    public function refereeScanQr() {
        $seasonId = (int)($_GET['season_id'] ?? 0);
        $matchId = (int)($_GET['match_id'] ?? 0);
        $redirectPath = 'partido/arbitro/escanear-qr?season_id=' . $seasonId . '&match_id=' . $matchId;
        $this->requireLogin($redirectPath);

        if (($_SESSION['role'] ?? '') !== 'arbitro') {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }
        $arbitroId = (int)($_SESSION['arbitro_id'] ?? 0);
        if ($arbitroId <= 0 || $seasonId <= 0 || $matchId <= 0) {
            header('Location: ' . BASE_URL . 'partido/arbitro/mis-partidos');
            exit;
        }

        $leagueModel = new League();
        $seasonModel = new Season();
        $attendanceModel = new MatchAttendance();
        $teamModel = new Team();
        $league = $leagueModel->getMainLeague();
        $match = $seasonModel->getMatchByIdForSeason($matchId, $seasonId, (int)$league['id']);
        if (!$match || (int)($match['arbitro_id'] ?? 0) !== $arbitroId) {
            header('Location: ' . BASE_URL . 'partido/arbitro/mis-partidos');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$attendanceModel->hasValidacionArbitroColumn()) {
                header('Location: ' . BASE_URL . $redirectPath . '&msg=sin_columna_validacion');
                exit;
            }
            $validados = $_POST['validado'] ?? [];
            if (!is_array($validados)) {
                $validados = [];
            }
            $roster = $attendanceModel->getPresentRosterForMatch($matchId);
            $validadoSet = [];
            foreach ($validados as $jid) {
                $validadoSet[(int)$jid] = true;
            }
            foreach ($roster as $row) {
                $jid = (int)($row['jugador_id'] ?? 0);
                if ($jid <= 0) {
                    continue;
                }
                if (isset($validadoSet[$jid])) {
                    $attendanceModel->setValidacionArbitro($matchId, $jid, 'confirmado');
                }
            }
            header('Location: ' . BASE_URL . $redirectPath . '&msg=manual_guardado');
            exit;
        }

        $roster = $attendanceModel->getPresentRosterForMatch($matchId);
        $localTeam = $teamModel->getById((int)$match['equipo_local_id']);
        $visitTeam = $teamModel->getById((int)$match['equipo_visitante_id']);

        $this->render('matches/arbitro_scan_qr', [
            'pageTitle' => 'Escanear QR',
            'match' => $match,
            'seasonId' => $seasonId,
            'roster' => $roster,
            'localTeam' => $localTeam,
            'visitTeam' => $visitTeam,
            'validacionReady' => $attendanceModel->hasValidacionArbitroColumn(),
            'fotoReady' => $attendanceModel->hasFotoAsistenciaColumn()
        ]);
    }

    /**
     * Confirmación de asistencia por árbitro mediante escaneo de QR en ficha mostrada por DT.
     */
    public function refereeConfirmarAsistenciaQr() {
        $seasonId = (int)($_GET['season_id'] ?? 0);
        $matchId = (int)($_GET['match_id'] ?? 0);
        $equipoId = (int)($_GET['equipo_id'] ?? 0);
        $jugadorId = (int)($_GET['jugador_id'] ?? 0);
        $sig = trim($_GET['sig'] ?? '');
        $back = 'partido/arbitro/validar-asistencia?season_id=' . $seasonId . '&match_id=' . $matchId;

        $this->requireLogin('partido/arbitro/confirmar-asistencia-qr?' . http_build_query([
            'season_id' => $seasonId,
            'match_id' => $matchId,
            'equipo_id' => $equipoId,
            'jugador_id' => $jugadorId,
            'sig' => $sig
        ]));

        if (($_SESSION['role'] ?? '') !== 'arbitro') {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }
        $arbitroId = (int)($_SESSION['arbitro_id'] ?? 0);
        if ($arbitroId <= 0 || $seasonId <= 0 || $matchId <= 0 || $equipoId <= 0 || $jugadorId <= 0 || strlen($sig) !== 16) {
            header('Location: ' . BASE_URL . $back . '&msg=qr_invalido');
            exit;
        }

        $expected = self::attendanceQrSignature($matchId, $equipoId, $jugadorId);
        if (!hash_equals($expected, $sig)) {
            header('Location: ' . BASE_URL . $back . '&msg=qr_invalido');
            exit;
        }

        $leagueModel = new League();
        $seasonModel = new Season();
        $attendanceModel = new MatchAttendance();
        $league = $leagueModel->getMainLeague();
        $match = $seasonModel->getMatchByIdForSeason($matchId, $seasonId, (int)$league['id']);
        if (!$match || (int)($match['arbitro_id'] ?? 0) !== $arbitroId || ($match['estado'] ?? '') !== 'programado') {
            header('Location: ' . BASE_URL . $back . '&msg=qr_denegado');
            exit;
        }

        $localId = (int)$match['equipo_local_id'];
        $visitId = (int)$match['equipo_visitante_id'];
        if ($equipoId !== $localId && $equipoId !== $visitId) {
            header('Location: ' . BASE_URL . $back . '&msg=qr_invalido');
            exit;
        }

        $present = $attendanceModel->getPresentPlayerIds($matchId, $equipoId);
        if (!in_array($jugadorId, $present, true)) {
            header('Location: ' . BASE_URL . $back . '&msg=jugador_no_nomina');
            exit;
        }
        if (!$attendanceModel->hasValidacionArbitroColumn()) {
            header('Location: ' . BASE_URL . $back . '&msg=sin_columna_validacion');
            exit;
        }

        if ($attendanceModel->setValidacionArbitro($matchId, $jugadorId, 'confirmado')) {
            header('Location: ' . BASE_URL . $back . '&msg=qr_confirmado');
            exit;
        }
        header('Location: ' . BASE_URL . $back . '&msg=qr_error');
        exit;
    }
}
?>
