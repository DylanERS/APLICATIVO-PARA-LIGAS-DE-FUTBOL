<div class="row mb-3">
    <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="fw-bold mb-0">Resultados de partidos</h2>
            <p class="text-muted mb-0 small">Partidos de todas las temporadas (programados, en curso y finalizados).</p>
        </div>
        <a href="<?= BASE_URL ?>home" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver al panel
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

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
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
            <table class="table table-hover text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fecha programada</th>
                        <th>Local</th>
                        <th>Resultado</th>
                        <th>Visitante</th>
                        <th>Fase</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                        <th>Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($partidos)): ?>
                        <tr>
                            <td colspan="8" class="text-muted py-4">No hay partidos registrados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($partidos as $p): ?>
                            <?php
                            $tempLabel = trim((string)($p['temporada_nombre'] ?? ''));
                            if ($tempLabel !== '' && isset($p['temporada_anio'])) {
                                $tempLabel .= ' (' . (int)$p['temporada_anio'] . ')';
                            }
                            $estado = (string)($p['estado'] ?? '');
                            $estadoClass = 'bg-secondary';
                            if ($estado === 'en curso') {
                                $estadoClass = 'bg-warning text-dark';
                            } elseif ($estado === 'finalizado') {
                                $estadoClass = 'bg-success';
                            }
                            ?>
                            <tr>
                                <td>
                                    <?= date('d/m/Y', strtotime($p['fecha_hora'])) ?><br>
                                    <span class="small text-muted"><?= date('H:i', strtotime($p['fecha_hora'])) ?></span>
                                </td>
                                <td>
                                    <span class="team-chip">
                                        <?php $logoLocal = !empty($p['equipo_local_logo']) ? $p['equipo_local_logo'] : 'default_logo.png'; ?>
                                        <img src="<?= BASE_URL ?>assets/img/<?= htmlspecialchars($logoLocal) ?>" alt="Escudo local" onerror="this.onerror=null;this.src='<?= BASE_URL ?>assets/img/default_logo.png';">
                                        <span class="team-name"><?= htmlspecialchars($p['equipo_local_nombre'] ?? $p['local_nombre']) ?></span>
                                    </span>
                                </td>
                                <td class="fw-bold">
                                    <span class="badge bg-dark"><?= (int)$p['goles_local'] ?> - <?= (int)$p['goles_visitante'] ?></span>
                                </td>
                                <td>
                                    <span class="team-chip">
                                        <?php $logoVisit = !empty($p['equipo_visitante_logo']) ? $p['equipo_visitante_logo'] : 'default_logo.png'; ?>
                                        <img src="<?= BASE_URL ?>assets/img/<?= htmlspecialchars($logoVisit) ?>" alt="Escudo visitante" onerror="this.onerror=null;this.src='<?= BASE_URL ?>assets/img/default_logo.png';">
                                        <span class="team-name"><?= htmlspecialchars($p['equipo_visitante_nombre'] ?? $p['visitante_nombre']) ?></span>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= fase_badge_class($p['fase'] ?? 'regular') ?>"><?= htmlspecialchars(fase_label_text($p['fase'] ?? 'regular')) ?></span>
                                </td>
                                <td>
                                    <span class="badge <?= $estadoClass ?>"><?= htmlspecialchars(ucfirst($estado !== '' ? $estado : 'sin estado')) ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1 flex-wrap">
                                        <?php if (($p['estado'] ?? '') === 'programado'): ?>
                                            <a href="<?= BASE_URL ?>temporadas/match/edit?season_id=<?= (int)$p['temporada_id'] ?>&match_id=<?= (int)$p['id'] ?>" class="btn btn-compact btn-outline-primary">
                                                <i class="fa-solid fa-pen-to-square"></i> Editar
                                            </a>
                                            <?php $adtOk = (int)($p['asistencia_dt_habilitada'] ?? 0) === 1; ?>
                                            <?php if (!empty($asistenciaDtColumnReady)): ?>
                                                <?php if (!$adtOk): ?>
                                                    <form action="<?= BASE_URL ?>temporadas/match/asistencia-dt-habilitar" method="POST" class="d-inline">
                                                        <input type="hidden" name="season_id" value="<?= (int)$p['temporada_id'] ?>">
                                                        <input type="hidden" name="match_id" value="<?= (int)$p['id'] ?>">
                                                        <button type="submit" class="btn btn-compact btn-success">
                                                            <i class="fa-solid fa-unlock"></i> Habilitar asistencia
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <a href="<?= BASE_URL ?>partido/asistencia?season_id=<?= (int)$p['temporada_id'] ?>&match_id=<?= (int)$p['id'] ?>" class="btn btn-compact btn-outline-secondary">
                                                        <i class="fa-solid fa-eye"></i> Ver asistencia
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if (($p['estado'] ?? '') === 'programado' && !empty($p['can_start_match'])): ?>
                                            <form action="<?= BASE_URL ?>temporadas/match/start" method="POST" class="d-inline">
                                                <input type="hidden" name="season_id" value="<?= (int)$p['temporada_id'] ?>">
                                                <input type="hidden" name="match_id" value="<?= (int)$p['id'] ?>">
                                                <button type="submit" class="btn btn-compact btn-outline-success">
                                                    <i class="fa-solid fa-play"></i> Iniciar partido
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <?php if (($p['estado'] ?? '') === 'en curso'): ?>
                                            <a href="<?= BASE_URL ?>temporadas/match/finalizar?season_id=<?= (int)$p['temporada_id'] ?>&match_id=<?= (int)$p['id'] ?>" class="btn btn-compact btn-outline-danger">
                                                <i class="fa-solid fa-flag-checkered"></i> Terminar partido
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <a class="btn btn-compact btn-outline-primary" href="<?= BASE_URL ?>partidos-resultado-detalle?id=<?= (int)$p['id'] ?>">
                                        Detalle
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
