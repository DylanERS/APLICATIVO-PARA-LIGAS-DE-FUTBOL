<?php
require_once 'controllers/Controller.php';
require_once 'models/Team.php';

class TeamController extends Controller {

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
        $teamModel = new Team();
        $teams = $teamModel->getAll();

        $this->render('teams/index', [
            'pageTitle' => 'Gestión de Equipos',
            'teams' => $teams
        ]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $teamModel = new Team();
            $teamModel->nombre = $_POST['nombre'] ?? '';
            $teamModel->ciudad = $_POST['ciudad'] ?? '';
            $teamModel->entrenador = $_POST['entrenador'] ?? '';
            $teamModel->logo = 'default_logo.png';

            // Handle file upload if provided
            if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                // simple upload logic
                $uploadDir = 'assets/img/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = time() . '_' . basename($_FILES['logo']['name']);
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                    $teamModel->logo = $fileName;
                }
            }

            if($teamModel->create()){
                header('Location: ' . BASE_URL . 'equipos?msg=created');
                exit;
            }
        }

        $this->render('teams/create', [
            'pageTitle' => 'Crear Equipo'
        ]);
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ' . BASE_URL . 'equipos');
            exit;
        }

        $teamModel = new Team();
        $team = $teamModel->getById($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $teamModel->id = $id;
            $teamModel->nombre = $_POST['nombre'] ?? '';
            $teamModel->ciudad = $_POST['ciudad'] ?? '';
            $teamModel->entrenador = $_POST['entrenador'] ?? '';
            $teamModel->logo = $team['logo'];

            if(isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
                $uploadDir = 'assets/img/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = time() . '_' . basename($_FILES['logo']['name']);
                $uploadPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadPath)) {
                    // Delete old logo if it's not default
                    if($team['logo'] != 'default_logo.png' && file_exists($uploadDir . $team['logo'])) {
                        unlink($uploadDir . $team['logo']);
                    }
                    $teamModel->logo = $fileName;
                }
            }

            if($teamModel->update()){
                header('Location: ' . BASE_URL . 'equipos?msg=updated');
                exit;
            }
        }

        $this->render('teams/edit', [
            'pageTitle' => 'Editar Equipo',
            'team' => $team
        ]);
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $teamModel = new Team();
            // Get to delete old logo maybe
            $team = $teamModel->getById($id);
            if($team && $team['logo'] != 'default_logo.png' && file_exists('assets/img/' . $team['logo'])) {
                unlink('assets/img/' . $team['logo']);
            }
            $teamModel->delete($id);
        }
        header('Location: ' . BASE_URL . 'equipos?msg=deleted');
        exit;
    }
}
?>
