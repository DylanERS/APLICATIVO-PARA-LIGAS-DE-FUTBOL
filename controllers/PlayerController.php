<?php
require_once 'controllers/Controller.php';
require_once 'controllers/MatchAttendanceController.php';
require_once 'models/Player.php';
require_once 'models/Team.php';
require_once 'models/League.php';
require_once 'models/Season.php';

class PlayerController extends Controller {

    public function __construct() {
        if(!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        if (($_SESSION['role'] ?? '') === 'director_tecnico') {
            header('Location: ' . BASE_URL . 'home');
            exit;
        }
    }

    public function index() {
        $playerModel = new Player();
        $players = $playerModel->getAllWithTeam();

        $this->render('players/index', [
            'pageTitle' => 'Gestión de Jugadores',
            'players' => $players
        ]);
    }

    public function create() {
        $teamModel = new Team();
        $teams = $teamModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $playerModel = new Player();
            $playerModel->equipo_id = $_POST['equipo_id'] ?? null;
            $playerModel->nombre = $_POST['nombre'] ?? '';
            $playerModel->edad = $_POST['edad'] ?? 0;
            $playerModel->posicion = $_POST['posicion'] ?? '';
            $playerModel->numero = $_POST['numero'] ?? 0;
            $playerModel->foto = 'default_player.png';

            if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $uploadDir = 'assets/img/players/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = time() . '_' . basename($_FILES['foto']['name']);
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadPath)) {
                    $playerModel->foto = $fileName;
                }
            }

            if($playerModel->create()){
                header('Location: ' . BASE_URL . 'jugadores?msg=created');
                exit;
            }
        }

        $this->render('players/create', [
            'pageTitle' => 'Registrar Jugador',
            'teams' => $teams
        ]);
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ' . BASE_URL . 'jugadores');
            exit;
        }

        $playerModel = new Player();
        $player = $playerModel->getById($id);

        $teamModel = new Team();
        $teams = $teamModel->getAll();
        $currentTeam = !empty($player['equipo_id']) ? $teamModel->getById($player['equipo_id']) : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $playerModel->id = $id;
            $playerModel->equipo_id = $_POST['equipo_id'] ?? null;
            $playerModel->nombre = $_POST['nombre'] ?? '';
            $playerModel->edad = $_POST['edad'] ?? 0;
            $playerModel->posicion = $_POST['posicion'] ?? '';
            $playerModel->numero = $_POST['numero'] ?? 0;
            $playerModel->foto = $player['foto'];

            if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
                $uploadDir = 'assets/img/players/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = time() . '_' . basename($_FILES['foto']['name']);
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadPath)) {
                    if($player['foto'] != 'default_player.png' && file_exists($uploadDir . $player['foto'])) {
                        unlink($uploadDir . $player['foto']);
                    }
                    $playerModel->foto = $fileName;
                }
            }

            if($playerModel->update()){
                header('Location: ' . BASE_URL . 'jugadores?msg=updated');
                exit;
            }
        }

        $leagueModel = new League();
        $league = $leagueModel->getMainLeague();
        $ligaNombreMarca = '';
        if (!empty($league['nombre'])) {
            $nombreLiga = trim((string)$league['nombre']);
            $ligaNombreMarca = function_exists('mb_strtoupper')
                ? mb_strtoupper($nombreLiga, 'UTF-8')
                : strtoupper($nombreLiga);
        }
        if ($ligaNombreMarca === '') {
            $ligaNombreMarca = 'LIGA';
        }

        $registroQrHref = null;
        $registroQrPartidoFecha = null;
        if (!empty($player['equipo_id'])) {
            $seasonModel = new Season();
            $nextMatch = $seasonModel->getNextScheduledMatchForTeam((int)$player['equipo_id'], (int)$league['id']);
            if ($nextMatch) {
                $registroQrHref = MatchAttendanceController::buildQrMarcarAsistenciaUrl(
                    (int)$nextMatch['id'],
                    (int)$nextMatch['temporada_id'],
                    (int)$player['equipo_id'],
                    (int)$player['id']
                );
                if (!empty($nextMatch['fecha_hora'])) {
                    try {
                        $dt = new DateTime($nextMatch['fecha_hora']);
                        $registroQrPartidoFecha = $dt->format('d/m/Y H:i');
                    } catch (Exception $e) {
                        $registroQrPartidoFecha = null;
                    }
                }
            }
        }

        $this->render('players/edit', [
            'pageTitle' => 'Editar Jugador',
            'player' => $player,
            'teams' => $teams,
            'currentTeam' => $currentTeam,
            'registroQrHref' => $registroQrHref,
            'registroQrPartidoFecha' => $registroQrPartidoFecha,
            'ligaNombreMarca' => $ligaNombreMarca
        ]);
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $playerModel = new Player();
            $player = $playerModel->getById($id);
            if($player && $player['foto'] != 'default_player.png' && file_exists('assets/img/players/' . $player['foto'])) {
                unlink('assets/img/players/' . $player['foto']);
            }
            $playerModel->delete($id);
        }
        header('Location: ' . BASE_URL . 'jugadores?msg=deleted');
        exit;
    }
}
?>
