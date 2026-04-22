<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-clipboard-list text-primary me-2"></i> Nómina del partido</h2>
        <p class="text-muted small mb-0">Registro de jugadores presentes antes del inicio. El árbitro designado contrastará en cancha con la foto tomada por el DT.</p>
        <?php if (!empty($isAdminViewer)): ?>
            <p class="small text-info mb-0 mt-2"><i class="fa-solid fa-circle-info me-1"></i> Como administrador solo supervisas. Habilita el registro desde el detalle de temporada; el director técnico marca a sus jugadores presentes.</p>
        <?php elseif (!empty($canLocal) || !empty($canVisit)): ?>
            <p class="small text-muted mb-0 mt-2">
                <i class="fa-solid fa-check-double me-1"></i> Marque con casillas y <strong>Guardar</strong> (con foto por jugador si aplica).
            </p>
        <?php endif; ?>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>home" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-house me-1"></i> Inicio</a>
        <?php if (($_SESSION['role'] ?? '') === 'director_tecnico'): ?>
            <a href="<?= BASE_URL ?>partido/mis-partidos" class="btn btn-outline-primary btn-sm"><i class="fa-solid fa-calendar me-1"></i> Mis partidos</a>
        <?php endif; ?>
    </div>
</div>

<?php
$msgs = [
    'saved' => ['success', 'Nómina guardada correctamente.'],
    'error' => ['danger', 'No se pudo guardar la nómina.'],
    'team_invalid' => ['warning', 'Equipo no válido para este partido.'],
    'forbidden' => ['danger', 'No puedes modificar la nómina de ese equipo.'],
    'players_invalid' => ['warning', 'Algunos jugadores no pertenecen al equipo seleccionado.'],
    'qr_ok' => ['success', 'Asistencia registrada por QR.'],
    'qr_invalid' => ['warning', 'Enlace QR no válido o partido no disponible.'],
    'qr_denied' => ['danger', 'No tiene permiso para registrar con QR (solo el DT de ese equipo, con registro habilitado).'],
    'qr_error' => ['danger', 'No se pudo registrar la asistencia. Verifique la tabla de presencias.'],
    'qr_forbidden' => ['secondary', 'El administrador no registra asistencia por QR; use la vista de supervisión.'],
    'foto_requerida' => ['warning', 'Cada jugador en nómina debe tener una foto de validación (sube archivo o conserva la ya guardada). Ejecute la migración SQL si falta la columna foto_asistencia.'],
    'min_no_cumplido' => ['warning', 'Aún no cumples el mínimo de jugadores presentes para abrir el catálogo de asistencia.'],
    'foto_invalida' => ['danger', 'Una imagen no es válida (tipo o tamaño máx. 5 MB).'],
    'foto_ok' => ['success', 'Foto de validación guardada.'],
];
$m = $_GET['msg'] ?? '';
if (isset($msgs[$m])):
    [$level, $text] = $msgs[$m];
?>
    <div class="alert alert-<?= $level ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($text) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-center">
            <div class="col-md-8">
                <div class="fw-bold"><?= htmlspecialchars($season['nombre'] ?? '') ?></div>
                <div class="text-muted small">
                    <?= date('d/m/Y H:i', strtotime($match['fecha_hora'])) ?> —
                    <span class="fw-semibold text-dark"><?= htmlspecialchars($localTeam['nombre'] ?? '') ?></span>
                    vs
                    <span class="fw-semibold text-dark"><?= htmlspecialchars($visitTeam['nombre'] ?? '') ?></span>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="small text-muted">Mínimo requerido por equipo</div>
                <span class="badge bg-secondary fs-6"><?= (int)$minJugadores ?> jugadores</span>
            </div>
        </div>
        <div class="row mt-3 small">
            <div class="col-6 col-md-3">
                <span class="text-muted">Local presentes:</span>
                <strong class="<?= $countLocal >= $minJugadores ? 'text-success' : 'text-warning' ?>"><?= (int)$countLocal ?></strong>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted">Visitante presentes:</span>
                <strong class="<?= $countVisit >= $minJugadores ? 'text-success' : 'text-warning' ?>"><?= (int)$countVisit ?></strong>
            </div>
        </div>
    </div>
</div>

<?php
$mid = (int)($match['id'] ?? 0);
$sid = (int)($season['id'] ?? 0);
$fotoAsistenciaReady = !empty($fotoAsistenciaReady);
$presentDetailsLocal = $presentDetailsLocal ?? [];
$presentDetailsVisit = $presentDetailsVisit ?? [];

$renderTeamForm = function ($equipoId, $teamLabel, $teamLogo, $players, $presentIds, $presentDetails, $canEdit) use ($asistenciaUrl, $mid, $sid, $fotoAsistenciaReady, $minJugadores) {
    $presentSet = array_fill_keys($presentIds, true);
    $minOk = count($presentIds) >= (int)$minJugadores;
    ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold py-3">
            <i class="fa-solid fa-users me-2 text-success"></i><?= htmlspecialchars($teamLabel) ?>
            <?php if (!$canEdit): ?>
                <span class="badge bg-light text-muted border ms-2">Solo lectura</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (!$canEdit): ?>
                <p class="text-muted small mb-2">No puede editar este equipo. Solo el director técnico del club marca asistencia cuando el administrador lo habilitó.</p>
            <?php endif; ?>
            <?php if ($fotoAsistenciaReady && $canEdit): ?>
                <p class="small text-warning mb-3"><i class="fa-solid fa-camera me-1"></i> Por cada jugador presente suba una <strong>foto reciente</strong>: use <strong>Cámara</strong> para tomarla al momento (celular o webcam si el navegador lo permite) o <strong>Archivo</strong> para elegir una imagen ya guardada. El árbitro la usará en cancha.</p>
            <?php endif; ?>
            <?php if (empty($players)): ?>
                <div class="alert alert-light border mb-0 small">No hay jugadores registrados en plantilla.</div>
            <?php else: ?>
                <form method="post" action="<?= htmlspecialchars($asistenciaUrl) ?>" enctype="multipart/form-data">
                    <input type="hidden" name="equipo_id" value="<?= (int)$equipoId ?>">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-3">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:4.5rem" class="text-center">Asistencia</th>
                                    <th>#</th>
                                    <th>Jugador</th>
                                    <th>Pos.</th>
                                    <?php if ($fotoAsistenciaReady): ?>
                                        <th>Foto validación</th>
                                        <?php if (!$canEdit): ?>
                                            <th class="small">Árbitro</th>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($players as $pl):
                                    $jid = (int)$pl['id'];
                                    $det = $presentDetails[$jid] ?? [];
                                    $fotoRel = $det['foto_asistencia'] ?? '';
                                    $valArb = $det['validacion_arbitro'] ?? '';
                                ?>
                                    <tr>
                                        <td>
                                            <?php if ($canEdit): ?>
                                                <input class="form-check-input" type="checkbox" name="jugadores[]"
                                                       value="<?= $jid ?>"
                                                       <?= isset($presentSet[$jid]) ? 'checked' : '' ?>>
                                            <?php else: ?>
                                                <i class="fa-solid <?= isset($presentSet[$jid]) ? 'fa-check text-success' : 'fa-minus text-muted' ?>"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int)($pl['numero'] ?? 0) ?></td>
                                        <td class="fw-semibold"><?= htmlspecialchars($pl['nombre'] ?? '') ?></td>
                                        <td class="text-muted small"><?= htmlspecialchars($pl['posicion'] ?? '') ?></td>
                                        <?php if ($fotoAsistenciaReady): ?>
                                            <td>
                                                <?php if ($canEdit): ?>
                                                    <?php if ($fotoRel !== ''): ?>
                                                        <div class="mb-1">
                                                            <img src="<?= htmlspecialchars(BASE_URL . $fotoRel) ?>" alt="" class="rounded border" style="max-height:56px;width:auto">
                                                            <div class="small text-muted">Actual — suba otra para reemplazar</div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="d-flex flex-column gap-1 align-items-center">
                                                        <input type="file" class="d-none" id="foto_jugador_<?= $jid ?>" name="foto_jugador[<?= $jid ?>]" accept="image/*">
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-outline-secondary" title="Elegir imagen de galería o archivos" onclick="pickAttPhoto(<?= $jid ?>, false)"><i class="fa-solid fa-images"></i><span class="d-none d-xl-inline"> Archivo</span></button>
                                                            <button type="button" class="btn btn-outline-primary" title="Abrir cámara (trasera en celular)" onclick="pickAttPhoto(<?= $jid ?>, true)"><i class="fa-solid fa-camera"></i><span class="d-none d-xl-inline"> Cámara</span></button>
                                                        </div>
                                                        <span class="small text-muted text-break" id="foto_label_<?= $jid ?>" style="max-width:12rem;"></span>
                                                    </div>
                                                <?php else: ?>
                                                    <?php if ($fotoRel !== ''): ?>
                                                        <a href="<?= htmlspecialchars(BASE_URL . $fotoRel) ?>" target="_blank" rel="noopener">
                                                            <img src="<?= htmlspecialchars(BASE_URL . $fotoRel) ?>" alt="" class="rounded border" style="max-height:72px;width:auto">
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-warning small">Sin foto</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <?php if (!$canEdit): ?>
                                                <td class="small">
                                                    <?php if ($valArb === 'confirmado'): ?>
                                                        <span class="badge bg-success">Confirmado</span>
                                                    <?php elseif ($valArb === 'rechazado'): ?>
                                                        <span class="badge bg-danger">No coincide</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Pendiente</span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($canEdit): ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Guardar nómina de <?= htmlspecialchars($teamLabel) ?>
                        </button>
                    <?php endif; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php
};

$renderTeamForm($localTeam['id'], $localTeam['nombre'] ?? 'Local', $localTeam['logo'] ?? '', $playersLocal, $presentLocal, $presentDetailsLocal, $canLocal);
$renderTeamForm($visitTeam['id'], $visitTeam['nombre'] ?? 'Visitante', $visitTeam['logo'] ?? '', $playersVisit, $presentVisit, $presentDetailsVisit, $canVisit);
?>
<?php if (!empty($fotoAsistenciaReady) && (!empty($canLocal) || !empty($canVisit))): ?>
<script>
(function () {
    window.pickAttPhoto = function (jid, useCamera) {
        var el = document.getElementById('foto_jugador_' + jid);
        if (!el) return;
        el.value = '';
        if (useCamera) {
            el.setAttribute('capture', 'environment');
        } else {
            el.removeAttribute('capture');
        }
        el.click();
    };
    document.addEventListener('change', function (e) {
        var t = e.target;
        if (!t || !t.id || t.id.indexOf('foto_jugador_') !== 0) return;
        var jid = t.id.replace('foto_jugador_', '');
        var span = document.getElementById('foto_label_' + jid);
        if (!span) return;
        var f = t.files && t.files[0];
        span.textContent = f ? ('Seleccionado: ' + f.name) : '';
    });
})();
</script>
<?php endif; ?>
