<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-map-location-dot text-primary me-2"></i> Canchas</h2>
        <p class="text-muted small mb-0">Sedes donde se juegan los partidos de la liga.</p>
    </div>
    <div class="col-auto">
        <?php if (!empty($tabla_canchas_ok)): ?>
            <a href="<?= BASE_URL ?>canchas/create" class="btn btn-primary fw-bold shadow-sm">
                <i class="fa-solid fa-plus me-1"></i> Nueva cancha
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-1"></i> Cancha registrada.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php elseif ($_GET['msg'] === 'updated'): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-1"></i> Cancha actualizada.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php elseif ($_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-trash me-1"></i> Cancha eliminada.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php elseif ($_GET['msg'] === 'no_table'): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fa-solid fa-database me-1"></i> Falta crear la tabla <code>canchas</code> en la base de datos. Ejecute el script
            <code>migrations/sqlserver_canchas.sql</code> en SQL Server Management Studio.
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (empty($tabla_canchas_ok)): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body text-muted">No se encontró la tabla de canchas. Ejecute la migración indicada arriba.</div>
    </div>
<?php else: ?>
<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable align-middle">
                <thead class="table-light">
                    <tr>
                        <th width="50">#</th>
                        <th>Nombre</th>
                        <th>Dirección</th>
                        <th>Estado</th>
                        <th width="150" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($canchas as $index => $c): ?>
                    <tr>
                        <td class="text-muted fw-bold"><?= $index + 1 ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($c['nombre']) ?></td>
                        <td><?= $c['direccion'] !== null && $c['direccion'] !== '' ? htmlspecialchars($c['direccion']) : '—' ?></td>
                        <td>
                            <?php if ((int)($c['activa'] ?? 0) === 1): ?>
                                <span class="badge bg-success">Activa</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactiva</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>canchas/edit?id=<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-info">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                            <a href="#" class="btn btn-sm btn-outline-danger" onclick="confirmDelete(<?= (int)$c['id'] ?>); return false;">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($canchas)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No hay canchas registradas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    if (confirm('¿Eliminar esta cancha?')) {
        window.location.href = '<?= BASE_URL ?>canchas/delete?id=' + id;
    }
}
</script>
<?php endif; ?>
