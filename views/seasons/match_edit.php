<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-pen-to-square text-primary me-2"></i> Editar Partido</h2>
        <p class="text-muted small">Actualiza fecha y asigna arbitro/cancha para este partido.</p>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>temporadas/show?id=<?= (int)$season['id'] ?>" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </div>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'referee_created'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-check-circle me-1"></i> Arbitro registrado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'referee_invalid'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> El nombre del arbitro es obligatorio.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_invalid'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> La fecha y hora del partido es obligatoria.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'match_error'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-xmark me-1"></i> No se pudo guardar la informacion del partido.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'needs_referee_for_attendance'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Antes de habilitar asistencia, debe asignar un <strong>arbitro</strong> al partido.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if(isset($_GET['msg']) && $_GET['msg'] == 'needs_cancha_for_attendance'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Antes de habilitar asistencia, debe asignar una <strong>cancha</strong> al partido.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (empty($canchaColumnReady)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-database me-1"></i> Para asignar cancha al partido, ejecute la migración <code>migrations/sqlserver_partidos_cancha_id.sql</code>.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php elseif (empty($canchasTableReady)): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-database me-1"></i> No existe la tabla <code>canchas</code>. Ejecute <code>migrations/sqlserver_canchas.sql</code>.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="small text-muted">Temporada</div>
                <div class="fw-bold"><?= htmlspecialchars($season['nombre']) ?></div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Local</div>
                <div class="fw-bold"><?= htmlspecialchars($match['equipo_local_nombre']) ?></div>
            </div>
            <div class="col-md-4">
                <div class="small text-muted">Visitante</div>
                <div class="fw-bold"><?= htmlspecialchars($match['equipo_visitante_nombre']) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-7 mb-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white fw-bold">Datos del Partido</div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>temporadas/match/edit?season_id=<?= (int)$season['id'] ?>&match_id=<?= (int)$match['id'] ?>" method="POST">
                    <input type="hidden" name="action" value="update_match">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Fecha y Hora <span class="text-danger">*</span></label>
                        <input
                            type="datetime-local"
                            name="fecha_hora"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars(date('Y-m-d\TH:i', strtotime($match['fecha_hora']))) ?>"
                        >
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Arbitro</label>
                        <select name="arbitro_id" class="form-select">
                            <option value="">Sin asignar</option>
                            <?php foreach($referees as $referee): ?>
                                <option value="<?= (int)$referee['id'] ?>" <?= ((int)($match['arbitro_id'] ?? 0) === (int)$referee['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($referee['nombre']) ?><?= !empty($referee['telefono']) ? ' - ' . htmlspecialchars($referee['telefono']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if (!empty($canchaColumnReady)): ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Cancha</label>
                        <select name="cancha_id" class="form-select" <?= empty($canchasTableReady) ? 'disabled' : '' ?>>
                            <option value="">Sin asignar</option>
                            <?php foreach(($canchas ?? []) as $cancha): ?>
                                <option value="<?= (int)$cancha['id'] ?>" <?= ((int)($match['cancha_id'] ?? 0) === (int)$cancha['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cancha['nombre']) ?><?= !empty($cancha['direccion']) ? ' - ' . htmlspecialchars($cancha['direccion']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($canchasTableReady)): ?>
                            <div class="form-text text-danger">No disponible hasta crear tabla <code>canchas</code>.</div>
                        <?php elseif (empty($canchas)): ?>
                            <div class="form-text text-muted">Aún no hay canchas registradas en el módulo de canchas.</div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i> Guardar Partido
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5 mb-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white fw-bold">Registrar Arbitro</div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>temporadas/match/edit?season_id=<?= (int)$season['id'] ?>&match_id=<?= (int)$match['id'] ?>" method="POST">
                    <input type="hidden" name="action" value="create_referee">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre_arbitro" class="form-control" required placeholder="Ej. Carlos Ramirez">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Telefono</label>
                        <input type="text" name="telefono_arbitro" class="form-control" placeholder="Opcional">
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fa-solid fa-user-plus me-1"></i> Guardar Arbitro
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
