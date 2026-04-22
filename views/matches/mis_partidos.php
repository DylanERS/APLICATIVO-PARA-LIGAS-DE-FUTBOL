<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-calendar-check text-primary me-2"></i> Mis partidos</h2>
        <p class="text-muted small mb-0">
            Partidos programados de
            <strong><?= htmlspecialchars($equipo['nombre'] ?? 'tu equipo') ?></strong>.
            Cuando el administrador habilite el registro, podrás marcar qué jugadores están presentes en tu partido.
        </p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (isset($asistenciaDtColumnReady) && !$asistenciaDtColumnReady): ?>
    <div class="alert alert-info small">
        <i class="fa-solid fa-info-circle me-1"></i> Sin la columna <code>asistencia_dt_habilitada</code> en <code>partidos</code> el administrador no puede habilitar el registro; puede marcar asistencia sin ese paso. Ejecute la migración en <code>migrations/sqlserver_partidos_asistencia_dt_habilitada.sql</code> para el flujo completo.
    </div>
<?php endif; ?>

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

<?php if (empty($matches)): ?>
    <div class="card shadow-sm border-0">
        <div class="card-body text-muted">
            <i class="fa-solid fa-circle-info me-1"></i> No hay partidos programados próximos para tu equipo.
        </div>
    </div>
<?php else: ?>
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Rival / Condición</th>
                            <th>Fase</th>
                            <th>Temporada</th>
                            <th class="text-center">Presentes</th>
                            <th class="text-end">Asistencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $eqId = (int)($equipo['id'] ?? 0);
                        foreach ($matches as $m):
                            $localId = (int)$m['equipo_local_id'];
                            $esLocal = ($eqId === $localId);
                            $rival = $esLocal ? ($m['equipo_visitante_nombre'] ?? '') : ($m['equipo_local_nombre'] ?? '');
                            $cond = $esLocal ? 'Local' : 'Visitante';
                            $cL = (int)($m['count_local'] ?? 0);
                            $cV = (int)($m['count_visit'] ?? 0);
                            $mine = $esLocal ? $cL : $cV;
                            $ok = $mine >= (int)$minJugadores;
                            $colDt = !empty($asistenciaDtColumnReady);
                            $habDt = $colDt ? ((int)($m['asistencia_dt_habilitada'] ?? 0) === 1) : true;
                        ?>
                            <tr>
                                <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($m['fecha_hora'])) ?></td>
                                <td>
                                    <span class="fw-semibold"><?= htmlspecialchars($rival) ?></span>
                                    <span class="badge bg-light text-dark border ms-1"><?= $cond ?></span>
                                </td>
                                <td><span class="badge <?= fase_badge_class($m['fase'] ?? 'regular') ?>"><?= htmlspecialchars(fase_label_text($m['fase'] ?? 'regular')) ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars($m['temporada_nombre'] ?? '') ?></td>
                                <td class="text-center">
                                    <span class="<?= $ok ? 'text-success' : 'text-warning' ?> fw-bold"><?= $mine ?></span>
                                    <span class="text-muted small">/ <?= (int)$minJugadores ?> min.</span>
                                </td>
                                <td class="text-end">
                                    <?php if ($habDt): ?>
                                        <a class="btn btn-sm btn-success" href="<?= BASE_URL ?>partido/asistencia?season_id=<?= (int)$m['temporada_id'] ?>&match_id=<?= (int)$m['id'] ?>">
                                            <i class="fa-solid fa-user-check me-1"></i> Marcar asistencia
                                        </a>
                                        <?php if ($ok): ?>
                                            <a class="btn btn-sm btn-outline-primary ms-1" href="<?= BASE_URL ?>partido/asistencia/catalogo?season_id=<?= (int)$m['temporada_id'] ?>&match_id=<?= (int)$m['id'] ?>&equipo_id=<?= $eqId ?>&modo=grid">
                                                <i class="fa-solid fa-id-card me-1"></i> Mostrar asistencia
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small">Pendiente: el administrador debe habilitar el registro</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>
