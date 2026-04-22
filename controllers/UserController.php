<?php
require_once 'controllers/Controller.php';
require_once 'models/User.php';
require_once 'models/Team.php';
require_once 'models/Referee.php';

class UserController extends Controller {
    private function mapUserErrorToMsg($error) {
        $err = strtolower((string)$error);
        // SQL Server: CK__usuarios__role__... o mensaje CHECK en columna role
        if (strpos($err, 'ck__usuarios__role') !== false) {
            return 'role_constraint';
        }
        if (strpos($err, 'check constraint') !== false && strpos($err, 'usuarios') !== false) {
            return 'role_constraint';
        }
        if (strpos($err, 'check constraint') !== false && strpos($err, 'role') !== false) {
            return 'role_constraint';
        }
        return 'db_error';
    }

    public function __construct() {
        if(!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        
        // Solo el Dueño de la liga (admin) puede gestionar usuarios
        if($_SESSION['role'] !== 'admin') {
            die("Acceso denegado. Solo el Dueño de la liga puede acceder a esta sección.");
        }
    }

    public function index() {
        $userModel = new User();
        $users = $userModel->getAll();

        $this->render('users/index', [
            'pageTitle' => 'Gestión de Usuarios',
            'users' => $users
        ]);
    }

    public function create() {
        $teamModel = new Team();
        $refereeModel = new Referee();
        $teams = $teamModel->getAll();
        $referees = $refereeModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userModel = new User();
            $userModel->username = $_POST['username'] ?? '';
            $userModel->password = $_POST['password'] ?? '';
            $userModel->role = $_POST['role'] ?? 'organizador';
            $userModel->equipo_id = (int)($_POST['equipo_id'] ?? 0);

            if (($userModel->role === 'director_tecnico') && (int)$userModel->equipo_id <= 0) {
                header('Location: ' . BASE_URL . 'usuarios/create?msg=dt_team');
                exit;
            }
            $userModel->arbitro_id = (int)($_POST['arbitro_id'] ?? 0);
            if (($userModel->role === 'arbitro') && (int)$userModel->arbitro_id <= 0) {
                header('Location: ' . BASE_URL . 'usuarios/create?msg=arbitro_ref');
                exit;
            }

            if($userModel->create()){
                header('Location: ' . BASE_URL . 'usuarios?msg=created');
                exit;
            }
            $msg = $this->mapUserErrorToMsg($userModel->lastError ?? '');
            header('Location: ' . BASE_URL . 'usuarios/create?msg=' . $msg);
            exit;
        }

        $this->render('users/create', [
            'pageTitle' => 'Crear Usuario',
            'teams' => $teams,
            'referees' => $referees
        ]);
    }

    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ' . BASE_URL . 'usuarios');
            exit;
        }

        $userModel = new User();
        $teamModel = new Team();
        $refereeModel = new Referee();
        $teams = $teamModel->getAll();
        $referees = $refereeModel->getAll();
        $user = $userModel->getById($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userModel->id = $id;
            $userModel->username = $_POST['username'] ?? '';
            $userModel->role = $_POST['role'] ?? 'organizador';
            $userModel->equipo_id = (int)($_POST['equipo_id'] ?? 0);
            $userModel->arbitro_id = (int)($_POST['arbitro_id'] ?? 0);
            
            $password = $_POST['password'] ?? '';
            $update_password = false;

            if (!empty($password)) {
                $userModel->password = $password;
                $update_password = true;
            }

            if (($userModel->role === 'director_tecnico') && (int)$userModel->equipo_id <= 0) {
                header('Location: ' . BASE_URL . 'usuarios/edit?id=' . rawurlencode((string)$id) . '&msg=dt_team');
                exit;
            }
            if (($userModel->role === 'arbitro') && (int)$userModel->arbitro_id <= 0) {
                header('Location: ' . BASE_URL . 'usuarios/edit?id=' . rawurlencode((string)$id) . '&msg=arbitro_ref');
                exit;
            }

            if($userModel->update($update_password)){
                header('Location: ' . BASE_URL . 'usuarios?msg=updated');
                exit;
            }
            $msg = $this->mapUserErrorToMsg($userModel->lastError ?? '');
            header('Location: ' . BASE_URL . 'usuarios/edit?id=' . rawurlencode((string)$id) . '&msg=' . $msg);
            exit;
        }

        $this->render('users/edit', [
            'pageTitle' => 'Editar Usuario',
            'user' => $user,
            'teams' => $teams,
            'referees' => $referees
        ]);
    }

    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id && $id != $_SESSION['user_id']) { // Prevenir auto-eliminación
            $userModel = new User();
            $userModel->delete($id);
        }
        header('Location: ' . BASE_URL . 'usuarios?msg=deleted');
        exit;
    }
}
?>
