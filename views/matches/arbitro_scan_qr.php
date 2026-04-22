<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-qrcode text-warning me-2"></i> Escanear QR de asistencia</h2>
        <p class="text-muted small mb-0">
            Apunta la cámara al QR mostrado por el DT. La confirmación se enviará automáticamente.
        </p>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>partido/arbitro/validar-asistencia?season_id=<?= (int)$seasonId ?>&match_id=<?= (int)$match['id'] ?>" class="btn btn-outline-secondary btn-sm">Volver</a>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'manual_guardado'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        Validaciones manuales guardadas.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'sin_columna_validacion'): ?>
    <div class="alert alert-warning alert-dismissible fade show">
        Falta la columna de validación del árbitro en base de datos.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="small text-muted mb-2">Partido #<?= (int)$match['id'] ?> · <?= date('d/m/Y H:i', strtotime($match['fecha_hora'])) ?></div>
        <div id="qr-reader" style="max-width:520px;margin:0 auto;"></div>
        <div class="small text-muted mt-2" id="scan-status">Esperando escaneo...</div>

        <div class="mt-4 d-none" id="fallback-manual-box">
            <div class="alert alert-info py-2">
                No se pudo abrir la cámara. Usa validación manual con checkbox. Compare la <strong>foto tomada por el DT</strong> al marcar asistencia con la persona en cancha antes de marcar validado.
            </div>
            <?php if (empty($validacionReady)): ?>
                <div class="alert alert-warning py-2">No está disponible la columna de validación en BD.</div>
            <?php elseif (empty($roster)): ?>
                <div class="alert alert-light border py-2">No hay jugadores en nómina para validar.</div>
            <?php else: ?>
                <?php
                $rosterByTeam = [];
                foreach (($roster ?? []) as $row) {
                    $teamNameKey = trim((string)($row['equipo_nombre'] ?? 'Equipo'));
                    if ($teamNameKey === '') {
                        $teamNameKey = 'Equipo';
                    }
                    if (!isset($rosterByTeam[$teamNameKey])) {
                        $rosterByTeam[$teamNameKey] = [];
                    }
                    $rosterByTeam[$teamNameKey][] = $row;
                }
                ?>
                <form method="post" action="<?= BASE_URL ?>partido/arbitro/escanear-qr?season_id=<?= (int)$seasonId ?>&match_id=<?= (int)$match['id'] ?>">
                    <?php if (empty($fotoReady)): ?>
                        <div class="alert alert-warning py-2 small mb-2">La columna de foto de asistencia no está en BD; ejecute <code>migrations/sqlserver_asistencia_foto_y_arbitro.sql</code> para ver las fotos del DT.</div>
                    <?php endif; ?>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleAllValidation(true)">
                            <i class="fa-solid fa-check-double me-1"></i> Seleccionar todos
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleAllValidation(false)">
                            <i class="fa-solid fa-eraser me-1"></i> Limpiar selección
                        </button>
                    </div>
                    <?php foreach ($rosterByTeam as $teamName => $teamRows): ?>
                        <?php $teamSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($teamName)); ?>
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
                                <strong><?= htmlspecialchars($teamName) ?></strong>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleTeamValidation('<?= htmlspecialchars($teamSlug) ?>', true)">
                                    Seleccionar equipo
                                </button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Validado</th>
                                                <th>Foto (DT)</th>
                                                <th>Jugador</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($teamRows as $row):
                                                $jid = (int)($row['jugador_id'] ?? 0);
                                                $v = strtolower(trim((string)($row['validacion_arbitro'] ?? '')));
                                                $fotoDt = trim((string)($row['foto_asistencia'] ?? ''));
                                            ?>
                                                <tr>
                                                    <td>
                                                        <input
                                                            type="checkbox"
                                                            class="form-check-input chk-validado chk-team-<?= htmlspecialchars($teamSlug) ?>"
                                                            name="validado[]"
                                                            value="<?= $jid ?>"
                                                            <?= $v === 'confirmado' ? 'checked' : '' ?>
                                                        >
                                                    </td>
                                                    <td class="text-center" style="width:110px">
                                                        <?php if ($fotoDt !== ''): ?>
                                                            <a href="<?= htmlspecialchars(BASE_URL . $fotoDt) ?>" target="_blank" rel="noopener" title="Ver tamaño completo">
                                                                <img src="<?= htmlspecialchars(BASE_URL . $fotoDt) ?>" alt="" class="rounded border bg-light" style="max-height:88px;width:auto;object-fit:cover">
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-warning small">Sin foto</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="fw-semibold">
                                                        <?= htmlspecialchars($row['jugador_nombre'] ?? '') ?>
                                                        <span class="text-muted small">#<?= (int)($row['numero'] ?? 0) ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($v === 'confirmado'): ?>
                                                            <span class="badge bg-success">Confirmado</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">Pendiente</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar validado
                    </button>
                </form>
                <script>
                function toggleAllValidation(mark) {
                    document.querySelectorAll('.chk-validado').forEach(function (el) { el.checked = !!mark; });
                }
                function toggleTeamValidation(teamSlug, mark) {
                    document.querySelectorAll('.chk-team-' + teamSlug).forEach(function (el) { el.checked = !!mark; });
                }
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    var statusEl = document.getElementById('scan-status');
    var fallbackBox = document.getElementById('fallback-manual-box');
    function goTo(url) {
        if (!url) return;
        try {
            var u = new URL(url, window.location.origin);
            // Permitir solo la ruta esperada por seguridad.
            if (u.pathname.indexOf('/partido/arbitro/confirmar-asistencia-qr') === -1) {
                statusEl.textContent = 'QR inválido: no corresponde a confirmación de asistencia.';
                return;
            }
            statusEl.textContent = 'QR detectado, enviando confirmación...';
            // Mantener mismo host/protocolo para no perder la cookie de sesión
            // cuando el QR trae una URL absoluta con otro dominio (ej: localhost vs 127.0.0.1).
            window.location.href = u.pathname + u.search + (u.hash || '');
        } catch (e) {
            statusEl.textContent = 'No se pudo interpretar el QR.';
        }
    }

    if (typeof Html5Qrcode === 'undefined') {
        statusEl.textContent = 'El escáner no está disponible en este navegador.';
        if (fallbackBox) fallbackBox.classList.remove('d-none');
        return;
    }

    var qr = new Html5Qrcode('qr-reader');
    qr.start(
        { facingMode: 'environment' },
        { fps: 12, qrbox: { width: 260, height: 260 } },
        function (decodedText) {
            goTo(decodedText);
        },
        function () {}
    ).catch(function () {
        statusEl.textContent = 'No se pudo iniciar la cámara. Usa la validación manual con checkbox.';
        if (fallbackBox) fallbackBox.classList.remove('d-none');
    });
})();
</script>
