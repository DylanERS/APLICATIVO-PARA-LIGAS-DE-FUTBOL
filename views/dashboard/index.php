<div class="row mb-4">
    <div class="col-12">
        <h2 class="fw-bold">Resumen de la Liga</h2>
        <p class="text-muted">Bienvenido al panel general de estadísticas.</p>
    </div>
</div>

<div class="row">
    <!-- Card 1 -->
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">Equipos Registrados</h6>
                        <h2 class="mb-0 fw-bold"><?= $total_equipos ?></h2>
                    </div>
                    <div>
                        <i class="fa-solid fa-shield-halved fs-1 text-white-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link text-decoration-none" href="<?= BASE_URL ?>equipos">Ver Detalles</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <!-- Card 2 -->
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">Jugadores Activos</h6>
                        <h2 class="mb-0 fw-bold"><?= $total_jugadores ?></h2>
                    </div>
                    <div>
                        <i class="fa-solid fa-users fs-1 text-white-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link text-decoration-none" href="<?= BASE_URL ?>jugadores">Ver Detalles</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <!-- Card 3 -->
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">Partidos Jugados</h6>
                        <h2 class="mb-0 fw-bold"><?= $partidos_jugados ?></h2>
                    </div>
                    <div>
                        <i class="fa-solid fa-futbol fs-1 text-dark" style="opacity: 0.5;"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-dark stretched-link text-decoration-none" href="<?= BASE_URL ?>partidos-resultados">Ver Detalles</a>
                <div class="small text-dark"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <!-- Card 4 -->
    <div class="col-md-3 mb-4">
        <div class="card bg-danger text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">Entradas de Dinero</h6>
                        <h2 class="mb-0 fw-bold">$<?= number_format((float)$entradas_dinero, 2) ?></h2>
                    </div>
                    <div>
                        <i class="fa-solid fa-sack-dollar fs-1 text-white-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link text-decoration-none" href="<?= BASE_URL ?>finanzas">Ver Detalles</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Ultimo Partido (Resultado)</div>
                    <?php if (!empty($ultimo_resultado)): ?>
                        <div class="fw-bold fs-5">
                            <?= htmlspecialchars($ultimo_resultado['local_nombre']) ?>
                            <span class="badge bg-dark mx-1">
                                <?= (int)$ultimo_resultado['goles_local'] ?> - <?= (int)$ultimo_resultado['goles_visitante'] ?>
                            </span>
                            <?= htmlspecialchars($ultimo_resultado['visitante_nombre']) ?>
                        </div>
                        <div class="small text-muted">
                            <?php
                            $ts = !empty($ultimo_resultado['fin_real'])
                                ? $ultimo_resultado['fin_real']
                                : $ultimo_resultado['fecha_hora'];
                            echo date('d/m/Y H:i', strtotime($ts));
                            ?>
                        </div>
                    <?php else: ?>
                        <div class="fw-bold fs-6 text-muted">Aun no hay partidos finalizados.</div>
                    <?php endif; ?>
                </div>
                <i class="fa-solid fa-trophy fs-3 text-warning"></i>
            </div>
        </div>

        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <div class="text-muted small text-uppercase fw-bold">Temporada Activa</div>
                    <?php if (!empty($temporada_activa)): ?>
                        <div class="fw-bold fs-5">
                            <?= htmlspecialchars($temporada_activa['nombre']) ?> (<?= (int)$temporada_activa['anio'] ?>)
                        </div>
                    <?php else: ?>
                        <div class="fw-bold fs-5 text-muted">No hay temporada activa</div>
                    <?php endif; ?>
                </div>
                <a href="<?= BASE_URL ?>temporadas" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-calendar-days me-1"></i> Ver Temporadas
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header fw-bold bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="fa-solid fa-clock me-1"></i>
                    Partidos Proximos
                </div>
                <a href="<?= BASE_URL ?>partidos-resultados" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-list me-1"></i> Ver todos los partidos
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table text-center mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Local</th>
                            <th>Visitante</th>
                            <th>Resultado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($proximos_partidos)): ?>
                            <tr><td colspan="4" class="text-muted text-center pt-3 pb-3">No hay partidos proximos programados.</td></tr>
                        <?php else: ?>
                            <?php foreach($proximos_partidos as $partido): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($partido['fecha_hora'])) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($partido['local_nombre']) ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($partido['visitante_nombre']) ?></td>
                                <td>
                                    <span class="badge <?= $partido['estado'] === 'en curso' ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                        <?= htmlspecialchars(ucfirst($partido['estado'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
