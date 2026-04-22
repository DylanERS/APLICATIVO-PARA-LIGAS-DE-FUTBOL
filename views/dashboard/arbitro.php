<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-clipboard-list text-warning me-2"></i> Panel árbitro</h2>
        <p class="text-muted small mb-0"><?= htmlspecialchars($referee['nombre'] ?? '') ?></p>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>partido/arbitro/mis-partidos" class="btn btn-warning text-dark btn-sm fw-semibold">
            <i class="fa-solid fa-calendar-days me-1"></i> Ver todos mis partidos
        </a>
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

<div class="card shadow-sm border-0">
    <div class="card-header bg-white fw-bold">Próximos partidos asignados</div>
    <div class="card-body p-0">
        <?php if (empty($proximos)): ?>
            <p class="text-muted small p-3 mb-0">No tiene partidos programados o en curso como árbitro designado.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Partido</th>
                            <th>Fase</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proximos as $m): ?>
                            <tr>
                                <td class="text-nowrap small"><?= date('d/m/Y H:i', strtotime($m['fecha_hora'])) ?></td>
                                <td class="small">
                                    <span class="fw-semibold"><?= htmlspecialchars($m['equipo_local_nombre'] ?? '') ?></span>
                                    vs
                                    <span class="fw-semibold"><?= htmlspecialchars($m['equipo_visitante_nombre'] ?? '') ?></span>
                                    <div class="text-muted"><?= htmlspecialchars($m['temporada_nombre'] ?? '') ?></div>
                                </td>
                                <td><span class="badge <?= fase_badge_class($m['fase'] ?? 'regular') ?>"><?= htmlspecialchars(fase_label_text($m['fase'] ?? 'regular')) ?></span></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($m['estado'] ?? '') ?></span></td>
                                <td class="text-end">
                                    <a class="btn btn-outline-warning btn-sm" href="<?= BASE_URL ?>partido/arbitro/validar-asistencia?season_id=<?= (int)$m['temporada_id'] ?>&match_id=<?= (int)$m['id'] ?>">
                                        Validar nómina
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
