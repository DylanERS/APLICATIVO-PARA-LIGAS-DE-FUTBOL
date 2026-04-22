<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-money-bill-trend-up text-warning me-2"></i> Finanzas y Tesorería</h2>
        <p class="text-muted small">Gestión de ingresos por pagos de equipos y multas aplicadas.</p>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <?php if($_GET['msg'] == 'pago_created'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-1"></i> Pago registrado exitosamente.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['msg'] == 'pago_invalid'): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-xmark me-1"></i> Datos de pago invalidos. Verifica el catalogo y los campos requeridos.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['msg'] == 'multa_created'): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-1"></i> Multa registrada y notificada al equipo.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php elseif($_GET['msg'] == 'multa_pagada'): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-check-circle me-1"></i> La multa ha sido marcada como pagada.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="row mb-4">
    <!-- Formulario Registro Pago -->
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm border-success h-100">
            <div class="card-header bg-success text-white fw-bold">
                <i class="fa-solid fa-hand-holding-dollar me-1"></i> Registrar Pago
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>finanzas" method="POST">
                    <input type="hidden" name="tipo_registro" value="pago">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Catalogo de Concepto</label>
                        <select name="catalogo_concepto" id="catalogo_concepto" class="form-select form-select-sm" required>
                            <option value="inscripcion">Inscripcion</option>
                            <option value="arbitraje">Arbitraje</option>
                            <option value="otros">Otros</option>
                        </select>
                    </div>

                    <div class="mb-3" id="bloque_temporada_pago">
                        <label class="form-label fw-bold small">Temporada/Torneo</label>
                        <select name="temporada_id" id="temporada_pago" class="form-select form-select-sm">
                            <option value="">Selecciona temporada...</option>
                            <?php foreach($seasons as $season): ?>
                                <option value="<?= (int)$season['id'] ?>">
                                    <?= htmlspecialchars($season['nombre']) ?> (<?= (int)$season['anio'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="bloque_partido_pago">
                        <label class="form-label fw-bold small">Partido (Arbitraje)</label>
                        <select name="partido_id" id="partido_pago" class="form-select form-select-sm">
                            <option value="">Selecciona partido...</option>
                            <?php foreach($matches as $match): ?>
                                <option
                                    value="<?= (int)$match['id'] ?>"
                                    data-local-id="<?= (int)$match['equipo_local_id'] ?>"
                                    data-local-name="<?= htmlspecialchars($match['equipo_local_nombre']) ?>"
                                    data-visitante-id="<?= (int)$match['equipo_visitante_id'] ?>"
                                    data-visitante-name="<?= htmlspecialchars($match['equipo_visitante_nombre']) ?>"
                                    data-temporada-id="<?= (int)$match['temporada_id'] ?>"
                                >
                                    #<?= (int)$match['id'] ?> - <?= htmlspecialchars($match['equipo_local_nombre']) ?> vs <?= htmlspecialchars($match['equipo_visitante_nombre']) ?> (<?= htmlspecialchars($match['temporada_nombre']) ?> <?= (int)$match['temporada_anio'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Equipo</label>
                        <select name="equipo_id" id="equipo_pago" class="form-select form-select-sm" required>
                            <option value="">Selecciona equipo...</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label fw-bold small">Concepto (Otros)</label>
                            <input type="text" name="concepto_otro" id="concepto_otro" class="form-control form-control-sm" placeholder="Ej: Uso de cancha extra">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-bold small">Monto ($)</label>
                            <input type="number" step="0.01" name="monto" class="form-control form-control-sm" required placeholder="0.00">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">Añadir Pago</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Formulario Registro Multa -->
    <div class="col-md-6 mb-3">
        <div class="card shadow-sm border-danger h-100">
            <div class="card-header bg-danger text-white fw-bold">
                <i class="fa-solid fa-gavel me-1"></i> Registrar Multa
            </div>
            <div class="card-body">
                <form action="<?= BASE_URL ?>finanzas" method="POST">
                    <input type="hidden" name="tipo_registro" value="multa">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Equipo Infractor</label>
                        <select name="equipo_id" class="form-select form-select-sm" required>
                            <option value="">Selecciona equipo...</option>
                            <?php foreach($teams as $team): ?>
                                <option value="<?= $team['id'] ?>"><?= htmlspecialchars($team['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-7 mb-3">
                            <label class="form-label fw-bold small">Motivo</label>
                            <input type="text" name="motivo" class="form-control form-control-sm" required placeholder="Ej: Conducta antideportiva">
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label fw-bold small">Monto ($)</label>
                            <input type="number" step="0.01" name="monto" class="form-control form-control-sm" required placeholder="0.00">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-danger btn-sm w-100 fw-bold">Aplicar Multa</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        const catalogo = document.getElementById('catalogo_concepto');
        const temporadaSelect = document.getElementById('temporada_pago');
        const partidoSelect = document.getElementById('partido_pago');
        const equipoSelect = document.getElementById('equipo_pago');
        const conceptoOtro = document.getElementById('concepto_otro');
        const bloqueTemporada = document.getElementById('bloque_temporada_pago');
        const bloquePartido = document.getElementById('bloque_partido_pago');

        const seasonTeamsMap = <?= json_encode($seasonTeamsMap, JSON_UNESCAPED_UNICODE) ?>;

        function clearOptions(select, placeholder) {
            select.innerHTML = '';
            const option = document.createElement('option');
            option.value = '';
            option.textContent = placeholder;
            select.appendChild(option);
        }

        function loadTeamsBySeason(seasonId) {
            clearOptions(equipoSelect, 'Selecciona equipo...');
            const teams = seasonTeamsMap[seasonId] || [];
            teams.forEach((team) => {
                const option = document.createElement('option');
                option.value = team.id;
                option.textContent = team.nombre;
                equipoSelect.appendChild(option);
            });
        }

        function loadTeamsByMatch() {
            clearOptions(equipoSelect, 'Selecciona equipo pagador...');
            const selected = partidoSelect.options[partidoSelect.selectedIndex];
            if (!selected || !selected.value) return;

            const localId = selected.getAttribute('data-local-id');
            const localName = selected.getAttribute('data-local-name');
            const visitanteId = selected.getAttribute('data-visitante-id');
            const visitanteName = selected.getAttribute('data-visitante-name');

            const option1 = document.createElement('option');
            option1.value = localId;
            option1.textContent = localName;
            equipoSelect.appendChild(option1);

            const option2 = document.createElement('option');
            option2.value = visitanteId;
            option2.textContent = visitanteName;
            equipoSelect.appendChild(option2);
        }

        function syncMode() {
            const mode = catalogo.value;
            conceptoOtro.required = false;
            temporadaSelect.required = false;
            partidoSelect.required = false;

            if (mode === 'inscripcion') {
                bloqueTemporada.classList.remove('d-none');
                bloquePartido.classList.add('d-none');
                temporadaSelect.required = true;
                loadTeamsBySeason(temporadaSelect.value);
            } else if (mode === 'arbitraje') {
                bloqueTemporada.classList.add('d-none');
                bloquePartido.classList.remove('d-none');
                partidoSelect.required = true;
                loadTeamsByMatch();
            } else {
                bloqueTemporada.classList.remove('d-none');
                bloquePartido.classList.add('d-none');
                temporadaSelect.required = true;
                conceptoOtro.required = true;
                loadTeamsBySeason(temporadaSelect.value);
            }
        }

        if (catalogo) catalogo.addEventListener('change', syncMode);
        if (temporadaSelect) temporadaSelect.addEventListener('change', function() {
            if (catalogo.value === 'inscripcion' || catalogo.value === 'otros') {
                loadTeamsBySeason(temporadaSelect.value);
            }
        });
        if (partidoSelect) partidoSelect.addEventListener('change', loadTeamsByMatch);

        syncMode();
    })();
</script>

<!-- Tabs para Listados -->
<ul class="nav nav-tabs mb-4" id="finanzasTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-bold text-success" id="pagos-tab" data-bs-toggle="tab" data-bs-target="#pagos" type="button" role="tab" aria-controls="pagos" aria-selected="true"><i class="fa-solid fa-list me-1"></i> Historial de Pagos</button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-bold text-danger" id="multas-tab" data-bs-toggle="tab" data-bs-target="#multas" type="button" role="tab" aria-controls="multas" aria-selected="false"><i class="fa-solid fa-list me-1"></i> Historial de Multas</button>
  </li>
</ul>

<div class="tab-content" id="finanzasTabsContent">
  <!-- Pestaña Pagos -->
  <div class="tab-pane fade show active" id="pagos" role="tabpanel" aria-labelledby="pagos-tab">
      <div class="card shadow-sm border-0">
          <div class="card-body">
              <table class="table table-hover datatable align-middle">
                  <thead class="table-light">
                      <tr>
                          <th>ID</th>
                          <th>Fecha</th>
                          <th>Equipo</th>
                          <th>Concepto</th>
                          <th class="text-end">Monto</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php foreach($pagos as $pago): ?>
                      <tr>
                          <td class="text-muted">#<?= $pago['id'] ?></td>
                          <td><?= date('d/m/Y H:i', strtotime($pago['fecha'])) ?></td>
                          <td class="fw-bold"><?= htmlspecialchars($pago['equipo_nombre']) ?></td>
                          <td><?= htmlspecialchars($pago['concepto']) ?></td>
                          <td class="text-end fw-bold text-success">$<?= number_format($pago['monto'], 2) ?></td>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
      </div>
  </div>
  
  <!-- Pestaña Multas -->
  <div class="tab-pane fade" id="multas" role="tabpanel" aria-labelledby="multas-tab">
      <div class="card shadow-sm border-0">
          <div class="card-body">
              <table class="table table-hover datatable align-middle">
                  <thead class="table-light">
                      <tr>
                          <th>ID</th>
                          <th>Fecha</th>
                          <th>Equipo</th>
                          <th>Motivo</th>
                          <th class="text-end">Monto</th>
                          <th class="text-center">Estado</th>
                          <th class="text-center">Acción</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php foreach($multas as $multa): ?>
                      <tr>
                          <td class="text-muted">#<?= $multa['id'] ?></td>
                          <td><?= date('d/m/Y H:i', strtotime($multa['fecha'])) ?></td>
                          <td class="fw-bold"><?= htmlspecialchars($multa['equipo_nombre']) ?></td>
                          <td><?= htmlspecialchars($multa['motivo']) ?></td>
                          <td class="text-end fw-bold text-danger">$<?= number_format($multa['monto'], 2) ?></td>
                          <td class="text-center">
                              <?php if($multa['estado'] == 'pagada'): ?>
                                <span class="badge bg-success">Pagada</span>
                              <?php else: ?>
                                <span class="badge bg-warning text-dark">Pendiente</span>
                              <?php endif; ?>
                          </td>
                          <td class="text-center">
                              <?php if($multa['estado'] == 'pendiente'): ?>
                              <form action="<?= BASE_URL ?>finanzas" method="POST" class="d-inline">
                                  <input type="hidden" name="tipo_registro" value="pagar_multa">
                                  <input type="hidden" name="multa_id" value="<?= $multa['id'] ?>">
                                  <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('¿Marcar esta multa como pagada?')">
                                      <i class="fa-solid fa-check"></i> Saldar
                                  </button>
                              </form>
                              <?php else: ?>
                                <button class="btn btn-sm btn-light" disabled><i class="fa-solid fa-lock"></i></button>
                              <?php endif; ?>
                          </td>
                      </tr>
                      <?php endforeach; ?>
                  </tbody>
              </table>
          </div>
      </div>
  </div>
</div>
