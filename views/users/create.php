<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-user-plus text-primary me-2"></i> Crear Usuario</h2>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>usuarios" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver al listado
        </a>
    </div>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'dt_team'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-exclamation me-1"></i> Un director técnico debe tener un equipo asignado.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'role_constraint'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-xmark me-1"></i>
        La tabla <code>usuarios</code> sigue con un <strong>CHECK</strong> de <code>role</code> desactualizado.
        Ejecuta en <strong>SQL Server</strong>:
        <code class="d-block mt-2 small">migrations/sqlserver_asistencia_foto_y_arbitro.sql</code>
        (incluye rol <code>arbitro</code>) o, si solo necesitas DT, <code>migrations/sqlserver_usuarios_rol_director_tecnico.sql</code>.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'db_error'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-xmark me-1"></i> Ocurrió un error al guardar el usuario. Si es al crear un DT, ejecuta
        <code>migrations/sqlserver_usuarios_rol_director_tecnico.sql</code> en la base <strong>LIGA_FUTBOL</strong>.
        Revisa también <code>errores.log</code>.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['msg']) && $_GET['msg'] === 'arbitro_ref'): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-exclamation me-1"></i> Un usuario árbitro debe estar vinculado a un registro en la tabla <strong>árbitros</strong>.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="<?= BASE_URL ?>usuarios/create" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nombre de Usuario <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" required placeholder="Ej: admin_liga">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Contraseña <span class="text-danger">*</span></label>
                    <input type="password" name="password" class="form-control" required placeholder="••••••••">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Rol en el sistema <span class="text-danger">*</span></label>
                    <select name="role" id="user_role" class="form-select" required>
                        <option value="organizador">Organizador (Gestión básica)</option>
                        <option value="admin">Dueño de la Liga (Acceso Total)</option>
                        <option value="director_tecnico">Director técnico (nómina de su equipo)</option>
                        <option value="arbitro">Árbitro (validar nómina en sus partidos)</option>
                    </select>
                </div>
                <div class="col-md-6 mb-3" id="wrap_equipo" style="display:none">
                    <label class="form-label fw-bold">Equipo del DT <span class="text-danger">*</span></label>
                    <select name="equipo_id" id="equipo_id" class="form-select">
                        <option value="0">— Seleccionar —</option>
                        <?php foreach ($teams ?? [] as $t): ?>
                            <option value="<?= (int)$t['id'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-3" id="wrap_arbitro" style="display:none">
                    <label class="form-label fw-bold">Árbitro (registro en liga) <span class="text-danger">*</span></label>
                    <select name="arbitro_id" id="arbitro_id" class="form-select">
                        <option value="0">— Seleccionar —</option>
                        <?php foreach ($referees ?? [] as $ar): ?>
                            <option value="<?= (int)$ar['id'] ?>"><?= htmlspecialchars($ar['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <hr class="my-4">
            
            <div class="text-end">
                <button type="submit" class="btn btn-primary fw-bold px-4">
                    <i class="fa-solid fa-save me-1"></i> Guardar Usuario
                </button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var role = document.getElementById('user_role');
    var wrap = document.getElementById('wrap_equipo');
    var eq = document.getElementById('equipo_id');
    var wrapAr = document.getElementById('wrap_arbitro');
    var ar = document.getElementById('arbitro_id');
    function sync() {
        var isDt = role && role.value === 'director_tecnico';
        var isAr = role && role.value === 'arbitro';
        if (wrap) wrap.style.display = isDt ? 'block' : 'none';
        if (eq) eq.required = !!isDt;
        if (wrapAr) wrapAr.style.display = isAr ? 'block' : 'none';
        if (ar) ar.required = !!isAr;
    }
    if (role) role.addEventListener('change', sync);
    sync();
})();
</script>
