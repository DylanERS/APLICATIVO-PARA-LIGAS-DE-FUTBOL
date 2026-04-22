<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-plus-circle text-primary me-2"></i> Registrar Equipo</h2>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>equipos" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver al listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="<?= BASE_URL ?>equipos/create" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nombre del Equipo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" required placeholder="Ej: Real Madrid">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Ciudad <span class="text-danger">*</span></label>
                    <input type="text" name="ciudad" class="form-control" required placeholder="Ej: Madrid">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Entrenador <span class="text-danger">*</span></label>
                    <input type="text" name="entrenador" class="form-control" required placeholder="Nombre del DT">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Logo (Opcional)</label>
                    <input type="file" name="logo" class="form-control" accept="image/*">
                    <div class="form-text">Formatos permitidos: JPG, PNG, GIF.</div>
                </div>
            </div>

            <hr class="my-4">
            
            <div class="text-end">
                <button type="reset" class="btn btn-light me-2">Limpiar</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fa-solid fa-save me-1"></i> Guardar Equipo
                </button>
            </div>
        </form>
    </div>
</div>
