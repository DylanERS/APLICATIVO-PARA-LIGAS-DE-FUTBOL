<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-users text-primary me-2"></i> Jugadores Registrados</h2>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>jugadores/create" class="btn btn-primary fw-bold shadow-sm">
            <i class="fa-solid fa-plus me-1"></i> Registrar Jugador
        </a>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] == 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-1"></i> Jugador registrado exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['msg'] == 'updated'): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-1"></i> Jugador actualizado exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa-trash fa-solid me-1"></i> Jugador eliminado del sistema.
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
                        <th width="80">Foto</th>
                        <th>Nombre</th>
                        <th>Equipo</th>
                        <th>Posición</th>
                        <th>Dorsal</th>
                        <th>Edad</th>
                        <th width="120" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($players as $player): ?>
                    <tr>
                        <td>
                            <?php 
                                $fotoPath = 'assets/img/players/' . htmlspecialchars($player['foto']);
                                if(!file_exists($fotoPath) || empty($player['foto'])) {
                                    $fotoPath = 'assets/img/default_player.png';
                                }
                            ?>
                            <img src="<?= BASE_URL . $fotoPath ?>" alt="Foto" class="rounded-circle" width="45" height="45" style="object-fit: cover; border: 2px solid #eee;">
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($player['nombre']) ?></td>
                        <td>
                            <?php if($player['equipo_nombre']): ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($player['equipo_nombre']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">Sin equipo</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($player['posicion']) ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($player['numero']) ?></td>
                        <td><?= htmlspecialchars($player['edad']) ?> años</td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>jugadores/edit?id=<?= $player['id'] ?>" class="btn btn-sm btn-outline-info">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= $player['id'] ?>)">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($players)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No hay jugadores registrados aún.</td></tr>
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
                window.location.href = '<?= BASE_URL ?>jugadores/delete?id=' + id;
            }
        })
    }
</script>
