<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-users-gear text-primary me-2"></i> Usuarios del Sistema</h2>
        <p class="text-muted small">Gestión de accesos para administradores y organizadores.</p>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>usuarios/create" class="btn btn-primary fw-bold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> Nuevo Usuario
        </a>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] == 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-1"></i> Usuario registrado exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['msg'] == 'updated'): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-1"></i> Usuario actualizado exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa-trash fa-solid me-1"></i> Usuario eliminado del sistema.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="50">ID</th>
                        <th>Nombre de Usuario</th>
                        <th>Rol</th>
                        <th>Fecha de Registro</th>
                        <th width="120" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td class="text-muted fw-bold"><?= $user['id'] ?></td>
                        <td class="fw-bold">
                            <i class="fa-solid fa-user-circle text-muted me-2"></i>
                            <?= htmlspecialchars($user['username']) ?>
                        </td>
                        <td>
                            <?php if($user['role'] == 'admin'): ?>
                                <span class="badge bg-danger"><i class="fa-solid fa-crown me-1"></i> Dueño de la Liga</span>
                            <?php elseif($user['role'] == 'director_tecnico'): ?>
                                <span class="badge bg-info text-dark"><i class="fa-solid fa-clipboard-user me-1"></i> Director técnico</span>
                                <?php if (!empty($user['equipo_nombre'])): ?>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars($user['equipo_nombre']) ?></div>
                                <?php endif; ?>
                            <?php elseif($user['role'] == 'arbitro'): ?>
                                <span class="badge bg-warning text-dark"><i class="fa-solid fa-flag me-1"></i> Árbitro</span>
                                <?php if (!empty($user['arbitro_nombre'])): ?>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars($user['arbitro_nombre']) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-primary">Organizador</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($user['created_at']))) ?></td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>usuarios/edit?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-info">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                            <a href="#" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $user['id'] ?>)">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-secondary" disabled title="No puedes eliminarte a ti mismo"><i class="fa-solid fa-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "El usuario perderá el acceso al sistema inmediatamente.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?= BASE_URL ?>usuarios/delete?id=' + id;
            }
        })
    }
</script>
