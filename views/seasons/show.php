<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-eye text-primary me-2"></i> Detalle de Temporada</h2>
        <p class="text-muted small">Consulta la informacion general y los partidos programados de la temporada.</p>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>temporadas" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </div>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_updated'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-check-circle me-1"></i> Partido actualizado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_started'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-play me-1"></i> Partido iniciado.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_finished'): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-flag-checkered me-1"></i> Partido finalizado.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_error'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-xmark me-1"></i> No se pudo actualizar el partido.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_needs_referee'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-user-slash me-1"></i> Debe asignar un árbitro al partido antes de iniciarlo.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_needs_roster'): ?>
    <?php $minR = (int)($_GET['min'] ?? ($minJugadores ?? 7)); ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-users me-1"></i> Cada equipo debe tener al menos <strong><?= $minR ?></strong> jugadores registrados como presentes antes de iniciar. Habilite el registro al DT y que cada director marque a sus jugadores.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_needs_referee_validation'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-id-card me-1"></i> El árbitro debe validar <strong>en cancha</strong> a todos los jugadores de la nómina (estado <strong>Coincide / confirmado</strong>) antes de iniciar el partido.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_validacion_column_missing'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-database me-1"></i> Falta la columna de validación del árbitro en la nómina. Ejecute <code>migrations/sqlserver_asistencia_foto_y_arbitro.sql</code>.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_not_in_progress'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-info me-1"></i> Solo puede registrar estadísticas si el partido está <strong>en curso</strong>.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_stats_tables_missing'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-database me-1"></i> Faltan tablas <code>goles</code>, <code>tarjetas</code> o <code>resultados</code> en la base de datos (ver <code>database.sql</code>).
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'asistencia_dt_habilitada'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-user-check me-1"></i> Registro de asistencia habilitado para los directores técnicos de este partido.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'asistencia_dt_error'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-xmark me-1"></i> No se pudo habilitar el registro. Verifique que el partido esté programado y ejecute la migración <code>asistencia_dt_habilitada</code> en <code>partidos</code>.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'knockout_generated'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-trophy me-1"></i> Fase eliminatoria generada correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'knockout_unavailable'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-info me-1"></i> Aun no se puede generar la fase eliminatoria. Verifique que todos los partidos regulares esten finalizados.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (empty($attendanceTableReady)): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-database me-1"></i> Para guardar nóminas, cree la tabla <code>partido_jugadores_presentes</code> (ver <code>database.sql</code>).
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (empty($asistenciaDtColumnReady)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-database me-1"></i> Para el flujo admin → DT, agregue la columna <code>partidos.asistencia_dt_habilitada</code> (ver <code>database.sql</code> o <code>migrations/sqlserver_partidos_asistencia_dt_habilitada.sql</code>).
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="small text-muted">Temporada</div>
                <div class="fw-bold"><?= htmlspecialchars($season['nombre']) ?></div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Ano</div>
                <div class="fw-bold"><?= (int)$season['anio'] ?></div>
            </div>
            <div class="col-md-3">
                <div class="small text-muted">Fechas</div>
                <div class="fw-bold">
                    <?= htmlspecialchars($season['fecha_inicio'] ?? '-') ?> - <?= htmlspecialchars($season['fecha_fin'] ?? '-') ?>
                </div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Dias</div>
                <div class="fw-bold"><?= htmlspecialchars($season['dias_juego'] ?? '-') ?></div>
            </div>
            <div class="col-md-2">
                <div class="small text-muted">Horario</div>
                <?php
                $hi = !empty($season['hora_inicio']) ? substr((string)$season['hora_inicio'], 0, 5) : '-';
                $hf = !empty($season['hora_fin']) ? substr((string)$season['hora_fin'], 0, 5) : '-';
                ?>
                <div class="fw-bold"><?= htmlspecialchars($hi) ?> - <?= htmlspecialchars($hf) ?></div>
            </div>
        </div>
    </div>
</div>

<?php
$knockoutEnabled = !empty($knockoutStageAvailability['enabled']);
$knockoutLabel = (string)($knockoutStageAvailability['label'] ?? '');
if (!function_exists('fase_label_text')) {
    function fase_label_text($fase) {
        $v = strtolower(trim((string)$fase));
        $map = [
            'regular' => 'Regular',
            'octavos' => 'Octavos',
            'cuartos' => 'Cuartos',
            'semifinal' => 'Semifinal',
            'final' => 'Final',
            'eliminatoria' => 'Eliminatoria',
        ];
        return $map[$v] ?? ucfirst($v ?: 'Regular');
    }
}
if (!function_exists('fase_badge_class')) {
    function fase_badge_class($fase) {
        $v = strtolower(trim((string)$fase));
        $map = [
            'regular' => 'bg-light text-dark border',
            'octavos' => 'bg-primary-subtle text-primary border border-primary-subtle',
            'cuartos' => 'bg-info-subtle text-info-emphasis border border-info-subtle',
            'semifinal' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'final' => 'bg-dark text-warning',
            'eliminatoria' => 'bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle',
        ];
        return $map[$v] ?? 'bg-light text-dark border';
    }
}
?>
<?php if (!empty($seasonAwards)): ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-medal text-warning me-2"></i>Reconocimientos de temporada</h5>
            <div class="row g-3">
                <div class="col-md-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Campeón</div>
                        <?php $champLogo = trim((string)($seasonAwards['champion']['logo'] ?? '')); ?>
                        <div class="d-flex align-items-center gap-2">
                            <img
                                src="<?= BASE_URL ?>assets/img/<?= htmlspecialchars($champLogo !== '' ? $champLogo : 'default_logo.png') ?>"
                                alt="Escudo campeon"
                                style="width:36px;height:36px;object-fit:cover;border-radius:50%;border:1px solid #dee2e6;background:#fff;"
                                onerror="this.onerror=null;this.src='<?= BASE_URL ?>assets/img/default_logo.png';"
                            >
                            <div class="fw-bold">
                                <?= htmlspecialchars($seasonAwards['champion']['name'] ?? 'Sin definir') ?>
                                <span class="badge bg-warning text-dark ms-1">CAMPEÓN</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Maximos goleadores (Top 3)</div>
                        <?php if (!empty($seasonAwards['top_scorers'])): ?>
                            <?php foreach ($seasonAwards['top_scorers'] as $g): ?>
                                <div class="small">
                                    <strong><?= htmlspecialchars($g['jugador_nombre'] ?? '') ?></strong>
                                    (<?= htmlspecialchars($g['equipo_nombre'] ?? '') ?>) - <?= (int)($g['goles'] ?? 0) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="small text-muted">Sin goles registrados.</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Equipo mas limpio</div>
                        <div class="fw-bold"><?= htmlspecialchars($seasonAwards['clean_team']['equipo_nombre'] ?? 'Sin definir') ?></div>
                        <div class="small text-muted">
                            Tarjetas: <?= (int)($seasonAwards['clean_team']['total_tarjetas'] ?? 0) ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="border rounded p-3 h-100">
                        <div class="small text-muted mb-1">Mejor portero (equipo)</div>
                        <div class="fw-bold"><?= htmlspecialchars($seasonAwards['best_goalkeeper_team']['equipo_nombre'] ?? 'Sin definir') ?></div>
                        <div class="small text-muted">
                            Goles recibidos: <?= (int)($seasonAwards['best_goalkeeper_team']['goles_recibidos'] ?? 0) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if ($knockoutEnabled): ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <div class="fw-bold mb-1"><i class="fa-solid fa-trophy text-warning me-1"></i> Fase eliminatoria disponible</div>
                <div class="small text-muted">Todos los partidos regulares estan finalizados. Ya puedes pasar a <strong><?= htmlspecialchars($knockoutLabel) ?></strong>.</div>
            </div>
            <form action="<?= BASE_URL ?>temporadas/generar-eliminatoria" method="POST" class="m-0">
                <input type="hidden" name="season_id" value="<?= (int)$season['id'] ?>">
                <button type="submit" class="btn btn-warning">
                    <i class="fa-solid fa-forward-step me-1"></i> Pasar a <?= htmlspecialchars($knockoutLabel) ?>
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="fw-bold mb-3"><i class="fa-solid fa-futbol me-2 text-success"></i>Partidos de la Temporada</h5>
        <?php if (empty($matches)): ?>
            <div class="alert alert-light border mb-0">
                <i class="fa-solid fa-circle-info me-1"></i> Esta temporada aun no tiene partidos programados.
            </div>
        <?php else: ?>
            <style>
                .team-chip {
                    display: inline-flex;
                    align-items: center;
                    gap: 8px;
                }
                .team-chip img {
                    width: 30px;
                    height: 30px;
                    object-fit: cover;
                    border-radius: 50%;
                    border: 1px solid #dee2e6;
                    background: #fff;
                }
                .team-chip .team-name {
                    display: block;
                    font-size: 0.78rem;
                    line-height: 1.1;
                    color: #6c757d;
                }
                .btn-compact {
                    padding: 0.2rem 0.45rem;
                    font-size: 0.74rem;
                    line-height: 1.2;
                }
            </style>
            <div class="table-responsive">
                <table class="table table-hover datatable align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha programada</th>
                            <th>Local</th>
                            <th>Resultado</th>
                            <th>Visitante</th>
                            <th>Fase</th>
                            <th>Estado</th>
                            <th class="text-center">Acciones</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): ?>
                            <tr>
                                <td>
                                    <?= date('d/m/Y', strtotime($match['fecha_hora'])) ?><br>
                                    <span class="small text-muted"><?= date('H:i', strtotime($match['fecha_hora'])) ?></span>
                                </td>
                                <td>
                                    <span class="team-chip">
                                        <?php $logoLocal = !empty($match['equipo_local_logo']) ? $match['equipo_local_logo'] : 'default_logo.png'; ?>
                                        <img src="<?= BASE_URL ?>assets/img/<?= htmlspecialchars($logoLocal) ?>" alt="Escudo local" onerror="this.onerror=null;this.src='<?= BASE_URL ?>assets/img/default_logo.png';">
                                        <span class="team-name"><?= htmlspecialchars($match['equipo_local_nombre']) ?></span>
                                    </span>
                                </td>
                                <td class="fw-bold">
                                    <span class="badge bg-dark"><?= (int)($match['goles_local'] ?? 0) ?> - <?= (int)($match['goles_visitante'] ?? 0) ?></span>
                                </td>
                                <td>
                                    <span class="team-chip">
                                        <?php $logoVisit = !empty($match['equipo_visitante_logo']) ? $match['equipo_visitante_logo'] : 'default_logo.png'; ?>
                                        <img src="<?= BASE_URL ?>assets/img/<?= htmlspecialchars($logoVisit) ?>" alt="Escudo visitante" onerror="this.onerror=null;this.src='<?= BASE_URL ?>assets/img/default_logo.png';">
                                        <span class="team-name"><?= htmlspecialchars($match['equipo_visitante_nombre']) ?></span>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= fase_badge_class($match['fase'] ?? 'regular') ?>"><?= htmlspecialchars(fase_label_text($match['fase'] ?? 'regular')) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $estado = (string)($match['estado'] ?? '');
                                    $estadoClass = 'bg-secondary';
                                    if ($estado === 'en curso') {
                                        $estadoClass = 'bg-warning text-dark';
                                    } elseif ($estado === 'finalizado') {
                                        $estadoClass = 'bg-success';
                                    }
                                    ?>
                                    <span class="badge <?= $estadoClass ?>"><?= htmlspecialchars(ucfirst($estado)) ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                                        <a href="<?= BASE_URL ?>temporadas/match/edit?season_id=<?= (int)$season['id'] ?>&match_id=<?= (int)$match['id'] ?>" class="btn btn-compact btn-outline-primary">
                                            <i class="fa-solid fa-pen-to-square"></i> Editar
                                        </a>
                                        <?php if (($match['estado'] ?? '') === 'programado'): ?>
                                            <?php
                                            $adtOk = (int)($match['asistencia_dt_habilitada'] ?? 0) === 1;
                                            ?>
                                            <?php if (!empty($asistenciaDtColumnReady)): ?>
                                                <?php if (!$adtOk): ?>
                                                    <form action="<?= BASE_URL ?>temporadas/match/asistencia-dt-habilitar" method="POST" class="d-inline">
                                                        <input type="hidden" name="season_id" value="<?= (int)$season['id'] ?>">
                                                        <input type="hidden" name="match_id" value="<?= (int)$match['id'] ?>">
                                                        <button type="submit" class="btn btn-compact btn-success">
                                                            <i class="fa-solid fa-unlock"></i> Habilitar asistencia
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <a href="<?= BASE_URL ?>partido/asistencia?season_id=<?= (int)$season['id'] ?>&match_id=<?= (int)$match['id'] ?>" class="btn btn-compact btn-outline-secondary">
                                                        <i class="fa-solid fa-eye"></i> Ver asistencia
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-compact btn-outline-secondary" disabled title="Agregue la columna asistencia_dt_habilitada">
                                                    <i class="fa-solid fa-unlock"></i> Habilitar asistencia
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if (($match['estado'] ?? '') === 'programado' && !empty($match['can_start_match'])): ?>
                                            <form action="<?= BASE_URL ?>temporadas/match/start" method="POST" class="d-inline">
                                                <input type="hidden" name="season_id" value="<?= (int)$season['id'] ?>">
                                                <input type="hidden" name="match_id" value="<?= (int)$match['id'] ?>">
                                                <button type="submit" class="btn btn-compact btn-outline-success">
                                                    <i class="fa-solid fa-play"></i> Iniciar partido
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (($match['estado'] ?? '') === 'en curso'): ?>
                                            <a href="<?= BASE_URL ?>temporadas/match/finalizar?season_id=<?= (int)$season['id'] ?>&match_id=<?= (int)$match['id'] ?>" class="btn btn-compact btn-outline-danger">
                                                <i class="fa-solid fa-flag-checkered"></i> Terminar partido
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>partidos-resultado-detalle?id=<?= (int)$match['id'] ?>" class="btn btn-compact btn-outline-primary">Detalle</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
