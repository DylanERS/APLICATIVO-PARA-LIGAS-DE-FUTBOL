<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-pen-to-square text-info me-2"></i> Editar Equipo</h2>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>equipos" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver al listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="<?= BASE_URL ?>equipos/edit?id=<?= $team['id'] ?>" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nombre del Equipo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($team['nombre']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Ciudad <span class="text-danger">*</span></label>
                    <input type="text" name="ciudad" class="form-control" required value="<?= htmlspecialchars($team['ciudad']) ?>">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Entrenador <span class="text-danger">*</span></label>
                    <input type="text" name="entrenador" class="form-control" required value="<?= htmlspecialchars($team['entrenador']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Logo (Opcional)</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <div class="form-text">Selecciona una imagen si deseas actualizar el logo actual.</div>
                    
                    <?php if($team['logo'] != 'default_logo.png'): ?>
                    <div class="mt-2 text-muted small">
                        <strong>Logo actual:</strong> <?= htmlspecialchars($team['logo']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <hr class="my-4">
            
            <div class="text-end">
                <button type="submit" class="btn btn-info text-white fw-bold px-4">
                    <i class="fa-solid fa-save me-1"></i> Actualizar Equipo
                </button>
            </div>
        </form>
    </div>
</div>
