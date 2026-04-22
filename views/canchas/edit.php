<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-pen text-primary me-2"></i> Editar cancha</h2>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>canchas" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="<?= BASE_URL ?>canchas/edit?id=<?= (int)$cancha['id'] ?>" method="POST">
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label class="form-label fw-bold">Nombre <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($cancha['nombre']) ?>" maxlength="150">
                </div>
                <div class="col-md-4 mb-3 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="activa" id="activa" value="1" <?= (int)($cancha['activa'] ?? 0) === 1 ? 'checked' : '' ?>>
                        <label class="form-check-label" for="activa">Cancha activa</label>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Dirección</label>
                <input type="text" name="direccion" class="form-control" value="<?= htmlspecialchars((string)($cancha['direccion'] ?? '')) ?>" maxlength="255">
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Notas</label>
                <textarea name="notas" class="form-control" rows="3" maxlength="500"><?= htmlspecialchars((string)($cancha['notas'] ?? '')) ?></textarea>
            </div>
            <hr class="my-4">
            <div class="text-end">
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fa-solid fa-save me-1"></i> Guardar cambios
                </button>
            </div>
        </form>
    </div>
</div>
