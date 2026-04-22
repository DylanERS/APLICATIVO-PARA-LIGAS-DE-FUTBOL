<?php
require_once 'controllers/Controller.php';
require_once 'models/League.php';
require_once 'models/Cancha.php';

class CanchaController extends Controller {

    public function __construct() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        if (($_SESSION['role'] ?? '') !== 'admin') {
            die('Acceso denegado. Solo el Dueño de la liga puede acceder a esta sección.');
        }
    }

    public function index() {
        $leagueModel = new League();
        $league = $leagueModel->getMainLeague();
        $canchaModel = new Cancha();
        $canchas = $canchaModel->getAllByLiga((int)$league['id']);
        $tablaLista = $canchaModel->tableExists();

        $this->render('canchas/index', [
            'pageTitle' => 'Canchas',
            'canchas' => $canchas,
            'tabla_canchas_ok' => $tablaLista,
        ]);
    }

    public function create() {
        $leagueModel = new League();
        $league = $leagueModel->getMainLeague();
        $canchaModel = new Cancha();
        if (!$canchaModel->tableExists()) {
            header('Location: ' . BASE_URL . 'canchas?msg=no_table');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $canchaModel->liga_id = (int)$league['id'];
            $canchaModel->nombre = $_POST['nombre'] ?? '';
            $canchaModel->direccion = $_POST['direccion'] ?? '';
            $canchaModel->notas = $_POST['notas'] ?? '';
            $canchaModel->activa = isset($_POST['activa']) ? 1 : 0;
            if ($canchaModel->create()) {
                header('Location: ' . BASE_URL . 'canchas?msg=created');
                exit;
            }
        }

        $this->render('canchas/create', [
            'pageTitle' => 'Nueva cancha',
        ]);
    }

    public function edit() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ' . BASE_URL . 'canchas');
            exit;
        }
        $leagueModel = new League();
        $league = $leagueModel->getMainLeague();
        $canchaModel = new Cancha();
        if (!$canchaModel->tableExists()) {
            header('Location: ' . BASE_URL . 'canchas?msg=no_table');
            exit;
        }
        $cancha = $canchaModel->getById($id);
        if (!$cancha || (int)$cancha['liga_id'] !== (int)$league['id']) {
            header('Location: ' . BASE_URL . 'canchas');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $canchaModel->id = $id;
            $canchaModel->nombre = $_POST['nombre'] ?? '';
            $canchaModel->direccion = $_POST['direccion'] ?? '';
            $canchaModel->notas = $_POST['notas'] ?? '';
            $canchaModel->activa = isset($_POST['activa']) ? 1 : 0;
            if ($canchaModel->update()) {
                header('Location: ' . BASE_URL . 'canchas?msg=updated');
                exit;
            }
        }

        $this->render('canchas/edit', [
            'pageTitle' => 'Editar cancha',
            'cancha' => $cancha,
        ]);
    }

    public function delete() {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $leagueModel = new League();
            $league = $leagueModel->getMainLeague();
            $canchaModel = new Cancha();
            $cancha = $canchaModel->getById($id);
            if ($cancha && (int)$cancha['liga_id'] === (int)$league['id']) {
                $canchaModel->delete($id);
            }
        }
        header('Location: ' . BASE_URL . 'canchas?msg=deleted');
        exit;
    }
}
