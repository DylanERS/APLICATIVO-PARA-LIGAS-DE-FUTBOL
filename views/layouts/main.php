<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?> - <?= isset($pageTitle) ? $pageTitle : 'Dashboard' ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            position: sticky;
            top: 0;
            align-self: flex-start;
            height: 100vh;
            z-index: 1020;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }
        .sidebar-inner {
            height: 100%;
            min-height: 0;
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
        }
        .sidebar-menu-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.35) rgba(255,255,255,0.08);
        }
        .sidebar-menu-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar-menu-scroll::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
            border-radius: 4px;
        }
        .sidebar-menu-scroll::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.25);
            border-radius: 4px;
        }
        .sidebar-menu-scroll::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.4);
        }
        .sidebar-footer {
            flex-shrink: 0;
        }
        /* Barra superior móvil (hamburguesa) */
        .mobile-top-bar {
            z-index: 1035;
        }
        /* Offcanvas menú: altura útil y scroll interno */
        #sidebarOffcanvas .offcanvas-body {
            min-height: calc(100vh - 3.5rem);
            max-height: calc(100vh - 3.5rem);
            overflow: hidden;
        }
        @supports (height: 100dvh) {
            #sidebarOffcanvas .offcanvas-body {
                min-height: calc(100dvh - 3.5rem);
                max-height: calc(100dvh - 3.5rem);
            }
        }
        #sidebarOffcanvas .sidebar-inner {
            min-height: 100%;
        }
        #sidebarOffcanvas .sidebar-menu-scroll {
            max-height: none;
        }
        @media (max-width: 767.98px) {
            .content-area {
                padding: 1rem;
            }
        }
        .nav-link {
            color: #dee2e6;
            margin-bottom: 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            color: #fff;
        }
        .nav-link i {
            width: 25px;
        }
        .content-area {
            padding: 30px;
        }
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border-radius: 10px;
        }
        /* Tablas: texto centrado horizontal y vertical en todo el sistema (vistas con layout) */
        .content-area .table > thead > tr > th,
        .content-area .table > thead > tr > td,
        .content-area .table > tbody > tr > th,
        .content-area .table > tbody > tr > td,
        .content-area .table > tfoot > tr > th,
        .content-area .table > tfoot > tr > td {
            text-align: center;
            vertical-align: middle;
        }
        /* DataTables puede forzar alineación izquierda en columnas */
        .content-area table.dataTable > thead > tr > th,
        .content-area table.dataTable > thead > tr > td,
        .content-area table.dataTable > tbody > tr > th,
        .content-area table.dataTable > tbody > tr > td,
        .content-area table.dataTable > tfoot > tr > th,
        .content-area table.dataTable > tfoot > tr > td {
            text-align: center !important;
            vertical-align: middle !important;
        }
    </style>
</head>
<body>

<?php if(isset($_SESSION['user_id'])): ?>
<!-- Móvil: barra con menú hamburguesa -->
<header class="mobile-top-bar d-md-none sticky-top bg-dark text-white border-bottom border-secondary shadow-sm">
    <div class="d-flex align-items-center justify-content-between px-2 py-2 gap-2">
        <button type="button" class="btn btn-outline-light btn-sm flex-shrink-0" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas" aria-controls="sidebarOffcanvas" aria-label="Abrir menú">
            <i class="fa-solid fa-bars fa-lg"></i>
        </button>
        <span class="fw-bold text-truncate small text-center flex-grow-1"><i class="fa-solid fa-futbol text-success me-1"></i><?= htmlspecialchars($app_league_display_name ?? 'LIGA') ?></span>
        <span class="small text-white-50 text-truncate flex-shrink-0" style="max-width:32%"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
    </div>
</header>

<!-- Móvil: menú lateral deslizable -->
<div class="offcanvas offcanvas-start text-bg-dark" tabindex="-1" id="sidebarOffcanvas" aria-labelledby="sidebarOffcanvasLabel" data-bs-scroll="true">
    <div class="offcanvas-header border-bottom border-secondary py-3">
        <h5 class="offcanvas-title text-white mb-0" id="sidebarOffcanvasLabel"><i class="fa-solid fa-bars me-2"></i>Menú</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar menú"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column">
        <?php $sidebarInstance = 'mobile'; require __DIR__ . '/sidebar_inner.php'; ?>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Escritorio: columna lateral fija -->
        <div class="d-none d-md-block col-md-3 col-lg-2 px-0 bg-dark sidebar">
            <?php $sidebarInstance = 'desktop'; require __DIR__ . '/sidebar_inner.php'; ?>
        </div>

        <!-- Contenido -->
        <div class="col-12 col-md-9 col-lg-10 content-area">
            <?php require_once $contentView; ?>
        </div>
    </div>
</div>
<?php else: ?>
    <!-- Public / Login Content -->
    <?php require_once $contentView; ?>
<?php endif; ?>

<!-- Core Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<!-- SweetAlert2 for notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    (function () {
        var dtLangEsMX = {
            emptyTable: 'No hay información disponible en la tabla',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros en total)',
            infoThousands: ',',
            lengthMenu: 'Mostrar _MENU_ registros',
            loadingRecords: 'Cargando…',
            processing: 'Procesando…',
            search: 'Buscar:',
            zeroRecords: 'No se encontraron registros coincidentes',
            paginate: {
                first: 'Primero',
                last: 'Último',
                next: 'Siguiente',
                previous: 'Anterior'
            },
            aria: {
                sortAscending: ': activar para ordenar la columna de forma ascendente',
                sortDescending: ': activar para ordenar la columna de forma descendente'
            },
            decimal: '.',
            thousands: ','
        };
        $(document).ready(function () {
            if ($('.datatable').length) {
                $('.datatable').DataTable({
                    language: dtLangEsMX
                });
            }
        });
    })();
</script>
<?php if (isset($_SESSION['user_id'])): ?>
<script>
(function () {
    var el = document.getElementById('sidebarOffcanvas');
    if (!el || typeof bootstrap === 'undefined') return;
    function closeMobileMenu() {
        var inst = bootstrap.Offcanvas.getInstance(el);
        if (inst) inst.hide();
    }
    el.querySelectorAll('a.sidebar-nav-link').forEach(function (a) {
        a.addEventListener('click', closeMobileMenu);
    });
    el.querySelectorAll('a.dropdown-item[href]').forEach(function (a) {
        if (a.getAttribute('href') === '#') return;
        a.addEventListener('click', closeMobileMenu);
    });
})();
</script>
<?php endif; ?>
</body>
</html>
