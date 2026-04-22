<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-id-card text-warning me-2"></i> Validar nómina en cancha</h2>
        <p class="text-muted small mb-0">
            El DT mostrará las fichas con QR. Escanee cada código para confirmar presencia en cancha.
        </p>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>partido/arbitro/escanear-qr?season_id=<?= (int)$season['id'] ?>&match_id=<?= (int)$match['id'] ?>" class="btn btn-dark btn-sm me-2">
            <i class="fa-solid fa-qrcode me-1"></i> Iniciar escaneo QR
        </a>
        <a href="<?= BASE_URL ?>partido/arbitro/mis-partidos" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'qr_confirmado'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Asistencia confirmada por QR.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'jugador_no_nomina'): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        El jugador no está en la nómina del DT para este partido.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && in_array($_GET['msg'], ['qr_invalido', 'qr_denegado', 'qr_error'], true)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        No se pudo confirmar la asistencia con ese QR.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!$fotoReady): ?>
    <div class="alert alert-warning">La base de datos aún no tiene la columna de foto de asistencia. Ejecute <code>migrations/sqlserver_asistencia_foto_y_arbitro.sql</code>.</div>
<?php endif; ?>
<?php if (!$validacionReady): ?>
    <div class="alert alert-warning">La columna de validación del árbitro no existe; ejecute la migración correspondiente para confirmar por QR.</div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body small">
        <strong><?= htmlspecialchars($season['nombre'] ?? '') ?></strong> —
        <?= date('d/m/Y H:i', strtotime($match['fecha_hora'])) ?> —
        <span class="fw-semibold"><?= htmlspecialchars($localTeam['nombre'] ?? '') ?></span>
        vs
        <span class="fw-semibold"><?= htmlspecialchars($visitTeam['nombre'] ?? '') ?></span>
    </div>
</div>

<?php if (empty($roster)): ?>
    <div class="alert alert-light border">Aún no hay jugadores en la nómina de este partido.</div>
<?php else: ?>
    <?php
    $total = count($roster);
    $confirmados = 0;
    foreach ($roster as $r) {
        if (strtolower(trim((string)($r['validacion_arbitro'] ?? ''))) === 'confirmado') {
            $confirmados++;
        }
    }
    ?>
    <div class="alert alert-info">
        Confirmados por QR: <strong><?= (int)$confirmados ?></strong> de <strong><?= (int)$total ?></strong>.
    </div>
    <div class="row g-3">
        <?php foreach ($roster as $row):
            $fotoRel = $row['foto_asistencia'] ?? '';
            $v = strtolower(trim((string)($row['validacion_arbitro'] ?? '')));
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm border-0">
                    <div class="card-body">
                        <div class="fw-bold"><?= htmlspecialchars($row['jugador_nombre'] ?? '') ?></div>
                        <div class="small text-muted">#<?= (int)($row['numero'] ?? 0) ?> · <?= htmlspecialchars($row['equipo_nombre'] ?? '') ?></div>
                        <div class="mt-2 mb-2 text-center bg-light rounded p-2">
                            <?php if ($fotoRel !== ''): ?>
                                <a href="<?= htmlspecialchars(BASE_URL . $fotoRel) ?>" target="_blank" rel="noopener">
                                    <img src="<?= htmlspecialchars(BASE_URL . $fotoRel) ?>" alt="" class="img-fluid rounded" style="max-height:160px">
                                </a>
                            <?php else: ?>
                                <span class="text-warning small">Sin foto del DT</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($v === 'confirmado'): ?>
                                <span class="badge bg-success">Confirmado por QR</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Pendiente por escaneo</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
