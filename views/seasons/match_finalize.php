<?php
$pl = $playersLocal ?? [];
$pv = $playersVisit ?? [];
$ln = $localTeamName ?? '';
$vn = $visitTeamName ?? '';
$error = $error ?? null;

function match_finalize_player_options($players, $selected = 0) {
    $html = '<option value="">— Jugador —</option>';
    foreach ($players as $p) {
        $id = (int)($p['id'] ?? 0);
        if ($id <= 0) {
            continue;
        }
        $sel = $id === (int)$selected ? ' selected' : '';
        $num = (int)($p['numero'] ?? 0);
        $label = htmlspecialchars($p['nombre'] ?? '') . ($num > 0 ? ' #' . $num : '');
        $html .= '<option value="' . $id . '"' . $sel . '>' . $label . '</option>';
    }
    return $html;
}
?>
<style>
    .content-area .match-finalize-wrap .table > thead > tr > th,
    .content-area .match-finalize-wrap .table > tbody > tr > td { text-align: left !important; vertical-align: middle !important; }
</style>
<div class="row align-items-center mb-4">
    <div class="col">
        <h2 class="fw-bold mb-0"><i class="fa-solid fa-clipboard-list text-danger me-2"></i> Finalizar partido — estadísticas</h2>
        <p class="text-muted small mb-0">
            Registre goles, tarjetas (con causal) e informe del árbitro. El marcador se calcula con los goles anotados.
        </p>
    </div>
    <div class="col-auto">
        <a href="<?= BASE_URL ?>temporadas/show?id=<?= (int)($season['id'] ?? 0) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Volver
        </a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0 mb-3">
    <div class="card-body small">
        <strong><?= htmlspecialchars($season['nombre'] ?? '') ?></strong> —
        <?= date('d/m/Y H:i', strtotime($match['fecha_hora'])) ?> —
        <span class="fw-semibold"><?= htmlspecialchars($match['equipo_local_nombre'] ?? $ln) ?></span>
        vs
        <span class="fw-semibold"><?= htmlspecialchars($match['equipo_visitante_nombre'] ?? $vn) ?></span>
    </div>
</div>

<form method="post" class="match-finalize-wrap" action="<?= BASE_URL ?>temporadas/match/finalizar?season_id=<?= (int)$season['id'] ?>&match_id=<?= (int)$match['id'] ?>" id="form-finalizar-partido">
    <input type="hidden" name="season_id" value="<?= (int)$season['id'] ?>">
    <input type="hidden" name="match_id" value="<?= (int)$match['id'] ?>">

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white fw-bold py-3">
            <i class="fa-solid fa-futbol text-success me-2"></i>Goles
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Jugador</th>
                            <th style="width:7rem">Minuto</th>
                            <th style="width:4rem"></th>
                        </tr>
                    </thead>
                    <tbody id="gol-rows">
                        <tr class="gol-row">
                            <td>
                                <select name="gol_jugador[]" class="form-select form-select-sm">
                                    <optgroup label="<?= htmlspecialchars($ln) ?>">
                                        <?= match_finalize_player_options($pl) ?>
                                    </optgroup>
                                    <optgroup label="<?= htmlspecialchars($vn) ?>">
                                        <?= match_finalize_player_options($pv) ?>
                                    </optgroup>
                                </select>
                            </td>
                            <td><input type="number" name="gol_minuto[]" class="form-control form-control-sm" min="0" max="130" placeholder="0" value=""></td>
                            <td><button type="button" class="btn btn-sm btn-outline-secondary btn-del-gol" title="Quitar fila"><i class="fa-solid fa-times"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                <button type="button" class="btn btn-sm btn-outline-success" id="btn-add-gol"><i class="fa-solid fa-plus me-1"></i> Agregar gol</button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white fw-bold py-3">
            <i class="fa-solid fa-square text-warning me-1"></i><i class="fa-solid fa-square text-danger me-2"></i>Tarjetas amarillas y rojas
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Jugador</th>
                            <th style="width:8rem">Tipo</th>
                            <th style="width:7rem">Minuto</th>
                            <th>Causal / motivo</th>
                            <th style="width:4rem"></th>
                        </tr>
                    </thead>
                    <tbody id="tarj-rows">
                        <tr class="tarj-row">
                            <td>
                                <select name="tarj_jugador[]" class="form-select form-select-sm">
                                    <optgroup label="<?= htmlspecialchars($ln) ?>">
                                        <?= match_finalize_player_options($pl) ?>
                                    </optgroup>
                                    <optgroup label="<?= htmlspecialchars($vn) ?>">
                                        <?= match_finalize_player_options($pv) ?>
                                    </optgroup>
                                </select>
                            </td>
                            <td>
                                <select name="tarj_tipo[]" class="form-select form-select-sm">
                                    <option value="amarilla">Amarilla</option>
                                    <option value="roja">Roja</option>
                                </select>
                            </td>
                            <td><input type="number" name="tarj_minuto[]" class="form-control form-control-sm" min="0" max="130" placeholder="0"></td>
                            <td><input type="text" name="tarj_motivo[]" class="form-control form-control-sm" placeholder="Ej. conducta antideportiva" maxlength="500"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-secondary btn-del-tarj"><i class="fa-solid fa-times"></i></button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top">
                <button type="button" class="btn btn-sm btn-outline-warning" id="btn-add-tarj"><i class="fa-solid fa-plus me-1"></i> Agregar tarjeta</button>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white fw-bold py-3">
            <i class="fa-solid fa-file-lines text-primary me-2"></i>Informe del árbitro (polémica u observaciones)
        </div>
        <div class="card-body">
            <textarea name="informe_arbitro" class="form-control" rows="4" placeholder="Opcional: incidentes, decisiones controvertidas, notas del partido."><?= isset($post['informe_arbitro']) ? htmlspecialchars((string)$post['informe_arbitro']) : '' ?></textarea>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>temporadas/show?id=<?= (int)$season['id'] ?>" class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-danger fw-bold px-4">
            <i class="fa-solid fa-flag-checkered me-1"></i> Guardar estadísticas y cerrar partido
        </button>
    </div>
</form>

<template id="tpl-gol-row">
    <tr class="gol-row">
        <td>
            <select name="gol_jugador[]" class="form-select form-select-sm">
                <optgroup label="<?= htmlspecialchars($ln) ?>">
                    <?= match_finalize_player_options($pl) ?>
                </optgroup>
                <optgroup label="<?= htmlspecialchars($vn) ?>">
                    <?= match_finalize_player_options($pv) ?>
                </optgroup>
            </select>
        </td>
        <td><input type="number" name="gol_minuto[]" class="form-control form-control-sm" min="0" max="130" placeholder="0"></td>
        <td><button type="button" class="btn btn-sm btn-outline-secondary btn-del-gol"><i class="fa-solid fa-times"></i></button></td>
    </tr>
</template>

<template id="tpl-tarj-row">
    <tr class="tarj-row">
        <td>
            <select name="tarj_jugador[]" class="form-select form-select-sm">
                <optgroup label="<?= htmlspecialchars($ln) ?>">
                    <?= match_finalize_player_options($pl) ?>
                </optgroup>
                <optgroup label="<?= htmlspecialchars($vn) ?>">
                    <?= match_finalize_player_options($pv) ?>
                </optgroup>
            </select>
        </td>
        <td>
            <select name="tarj_tipo[]" class="form-select form-select-sm">
                <option value="amarilla">Amarilla</option>
                <option value="roja">Roja</option>
            </select>
        </td>
        <td><input type="number" name="tarj_minuto[]" class="form-control form-control-sm" min="0" max="130" placeholder="0"></td>
        <td><input type="text" name="tarj_motivo[]" class="form-control form-control-sm" placeholder="Causal obligatoria si elige jugador" maxlength="500"></td>
        <td><button type="button" class="btn btn-sm btn-outline-secondary btn-del-tarj"><i class="fa-solid fa-times"></i></button></td>
    </tr>
</template>

<script>
(function () {
    var gBody = document.getElementById('gol-rows');
    var tBody = document.getElementById('tarj-rows');
    var tplG = document.getElementById('tpl-gol-row');
    var tplT = document.getElementById('tpl-tarj-row');
    document.getElementById('btn-add-gol').addEventListener('click', function () {
        gBody.appendChild(tplG.content.cloneNode(true));
    });
    document.getElementById('btn-add-tarj').addEventListener('click', function () {
        tBody.appendChild(tplT.content.cloneNode(true));
    });
    gBody.addEventListener('click', function (e) {
        if (e.target.closest('.btn-del-gol')) {
            var tr = e.target.closest('tr');
            if (tr && gBody.querySelectorAll('tr.gol-row').length > 1) tr.remove();
        }
    });
    tBody.addEventListener('click', function (e) {
        if (e.target.closest('.btn-del-tarj')) {
            var tr = e.target.closest('tr');
            if (tr && tBody.querySelectorAll('tr.tarj-row').length > 1) tr.remove();
        }
    });
})();
</script>
