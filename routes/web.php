<?php

class Router {
    protected $routes = [
        'home' => ['controller' => 'HomeController', 'action' => 'index'],
        'partidos-resultados' => ['controller' => 'HomeController', 'action' => 'partidosResultados'],
        'partidos-resultado-detalle' => ['controller' => 'HomeController', 'action' => 'partidoResultadoDetalle'],
        'login' => ['controller' => 'AuthController', 'action' => 'login'],
        'logout' => ['controller' => 'AuthController', 'action' => 'logout'],

        // Equipos
        'equipos' => ['controller' => 'TeamController', 'action' => 'index'],
        'equipos/create' => ['controller' => 'TeamController', 'action' => 'create'],
        'equipos/edit' => ['controller' => 'TeamController', 'action' => 'edit'],
        'equipos/delete' => ['controller' => 'TeamController', 'action' => 'delete'],
        
        // Jugadores
        'jugadores' => ['controller' => 'PlayerController', 'action' => 'index'],
        'jugadores/create' => ['controller' => 'PlayerController', 'action' => 'create'],
        'jugadores/edit' => ['controller' => 'PlayerController', 'action' => 'edit'],
        'jugadores/delete' => ['controller' => 'PlayerController', 'action' => 'delete'],
        
        // Usuarios
        'usuarios' => ['controller' => 'UserController', 'action' => 'index'],
        'usuarios/create' => ['controller' => 'UserController', 'action' => 'create'],
        'usuarios/edit' => ['controller' => 'UserController', 'action' => 'edit'],
        'usuarios/delete' => ['controller' => 'UserController', 'action' => 'delete'],

        // Configuración Liga
        'configuracion' => ['controller' => 'LeagueController', 'action' => 'index'],
        'canchas' => ['controller' => 'CanchaController', 'action' => 'index'],
        'canchas/create' => ['controller' => 'CanchaController', 'action' => 'create'],
        'canchas/edit' => ['controller' => 'CanchaController', 'action' => 'edit'],
        'canchas/delete' => ['controller' => 'CanchaController', 'action' => 'delete'],
        'temporadas' => ['controller' => 'SeasonController', 'action' => 'index'],
        'temporadas/edit' => ['controller' => 'SeasonController', 'action' => 'edit'],
        'temporadas/show' => ['controller' => 'SeasonController', 'action' => 'show'],
        'temporadas/match/edit' => ['controller' => 'SeasonController', 'action' => 'matchEdit'],
        'temporadas/match/start' => ['controller' => 'SeasonController', 'action' => 'startMatch'],
        'temporadas/match/end' => ['controller' => 'SeasonController', 'action' => 'endMatch'],
        'temporadas/match/finalizar' => ['controller' => 'SeasonController', 'action' => 'matchFinalize'],
        'temporadas/match/asistencia-dt-habilitar' => ['controller' => 'SeasonController', 'action' => 'enableDtAttendance'],
        'temporadas/generar-eliminatoria' => ['controller' => 'SeasonController', 'action' => 'generateKnockoutStage'],

        'partido/asistencia' => ['controller' => 'MatchAttendanceController', 'action' => 'asistencia'],
        'partido/asistencia/catalogo' => ['controller' => 'MatchAttendanceController', 'action' => 'asistenciaCatalogo'],
        'partido/asistencia/qr-marcar' => ['controller' => 'MatchAttendanceController', 'action' => 'qrMarcarAsistencia'],
        'partido/asistencia/foto-jugador' => ['controller' => 'MatchAttendanceController', 'action' => 'asistenciaFotoJugador'],
        'partido/mis-partidos' => ['controller' => 'MatchAttendanceController', 'action' => 'myMatches'],
        'partido/arbitro/mis-partidos' => ['controller' => 'MatchAttendanceController', 'action' => 'refereeMyMatches'],
        'partido/arbitro/validar-asistencia' => ['controller' => 'MatchAttendanceController', 'action' => 'refereeValidarAsistencia'],
        'partido/arbitro/escanear-qr' => ['controller' => 'MatchAttendanceController', 'action' => 'refereeScanQr'],
        'partido/arbitro/confirmar-asistencia-qr' => ['controller' => 'MatchAttendanceController', 'action' => 'refereeConfirmarAsistenciaQr'],

        // Finanzas
        'finanzas' => ['controller' => 'FinanceController', 'action' => 'index']
    ];

    public function dispatch($url) {
        // Simple routing matching exact paths, or paths with ID parameters
        // Example: equipos/edit?id=5 (Handled by URL parsing logic)
        
        $urlParts = explode('/', $url);
        $routeKey = $url;
        
        if (count($urlParts) > 1 && is_numeric(end($urlParts))) {
            // It means URL looks like equipos/edit/5
            array_pop($urlParts);
            $routeKey = implode('/', $urlParts);
        }

        if (array_key_exists($routeKey, $this->routes)) {
            $controllerName = $this->routes[$routeKey]['controller'];
            $actionName = $this->routes[$routeKey]['action'];

            $controllerFile = 'controllers/' . $controllerName . '.php';

            if (file_exists($controllerFile)) {
                require_once $controllerFile;
                
                if (class_exists($controllerName)) {
                    $controllerInstance = new $controllerName();
                    
                    if (method_exists($controllerInstance, $actionName)) {
                        $controllerInstance->$actionName();
                        return;
                    }
                }
            }
        }

        // 404 Not Found
        http_response_code(404);
        require_once 'views/layouts/404.php';
    }
}
?>
