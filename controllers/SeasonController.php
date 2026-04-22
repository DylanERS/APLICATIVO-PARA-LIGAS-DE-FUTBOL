<?php
require_once 'controllers/Controller.php';
require_once 'models/League.php';
require_once 'models/Team.php';
require_once 'models/Season.php';
require_once 'models/Referee.php';
require_once 'models/Cancha.php';
require_once 'models/MatchAttendance.php';
require_once 'models/MatchResult.php';
require_once 'models/Player.php';
require_once 'models/Dashboard.php';

class SeasonController extends Controller {
    public function __construct() {
        if(!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        if($_SESSION['role'] !== 'admin') {
            die("Acceso denegado. Solo el Dueño de la liga puede acceder a esta sección.");
        }
    }

    public function index() {
        $leagueModel = new League();
        $teamModel = new Team();
        $seasonModel = new Season();
        $league = $leagueModel->getMainLeague();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'create';

            if ($action === 'deactivate') {
                $seasonId = (int)($_POST['season_id'] ?? 0);
                if ($seasonId > 0 && $seasonModel->deactivateById($seasonId, (int)$league['id'])) {
                    header('Location: ' . BASE_URL . 'temporadas?msg=season_inactivated');
                    exit;
                }

                header('Location: ' . BASE_URL . 'temporadas?msg=season_error');
                exit;
            }

            $normalized = $this->normalizeSeasonInput($_POST);
            $validationError = $this->validateSeasonInput($normalized);
            if ($validationError !== null) {
                header('Location: ' . BASE_URL . 'temporadas?msg=' . $validationError);
                exit;
            }

            if ($seasonModel->createWithTeams(
                $league['id'],
                $normalized['nombre'],
                $normalized['anio'],
                $normalized['fecha_inicio'],
                $normalized['fecha_fin'],
                $normalized['dias_juego'],
                $normalized['hora_inicio'],
                $normalized['hora_fin'],
                $normalized['equipos']
            )) {
                header('Location: ' . BASE_URL . 'temporadas?msg=season_created');
                exit;
            }

            header('Location: ' . BASE_URL . 'temporadas?msg=season_error');
            exit;
        }

        $teams = $teamModel->getAll();
        $seasons = $seasonModel->getByLeagueWithTeams($league['id']);

        $this->render('seasons/index', [
            'pageTitle' => 'Gestión de Temporadas',
            'teams' => $teams,
            'seasons' => $seasons
        ]);
    }

    public function edit() {
        $seasonId = (int)($_GET['id'] ?? 0);
        if ($seasonId <= 0) {
            header('Location: ' . BASE_URL . 'temporadas');
            exit;
        }

        $leagueModel = new League();
        $teamModel = new Team();
        $seasonModel = new Season();
        $league = $leagueModel->getMainLeague();
        $season = $seasonModel->getByIdWithTeams($seasonId, (int)$league['id']);

        if (!$season) {
            header('Location: ' . BASE_URL . 'temporadas?msg=season_not_found');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $normalized = $this->normalizeSeasonInput($_POST);
            $validationError = $this->validateSeasonInput($normalized);
            if ($validationError !== null) {
                header('Location: ' . BASE_URL . 'temporadas/edit?id=' . $seasonId . '&msg=' . $validationError);
                exit;
            }

            if ($seasonModel->updateWithTeams(
                $seasonId,
                (int)$league['id'],
                $normalized['nombre'],
                $normalized['anio'],
                $normalized['fecha_inicio'],
                $normalized['fecha_fin'],
                $normalized['dias_juego'],
                $normalized['hora_inicio'],
                $normalized['hora_fin'],
                $normalized['equipos']
            )) {
                header('Location: ' . BASE_URL . 'temporadas?msg=season_updated');
                exit;
            }

            header('Location: ' . BASE_URL . 'temporadas/edit?id=' . $seasonId . '&msg=season_error');
            exit;
        }

        $teams = $teamModel->getAll();

        $this->render('seasons/edit', [
            'pageTitle' => 'Editar Temporada',
            'teams' => $teams,
            'season' => $season
        ]);
    }

    public function show() {
        $seasonId = (int)($_GET['id'] ?? 0);
        if ($seasonId <= 0) {
            header('Location: ' . BASE_URL . 'temporadas');
            exit;
        }

        $leagueModel = new League();
        $seasonModel = new Season();
        $league = $leagueModel->getMainLeague();
        $season = $seasonModel->getByIdWithTeams($seasonId, (int)$league['id']);

        if (!$season) {
            header('Location: ' . BASE_URL . 'temporadas?msg=season_not_found');
            exit;
        }

        $matches = $seasonModel->getMatchesBySeason($seasonId, (int)$league['id']);
        $attendanceModel = new MatchAttendance();
        $minJugadores = $leagueModel->getMinJugadoresPartido();

        foreach ($matches as &$m) {
            $mid = (int)$m['id'];
            $lid = (int)$m['equipo_local_id'];
            $vid = (int)$m['equipo_visitante_id'];
            $m['present_local'] = $attendanceModel->countPresent($mid, $lid);
            $m['present_visit'] = $attendanceModel->countPresent($mid, $vid);

            $m['referee_validacion_ok'] = true;
            $m['referee_validation_hint'] = '';
            if ($attendanceModel->tableReady()) {
                if (!$attendanceModel->hasValidacionArbitroColumn()) {
                    $m['referee_validacion_ok'] = false;
                    if (($m['estado'] ?? '') === 'programado') {
                        $m['referee_validation_hint'] = 'Falta columna validación árbitro (migración).';
                    }
                } else {
                    $m['referee_validacion_ok'] = $attendanceModel->countPresentWithoutConfirmacion($mid) === 0;
                    if (($m['estado'] ?? '') === 'programado' && !$m['referee_validacion_ok']) {
                        $m['referee_validation_hint'] = 'El árbitro debe validar a todos en nómina (coincide).';
                    }
                }
            }

            $m['can_start_match'] = false;
            if (($m['estado'] ?? '') === 'programado') {
                $canStart = true;
                if ($seasonModel->hasRefereeColumn()) {
                    $aid = isset($m['arbitro_id']) ? (int)$m['arbitro_id'] : 0;
                    if ($aid <= 0) {
                        $canStart = false;
                    }
                }
                if ($attendanceModel->tableReady()) {
                    if ($m['present_local'] < $minJugadores || $m['present_visit'] < $minJugadores) {
                        $canStart = false;
                    }
                    if (empty($m['referee_validacion_ok'])) {
                        $canStart = false;
                    }
                }
                $m['can_start_match'] = $canStart;
            }
        }
        unset($m);

        $this->render('seasons/show', [
            'pageTitle' => 'Detalle de Temporada',
            'season' => $season,
            'matches' => $matches,
            'minJugadores' => $minJugadores,
            'attendanceTableReady' => $attendanceModel->tableReady(),
            'asistenciaDtColumnReady' => $seasonModel->hasAsistenciaDtHabilitadaColumn(),
            'knockoutStageAvailability' => $seasonModel->getKnockoutStageAvailability($seasonId, (int)$league['id']),
            'seasonAwards' => $seasonModel->getSeasonFinalAwards($seasonId, (int)$league['id'])
        ]);
    }

    public function generateKnockoutStage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'temporadas');
            exit;
        }

        $seasonId = (int)($_POST['season_id'] ?? 0);
        $leagueModel = new League();
        $seasonModel = new Season();
        $league = $leagueModel->getMainLeague();

        if ($seasonId <= 0) {
            header('Location: ' . BASE_URL . 'temporadas?msg=season_not_found');
            exit;
        }

        $res = $seasonModel->createInitialKnockoutStage($seasonId, (int)$league['id']);
        if (!empty($res['ok'])) {
            header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=knockout_generated');
            exit;
        }

        header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=knockout_unavailable');
        exit;
    }

    public function enableDtAttendance() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . 'temporadas');
            exit;
        }

        $seasonId = (int)($_POST['season_id'] ?? 0);
        $matchId = (int)($_POST['match_id'] ?? 0);
        $leagueModel = new League();
        $seasonModel = new Season();
        $league = $leagueModel->getMainLeague();

        if ($seasonId <= 0 || $matchId <= 0) {
            header('Location: ' . BASE_URL . 'temporadas?msg=match_error');
            exit;
        }

        $match = $seasonModel->getMatchByIdForSeason($matchId, $seasonId, (int)$league['id']);
        if (!$match) {
            header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_error');
            exit;
        }

        // Antes de habilitar asistencia del DT, el partido debe tener árbitro y cancha asignados.
        if ($seasonModel->hasRefereeColumn()) {
            $arbitroId = (int)($match['arbitro_id'] ?? 0);
            if ($arbitroId <= 0) {
                header('Location: ' . BASE_URL . 'temporadas/match/edit?season_id=' . $seasonId . '&match_id=' . $matchId . '&msg=needs_referee_for_attendance');
                exit;
            }
        }
        if ($seasonModel->hasCanchaColumn()) {
            $canchaId = (int)($match['cancha_id'] ?? 0);
            if ($canchaId <= 0) {
                header('Location: ' . BASE_URL . 'temporadas/match/edit?season_id=' . $seasonId . '&match_id=' . $matchId . '&msg=needs_cancha_for_attendance');
                exit;
            }
        }

        if ($seasonModel->setMatchAsistenciaDtHabilitada($matchId, $seasonId, (int)$league['id'], true)) {
            header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=asistencia_dt_habilitada');
            exit;
        }

        header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=asistencia_dt_error');
        exit;
    }

    public function matchEdit() {
        $seasonId = (int)($_GET['season_id'] ?? 0);
        $matchId = (int)($_GET['match_id'] ?? 0);
        if ($seasonId <= 0 || $matchId <= 0) {
            header('Location: ' . BASE_URL . 'temporadas');
            exit;
        }

        $leagueModel = new League();
        $seasonModel = new Season();
        $refereeModel = new Referee();
        $canchaModel = new Cancha();
        $league = $leagueModel->getMainLeague();
        $season = $seasonModel->getByIdWithTeams($seasonId, (int)$league['id']);
        $match = $seasonModel->getMatchByIdForSeason($matchId, $seasonId, (int)$league['id']);

        if (!$season || !$match) {
            header('Location: ' . BASE_URL . 'temporadas?msg=season_not_found');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? 'update_match';

            if ($action === 'create_referee') {
                $nombreArbitro = trim($_POST['nombre_arbitro'] ?? '');
                $telefonoArbitro = trim($_POST['telefono_arbitro'] ?? '');

                if ($nombreArbitro === '') {
                    header('Location: ' . BASE_URL . 'temporadas/match/edit?season_id=' . $seasonId . '&match_id=' . $matchId . '&msg=referee_invalid');
                    exit;
                }

                if ($refereeModel->create($nombreArbitro, $telefonoArbitro !== '' ? $telefonoArbitro : null)) {
                    header('Location: ' . BASE_URL . 'temporadas/match/edit?season_id=' . $seasonId . '&match_id=' . $matchId . '&msg=referee_created');
                    exit;
                }

                header('Location: ' . BASE_URL . 'temporadas/match/edit?season_id=' . $seasonId . '&match_id=' . $matchId . '&msg=match_error');
                exit;
            }

            $fechaHoraInput = trim($_POST['fecha_hora'] ?? '');
            $arbitroId = (int)($_POST['arbitro_id'] ?? 0);
            $canchaId = (int)($_POST['cancha_id'] ?? 0);
            if ($fechaHoraInput === '') {
                header('Location: ' . BASE_URL . 'temporadas/match/edit?season_id=' . $seasonId . '&match_id=' . $matchId . '&msg=match_invalid');
                exit;
            }

            $fechaHora = str_replace('T', ' ', $fechaHoraInput) . ':00';

            if ($seasonModel->updateMatch(
                $matchId,
                $seasonId,
                (int)$league['id'],
                $fechaHora,
                $arbitroId > 0 ? $arbitroId : null,
                $canchaId > 0 ? $canchaId : null
            )) {
                header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_updated');
                exit;
            }

            header('Location: ' . BASE_URL . 'temporadas/match/edit?season_id=' . $seasonId . '&match_id=' . $matchId . '&msg=match_error');
            exit;
        }

        $referees = $refereeModel->getAll();
        $canchas = $canchaModel->getAllByLiga((int)$league['id']);

        $this->render('seasons/match_edit', [
            'pageTitle' => 'Editar Partido',
            'season' => $season,
            'match' => $match,
            'referees' => $referees,
            'canchas' => $canchas,
            'canchaColumnReady' => $seasonModel->hasCanchaColumn(),
            'canchasTableReady' => $canchaModel->tableExists()
        ]);
    }

    public function startMatch() {
        $seasonId = (int)($_POST['season_id'] ?? 0);
        $matchId = (int)($_POST['match_id'] ?? 0);
        $leagueModel = new League();
        $seasonModel = new Season();
        $attendanceModel = new MatchAttendance();
        $league = $leagueModel->getMainLeague();

        if ($seasonId <= 0 || $matchId <= 0) {
            header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_error');
            exit;
        }

        $match = $seasonModel->getMatchByIdForSeason($matchId, $seasonId, (int)$league['id']);
        if (!$match) {
            header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_error');
            exit;
        }

        if ($seasonModel->hasRefereeColumn()) {
            $aid = isset($match['arbitro_id']) ? (int)$match['arbitro_id'] : 0;
            if ($aid <= 0) {
                header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_needs_referee');
                exit;
            }
        }

        if ($attendanceModel->tableReady()) {
            $min = $leagueModel->getMinJugadoresPartido();
            $localId = (int)$match['equipo_local_id'];
            $visitId = (int)$match['equipo_visitante_id'];
            $cL = $attendanceModel->countPresent($matchId, $localId);
            $cV = $attendanceModel->countPresent($matchId, $visitId);
            if ($cL < $min || $cV < $min) {
                header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_needs_roster&min=' . $min);
                exit;
            }
            if (!$attendanceModel->hasValidacionArbitroColumn()) {
                header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_validacion_column_missing');
                exit;
            }
            $pendVal = $attendanceModel->countPresentWithoutConfirmacion($matchId);
            if ($pendVal > 0) {
                header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_needs_referee_validation');
                exit;
            }
        }

        if ($seasonModel->updateMatchStatus($matchId, $seasonId, (int)$league['id'], 'en curso', ['programado'])) {
            $seasonModel->setMatchInicioReal($matchId, $seasonId, (int)$league['id']);
            header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_started');
            exit;
        }

        header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_error');
        exit;
    }

    /**
     * Finalización directa sin estadísticas (evitar uso; preferir matchFinalize).
     */
    public function endMatch() {
        $seasonId = (int)($_POST['season_id'] ?? 0);
        $matchId = (int)($_POST['match_id'] ?? 0);
        header('Location: ' . BASE_URL . 'temporadas/match/finalizar?season_id=' . $seasonId . '&match_id=' . $matchId);
        exit;
    }

    public function matchFinalize() {
        $seasonId = (int)($_GET['season_id'] ?? $_POST['season_id'] ?? 0);
        $matchId = (int)($_GET['match_id'] ?? $_POST['match_id'] ?? 0);

        $leagueModel = new League();
        $seasonModel = new Season();
        $league = $leagueModel->getMainLeague();
        $matchResultModel = new MatchResult();

        if ($seasonId <= 0 || $matchId <= 0) {
            header('Location: ' . BASE_URL . 'temporadas?msg=match_error');
            exit;
        }

        $match = $seasonModel->getMatchByIdForSeason($matchId, $seasonId, (int)$league['id']);
        if (!$match || ($match['estado'] ?? '') !== 'en curso') {
            header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_not_in_progress');
            exit;
        }

        if (!$matchResultModel->statsTablesReady()) {
            header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_stats_tables_missing');
            exit;
        }

        $playerModel = new Player();
        $localId = (int)$match['equipo_local_id'];
        $visitId = (int)$match['equipo_visitante_id'];
        $playersLocal = $playerModel->getByEquipoId($localId);
        $playersVisit = $playerModel->getByEquipoId($visitId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $goles = [];
            $gJ = $_POST['gol_jugador'] ?? [];
            $gM = $_POST['gol_minuto'] ?? [];
            if (is_array($gJ) && is_array($gM)) {
                $n = max(count($gJ), count($gM));
                for ($i = 0; $i < $n; $i++) {
                    $jid = (int)($gJ[$i] ?? 0);
                    if ($jid <= 0) {
                        continue;
                    }
                    $goles[] = [
                        'jugador_id' => $jid,
                        'minuto' => (int)($gM[$i] ?? 0),
                    ];
                }
            }

            $tarjetas = [];
            $tJ = $_POST['tarj_jugador'] ?? [];
            $tTipo = $_POST['tarj_tipo'] ?? [];
            $tM = $_POST['tarj_minuto'] ?? [];
            $tMot = $_POST['tarj_motivo'] ?? [];
            if (is_array($tJ)) {
                $n = count($tJ);
                for ($i = 0; $i < $n; $i++) {
                    $jid = (int)($tJ[$i] ?? 0);
                    if ($jid <= 0) {
                        continue;
                    }
                    $tarjetas[] = [
                        'jugador_id' => $jid,
                        'tipo' => (string)($tTipo[$i] ?? 'amarilla'),
                        'minuto' => (int)($tM[$i] ?? 0),
                        'motivo' => (string)($tMot[$i] ?? ''),
                    ];
                }
            }

            $informe = trim((string)($_POST['informe_arbitro'] ?? ''));

            $res = $matchResultModel->saveAndFinalizeMatch(
                $matchId,
                $seasonId,
                (int)$league['id'],
                $localId,
                $visitId,
                $goles,
                $tarjetas,
                $informe,
                $seasonModel
            );

            if (!empty($res['ok'])) {
                header('Location: ' . BASE_URL . 'temporadas/show?id=' . $seasonId . '&msg=match_finished');
                exit;
            }

            $this->render('seasons/match_finalize', [
                'pageTitle' => 'Registrar estadísticas del partido',
                'season' => $seasonModel->getByIdWithTeams($seasonId, (int)$league['id']),
                'match' => $match,
                'playersLocal' => $playersLocal,
                'playersVisit' => $playersVisit,
                'localTeamName' => $match['equipo_local_nombre'] ?? '',
                'visitTeamName' => $match['equipo_visitante_nombre'] ?? '',
                'error' => $res['error'] ?? 'No se pudo guardar.',
                'post' => $_POST,
            ]);
            return;
        }

        $season = $seasonModel->getByIdWithTeams($seasonId, (int)$league['id']);
        $this->render('seasons/match_finalize', [
            'pageTitle' => 'Registrar estadísticas del partido',
            'season' => $season,
            'match' => $match,
            'playersLocal' => $playersLocal,
            'playersVisit' => $playersVisit,
            'localTeamName' => $match['equipo_local_nombre'] ?? '',
            'visitTeamName' => $match['equipo_visitante_nombre'] ?? '',
            'error' => null,
            'post' => null,
        ]);
    }

    private function normalizeSeasonInput($input) {
        $diasJuego = $input['dias_juego'] ?? [];
        $equipos = $input['equipos'] ?? [];

        if (!is_array($equipos)) {
            $equipos = [];
        }
        if (!is_array($diasJuego)) {
            $diasJuego = [];
        }

        $equipos = array_unique(array_map('intval', $equipos));
        $equipos = array_values(array_filter($equipos, function($id) {
            return $id > 0;
        }));

        $diasJuego = array_unique(array_map('strval', $diasJuego));
        $diasPermitidos = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $diasJuego = array_values(array_filter($diasJuego, function($dia) use ($diasPermitidos) {
            return in_array($dia, $diasPermitidos, true);
        }));

        return [
            'nombre' => trim($input['nombre_temporada'] ?? ''),
            'anio' => (int)($input['anio_temporada'] ?? 0),
            'fecha_inicio' => trim($input['fecha_inicio'] ?? ''),
            'fecha_fin' => trim($input['fecha_fin'] ?? ''),
            'hora_inicio' => trim($input['hora_inicio'] ?? ''),
            'hora_fin' => trim($input['hora_fin'] ?? ''),
            'dias_juego' => $diasJuego,
            'equipos' => $equipos
        ];
    }

    private function validateSeasonInput($data) {
        if ($data['nombre'] === '' || $data['anio'] < 2000 || $data['anio'] > 2100) {
            return 'season_invalid';
        }
        if ($data['fecha_inicio'] === '') {
            return 'season_dates_required';
        }
        if ($data['fecha_fin'] !== '' && $data['fecha_inicio'] > $data['fecha_fin']) {
            return 'season_dates_invalid';
        }

        $horaInicioVacia = ($data['hora_inicio'] === '');
        $horaFinVacia = ($data['hora_fin'] === '');
        if ($horaInicioVacia xor $horaFinVacia) {
            return 'season_time_pair_required';
        }
        if (!$horaInicioVacia && !$horaFinVacia && $data['hora_inicio'] >= $data['hora_fin']) {
            return 'season_time_invalid';
        }
        if (count($data['dias_juego']) < 1) {
            return 'season_days_required';
        }
        if (count($data['equipos']) < 2) {
            return 'season_min_teams';
        }

        return null;
    }
}
?>
