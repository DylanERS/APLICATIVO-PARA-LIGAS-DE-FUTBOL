<?php
require_once 'controllers/Controller.php';
require_once 'models/User.php';
require_once 'models/League.php';

class AuthController extends Controller {
    private function redirectAfterLogin($pathOrUrl) {
        $pathOrUrl = trim((string)$pathOrUrl);
        if ($pathOrUrl !== '' && !preg_match('#^https?://#i', $pathOrUrl) && strpos($pathOrUrl, '//') === false) {
            $pathOrUrl = ltrim($pathOrUrl, '/');
            header('Location: ' . BASE_URL . $pathOrUrl);
            exit;
        }
        header('Location: ' . BASE_URL . 'home');
        exit;
    }

    public function login() {
        // If already logged in, redirect
        if(isset($_SESSION['user_id'])) {
            $this->redirectAfterLogin($_GET['redirect'] ?? '');
        }

        $error = '';
        $redirect = $_GET['redirect'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $redirect = $_POST['redirect'] ?? $redirect;

            if (empty($username) || empty($password)) {
                $error = 'Por favor ingrese usuario y contraseña.';
            } else {
                $userModel = new User();
                $user = $userModel->getUserByUsername($username);

                // For initial setup we bypass password_verify if password == admin123 
                // Or you can strictly use password_verify if the hash in DB is set up right
                // The sql inserted hash is for 'admin123'
                if ($user && password_verify($password, $user['password'])) {
                    // Login success
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['equipo_id'] = isset($user['equipo_id']) && $user['equipo_id'] !== null && $user['equipo_id'] !== ''
                        ? (int)$user['equipo_id']
                        : null;
                    $_SESSION['arbitro_id'] = isset($user['arbitro_id']) && $user['arbitro_id'] !== null && $user['arbitro_id'] !== ''
                        ? (int)$user['arbitro_id']
                        : null;

                    $this->redirectAfterLogin($redirect);
                } else {
                    $error = 'Credenciales incorrectas.';
                }
            }
        }

        $leagueModel = new League();
        $league = $leagueModel->getMainLeague();
        $app_league_display_name = 'LIGA';
        if (!empty($league['nombre'])) {
            $nombre = trim((string)$league['nombre']);
            $app_league_display_name = function_exists('mb_strtoupper')
                ? mb_strtoupper($nombre, 'UTF-8')
                : strtoupper($nombre);
        }

        $this->renderPartial('auth/login', [
            'error' => $error,
            'redirect' => $redirect,
            'app_league_display_name' => $app_league_display_name
        ]);
    }

    public function logout() {
        session_destroy();
        header('Location: ' . BASE_URL . 'login');
        exit;
    }
}
?>
