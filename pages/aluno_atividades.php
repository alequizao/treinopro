<?php
/** App do aluno — Atividades: estatísticas, regiões treinadas, recuperação e tipos de exercício */
exige('aluno');
$EU = (int) usuario()['id'];
$dias = (int) get('d', 7);
if (!in_array($dias, [7, 14, 28], true)) { $dias = 7; }

$st   = estatisticas_treino($EU, $dias);
$vol  = volume_por_grupo($EU, $dias);
$rec  = recuperacao_muscular($EU);
$tipos = tipos_exercicio($EU, $dias);

// % de séries por grupo (para o mapa "regiões mais treinadas")
$totSeries = array_sum(array_column($vol, 'series')) ?: 1;
$pctGrupo = [];
foreach ($vol as $g => $r) { $pctGrupo[$g] = round($r['series'] / $totSeries * 100, 1); }
arsort($pctGrupo);

// cores do mapa de volume: laranja com opacidade proporcional
$maxPct = max($pctGrupo ?: [1]);
$coresVol = cores_do_mapa($pctGrupo, function ($p) use ($maxPct) {
    if ($p <= 0) return null;
    $o = 0.35 + 0.65 * ($p / max($maxPct, 0.01));
    return 'rgba(255,122,15,' . round(min(1, $o), 2) . ')';
});
$coresRec = cores_do_mapa($rec, function ($r) {
    $c = cores_recuperacao();
    return $c[$r['estado']][0] ?? null;
});

// semana (dias com treino)
$semana = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $semana[] = ['data' => $d,
        'treinou' => (bool) valor("SELECT 1 FROM tr_sessoes WHERE aluno_id=? AND data=? AND status='concluida'", [$EU, $d])];
}
$diasBr = ['Sun' => 'dom.', 'Mon' => 'seg.', 'Tue' => 'ter.', 'Wed' => 'qua.', 'Thu' => 'qui.', 'Fri' => 'sex.', 'Sat' => 'sáb.'];
$horas = floor($st['segundos'] / 3600); $min = floor($st['segundos'] % 3600 / 60); $seg = $st['segundos'] % 60;

topo('Atividades', '');
?>
<div class="tit-ios"><h3>Estatísticas</h3></div>
<div class="seg">
  <?php foreach ([7 => '7 dias', 14 => '14 dias', 28 => '28 dias'] as $k => $lb): ?>
    <a href="<?= url('atividades', ['d' => $k]) ?>" class="<?= $dias === $k ? 'on' : '' ?>"><?= $lb ?></a>
  <?php endforeach; ?>
</div>

<p style="font-size:1.02rem;margin-bottom:16px">
  Você realizou <strong style="color:var(--ios-laranja)"><?= $st['treinos'] ?> treino<?= $st['treinos'] == 1 ? '' : 's' ?></strong>
  nos últimos <?= $dias ?> dias
</p>

<div class="metrica">
  <div class="txt"><small>Tempo de treino</small>
    <strong><?= sprintf('%02d:%02d:%02d', $horas, $min, $seg) ?></strong></div>
  <div><div class="lado">Últimos <?= $dias ?> dias</div>
    <?= sparkline(serie_diaria($EU, $dias, 'tempo')) ?></div>
</div>
<div class="metrica">
  <div class="txt"><small>Calorias</small>
    <strong data-conta="<?= $st['kcal'] ?>">0</strong> <span style="color:var(--ios-txt2)">kcal</span></div>
  <div><div class="lado">Últimos <?= $dias ?> dias</div>
    <?= sparkline(array_map(function ($m) { return $m * 7.5; }, serie_diaria($EU, $dias, 'tempo')), '#FF9F0A') ?></div>
</div>
<div class="metrica">
  <div class="txt"><small>Exercícios</small>
    <strong data-conta="<?= $st['exercicios'] ?>">0</strong></div>
  <div><div class="lado">Últimos <?= $dias ?> dias</div>
    <?= sparkline(serie_diaria($EU, $dias, 'series'), '#0A84FF') ?></div>
</div>

<div class="metricas-mini">
  <div class="m"><small>Séries</small><strong data-conta="<?= $st['series'] ?>">0</strong>
    <?= sparkline(serie_diaria($EU, $dias, 'series'), '#FF7A0F', 100, 34) ?></div>
  <div class="m"><small>Repetições</small><strong data-conta="<?= $st['reps'] ?>">0</strong>
    <?= sparkline(serie_diaria($EU, $dias, 'reps'), '#32D74B', 100, 34) ?></div>
  <div class="m"><small>Carga</small><strong data-conta="<?= (int) $st['carga'] ?>">0</strong>
    <?= sparkline(serie_diaria($EU, $dias, 'carga'), '#BF5AF2', 100, 34) ?></div>
</div>

<!-- ============ REGIÕES MAIS TREINADAS ============ -->
<div class="card-ios">
  <div class="ch">Regiões mais treinadas</div>
  <div class="sub">Últimos <?= $dias ?> dias</div>
  <?php if (!$pctGrupo) { vazio('Nenhum treino registrado no período.', 'fa-dumbbell'); } else { ?>
    <div class="mapa-corpo">
      <?= boneco('frente', $coresVol) ?>
      <?= boneco('costas', $coresVol) ?>
    </div>
    <div class="lista-ios" style="margin:0">
      <?php $i = 0; foreach ($pctGrupo as $g => $p): if ($i++ >= 6) break; ?>
        <div class="li"><span class="lbl"><?= e(mapa_grupos()[$g]['label'] ?? $g) ?></span>
          <span class="val"><?= number_format($p, 1, ',', '') ?><small>%</small></span></div>
      <?php endforeach; ?>
    </div>
  <?php } ?>
</div>

<!-- ============ RECUPERAÇÃO ============ -->
<div class="tit-ios"><h3>Recuperação</h3></div>
<div class="card-ios">
  <div class="mapa-corpo">
    <?= boneco('frente', $coresRec, '#4A4A4E') ?>
    <?= boneco('costas', $coresRec, '#4A4A4E') ?>
  </div>
  <div class="legenda">
    <?php foreach (cores_recuperacao() as $k => $c): ?>
      <span><i style="background:<?= $c[0] ?>"></i> <?= e($c[1]) ?></span>
    <?php endforeach; ?>
  </div>
  <div class="lista-ios mt-3" style="margin-bottom:0">
    <?php foreach ($rec as $g => $r):
        $c = cores_recuperacao()[$r['estado']]; ?>
      <div class="li"><span class="lbl"><?= e(mapa_grupos()[$g]['label'] ?? $g) ?></span>
        <span class="val" style="color:<?= $c[0] ?>;font-size:.86rem"><?= e($c[1]) ?>
          <?php if ($r['dias'] !== null): ?><small>· <?= $r['dias'] ?>d</small><?php endif; ?></span></div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ============ TIPOS DE EXERCÍCIO ============ -->
<div class="card-ios">
  <div class="ch">Tipos de exercícios</div>
  <div class="sub">Últimos <?= $dias ?> dias</div>
  <?php if (!$tipos) { vazio('Sem dados no período.', 'fa-chart-pie'); } else { ?>
    <canvas id="gTipos" height="210"></canvas>
  <?php } ?>
</div>

<!-- ============ HISTÓRICO DA SEMANA ============ -->
<div class="tit-ios"><h3>Histórico de treino</h3>
  <a href="<?= url('meu-treino') ?>">ver tudo</a></div>
<div class="semana-pills">
  <?php foreach ($semana as $d): $hoje = $d['data'] === date('Y-m-d'); ?>
    <div>
      <small><?= $diasBr[date('D', strtotime($d['data']))] ?></small>
      <b class="<?= $d['treinou'] ? 'treinou' : ($hoje ? 'hoje' : '') ?>"><?= date('j', strtotime($d['data'])) ?></b>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($tipos): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color = '#8E8E93';
var tp = <?= json_encode($tipos) ?>;
new Chart(document.getElementById('gTipos'), {
  type: 'pie',
  data: {
    labels: tp.map(function(t){ return t.tipo; }),
    datasets: [{ data: tp.map(function(t){ return +t.qtd; }),
      backgroundColor: ['#FF453A','#0A84FF','#FF9F0A','#FF375F','#64D2C7','#BF5AF2','#32D74B'],
      borderColor: '#1C1C1E', borderWidth: 2 }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 12 } } },
    tooltip: { callbacks: { label: function(c){
      var t = c.dataset.data.reduce(function(a,b){return a+b;},0);
      return c.label + ': ' + c.raw + ' séries (' + (c.raw/t*100).toFixed(1) + '%)'; } } } } }
});
</script>
<?php endif; ?>
<?php rodape();
