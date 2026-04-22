<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-cogs text-primary me-2"></i> Configuración de la Liga</h2>
        <p class="text-muted small">Modifica los detalles generales del torneo.</p>
    </div>
</div>

<?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-check-circle me-1"></i> Configuración de la liga actualizada exitosamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="<?= BASE_URL ?>configuracion" method="POST">
            <input type="hidden" name="form_type" value="league">
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Nombre de la Liga <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control form-control-lg" required value="<?= htmlspecialchars($league['nombre']) ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="3"><?= htmlspecialchars($league['descripcion']) ?></textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Mínimo de jugadores presentes por equipo</label>
                    <input type="number" name="min_jugadores_partido" class="form-control" min="1" max="50" required
                           value="<?= (int)($league['min_jugadores_partido'] ?? 7) ?>">
                    <div class="form-text">Requisito para poder dar inicio al partido (nómina registrada).</div>
                </div>
                <?php if (!empty($has_duracion_column)): ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Duración del partido (minutos)</label>
                    <input type="number" name="duracion_partido_minutos" class="form-control" min="15" max="150" required
                           value="<?= (int)($league['duracion_partido_minutos'] ?? 90) ?>">
                    <div class="form-text">Tiempo reglamentario de juego (p. ej. 90 para dos tiempos de 45).</div>
                </div>
                <?php endif; ?>
            </div>

            <hr class="my-4">
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fa-solid fa-save me-1"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

