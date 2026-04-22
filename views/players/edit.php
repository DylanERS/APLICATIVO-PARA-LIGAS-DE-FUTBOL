<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-user-pen text-info me-2"></i> Editar Jugador</h2>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>jugadores" class="btn btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver al listado
        </a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form action="<?= BASE_URL ?>jugadores/edit?id=<?= $player['id'] ?>" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Nombre Completo <span class="text-danger">*</span></label>
                    <input type="text" name="nombre" class="form-control" required value="<?= htmlspecialchars($player['nombre']) ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">Equipo <span class="text-danger">*</span></label>
                    <select name="equipo_id" class="form-select" required>
                        <option value="">Seleccione un equipo...</option>
                        <?php foreach($teams as $team): ?>
                        <option value="<?= $team['id'] ?>" <?= $team['id'] == $player['equipo_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($team['nombre']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Posición <span class="text-danger">*</span></label>
                    <select name="posicion" class="form-select" required>
                        <option value="Portero" <?= $player['posicion'] == 'Portero' ? 'selected' : '' ?>>Portero</option>
                        <option value="Defensa" <?= $player['posicion'] == 'Defensa' ? 'selected' : '' ?>>Defensa</option>
                        <option value="Medio" <?= $player['posicion'] == 'Medio' ? 'selected' : '' ?>>Medio</option>
                        <option value="Delantero" <?= $player['posicion'] == 'Delantero' ? 'selected' : '' ?>>Delantero</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Dorsal (Número)</label>
                    <input type="number" name="numero" class="form-control" min="1" max="99" value="<?= htmlspecialchars($player['numero']) ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Edad</label>
                    <input type="number" name="edad" class="form-control" min="15" max="50" value="<?= htmlspecialchars($player['edad']) ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-bold">Fotografía (Opcional)</label>
                    <input type="file" name="foto" class="form-control" accept="image/*">
                    <div class="form-text">Si subes una nueva imagen, reemplazará a la actual.</div>
                    
                    <?php if($player['foto'] != 'default_player.png'): ?>
                    <div class="mt-2 text-muted small">
                        <strong>Foto actual:</strong> <?= htmlspecialchars($player['foto']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            $currentTeam = $currentTeam ?? null;
            $logoRel = 'assets/img/default_logo.png';
            if ($currentTeam && !empty($currentTeam['logo'])) {
                $tryLogo = 'assets/img/' . $currentTeam['logo'];
                if (file_exists($tryLogo)) {
                    $logoRel = $tryLogo;
                }
            }
            $fotoRel = 'assets/img/default_player.png';
            if (!empty($player['foto'])) {
                $tryFoto = 'assets/img/players/' . $player['foto'];
                if (file_exists($tryFoto)) {
                    $fotoRel = $tryFoto;
                }
            }
            $numeroCard = ($player['numero'] !== '' && $player['numero'] !== null && (int)$player['numero'] > 0)
                ? (string)(int)$player['numero']
                : '—';
            $numeroHash = $numeroCard !== '—' ? '#' . $numeroCard : '—';
            $teamNombreCard = $currentTeam['nombre'] ?? '—';
            $registroQrHref = $registroQrHref ?? null;
            $registroQrPartidoFecha = $registroQrPartidoFecha ?? null;
            $ligaNombreMarca = isset($ligaNombreMarca) && $ligaNombreMarca !== '' ? $ligaNombreMarca : 'LIGA';
            $registroQrImgSrc = $registroQrHref
                ? ('https://api.qrserver.com/v1/create-qr-code/?size=110x110&data=' . rawurlencode($registroQrHref))
                : null;
            ?>

            <div class="mt-4 mb-2">
                <label class="form-label fw-bold mb-2 no-print-registro">Vista previa · tarjeta de jugador</label>
                <p class="text-muted small mb-3 no-print-registro">Muestra los datos guardados del jugador. Usa <strong>Imprimir registro</strong> para obtener una copia.</p>

                <style id="registro-lmx-styles">
                    #registro-jugador-lmx {
                        --lmx-blue: #0d47a1;
                        --lmx-blue-light: #1565c0;
                        --lmx-accent: #0277bd;
                        width: fit-content;
                        max-width: 100%;
                        margin: 0 auto;
                        background: #fff;
                        border-radius: 12px;
                        border: 1px solid rgba(0,0,0,.08);
                        box-shadow: 0 8px 24px rgba(0,0,0,.1);
                        overflow: hidden;
                        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
                    }
                    #registro-jugador-lmx .lmx-inner {
                        display: flex;
                        flex-direction: row;
                        flex-wrap: nowrap;
                        align-items: stretch;
                        gap: 0;
                        min-height: 200px;
                    }
                    #registro-jugador-lmx .lmx-photo-wrap {
                        position: relative;
                        flex: 0 0 200px;
                        width: 200px;
                        min-width: 200px;
                        background: linear-gradient(180deg, #e3f2fd 0%, #bbdefb 100%);
                    }
                    #registro-jugador-lmx .lmx-photo-accent {
                        position: absolute;
                        left: 0;
                        top: 0;
                        bottom: 0;
                        width: 8px;
                        background: linear-gradient(180deg, var(--lmx-accent), var(--lmx-blue));
                        border-radius: 0 6px 6px 0;
                    }
                    #registro-jugador-lmx .lmx-photo-img {
                        position: absolute;
                        inset: 10px 10px 10px 14px;
                        border-radius: 10px;
                        overflow: hidden;
                        box-shadow: 0 4px 12px rgba(0,0,0,.15);
                    }
                    #registro-jugador-lmx .lmx-photo-img img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                        object-position: center top;
                        display: block;
                    }
                    #registro-jugador-lmx .lmx-badge-team {
                        position: absolute;
                        top: 8px;
                        left: 8px;
                        width: 52px;
                        height: 52px;
                        border-radius: 50%;
                        background: #fff;
                        border: 3px solid #fff;
                        box-shadow: 0 2px 8px rgba(0,0,0,.2);
                        overflow: hidden;
                        z-index: 2;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }
                    #registro-jugador-lmx .lmx-badge-team img {
                        width: 88%;
                        height: 88%;
                        object-fit: contain;
                    }
                    #registro-jugador-lmx .lmx-photo-caption {
                        position: absolute;
                        left: 18px;
                        right: 14px;
                        bottom: 10px;
                        font-size: 11px;
                        font-weight: 600;
                        color: #fff;
                        text-shadow: 0 1px 3px rgba(0,0,0,.85);
                        line-height: 1.2;
                        z-index: 1;
                    }
                    #registro-jugador-lmx .lmx-info {
                        flex: 0 0 auto;
                        display: flex;
                        flex-direction: column;
                        align-items: flex-end;
                        justify-content: center;
                        text-align: right;
                        padding: 14px 18px 14px 12px;
                        background: #fff;
                    }
                    #registro-jugador-lmx .lmx-name {
                        font-size: 1.35rem;
                        font-weight: 700;
                        color: var(--lmx-blue-light);
                        line-height: 1.15;
                        margin-bottom: 4px;
                    }
                    #registro-jugador-lmx .lmx-number {
                        font-size: 2.75rem;
                        font-weight: 800;
                        color: var(--lmx-blue);
                        line-height: 1;
                        letter-spacing: -0.02em;
                    }
                    #registro-jugador-lmx .lmx-pos {
                        font-size: 1.05rem;
                        color: #546e7a;
                        margin-top: 8px;
                        font-weight: 500;
                    }
                    #registro-jugador-lmx .lmx-meta {
                        margin-top: 10px;
                        padding-top: 0;
                        font-size: 0.75rem;
                        color: #90a4ae;
                        letter-spacing: .08em;
                    }
                    #registro-jugador-lmx .lmx-qr {
                        flex: 0 0 auto;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        padding: 12px 14px;
                        border-left: 1px solid #e8eaf0;
                        background: #fafbfd;
                        min-width: 132px;
                    }
                    #registro-jugador-lmx .lmx-qr-img {
                        display: block;
                        width: 110px;
                        height: 110px;
                    }
                    #registro-jugador-lmx .lmx-qr-cap {
                        display: block;
                        font-size: 10px;
                        font-weight: 600;
                        color: #546e7a;
                        text-align: center;
                        margin-top: 6px;
                        line-height: 1.25;
                        max-width: 120px;
                    }
                    #registro-jugador-lmx .lmx-qr-fecha {
                        font-weight: 500;
                        color: #78909c;
                        font-size: 9px;
                        margin-top: 4px;
                    }
                    #registro-jugador-lmx .lmx-qr-empty {
                        text-align: center;
                        line-height: 1.35;
                        max-width: 130px;
                    }
                    #registro-jugador-lmx .lmx-brand {
                        position: absolute;
                        right: 10px;
                        bottom: 4px;
                        left: 14px;
                        font-size: 9px;
                        font-weight: 700;
                        color: #fbc02d;
                        letter-spacing: .08em;
                        text-shadow: 0 0 1px rgba(0,0,0,.2);
                        text-align: right;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }
                    @media (max-width: 576px) {
                        #registro-jugador-lmx { width: 100%; }
                        #registro-jugador-lmx .lmx-inner { flex-direction: column; }
                        #registro-jugador-lmx .lmx-photo-wrap {
                            width: 100%;
                            min-width: 0;
                            max-width: none;
                            min-height: 200px;
                        }
                        #registro-jugador-lmx .lmx-info { align-items: center; text-align: center; }
                        #registro-jugador-lmx .lmx-qr {
                            border-left: none;
                            border-top: 1px solid #e8eaf0;
                            min-width: 0;
                            width: 100%;
                            padding: 16px;
                        }
                    }
                    @media print {
                        @page { margin: 8mm; size: landscape; }
                        html, body { margin: 0 !important; padding: 0 !important; }
                        #registro-jugador-lmx {
                            box-shadow: none !important;
                            border: 1px solid #ccc !important;
                            page-break-inside: avoid;
                            break-inside: avoid;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                    }
                </style>

                <div id="registro-jugador-lmx" class="position-relative">
                    <div class="lmx-inner">
                        <div class="lmx-photo-wrap">
                            <div class="lmx-photo-accent" aria-hidden="true"></div>
                            <div class="lmx-badge-team" title="<?= htmlspecialchars($teamNombreCard) ?>">
                                <img src="<?= htmlspecialchars(BASE_URL . $logoRel) ?>" alt="">
                            </div>
                            <div class="lmx-photo-img">
                                <img src="<?= htmlspecialchars(BASE_URL . $fotoRel) ?>" alt="<?= htmlspecialchars($player['nombre']) ?>">
                            </div>
                            <div class="lmx-photo-caption">
                                <?= htmlspecialchars($numeroCard) ?> · <?= htmlspecialchars($player['nombre']) ?>
                            </div>
                            <span class="lmx-brand"><?= htmlspecialchars($ligaNombreMarca) ?></span>
                        </div>
                        <div class="lmx-info">
                            <div class="lmx-name"><?= htmlspecialchars($player['nombre']) ?></div>
                            <div class="lmx-number"><?= htmlspecialchars($numeroHash) ?></div>
                            <div class="lmx-pos"><?= htmlspecialchars($player['posicion']) ?></div>
                            <div class="lmx-meta">
                                <?= $player['edad'] ? 'Edad ' . (int)$player['edad'] : '' ?>
                                <?= ($player['edad'] && $teamNombreCard !== '—') ? ' · ' : '' ?>
                                <?= $teamNombreCard !== '—' ? htmlspecialchars($teamNombreCard) : '' ?>
                            </div>
                        </div>
                        <div class="lmx-qr">
                            <?php if ($registroQrImgSrc): ?>
                                <img src="<?= htmlspecialchars($registroQrImgSrc) ?>" alt="QR asistencia" width="110" height="110" class="lmx-qr-img border rounded bg-white" loading="lazy">
                                <span class="lmx-qr-cap">Asistencia (DT)</span>
                                <?php if ($registroQrPartidoFecha): ?>
                                    <span class="lmx-qr-cap lmx-qr-fecha">Próximo partido: <?= htmlspecialchars($registroQrPartidoFecha) ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="lmx-qr-empty">Sin partido programado próximo. El QR de cada jugador también aparece en la pantalla de asistencia del partido.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3 no-print-registro">
                    <button type="button" class="btn btn-outline-primary fw-bold px-4" id="btn-print-registro-lmx">
                        <i class="fa-solid fa-print me-2"></i> Imprimir registro
                    </button>
                </div>
            </div>

            <style>
                @media print {
                    .no-print-registro { display: none !important; }
                }
            </style>

            <script>
            (function () {
                var btn = document.getElementById('btn-print-registro-lmx');
                if (!btn) return;
                btn.addEventListener('click', function () {
                    var card = document.getElementById('registro-jugador-lmx');
                    var styleEl = document.getElementById('registro-lmx-styles');
                    if (!card || !styleEl) {
                        window.print();
                        return;
                    }
                    var w = window.open('', '_blank');
                    if (!w) {
                        window.print();
                        return;
                    }
                    var html = '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Registro de jugador</title>';
                    html += '<style>' + styleEl.innerHTML + '</style>';
                    html += '<style>html,body{margin:0;padding:12px;background:#fff;}@media print{html,body{padding:0;}}</style>';
                    html += '</head><body>';
                    html += card.outerHTML;
                    html += '</body></html>';
                    w.document.open();
                    w.document.write(html);
                    w.document.close();
                    var done = function () {
                        try {
                            w.focus();
                            w.print();
                            w.close();
                        } catch (e) {}
                    };
                    if (w.document.readyState === 'complete') {
                        setTimeout(done, 250);
                    } else {
                        w.onload = function () { setTimeout(done, 250); };
                    }
                });
            })();
            </script>

            <hr class="my-4">
            
            <div class="text-end">
                <button type="submit" class="btn btn-info text-white fw-bold px-4">
                    <i class="fa-solid fa-save me-1"></i> Actualizar Jugador
                </button>
            </div>
        </form>
    </div>
</div>
