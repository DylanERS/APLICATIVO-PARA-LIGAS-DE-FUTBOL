<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-user-plus text-primary me-2"></i> Registrar Jugador</h2>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>jugadores" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver al listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="<?= BASE_URL ?>jugadores/create" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" required placeholder="Ej: Lionel Messi">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Equipo <span class="text-danger">*</span></label>
                    <select name="equipo_id" class="form-select" required>
                        <option value="">Seleccione un equipo...</option>
                        <?php foreach($teams as $team): ?>
                        <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Posición <span class="text-danger">*</span></label>
                    <select name="posicion" class="form-select" required>
                        <option value="Portero">Portero</option>
                        <option value="Defensa">Defensa</option>
                        <option value="Medio">Medio</option>
                        <option value="Delantero">Delantero</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Dorsal (Número)</label>
                    <input type="number" name="numero" class="form-control" min="1" max="99" placeholder="10">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Edad</label>
                    <input type="number" name="edad" class="form-control" min="15" max="50" placeholder="25">
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Fotografía (Opcional)</label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                    <div class="form-text">Formatos permitidos: JPG, PNG, GIF. Idealmente proporciones 1:1.</div>
                </div>
            </div>

            <hr class="my-4">
            
            <div class="text-end">
                <button type="reset" class="btn btn-light me-2">Limpiar</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fa-solid fa-save me-1"></i> Guardar Jugador
                </button>
            </div>
        </form>
    </div>
</div>
