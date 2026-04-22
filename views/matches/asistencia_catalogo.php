<?php
$mode = $mode ?? 'grid';
$teamName = $team['nombre'] ?? 'Equipo';
$teamLogoRel = 'assets/img/default_logo.png';
if (!empty($team['logo']) && file_exists('assets/img/' . $team['logo'])) {
    $teamLogoRel = 'assets/img/' . $team['logo'];
}
$baseQs = 'season_id=' . (int)$season['id'] . '&match_id=' . (int)$match['id'] . '&equipo_id=' . (int)$team['id'];
?>

<div class="row align-items-center mb-3">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-id-card text-primary me-2"></i> Catálogo de asistencia</h2>
        <p class="text-muted small mb-0">
            <?= htmlspecialchars($teamName) ?> · <?= date('d/m/Y H:i', strtotime($match['fecha_hora'])) ?> ·
            Jugadores presentes: <strong><?= count($players) ?></strong>
        </p>
    </div>
    <div class="col-auto d-flex gap-2">
        <a href="<?= BASE_URL ?>partido/asistencia?season_id=<?= (int)$season['id'] ?>&match_id=<?= (int)$match['id'] ?>" class="btn btn-outline-secondary btn-sm">Volver a nómina</a>
    </div>
</div>

<div class="mb-3 d-flex gap-2 flex-wrap">
    <a href="<?= BASE_URL ?>partido/asistencia/catalogo?<?= $baseQs ?>&modo=grid" class="btn btn-sm <?= $mode === 'grid' ? 'btn-primary' : 'btn-outline-primary' ?>">Cuadrícula</a>
    <a href="<?= BASE_URL ?>partido/asistencia/catalogo?<?= $baseQs ?>&modo=carrusel" class="btn btn-sm <?= $mode === 'carrusel' ? 'btn-dark' : 'btn-outline-dark' ?>">Carrusel</a>
    <a href="<?= BASE_URL ?>partido/asistencia/catalogo?<?= $baseQs ?>&modo=fullscreen" class="btn btn-sm <?= $mode === 'fullscreen' ? 'btn-warning text-dark' : 'btn-outline-warning' ?>">Pantalla completa</a>
</div>

<?php if (empty($players)): ?>
    <div class="alert alert-light border">No hay jugadores presentes para mostrar.</div>
<?php else: ?>
    <?php if ($mode === 'fullscreen'): ?>
        <style>
            .cat-fs-wrap{background:#0b1220;border-radius:14px;padding:12px;color:#fff}
            .cat-fs-track{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;gap:12px}
            .cat-fs-item{flex:0 0 100%;scroll-snap-align:center}
            .cat-fs-card{background:#111827;border:1px solid #1f2937;border-radius:12px;min-height:78vh;display:flex;align-items:center;justify-content:center}
            .cat-fs-photo{max-height:260px;width:auto;border-radius:10px;border:1px solid #334155}
            .cat-fs-qr{width:240px;height:240px;border-radius:10px;background:#fff;border:1px solid #334155}
        </style>
        <div class="cat-fs-wrap">
            <div class="d-flex justify-content-center gap-2 mb-2">
                <button class="btn btn-outline-light btn-sm" onclick="catMove(-1)">Anterior</button>
                <button class="btn btn-outline-light btn-sm" onclick="catMove(1)">Siguiente</button>
            </div>
            <div class="cat-fs-track" id="cat-track" onscroll="catCounter()">
                <?php foreach ($players as $pl):
                    $jid = (int)$pl['id'];
                    $qrHref = MatchAttendanceController::buildQrConfirmacionArbitroUrl((int)$match['id'], (int)$season['id'], (int)$team['id'], $jid);
                    $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=' . rawurlencode($qrHref);
                    $fotoRel = 'assets/img/default_player.png';
                    if (!empty($pl['foto']) && file_exists('assets/img/players/' . $pl['foto'])) $fotoRel = 'assets/img/players/' . $pl['foto'];
                ?>
                <div class="cat-fs-item">
                    <div class="cat-fs-card">
                        <div class="text-center p-3" style="width:min(96vw,900px)">
                            <img src="<?= htmlspecialchars(BASE_URL . $teamLogoRel) ?>" alt="" width="54" height="54" class="rounded-circle border bg-white mb-2">
                            <div class="h3 fw-bold text-white mb-0"><?= htmlspecialchars($pl['nombre'] ?? '') ?></div>
                            <div class="small text-light mb-3">#<?= (int)($pl['numero'] ?? 0) ?> · <?= htmlspecialchars($teamName) ?></div>
                            <img src="<?= htmlspecialchars(BASE_URL . $fotoRel) ?>" alt="" class="cat-fs-photo mb-3">
                            <div><img src="<?= htmlspecialchars($qrImg) ?>" alt="QR árbitro" class="cat-fs-qr"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="small text-center text-light mt-2" id="cat-counter"></div>
        </div>
        <script>
        function catMove(step){var t=document.getElementById('cat-track');if(!t)return;var i=t.querySelector('.cat-fs-item');var d=i?(i.getBoundingClientRect().width+12):600;t.scrollBy({left:d*step,behavior:'smooth'});}
        function catCounter(){var t=document.getElementById('cat-track');var c=document.getElementById('cat-counter');if(!t||!c)return;var items=t.querySelectorAll('.cat-fs-item');if(!items.length)return;var idx=Math.round(t.scrollLeft/(items[0].getBoundingClientRect().width+12))+1;if(idx<1)idx=1;if(idx>items.length)idx=items.length;c.textContent='Ficha '+idx+' de '+items.length;}
        setTimeout(catCounter,60);
        </script>
    <?php elseif ($mode === 'carrusel'): ?>
        <style>
            .cat-c-track{display:flex;overflow-x:auto;scroll-snap-type:x mandatory;gap:14px;padding:6px 2px 8px}
            .cat-c-item{flex:0 0 min(88vw,560px);scroll-snap-align:center}
        </style>
        <div class="d-flex justify-content-center gap-2 mb-2">
            <button class="btn btn-outline-secondary btn-sm" onclick="catMove2(-1)">Anterior</button>
            <button class="btn btn-outline-secondary btn-sm" onclick="catMove2(1)">Siguiente</button>
        </div>
        <div class="cat-c-track" id="cat-track2">
            <?php foreach ($players as $pl):
                $jid = (int)$pl['id'];
                $qrHref = MatchAttendanceController::buildQrConfirmacionArbitroUrl((int)$match['id'], (int)$season['id'], (int)$team['id'], $jid);
                $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=170x170&data=' . rawurlencode($qrHref);
                $fotoRel = 'assets/img/default_player.png';
                if (!empty($pl['foto']) && file_exists('assets/img/players/' . $pl['foto'])) $fotoRel = 'assets/img/players/' . $pl['foto'];
            ?>
            <div class="cat-c-item">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <img src="<?= htmlspecialchars(BASE_URL . $teamLogoRel) ?>" alt="" width="48" height="48" class="rounded-circle border bg-white mb-2">
                        <div class="h5 fw-bold mb-0"><?= htmlspecialchars($pl['nombre'] ?? '') ?></div>
                        <div class="small text-muted mb-2">#<?= (int)($pl['numero'] ?? 0) ?> · <?= htmlspecialchars($teamName) ?></div>
                        <img src="<?= htmlspecialchars(BASE_URL . $fotoRel) ?>" alt="" class="rounded border mb-3" style="max-height:180px;width:auto">
                        <div><img src="<?= htmlspecialchars($qrImg) ?>" alt="QR árbitro" class="border rounded bg-white" width="170" height="170"></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <script>
        function catMove2(step){var t=document.getElementById('cat-track2');if(!t)return;var i=t.querySelector('.cat-c-item');var d=i?(i.getBoundingClientRect().width+14):420;t.scrollBy({left:d*step,behavior:'smooth'});}
        </script>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($players as $pl):
                $jid = (int)$pl['id'];
                $qrHref = MatchAttendanceController::buildQrConfirmacionArbitroUrl((int)$match['id'], (int)$season['id'], (int)$team['id'], $jid);
                $qrImg = 'https://api.qrserver.com/v1/create-qr-code/?size=130x130&data=' . rawurlencode($qrHref);
                $fotoRel = 'assets/img/default_player.png';
                if (!empty($pl['foto']) && file_exists('assets/img/players/' . $pl['foto'])) $fotoRel = 'assets/img/players/' . $pl['foto'];
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <img src="<?= htmlspecialchars(BASE_URL . $teamLogoRel) ?>" alt="" width="42" height="42" class="rounded-circle border bg-white mb-2">
                            <div class="fw-bold"><?= htmlspecialchars($pl['nombre'] ?? '') ?></div>
                            <div class="small text-muted mb-2">#<?= (int)($pl['numero'] ?? 0) ?> · <?= htmlspecialchars($teamName) ?></div>
                            <img src="<?= htmlspecialchars(BASE_URL . $fotoRel) ?>" alt="" class="rounded border mb-2" style="max-height:95px;width:auto">
                            <div><img src="<?= htmlspecialchars($qrImg) ?>" alt="QR árbitro" class="border rounded bg-white" width="130" height="130"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

