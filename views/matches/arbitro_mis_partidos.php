<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-clipboard-list text-warning me-2"></i> Mis partidos (árbitro)</h2>
        <?php if (!empty($referee['nombre'])): ?>
            <p class="text-muted small mb-0"><?= htmlspecialchars($referee['nombre']) ?></p>
        <?php endif; ?>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>home" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-house me-1"></i> Inicio</a>
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
    <div class="card-body p-0">
        <?php if (empty($matches)): ?>
            <p class="text-muted small p-3 mb-0">No hay partidos asignados en estado programado o en curso.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Encuentro</th>
                            <th>Fase</th>
                            <th>Temporada</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $m): ?>
                            <tr>
                                <td class="text-nowrap"><?= date('d/m/Y H:i', strtotime($m['fecha_hora'])) ?></td>
                                <td>
                                    <span class="fw-semibold"><?= htmlspecialchars($m['equipo_local_nombre'] ?? '') ?></span>
                                    <span class="text-muted">vs</span>
                                    <span class="fw-semibold"><?= htmlspecialchars($m['equipo_visitante_nombre'] ?? '') ?></span>
                                </td>
                                <td><span class="badge <?= fase_badge_class($m['fase'] ?? 'regular') ?>"><?= htmlspecialchars(fase_label_text($m['fase'] ?? 'regular')) ?></span></td>
                                <td class="small text-muted"><?= htmlspecialchars($m['temporada_nombre'] ?? '') ?></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($m['estado'] ?? '') ?></span></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-dark me-1" href="<?= BASE_URL ?>partido/arbitro/escanear-qr?season_id=<?= (int)$m['temporada_id'] ?>&match_id=<?= (int)$m['id'] ?>">
                                        Escanear QR
                                    </a>
                                    <a class="btn btn-sm btn-warning text-dark" href="<?= BASE_URL ?>partido/arbitro/validar-asistencia?season_id=<?= (int)$m['temporada_id'] ?>&match_id=<?= (int)$m['id'] ?>">
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
