<?php
$tempLabel = trim((string)($partido['temporada_nombre'] ?? ''));
if ($tempLabel !== '' && isset($partido['temporada_anio'])) {
    $tempLabel .= ' (' . (int)$partido['temporada_anio'] . ')';
}
?>
<style>
    .match-timeline {
        position: relative;
        padding-left: 0;
        list-style: none;
        margin: 0;
    }
    .match-timeline::before {
        content: '';
        position: absolute;
        left: 18px;
        top: 0;
        bottom: 0;
        width: 3px;
        background: linear-gradient(to bottom, #dee2e6, #adb5bd);
        border-radius: 2px;
    }
    .match-timeline-item {
        position: relative;
        padding-left: 52px;
        padding-bottom: 1.25rem;
    }
    .match-timeline-item:last-child {
        padding-bottom: 0;
    }
    .match-timeline-badge {
        position: absolute;
        left: 0;
        top: 0;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        color: #fff;
        z-index: 1;
        border: 3px solid #f8f9fa;
    }
    .match-timeline-badge.gol { background: #198754; }
    .match-timeline-badge.amarilla { background: #ffc107; color: #212529; }
    .match-timeline-badge.roja { background: #dc3545; }
</style>

<div class="row mb-3">
    <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h2 class="fw-bold mb-0">Detalle del partido</h2>
            <p class="text-muted mb-0 small"><?= htmlspecialchars($tempLabel !== '' ? $tempLabel : 'Temporada') ?></p>
        </div>
        <a href="<?= BASE_URL ?>partidos-resultados" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver a resultados
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center text-center text-md-start gy-3">
            <div class="col-md-4">
                <div class="text-muted small text-uppercase">Local</div>
                <div class="fs-4 fw-bold"><?= htmlspecialchars($partido['local_nombre']) ?></div>
            </div>
            <div class="col-md-4 text-center">
                <div class="display-6 fw-bold">
                    <span class="badge bg-dark px-4 py-2">
                        <?= (int)$partido['goles_local'] ?> — <?= (int)$partido['goles_visitante'] ?>
                    </span>
                </div>
                <div class="small text-muted mt-2">
                    Programado: <?= date('d/m/Y H:i', strtotime($partido['fecha_hora'])) ?>
                    <?php if (!empty($partido['fin_real'])): ?>
                        <br>Finalizado: <?= date('d/m/Y H:i', strtotime($partido['fin_real'])) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="text-muted small text-uppercase">Visitante</div>
                <div class="fs-4 fw-bold"><?= htmlspecialchars($partido['visitante_nombre']) ?></div>
            </div>
        </div>
        <?php if (!empty(trim((string)($partido['observaciones'] ?? '')))): ?>
            <hr>
            <div class="small text-muted text-uppercase fw-bold mb-1">Observaciones</div>
            <p class="mb-0"><?= nl2br(htmlspecialchars((string)$partido['observaciones'])) ?></p>
        <?php endif; ?>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-bold border-bottom">
        <i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>
        Línea del tiempo (goles y tarjetas)
    </div>
    <div class="card-body">
        <?php if (empty($timeline)): ?>
            <p class="text-muted mb-0">No hay goles ni tarjetas registrados para este partido.</p>
        <?php else: ?>
            <ul class="match-timeline">
                <?php foreach ($timeline as $ev): ?>
                    <?php
                    $isGol = ($ev['tipo'] ?? '') === 'gol';
                    $min = (int)($ev['minuto'] ?? 0);
                    $badgeClass = 'gol';
                    if (!$isGol) {
                        $tt = strtolower((string)($ev['tarjeta_tipo'] ?? ''));
                        $badgeClass = ($tt === 'roja') ? 'roja' : 'amarilla';
                    }
                    ?>
                    <li class="match-timeline-item">
                        <div class="match-timeline-badge <?= $badgeClass ?>">
                            <?php if ($isGol): ?>
                                <i class="fa-solid fa-futbol"></i>
                            <?php else: ?>
                                <span class="fw-bold" style="font-size:0.7rem;"><?= $badgeClass === 'roja' ? 'R' : 'A' ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="border rounded-3 p-3 bg-light">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                <div>
                                    <span class="badge bg-secondary"><?= $min ?>'</span>
                                    <?php if ($isGol): ?>
                                        <span class="fw-bold text-success ms-1">Gol</span>
                                    <?php else: ?>
                                        <span class="fw-bold ms-1 <?= ($ev['tarjeta_tipo'] ?? '') === 'roja' ? 'text-danger' : 'text-warning' ?>">
                                            Tarjeta <?= htmlspecialchars(ucfirst((string)($ev['tarjeta_tipo'] ?? ''))) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-white text-dark border">
                                    <?= htmlspecialchars($ev['equipo_nombre'] ?? '') ?>
                                </span>
                            </div>
                            <div class="mt-2 fw-semibold">
                                <?= htmlspecialchars($ev['jugador'] ?? '') ?>
                                <?php if (isset($ev['numero']) && $ev['numero'] !== '' && $ev['numero'] !== null): ?>
                                    <span class="text-muted fw-normal">#<?= htmlspecialchars((string)$ev['numero']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!$isGol && !empty(trim((string)($ev['motivo'] ?? '')))): ?>
                                <div class="small text-muted mt-1"><?= htmlspecialchars((string)$ev['motivo']) ?></div>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
