<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Editar Temporada</h2>
        <p class="text-muted small">Actualiza datos del torneo, calendario y equipos participantes.</p>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>temporadas" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </div>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_min_teams'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Debes seleccionar al menos 2 equipos para guardar.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_invalid'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Verifica el nombre y año de la temporada.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_dates_required'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Debes indicar fecha de inicio.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_dates_invalid'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> La fecha de inicio no puede ser mayor que la fecha de fin.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_time_pair_required'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Si defines una hora, debes completar inicio y fin.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_time_invalid'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> La hora de inicio debe ser menor que la hora de fin.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_days_required'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Selecciona al menos un día de juego.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_error'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-xmark me-1"></i> Ocurrió un error al guardar la temporada.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
$diasSeleccionados = [];
if (!empty($season['dias_juego'])) {
    $diasSeleccionados = array_map('trim', explode(',', (string)$season['dias_juego']));
}
$equiposSeleccionados = $season['equipos_ids'] ?? [];
?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="<?= BASE_URL ?>temporadas/edit?id=<?= (int)$season['id'] ?>" method="POST">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label fw-bold">Nombre del Torneo/Temporada <span class="text-danger">*</span></label>
                    <input type="text" name="nombre_temporada" class="form-control" required value="<?= htmlspecialchars($season['nombre']) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Ano <span class="text-danger">*</span></label>
                    <input type="number" name="anio_temporada" class="form-control" min="2000" max="2100" required value="<?= (int)$season['anio'] ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Fecha Inicio <span class="text-danger">*</span></label>
                    <input type="date" name="fecha_inicio" class="form-control" required value="<?= htmlspecialchars((string)$season['fecha_inicio']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Fecha Fin</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?= htmlspecialchars((string)($season['fecha_fin'] ?? '')) ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Hora Inicio</label>
                    <input type="time" name="hora_inicio" class="form-control" value="<?= htmlspecialchars(!empty($season['hora_inicio']) ? substr((string)$season['hora_inicio'], 0, 5) : '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Hora Fin</label>
                    <input type="time" name="hora_fin" class="form-control" value="<?= htmlspecialchars(!empty($season['hora_fin']) ? substr((string)$season['hora_fin'], 0, 5) : '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Dias de Juego <span class="text-danger">*</span></label>
                <div class="row">
                    <?php
                    $dias = [
                        'lunes' => 'Lunes',
                        'martes' => 'Martes',
                        'miercoles' => 'Miercoles',
                        'jueves' => 'Jueves',
                        'viernes' => 'Viernes',
                        'sabado' => 'Sabado',
                        'domingo' => 'Domingo'
                    ];
                    ?>
                    <?php foreach ($dias as $value => $label): ?>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="form-check border rounded px-3 py-2">
                                <input class="form-check-input" type="checkbox" name="dias_juego[]" value="<?= $value ?>" id="dia_<?= $value ?>" <?= in_array($value, $diasSeleccionados, true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="dia_<?= $value ?>"><?= $label ?></label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Equipos que participan <span class="text-danger">*</span></label>
                <div class="mb-2">
                    <input type="text" id="filtroEquiposTemporada" class="form-control form-control-sm" placeholder="Buscar equipo por nombre...">
                </div>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    <button type="button" id="btnSeleccionarTodosEquipos" class="btn btn-sm btn-outline-primary">
                        <i class="fa-solid fa-check-double me-1"></i> Seleccionar todos
                    </button>
                    <button type="button" id="btnLimpiarEquipos" class="btn btn-sm btn-outline-secondary">
                        <i class="fa-solid fa-eraser me-1"></i> Limpiar seleccion
                    </button>
                    <span class="small text-muted align-self-center">Seleccionados: <strong id="contadorEquiposSeleccionados">0</strong></span>
                </div>
                <div class="border rounded p-2" style="max-height: 260px; overflow-y: auto;">
                    <div class="row">
                        <?php foreach ($teams as $team): ?>
                            <div class="col-md-6 col-lg-4 mb-2 season-team-item" data-team-name="<?= htmlspecialchars(strtolower($team['nombre'])) ?>">
                                <div class="form-check border rounded px-3 py-2">
                                    <input class="form-check-input season-team-checkbox" type="checkbox" name="equipos[]" value="<?= (int)$team['id'] ?>" id="equipo_<?= (int)$team['id'] ?>" <?= in_array((int)$team['id'], $equiposSeleccionados, true) ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100" for="equipo_<?= (int)$team['id'] ?>">
                                        <?= htmlspecialchars($team['nombre']) ?>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <small class="text-muted">Selecciona al menos 2 equipos.</small>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fa-solid fa-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        const checkboxes = document.querySelectorAll('.season-team-checkbox');
        const teamItems = document.querySelectorAll('.season-team-item');
        const btnAll = document.getElementById('btnSeleccionarTodosEquipos');
        const btnClear = document.getElementById('btnLimpiarEquipos');
        const counter = document.getElementById('contadorEquiposSeleccionados');
        const filterInput = document.getElementById('filtroEquiposTemporada');

        function updateCounter() {
            if (!counter) return;
            let selected = 0;
            checkboxes.forEach((cb) => {
                if (cb.checked) selected++;
            });
            counter.textContent = selected;
        }

        if (btnAll) {
            btnAll.addEventListener('click', function() {
                teamItems.forEach((item) => {
                    if (item.style.display !== 'none') {
                        const cb = item.querySelector('.season-team-checkbox');
                        if (cb) cb.checked = true;
                    }
                });
                updateCounter();
            });
        }

        if (btnClear) {
            btnClear.addEventListener('click', function() {
                checkboxes.forEach((cb) => { cb.checked = false; });
                updateCounter();
            });
        }

        checkboxes.forEach((cb) => {
            cb.addEventListener('change', updateCounter);
        });

        if (filterInput) {
            filterInput.addEventListener('input', function() {
                const search = filterInput.value.toLowerCase().trim();
                teamItems.forEach((item) => {
                    const name = item.getAttribute('data-team-name') || '';
                    item.style.display = name.includes(search) ? '' : 'none';
                });
            });
        }

        updateCounter();
    })();
</script>
