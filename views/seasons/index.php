<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-calendar-days text-primary me-2"></i> Gestión de Temporadas</h2>
        <p class="text-muted small">Crea torneos, define calendario y asigna equipos participantes.</p>
    </div>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_created'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-check-circle me-1"></i> Temporada creada y equipos asignados correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_inactivated'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-check-circle me-1"></i> Temporada marcada como inactiva correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_updated'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-check-circle me-1"></i> Temporada actualizada correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_not_found'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> La temporada no existe o no pertenece a esta liga.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_min_teams'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Debes seleccionar al menos 2 equipos para crear una temporada.
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
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Debes indicar fecha de inicio y fecha de fin.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_dates_invalid'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> La fecha de inicio no puede ser mayor que la fecha de fin.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_days_required'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Selecciona al menos un día de juego.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_time_required'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Debes indicar hora de inicio y fin.
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
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'season_error'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-xmark me-1"></i> Ocurrió un error al crear la temporada.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-1"><i class="fa-solid fa-plus text-success me-2"></i>Crear Temporada</h4>
        <p class="text-muted small mb-4">Define un torneo con rango de fechas, dias de juego, horario y equipos participantes.</p>

        <?php if (empty($teams)): ?>
            <div class="alert alert-info mb-0">
                <i class="fa-solid fa-circle-info me-1"></i> Primero debes registrar equipos para poder crear temporadas.
            </div>
        <?php else: ?>
            <form action="<?= BASE_URL ?>temporadas" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">Nombre del Torneo/Temporada <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_temporada" class="form-control" required placeholder="Ej. Clausura 2026">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">Ano <span class="text-danger">*</span></label>
                        <input type="number" name="anio_temporada" class="form-control" min="2000" max="2100" value="<?= date('Y') ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Fecha Inicio <span class="text-danger">*</span></label>
                        <input type="date" name="fecha_inicio" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Hora Inicio</label>
                        <input type="time" name="hora_inicio" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">Hora Fin</label>
                        <input type="time" name="hora_fin" class="form-control">
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
                                    <input class="form-check-input" type="checkbox" name="dias_juego[]" value="<?= $value ?>" id="dia_<?= $value ?>">
                                    <label class="form-check-label" for="dia_<?= $value ?>"><?= $label ?></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Equipos que participan <span class="text-danger">*</span></label>
                    <div class="mb-2">
                        <input
                            type="text"
                            id="filtroEquiposTemporada"
                            class="form-control form-control-sm"
                            placeholder="Buscar equipo por nombre..."
                        >
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
                                        <input class="form-check-input season-team-checkbox" type="checkbox" name="equipos[]" value="<?= (int)$team['id'] ?>" id="equipo_<?= (int)$team['id'] ?>">
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
                    <button type="submit" class="btn btn-success fw-bold px-4">
                        <i class="fa-solid fa-plus me-1"></i> Crear Temporada
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm border-0 mt-4">
    <div class="card-body p-4">
        <h4 class="fw-bold mb-3"><i class="fa-solid fa-list-check text-primary me-2"></i>Temporadas Registradas</h4>

        <?php if (empty($seasons)): ?>
            <div class="alert alert-light border mb-0">
                <i class="fa-solid fa-circle-info me-1"></i> Aun no hay temporadas creadas.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark text-center align-middle">
                        <tr>
                            <th>Temporada</th>
                            <th>Ano</th>
                            <th>Fechas</th>
                            <th>Dias y Horario</th>
                            <th>Estado</th>
                            <th>Total Equipos</th>
                            <th>Equipos</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody class="text-center align-middle">
                        <?php foreach ($seasons as $season): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($season['nombre']) ?></td>
                                <td><?= (int)$season['anio'] ?></td>
                                <td>
                                    <?= htmlspecialchars($season['fecha_inicio'] ?? '-') ?> - <?= htmlspecialchars($season['fecha_fin'] ?? '-') ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($season['dias_juego'] ?? '') ?><br>
                                    <small class="text-muted">
                                        <?php
                                        $hi = !empty($season['hora_inicio']) ? substr((string)$season['hora_inicio'], 0, 5) : '-';
                                        $hf = !empty($season['hora_fin']) ? substr((string)$season['hora_fin'], 0, 5) : '-';
                                        ?>
                                        <?= htmlspecialchars($hi) ?> - <?= htmlspecialchars($hf) ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge <?= $season['estado'] === 'activa' ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= htmlspecialchars(ucfirst($season['estado'])) ?>
                                    </span>
                                </td>
                                <td><?= (int)$season['total_equipos'] ?></td>
                                <td><?= htmlspecialchars($season['equipos'] ?? '') ?></td>
                                <td>
                                    <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                                        <a href="<?= BASE_URL ?>temporadas/show?id=<?= (int)$season['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fa-solid fa-eye me-1"></i> Ver
                                        </a>
                                        <a href="<?= BASE_URL ?>temporadas/edit?id=<?= (int)$season['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fa-solid fa-pen-to-square me-1"></i> Editar
                                        </a>
                                        <?php if (($season['estado'] ?? '') === 'activa'): ?>
                                            <form action="<?= BASE_URL ?>temporadas" method="POST" onsubmit="return confirm('Deseas inactivar esta temporada?');">
                                                <input type="hidden" name="action" value="deactivate">
                                                <input type="hidden" name="season_id" value="<?= (int)$season['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fa-solid fa-ban me-1"></i> Inactivar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
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
