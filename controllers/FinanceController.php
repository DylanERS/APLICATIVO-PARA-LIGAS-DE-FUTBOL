<?php
require_once 'controllers/Controller.php';
require_once 'models/Finance.php';
require_once 'models/Team.php';

class FinanceController extends Controller {

    public function __construct() {
        if(!isset($_SESSION['user_id'])) {
            header('Location: ' . BASE_URL . 'login');
            exit;
        }
        
        if($_SESSION['role'] !== 'admin') {
            die("Acceso denegado. Solo el Dueño de la liga puede gestionar las finanzas.");
        }
    }

    public function index() {
        $financeModel = new Finance();
        $pagos = $financeModel->getPagos();
        $multas = $financeModel->getMultas();
        $seasons = $financeModel->getSeasonsForPayments();
        $seasonTeamsMap = $financeModel->getSeasonTeamsMap();
        $matches = $financeModel->getMatchesForPayments();

        $teamModel = new Team();
        $teams = $teamModel->getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipo = $_POST['tipo_registro'] ?? '';
            
            if ($tipo == 'pago') {
                $catalogo = $_POST['catalogo_concepto'] ?? '';
                $monto = (float)($_POST['monto'] ?? 0);

                if ($monto <= 0) {
                    header('Location: ' . BASE_URL . 'finanzas?msg=pago_invalid');
                    exit;
                }

                $equipo_id = 0;
                $temporada_id = 0;
                $concepto = '';

                if ($catalogo === 'inscripcion') {
                    $equipo_id = (int)($_POST['equipo_id'] ?? 0);
                    $temporada_id = (int)($_POST['temporada_id'] ?? 0);
                    if ($equipo_id <= 0 || $temporada_id <= 0 || !$financeModel->isTeamInSeason($equipo_id, $temporada_id)) {
                        header('Location: ' . BASE_URL . 'finanzas?msg=pago_invalid');
                        exit;
                    }
                    $concepto = 'Inscripcion';
                } else if ($catalogo === 'arbitraje') {
                    $partido_id = (int)($_POST['partido_id'] ?? 0);
                    $equipo_id = (int)($_POST['equipo_id'] ?? 0);
                    $partido = $financeModel->getMatchById($partido_id);
                    if (!$partido) {
                        header('Location: ' . BASE_URL . 'finanzas?msg=pago_invalid');
                        exit;
                    }
                    $temporada_id = (int)$partido['temporada_id'];
                    $localId = (int)$partido['equipo_local_id'];
                    $visitanteId = (int)$partido['equipo_visitante_id'];
                    if ($equipo_id !== $localId && $equipo_id !== $visitanteId) {
                        header('Location: ' . BASE_URL . 'finanzas?msg=pago_invalid');
                        exit;
                    }
                    $concepto = 'Arbitraje - Partido #' . $partido_id;
                } else if ($catalogo === 'otros') {
                    $equipo_id = (int)($_POST['equipo_id'] ?? 0);
                    $temporada_id = (int)($_POST['temporada_id'] ?? 0);
                    $conceptoOtro = trim($_POST['concepto_otro'] ?? '');
                    if ($equipo_id <= 0 || $temporada_id <= 0 || $conceptoOtro === '') {
                        header('Location: ' . BASE_URL . 'finanzas?msg=pago_invalid');
                        exit;
                    }
                    if (!$financeModel->isTeamInSeason($equipo_id, $temporada_id)) {
                        header('Location: ' . BASE_URL . 'finanzas?msg=pago_invalid');
                        exit;
                    }
                    $concepto = 'Otros - ' . $conceptoOtro;
                } else {
                    header('Location: ' . BASE_URL . 'finanzas?msg=pago_invalid');
                    exit;
                }

                if($financeModel->createPago($equipo_id, $temporada_id, $concepto, $monto)){
                    header('Location: ' . BASE_URL . 'finanzas?msg=pago_created');
                    exit;
                }
            } else if ($tipo == 'multa') {
                $equipo_id = $_POST['equipo_id'] ?? 0;
                $motivo = $_POST['motivo'] ?? '';
                $monto = $_POST['monto'] ?? 0;
                
                if($financeModel->createMulta($equipo_id, $motivo, $monto)){
                    header('Location: ' . BASE_URL . 'finanzas?msg=multa_created');
                    exit;
                }
            } else if ($tipo == 'pagar_multa') {
                $multa_id = $_POST['multa_id'] ?? 0;
                if($financeModel->pagarMulta($multa_id)) {
                    header('Location: ' . BASE_URL . 'finanzas?msg=multa_pagada');
                    exit;
                }
            }
        }

        $this->render('finances/index', [
            'pageTitle' => 'Finanzas y Tesorería',
            'pagos' => $pagos,
            'multas' => $multas,
            'teams' => $teams,
            'seasons' => $seasons,
            'seasonTeamsMap' => $seasonTeamsMap,
            'matches' => $matches
        ]);
    }
}
?>
