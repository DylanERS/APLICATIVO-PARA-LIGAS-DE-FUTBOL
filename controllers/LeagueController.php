<?php
require_once 'controllers/Controller.php';
require_once 'models/League.php';

class LeagueController extends Controller {

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
        $league = $leagueModel->getMainLeague();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $leagueModel->id = $league['id'];
            $leagueModel->nombre = $_POST['nombre'] ?? '';
            $leagueModel->descripcion = $_POST['descripcion'] ?? '';
            $leagueModel->min_jugadores_partido = (int)($_POST['min_jugadores_partido'] ?? 7);
            $leagueModel->duracion_partido_minutos = (int)($_POST['duracion_partido_minutos'] ?? 90);

            if($leagueModel->update()){
                header('Location: ' . BASE_URL . 'configuracion?msg=updated');
                exit;
            }
        }

        $this->render('league/index', [
            'pageTitle' => 'Configuración de la Liga',
            'league' => $league,
            'has_duracion_column' => $leagueModel->hasDuracionPartidoColumn(),
        ]);
    }
}
?>
