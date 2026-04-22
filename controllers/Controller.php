<?php

class Controller {
    // Renderiza una vista dentro del layout principal
    protected function render($view, $data = []) {
        $data['sidebar_active_seasons'] = [];

        require_once __DIR__ . '/../models/League.php';
        $leagueModel = new League();
        $league = $leagueModel->getMainLeague();
        $data['app_league_display_name'] = 'LIGA';
        if (!empty($league['nombre'])) {
            $nombre = trim((string)$league['nombre']);
            $data['app_league_display_name'] = function_exists('mb_strtoupper')
                ? mb_strtoupper($nombre, 'UTF-8')
                : strtoupper($nombre);
        }

        if (isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin') {
            require_once __DIR__ . '/../models/Season.php';
            $seasonModel = new Season();
            $data['sidebar_active_seasons'] = $seasonModel->getActiveSeasonsByLeague((int)$league['id']);
        }

        // Extrae las variables del arreglo $data para usarlas en la vista
        extract($data);
        
        $contentView = 'views/' . $view . '.php';
        
        if (file_exists($contentView)) {
            // El archivo main.php incluirá $contentView
            require_once 'views/layouts/main.php';
        } else {
            die("La vista $contentView no existe.");
        }
    }

    // Renderiza una vista sin layout (ideal para login o reportes)
    protected function renderPartial($view, $data = []) {
        extract($data);
        
        $contentView = 'views/' . $view . '.php';
        if (file_exists($contentView)) {
            require_once $contentView;
        } else {
            die("La vista $contentView no existe.");
        }
    }
}
?>
