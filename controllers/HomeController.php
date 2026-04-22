<?php
require_once 'controllers/Controller.php';

class HomeController extends Controller {
    public function index() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }

        require_once 'models/Dashboard.php';
        require_once 'models/League.php';
        require_once 'models/Season.php';
        require_once 'models/Team.php';
        require_once 'models/Referee.php';

        $dashboardModel = new Dashboard();

        if (($_SESSION['role'] ?? '') === 'arbitro') {
            $arbitroId = (int)($_SESSION['arbitro_id'] ?? 0);
            $leagueModel = new League();
            $seasonModel = new Season();
            $refereeModel = new Referee();
            $league = $leagueModel->getMainLeague();
            $referee = $arbitroId > 0 ? $refereeModel->getById($arbitroId) : null;
            $proximos = $arbitroId > 0 ? $seasonModel->getMatchesForReferee($arbitroId, (int)$league['id']) : [];
            $proximos = array_slice($proximos, 0, 12);
            $error = null;
            if ($arbitroId <= 0 || !$referee) {
                $error = 'Tu usuario no tiene un árbitro asignado. Contacta al administrador.';
            }
            $this->render('dashboard/arbitro', [
                'pageTitle' => 'Panel árbitro',
                'referee' => $referee ?: ['id' => 0, 'nombre' => '—'],
                'proximos' => $proximos,
                'error' => $error
            ]);
            return;
        }

        if (($_SESSION['role'] ?? '') === 'director_tecnico') {
            $equipoId = (int)($_SESSION['equipo_id'] ?? 0);
            $leagueModel = new League();
            $seasonModel = new Season();
            $teamModel = new Team();
            $league = $leagueModel->getMainLeague();
            $equipo = $equipoId > 0 ? $teamModel->getById($equipoId) : null;

            $error = null;
            if ($equipoId <= 0 || !$equipo) {
                $error = 'Tu usuario no tiene un equipo asignado. Contacta al administrador.';
            }

            $temporadaActiva = $dashboardModel->getTemporadaActiva();
            $inSeason = false;
            $standingSummary = null;
            $goleadores = [];
            $tarjetasTop = [];
            $seasonAwards = null;
            $showChampionModal = false;

            if ($temporadaActiva && $equipoId > 0 && $dashboardModel->isTeamInSeason((int)$temporadaActiva['id'], $equipoId)) {
                $inSeason = true;
                $standingSummary = $dashboardModel->getTeamStandingSummary((int)$temporadaActiva['id'], $equipoId);
                $goleadores = $dashboardModel->getTopScorersForTeamInSeason((int)$temporadaActiva['id'], $equipoId, 8);
                $tarjetasTop = $dashboardModel->getTopCardsForTeamInSeason((int)$temporadaActiva['id'], $equipoId, 8);
            }
            if ($temporadaActiva) {
                $seasonAwards = $seasonModel->getSeasonFinalAwards((int)$temporadaActiva['id'], (int)$league['id']);
                $championId = (int)($seasonAwards['champion']['id'] ?? 0);
                $showChampionModal = $championId > 0 && $equipoId > 0 && $championId === $equipoId;
            }

            $proximosPropios = [];
            if ($equipoId > 0) {
                $proximosPropios = $seasonModel->getUpcomingMatchesForTeam($equipoId, (int)$league['id']);
                $proximosPropios = array_slice($proximosPropios, 0, 8);
            }

            $this->render('dashboard/dt', [
                'pageTitle' => 'Mi club',
                'equipo' => $equipo ?: ['id' => 0, 'nombre' => '—'],
                'temporada_activa' => $temporadaActiva,
                'in_season' => $inSeason,
                'standing_summary' => $standingSummary,
                'goleadores' => $goleadores,
                'tarjetas_top' => $tarjetasTop,
                'proximos_propios' => $proximosPropios,
                'season_awards' => $seasonAwards,
                'show_champion_modal' => $showChampionModal,
                'error' => $error
            ]);
            return;
        }

        $temporadaActiva = $dashboardModel->getTemporadaActiva();
        $proximosPartidos = $dashboardModel->getProximosPartidos($temporadaActiva['id'] ?? null, 5);
        $ultimoResultado = $dashboardModel->getUltimoPartidoResultado();

        $data = [
            'pageTitle' => 'Dashboard',
            'total_equipos' => $dashboardModel->getTotalEquipos(),
            'total_jugadores' => $dashboardModel->getTotalJugadores(),
            'partidos_jugados' => $dashboardModel->getPartidosJugados(),
            'entradas_dinero' => $dashboardModel->getEntradasDinero(),
            'temporada_activa' => $temporadaActiva,
            'proximos_partidos' => $proximosPartidos,
            'ultimo_resultado' => $ultimoResultado
        ];

        $this->render('dashboard/index', $data);
    }

    /**
     * Listado global de partidos (programados, en curso y finalizados) con acciones.
     */
    public function partidosResultados() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        $role = $_SESSION['role'] ?? '';
        if ($role === 'arbitro' || $role === 'director_tecnico') {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        require_once 'models/Dashboard.php';
        require_once 'models/League.php';
        require_once 'models/Season.php';
        require_once 'models/MatchAttendance.php';

        $leagueModel = new League();
        $seasonModel = new Season();
        $attendanceModel = new MatchAttendance();
        $league = $leagueModel->getMainLeague();
        $minJugadores = $leagueModel->getMinJugadoresPartido();

        $dashboardModel = new Dashboard();
        $temporadas = $seasonModel->getByLeagueWithTeams((int)$league['id']);
        $partidos = [];

        foreach ($temporadas as $t) {
            $sid = (int)($t['id'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $matches = $seasonModel->getMatchesBySeason($sid, (int)$league['id']);
            foreach ($matches as $m) {
                $m['temporada_id'] = $sid;
                $m['temporada_nombre'] = $t['nombre'] ?? '';
                $m['temporada_anio'] = $t['anio'] ?? null;
                $m['goles_local'] = 0;
                $m['goles_visitante'] = 0;
                $partidos[] = $m;
            }
        }

        if (!empty($partidos)) {
            $resultadosByMatch = $dashboardModel->getResultadosByPartidoIds(array_map(function ($p) {
                return (int)$p['id'];
            }, $partidos));

            foreach ($partidos as &$m) {
                $mid = (int)$m['id'];
                $lid = (int)$m['equipo_local_id'];
                $vid = (int)$m['equipo_visitante_id'];
                if (isset($resultadosByMatch[$mid])) {
                    $m['goles_local'] = (int)$resultadosByMatch[$mid]['goles_local'];
                    $m['goles_visitante'] = (int)$resultadosByMatch[$mid]['goles_visitante'];
                }

                $m['present_local'] = $attendanceModel->countPresent($mid, $lid);
                $m['present_visit'] = $attendanceModel->countPresent($mid, $vid);

                $m['referee_validacion_ok'] = true;
                if ($attendanceModel->tableReady()) {
                    if (!$attendanceModel->hasValidacionArbitroColumn()) {
                        $m['referee_validacion_ok'] = false;
                    } else {
                        $m['referee_validacion_ok'] = $attendanceModel->countPresentWithoutConfirmacion($mid) === 0;
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
                        if ((int)$m['present_local'] < $minJugadores || (int)$m['present_visit'] < $minJugadores) {
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
        }

        $this->render('dashboard/partidos_resultados', [
            'pageTitle' => 'Resultados de partidos',
            'partidos' => $partidos,
            'minJugadores' => $minJugadores,
            'asistenciaDtColumnReady' => $seasonModel->hasAsistenciaDtHabilitadaColumn(),
            'attendanceTableReady' => $attendanceModel->tableReady(),
        ]);
    }

    /**
     * Detalle de un partido finalizado: línea de tiempo de goles y tarjetas.
     */
    public function partidoResultadoDetalle() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        $role = $_SESSION['role'] ?? '';
        if ($role === 'arbitro' || $role === 'director_tecnico') {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }

        $partidoId = (int)($_GET['id'] ?? 0);
        if ($partidoId <= 0) {
            header('Location: ' . BASE_URL . 'partidos-resultados');
            exit;
        }

        require_once 'models/Dashboard.php';
        $dashboardModel = new Dashboard();
        $partido = $dashboardModel->getPartidoFinalizadoDetalle($partidoId);
        if (!$partido) {
            header('Location: ' . BASE_URL . 'partidos-resultados');
            exit;
        }

        $timeline = $dashboardModel->getPartidoTimeline(
            $partidoId,
            (int)$partido['equipo_local_id'],
            (int)$partido['equipo_visitante_id'],
            (string)$partido['local_nombre'],
            (string)$partido['visitante_nombre']
        );

        $this->render('dashboard/partido_resultado_detalle', [
            'pageTitle' => 'Detalle del partido',
            'partido' => $partido,
            'timeline' => $timeline
        ]);
    }
}
?>
