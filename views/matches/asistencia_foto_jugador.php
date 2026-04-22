<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white fw-bold">
                <i class="fa-solid fa-camera text-primary me-2"></i> Foto de validación
            </div>
            <div class="card-body">
                <?php
                $fm = $_GET['msg'] ?? '';
                if ($fm === 'sin_foto'): ?>
                    <div class="alert alert-warning small">Seleccione una imagen.</div>
                <?php elseif ($fm === 'error'): ?>
                    <div class="alert alert-danger small">No se pudo guardar. Verifique formato (JPG, PNG, WebP, GIF, máx. 5 MB).</div>
                <?php endif; ?>

                <p class="small text-muted mb-2">Jugador: <strong><?= htmlspecialchars($player['nombre'] ?? '') ?></strong> (#<?= (int)($player['numero'] ?? 0) ?>)</p>
                <p class="small text-muted mb-3">Equipo: <?= htmlspecialchars($equipo['nombre'] ?? '') ?></p>
                <p class="small text-info mb-3"><i class="fa-solid fa-circle-info me-1"></i> <strong>Cámara</strong> abre la cámara (en celular suele ser la trasera). <strong>Archivo</strong> permite elegir una foto de la galería o del equipo.</p>

                <form method="post" enctype="multipart/form-data" id="form_foto_asistencia" action="<?= BASE_URL ?>partido/asistencia/foto-jugador?<?= http_build_query([
                    'season_id' => (int)$seasonId,
                    'match_id' => (int)$matchId,
                    'equipo_id' => (int)$equipoId,
                    'jugador_id' => (int)$jugadorId,
                    'sig' => $sig
                ]) ?>">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Foto de validación</label>
                        <input type="file" name="foto" id="input_foto_asistencia" class="d-none" accept="image/*">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <button type="button" class="btn btn-outline-secondary" onclick="pickSingleFoto(false)"><i class="fa-solid fa-images me-1"></i> Elegir archivo</button>
                            <button type="button" class="btn btn-outline-primary" onclick="pickSingleFoto(true)"><i class="fa-solid fa-camera me-1"></i> Usar cámara</button>
                        </div>
                        <div class="small text-muted" id="foto_single_label"></div>
                    </div>
                    <button type="submit" class="btn btn-primary" id="btn_guardar_foto" disabled><i class="fa-solid fa-upload me-1"></i> Guardar foto</button>
                    <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-outline-secondary ms-2">Volver a la nómina</a>
                </form>
                <script>
                (function () {
                    window.pickSingleFoto = function (useCamera) {
                        var el = document.getElementById('input_foto_asistencia');
                        if (!el) return;
                        el.value = '';
                        if (useCamera) {
                            el.setAttribute('capture', 'environment');
                        } else {
                            el.removeAttribute('capture');
                        }
                        el.click();
                    };
                    var inp = document.getElementById('input_foto_asistencia');
                    var lbl = document.getElementById('foto_single_label');
                    var btn = document.getElementById('btn_guardar_foto');
                    if (inp && lbl && btn) {
                        inp.addEventListener('change', function () {
                            var f = inp.files && inp.files[0];
                            lbl.textContent = f ? ('Seleccionado: ' + f.name) : '';
                            btn.disabled = !f;
                        });
                    }
                })();
                </script>
            </div>
        </div>
    </div>
</div>
