<?php
/**
 * Contenido del menú lateral (escritorio + offcanvas móvil).
 * Requiere: $sidebarInstance string único p.ej. 'desktop' | 'mobile'
 */
$sfx = isset($sidebarInstance) ? preg_replace('/[^a-z0-9_-]/i', '', (string)$sidebarInstance) : 'main';
$sidebarSeasons = $sidebar_active_seasons ?? [];
?>
<div class="sidebar-inner align-items-center align-items-sm-start px-3 pt-3 pb-0 text-white h-100 d-flex flex-column">
    <a href="<?= BASE_URL ?>" class="sidebar-brand sidebar-nav-link flex-shrink-0 d-flex align-items-center pb-3 mb-0 me-md-auto text-white text-decoration-none border-bottom w-100">
        <span class="fs-5 fw-bold text-truncate d-inline-block" style="max-width:100%"><i class="fa-solid fa-futbol me-2 text-success"></i><?= htmlspecialchars($app_league_display_name ?? 'LIGA') ?></span>
    </a>
    <div class="sidebar-menu-scroll w-100 flex-grow-1">
        <ul class="nav nav-pills flex-column mb-0 align-items-center align-items-sm-start mt-2 w-100" id="menu-<?= htmlspecialchars($sfx) ?>">
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>home" class="nav-link align-middle px-3 sidebar-nav-link">
                    <i class="fs-5 fa-solid fa-chart-line"></i> <span class="ms-1 d-inline"><?php
                        $r = $_SESSION['role'] ?? '';
                        if ($r === 'director_tecnico') echo 'Mi club';
                        elseif ($r === 'arbitro') echo 'Inicio';
                        else echo 'Dashboard';
                    ?></span>
                </a>
            </li>
            <?php if (!in_array($_SESSION['role'] ?? '', ['director_tecnico', 'arbitro'], true)): ?>
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>equipos" class="nav-link align-middle px-3 sidebar-nav-link">
                    <i class="fs-5 fa-solid fa-shield-halved"></i> <span class="ms-1 d-inline">Equipos</span>
                </a>
            </li>
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>jugadores" class="nav-link align-middle px-3 sidebar-nav-link">
                    <i class="fs-5 fa-solid fa-users"></i> <span class="ms-1 d-inline">Jugadores</span>
                </a>
            </li>
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>partidos-resultados" class="nav-link align-middle px-3 sidebar-nav-link">
                    <i class="fs-5 fa-solid fa-futbol"></i> <span class="ms-1 d-inline">Partidos</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(($_SESSION['role'] ?? '') === 'director_tecnico'): ?>
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>partido/mis-partidos" class="nav-link align-middle px-3 text-info sidebar-nav-link">
                    <i class="fs-5 fa-solid fa-clipboard-check"></i> <span class="ms-1 d-inline">Mis partidos</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(($_SESSION['role'] ?? '') === 'arbitro'): ?>
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>partido/arbitro/mis-partidos" class="nav-link align-middle px-3 text-warning sidebar-nav-link">
                    <i class="fs-5 fa-solid fa-clipboard-list"></i> <span class="ms-1 d-inline">Mis partidos</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if(($_SESSION['role'] ?? '') === 'admin'): ?>
            <li class="nav-item w-100 mt-2">
                <div class="text-uppercase text-muted small fw-bold px-3 mb-1">Dueño de la Liga</div>
            </li>
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>finanzas" class="nav-link align-middle px-3 text-warning sidebar-nav-link">
                    <i class="fs-5 fa-solid fa-money-bill-trend-up"></i> <span class="ms-1 d-inline">Finanzas</span>
                </a>
            </li>
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>usuarios" class="nav-link align-middle px-3 text-info sidebar-nav-link">
                    <i class="fs-5 fa-solid fa-users-gear"></i> <span class="ms-1 d-inline">Usuarios</span>
                </a>
            </li>
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>configuracion" class="nav-link align-middle px-3 text-info sidebar-nav-link">
                    <i class="fs-5 fa-solid fa-cogs"></i> <span class="ms-1 d-inline">Config. Liga</span>
                </a>
            </li>
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>canchas" class="nav-link align-middle px-3 text-info sidebar-nav-link">
                    <i class="fs-5 fa-solid fa-map-location-dot"></i> <span class="ms-1 d-inline">Canchas</span>
                </a>
            </li>
            <li class="nav-item w-100 mt-2">
                <div class="text-uppercase text-muted small fw-bold px-3 mb-1">Torneos activos</div>
            </li>
            <?php
            if (!empty($sidebarSeasons)):
                foreach ($sidebarSeasons as $sAct):
            ?>
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>temporadas/show?id=<?= (int)$sAct['id'] ?>" class="nav-link align-middle px-3 py-2 text-white border-start border-success border-3 ms-2 rounded-end sidebar-nav-link" title="Ir al detalle del torneo activo">
                    <i class="fa-solid fa-trophy text-warning"></i>
                    <span class="ms-1 d-inline text-break small"><?= htmlspecialchars($sAct['nombre']) ?> <span class="text-white-50">(<?= (int)$sAct['anio'] ?>)</span></span>
                </a>
            </li>
            <?php
                endforeach;
            else:
            ?>
            <li class="nav-item w-100 px-3 mb-1">
                <span class="small text-secondary">Ninguna temporada activa</span>
            </li>
            <?php endif; ?>
            <li class="nav-item w-100">
                <a href="<?= BASE_URL ?>temporadas" class="nav-link align-middle px-3 text-success sidebar-nav-link">
                    <i class="fs-5 fa-solid fa-calendar-days"></i> <span class="ms-1 d-inline">Temporadas</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="sidebar-footer w-100 border-top border-secondary pt-3 mt-2 flex-shrink-0">
        <div class="dropdown pb-3 w-100">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle px-3" id="dropdownUser-<?= htmlspecialchars($sfx) ?>" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fa-solid fa-circle-user fs-4 me-2"></i>
                <span class="d-inline mx-1"><?= htmlspecialchars($_SESSION['username'] ?? 'User') ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser-<?= htmlspecialchars($sfx) ?>">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>logout">Cerrar Sesión</a></li>
            </ul>
        </div>
    </div>
</div>
