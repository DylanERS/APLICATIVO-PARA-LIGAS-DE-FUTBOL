<?php
session_start();

// Configuración para registrar errores en un archivo local
ini_set('display_errors', 0); // No mostrar errores en pantalla (por seguridad y diseño)
ini_set('log_errors', 1);     // Habilitar el registro de errores
ini_set('error_log', __DIR__ . '/errores.log'); // Ruta del archivo errores.log
error_reporting(E_ALL);       // Capturar todos los tipos de errores

// Cargar configuración y rutas
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'routes/web.php';

// Obtener la URL solicitada
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : 'home';

// Inicializar el enrutador
$router = new Router();
$router->dispatch($url);
?>
