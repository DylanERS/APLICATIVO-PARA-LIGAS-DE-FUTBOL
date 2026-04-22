<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold"><i class="fa-solid fa-house-chimney-user text-success me-2"></i> Panel de mi club</h2>
        <p class="text-muted mb-0">
            <?= htmlspecialchars($equipo['nombre'] ?? 'Tu equipo') ?>
            <?php if (!empty($temporada_activa)): ?>
                · Temporada activa: <strong><?= htmlspecialchars($temporada_activa['nombre']) ?></strong> (<?= (int)$temporada_activa['anio'] ?>)
            <?php endif; ?>
        </p>
    </div>
</div>

<?php
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

<?php if (!empty($error)): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (empty($temporada_activa)): ?>
    <div class="alert alert-info border-0 shadow-sm">
        <i class="fa-solid fa-calendar-xmark me-1"></i> No hay temporada activa en la liga. Cuando el administrador active una, verás aquí la tabla y las estadísticas de tu equipo.
    </div>
<?php elseif (empty($in_season)): ?>
    <div class="alert alert-info border-0 shadow-sm">
        <i class="fa-solid fa-circle-info me-1"></i> Tu equipo no está inscrito en la temporada activa. Contacta al administrador de la liga.
    </div>
<?php endif; ?>

<?php if (!empty($standing_summary)): ?>
    <?php $mi = $standing_summary['mi_fila']; ?>
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card border-0 shadow-sm h-100 bg-primary text-white">
                <div class="card-body">
                    <div class="text-uppercase small opacity-75">Posición en el torneo</div>
                    <div class="display-4 fw-bold"><?= (int)$standing_summary['posicion'] ?></div>
                    <div class="small">de <?= (int)$standing_summary['total_equipos'] ?> equipos</div>
                </div>
            </div>
        </div>
        <div class="col-md-8 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold mb-2">Resumen de tu equipo</div>
                    <div class="row g-2 text-center">
                        <div class="col-4 col-md-2">
                            <div class="small text-muted">PTS</div>
                            <div class="fs-4 fw-bold text-primary"><?= (int)$mi['pts'] ?></div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="small text-muted">PJ</div>
                            <div class="fs-5 fw-bold"><?= (int)$mi['pj'] ?></div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="small text-muted">GF</div>
                            <div class="fs-5 fw-bold text-success"><?= (int)$mi['gf'] ?></div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="small text-muted">GC</div>
                            <div class="fs-5 fw-bold text-danger"><?= (int)$mi['gc'] ?></div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="small text-muted">DG</div>
                            <div class="fs-5 fw-bold"><?= (int)$mi['gf'] - (int)$mi['gc'] ?></div>
                        </div>
                        <div class="col-4 col-md-2">
                            <div class="small text-muted">G-P-E</div>
                            <div class="small fw-bold"><?= (int)$mi['pg'] ?>-<?= (int)$mi['pe'] ?>-<?= (int)$mi['pp'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white fw-bold py-3">
            <i class="fa-solid fa-ranking-star me-1 text-warning"></i> Tabla general (temporada activa)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Equipo</th>
                            <th class="text-center">PJ</th>
                            <th class="text-center">GF</th>
                            <th class="text-center">GC</th>
                            <th class="text-center">DG</th>
                            <th class="text-center">Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($standing_summary['tabla'] as $idx => $fila): ?>
                            <tr class="<?= (int)$fila['equipo_id'] === (int)($equipo['id'] ?? 0) ? 'table-warning' : '' ?>">
                                <td class="text-muted"><?= $idx + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($fila['nombre']) ?></td>
                                <td class="text-center"><?= (int)$fila['pj'] ?></td>
                                <td class="text-center"><?= (int)$fila['gf'] ?></td>
                                <td class="text-center"><?= (int)$fila['gc'] ?></td>
                                <td class="text-center"><?= (int)$fila['gf'] - (int)$fila['gc'] ?></td>
                                <td class="text-center fw-bold"><?= (int)$fila['pts'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold">
                <i class="fa-solid fa-futbol me-1 text-success"></i> Goleadores del equipo (temporada activa)
            </div>
            <div class="card-body p-0">
                <?php if (empty($goleadores)): ?>
                    <div class="p-3 text-muted small">Aún no hay goles registrados para tu equipo en esta temporada.</div>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Jugador</th>
                                <th class="text-center">#</th>
                                <th class="text-end">Goles</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($goleadores as $g): ?>
                                <tr>
                                    <td><?= htmlspecialchars($g['nombre']) ?></td>
                                    <td class="text-center text-muted"><?= (int)($g['numero'] ?? 0) ?></td>
                                    <td class="text-end fw-bold"><?= (int)$g['goles'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white fw-bold">
                <i class="fa-solid fa-square text-warning me-1"></i><i class="fa-solid fa-square text-danger me-1"></i> Disciplina (amarillas / rojas)
            </div>
            <div class="card-body p-0">
                <?php if (empty($tarjetas_top)): ?>
                    <div class="p-3 text-muted small">Sin tarjetas registradas para tu equipo en esta temporada.</div>
                <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Jugador</th>
                                <th class="text-center">#</th>
                                <th class="text-center"><span class="text-warning">Am.</span></th>
                                <th class="text-center"><span class="text-danger">Roj.</span></th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tarjetas_top as $t): ?>
                                <tr>
                                    <td><?= htmlspecialchars($t['nombre']) ?></td>
                                    <td class="text-center text-muted"><?= (int)($t['numero'] ?? 0) ?></td>
                                    <td class="text-center"><?= (int)$t['amarillas'] ?></td>
                                    <td class="text-center"><?= (int)$t['rojas'] ?></td>
                                    <td class="text-end fw-bold"><?= (int)$t['total_tarjetas'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="fa-solid fa-calendar-day me-1 text-info"></i> Próximos partidos de tu equipo</span>
        <a href="<?= BASE_URL ?>partido/mis-partidos" class="btn btn-sm btn-outline-primary">Ir a asistencia</a>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Fecha</th>
                    <th>Rival</th>
                    <th>Condición</th>
                    <th>Fase</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($proximos_propios)): ?>
                    <tr><td colspan="4" class="text-muted text-center py-3">No hay partidos programados próximos.</td></tr>
                <?php else: ?>
                    <?php
                    $eqId = (int)($equipo['id'] ?? 0);
                    foreach ($proximos_propios as $pp):
                        $lid = (int)$pp['equipo_local_id'];
                        $esLocal = ($eqId === $lid);
                        $rival = $esLocal ? ($pp['equipo_visitante_nombre'] ?? '') : ($pp['equipo_local_nombre'] ?? '');
                    ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($pp['fecha_hora'])) ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($rival) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= $esLocal ? 'Local' : 'Visitante' ?></span></td>
                            <td><span class="badge <?= fase_badge_class($pp['fase'] ?? 'regular') ?>"><?= htmlspecialchars(fase_label_text($pp['fase'] ?? 'regular')) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (!empty($show_champion_modal) && !empty($season_awards['champion'])): ?>
    <?php $champLogo = trim((string)($season_awards['champion']['logo'] ?? '')); ?>
    <div class="modal fade" id="championCongratsModal" tabindex="-1" aria-labelledby="championCongratsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning-subtle">
                    <h5 class="modal-title fw-bold" id="championCongratsModalLabel">
                        <i class="fa-solid fa-trophy text-warning me-1"></i> ¡Felicitaciones, campeón!
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body text-center">
                    <img
                        src="<?= BASE_URL ?>assets/img/<?= htmlspecialchars($champLogo !== '' ? $champLogo : 'default_logo.png') ?>"
                        alt="Escudo campeón"
                        style="width:92px;height:92px;object-fit:cover;border-radius:50%;border:2px solid #ffc107;background:#fff;"
                        onerror="this.onerror=null;this.src='<?= BASE_URL ?>assets/img/default_logo.png';"
                    >
                    <div class="fw-bold fs-4 mt-3 mb-1"><?= htmlspecialchars($equipo['nombre'] ?? '') ?></div>
                    <div class="text-muted">
                        ¡Tu equipo ganó la temporada
                        <strong><?= htmlspecialchars($temporada_activa['nombre'] ?? '') ?></strong>!
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Continuar</button>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof bootstrap === 'undefined') return;
        var el = document.getElementById('championCongratsModal');
        if (!el) return;
        var modal = new bootstrap.Modal(el);
        modal.show();
    });
    </script>
<?php endif; ?>
