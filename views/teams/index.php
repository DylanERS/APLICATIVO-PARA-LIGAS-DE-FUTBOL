<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-shield-halved text-success me-2"></i> Equipos Registrados</h2>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>equipos/create" class="btn btn-primary fw-bold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> Nuevo Equipo
        </a>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] == 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-1"></i> Equipo registrado exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['msg'] == 'updated'): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-1"></i> Equipo actualizado exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa-trash fa-solid me-1"></i> Equipo eliminado del sistema.
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
                        <th width="50">#</th>
                        <th width="80">Logo</th>
                        <th>Nombre</th>
                        <th>Ciudad</th>
                        <th>Entrenador</th>
                        <th width="150" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($teams as $index => $team): ?>
                    <tr>
                        <td class="text-muted fw-bold"><?= $index + 1 ?></td>
                        <td>
                            <?php 
                                $logoPath = 'assets/img/' . htmlspecialchars($team['logo']);
                                if(!file_exists($logoPath) || empty($team['logo'])) {
                                    $logoPath = 'assets/img/default_logo.png';
                                }
                            ?>
                            <img src="<?= BASE_URL . $logoPath ?>" alt="Logo" class="rounded-circle" width="40" height="40" style="object-fit: cover; border: 2px solid #eee;">
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($team['nombre']) ?></td>
                        <td><?= htmlspecialchars($team['ciudad']) ?></td>
                        <td><?= htmlspecialchars($team['entrenador']) ?></td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>equipos/edit?id=<?= $team['id'] ?>" class="btn btn-sm btn-outline-info">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $team['id'] ?>)">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($teams)): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted">No hay equipos registrados aún.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function confirmDelete(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '<?= BASE_URL ?>equipos/delete?id=' + id;
            }
        })
    }
</script>
