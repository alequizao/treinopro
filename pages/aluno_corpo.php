<?php
/** App do aluno — Corpo: Peso, Medidas e Avançado (composição corporal) */
exige('aluno');
$EU  = (int) usuario()['id'];
$u   = um('SELECT * FROM tr_usuarios WHERE id=?', [$EU]);
$aba = (string) get('aba', 'peso');

$avs  = todos('SELECT * FROM tr_avaliacoes WHERE aluno_id=? ORDER BY data', [$EU]);
$ult  = $avs ? end($avs) : null;
$med  = $ult ? (json_decode((string) $ult['medidas'], true) ?: []) : [];
$dob  = $ult ? (json_decode((string) $ult['dobras'], true) ?: []) : [];
$peso = $ult ? (float) $ult['peso'] : null;
$alt  = $ult ? (float) $ult['altura'] : null;
$gord = $ult ? (float) $ult['gordura'] : null;
$metaPeso = $u['meta_peso'] !== null ? (float) $u['meta_peso'] : null;

$i    = imc($peso, $alt);
$cls  = classificacao_imc($i);
$ideal = peso_ideal($alt);
$comp = composicao($peso, $gord, array_filter([
    'massa_gorda' => $ult['massa_gorda'] ?? null, 'massa_magra' => $ult['massa_magra'] ?? null,
    'massa_ossea' => $ult['massa_ossea'] ?? null, 'massa_residual' => $ult['massa_residual'] ?? null,
    'massa_muscular' => $ult['massa_muscular'] ?? null,
], function ($v) { return $v !== null && $v !== ''; }));

topo('Corpo', '');
?>
<div class="seg">
  <?php foreach (['peso' => 'Peso', 'medidas' => 'Medidas', 'avancado' => 'Avançado'] as $k => $lb): ?>
    <a href="<?= url('corpo', ['aba' => $k]) ?>" class="<?= $aba === $k ? 'on' : '' ?>"><?= $lb ?></a>
  <?php endforeach; ?>
</div>

<?php if ($aba === 'peso'): ?>
  <div class="tit-ios"><h3>Peso</h3><button data-modal="mMed">Adicionar</button></div>
  <div class="card-ios">
    <div class="row text-center mb-3">
      <div class="col-4">
        <small style="color:var(--ios-txt2);font-size:.85rem">
          <i class="fa-solid fa-circle" style="color:var(--ios-laranja);font-size:.5rem;vertical-align:middle"></i> Atual</small>
        <div style="font-size:1.7rem;font-weight:800;color:var(--ios-laranja)">
          <?= $peso ? number_format($peso, 1, ',', '') : '--' ?><small style="font-size:.85rem"> kg</small></div>
      </div>
      <div class="col-4">
        <small style="color:var(--ios-txt2);font-size:.85rem">
          <i class="fa-solid fa-circle" style="color:var(--ios-azul);font-size:.5rem;vertical-align:middle"></i> Meta</small>
        <div style="font-size:1.7rem;font-weight:800;color:var(--ios-azul)">
          <?= $metaPeso ? number_format($metaPeso, 1, ',', '') : '--' ?><small style="font-size:.85rem"> kg</small></div>
      </div>
      <div class="col-4">
        <small style="color:var(--ios-txt2);font-size:.85rem">Diferença</small>
        <div style="font-size:1.7rem;font-weight:800;color:#fff">
          <?= ($peso && $metaPeso) ? ($peso - $metaPeso > 0 ? '−' : '+') . number_format(abs($peso - $metaPeso), 1, ',', '') : '--' ?>
          <small style="font-size:.85rem;color:var(--ios-txt2)"> kg</small></div>
      </div>
    </div>
    <?php if (count($avs) >= 1): ?><canvas id="gPeso" height="180"></canvas>
    <?php else: ?><?php vazio('Registre seu peso para ver o gráfico.', 'fa-weight-scale'); ?><?php endif; ?>
  </div>

  <div class="tit-ios"><h3>Diagnóstico</h3></div>
  <div class="card-ios">
    <div class="row text-center">
      <div class="col-4"><small style="color:var(--ios-txt2)">IMC</small>
        <div style="font-size:1.6rem;font-weight:800;color:<?= $cls[1] ?>"><?= $i ? number_format($i, 1, ',', '') : '--' ?></div>
        <small style="color:<?= $cls[1] ?>;font-size:.72rem"><?= e($cls[0]) ?></small></div>
      <div class="col-4"><small style="color:var(--ios-txt2)">Gordura</small>
        <div style="font-size:1.6rem;font-weight:800;color:var(--ios-verde)">
          <?= $gord ? number_format($gord, 1, ',', '') : '--' ?><small style="font-size:.8rem"> %</small></div></div>
      <div class="col-4"><small style="color:var(--ios-txt2)">Peso ideal</small>
        <div style="font-size:1.6rem;font-weight:800;color:var(--ios-verde)">
          <?= $ideal ? $ideal[0] . '-' . $ideal[1] : '--' ?><small style="font-size:.8rem"> kg</small></div></div>
    </div>
  </div>

  <div class="lista-ios">
    <div class="li"><span class="lbl">Minha meta de peso</span>
      <span class="val"><?= $metaPeso ? number_format($metaPeso, 1, ',', '') . ' kg' : '—' ?></span></div>
    <form data-acao="perfil_meta" class="li" style="gap:8px">
      <input name="meta_peso" type="number" step="0.1" class="form-control form-control-sm"
             placeholder="Definir meta (kg)" value="<?= e($metaPeso) ?>" style="max-width:150px">
      <button class="btn-acao" style="padding:7px 14px">Salvar</button>
    </form>
  </div>

<?php elseif ($aba === 'medidas'): ?>
  <div class="tit-ios"><h3>Medidas</h3><button data-modal="mMed">Adicionar</button></div>
  <div class="lista-ios">
    <?php foreach (campos_medidas() as $k => $lb):
        $v = $med[$k] ?? null;
        // variação em relação à avaliação anterior
        $ant = null;
        if (count($avs) > 1) { $a = json_decode((string) $avs[count($avs) - 2]['medidas'], true) ?: []; $ant = $a[$k] ?? null; }
        $dif = ($v !== null && $ant !== null) ? round($v - $ant, 1) : null; ?>
      <div class="li <?= $v === null ? 'vazia' : '' ?>">
        <span class="lbl"><?= e($lb) ?></span>
        <?php if ($dif !== null && $dif != 0): ?>
          <small style="color:<?= $dif > 0 ? 'var(--ios-verde)' : 'var(--ios-vermelho)' ?>;font-weight:700">
            <?= $dif > 0 ? '+' : '' ?><?= number_format($dif, 1, ',', '') ?></small>
        <?php endif; ?>
        <span class="val <?= $v === null ? 'off' : '' ?>">
          <?= $v !== null ? number_format((float) $v, 1, ',', '') : '--' ?><small>cm</small></span>
      </div>
    <?php endforeach; ?>
  </div>
  <?php if ($ult): ?>
    <p style="color:var(--ios-txt2);font-size:.83rem;text-align:center">
      Última medição em <?= data_br($ult['data']) ?></p>
  <?php endif; ?>

<?php else: ?>
  <div class="tit-ios"><h3>Geral</h3><button data-modal="mMed">Adicionar</button></div>
  <div class="lista-ios">
    <div class="li"><span class="lbl">IMC</span>
      <span class="val <?= $i ? '' : 'off' ?>"><?= $i ? number_format($i, 1, ',', '') : '--' ?><small>kg/m²</small></span></div>
    <div class="li"><span class="lbl">Taxa de Gordura</span>
      <span class="val <?= $gord ? '' : 'off' ?>"><?= $gord ? number_format($gord, 1, ',', '') : '--' ?><small>%</small></span></div>
    <div class="li"><span class="lbl">Altura</span>
      <span class="val <?= $alt ? '' : 'off' ?>"><?= $alt ? number_format($alt, 0, ',', '') : '--' ?><small>cm</small></span></div>
  </div>

  <div class="tit-ios"><h3>Composição Corporal</h3></div>
  <div class="lista-ios">
    <?php foreach (['massa_gorda' => 'Massa Gorda', 'massa_magra' => 'Massa Magra',
                    'massa_residual' => 'Massa Residual', 'massa_ossea' => 'Massa Óssea',
                    'massa_muscular' => 'Massa Muscular'] as $k => $lb): $v = $comp[$k]; ?>
      <div class="li <?= $v === null ? 'vazia' : '' ?>"><span class="lbl"><?= $lb ?></span>
        <span class="val <?= $v === null ? 'off' : '' ?>">
          <?= $v !== null ? number_format((float) $v, 1, ',', '') : '--' ?><small>kg</small></span></div>
    <?php endforeach; ?>
  </div>

  <div class="tit-ios"><h3>Dobras Cutâneas</h3></div>
  <div class="lista-ios">
    <?php foreach (campos_dobras() as $k => $lb): $v = $dob[$k] ?? null; ?>
      <div class="li <?= $v === null ? 'vazia' : '' ?>"><span class="lbl"><?= $lb ?></span>
        <span class="val <?= $v === null ? 'off' : '' ?>">
          <?= $v !== null ? number_format((float) $v, 1, ',', '') : '--' ?><small>mm</small></span></div>
    <?php endforeach; ?>
  </div>
  <p style="color:var(--ios-txt2);font-size:.83rem;text-align:center">
    As dobras cutâneas são preenchidas pelo seu treinador na avaliação física.</p>
<?php endif; ?>

<!-- modal: aluno registra a própria medição -->
<div class="modal-bg" id="mMed"><div class="modal-cx">
  <button class="fechar" data-fecha="mMed"><i class="fa-solid fa-xmark"></i></button>
  <h3>Nova medição</h3>
  <form data-acao="medicao_aluno">
    <div class="row g-3">
      <div class="col-4"><label class="form-label">Data</label>
        <input name="data" type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      <div class="col-4"><label class="form-label">Peso (kg)</label>
        <input name="peso" type="number" step="0.1" class="form-control" value="<?= e($peso) ?>"></div>
      <div class="col-4"><label class="form-label">Altura (cm)</label>
        <input name="altura" type="number" step="0.1" class="form-control" value="<?= e($alt) ?>"></div>
      <div class="col-12"><hr style="border-color:var(--ios-borda)">
        <strong style="font-size:.8rem;color:var(--ios-txt2)">MEDIDAS (cm) — opcional</strong></div>
      <?php foreach (campos_medidas() as $k => $lb): ?>
        <div class="col-6 col-md-4"><label class="form-label"><?= e($lb) ?></label>
          <input name="m_<?= $k ?>" type="number" step="0.1" class="form-control form-control-sm"
                 value="<?= e($med[$k] ?? '') ?>"></div>
      <?php endforeach; ?>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="button" class="btn-outline-soft" data-fecha="mMed">Cancelar</button>
      <button class="btn-acao">Salvar medição</button>
    </div>
  </form>
</div></div>

<?php if ($aba === 'peso' && $avs): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#8E8E93';
var pts = <?= json_encode(array_map(function ($a) {
    return ['d' => date('d M', strtotime($a['data'])), 'p' => (float) $a['peso']]; }, $avs)) ?>;
var meta = <?= json_encode($metaPeso) ?>;
new Chart(document.getElementById('gPeso'), {
  type: 'line',
  data: { labels: pts.map(function(p){return p.d}), datasets: [
    { label:'Peso', data: pts.map(function(p){return p.p}), borderColor:'#FF7A0F',
      backgroundColor:'rgba(255,122,15,.15)', fill:true, tension:.35, pointRadius:3 },
    meta ? { label:'Meta', data: pts.map(function(){return meta}), borderColor:'#0A84FF',
      borderWidth:3, pointRadius:0, fill:false } : null
  ].filter(Boolean) },
  options: { plugins:{legend:{display:false}},
    scales:{ y:{ grid:{color:'#2C2C2E'} }, x:{ grid:{display:false} } } }
});
</script>
<?php endif; ?>
<?php rodape();
