<?php
/** App do aluno — evolução (avaliações, gráficos, anamnese) */
exige('aluno');
$EU = (int) usuario()['id'];
$avs = todos('SELECT * FROM tr_avaliacoes WHERE aluno_id=? ORDER BY data', [$EU]);
$sessoes = todos("SELECT data, COUNT(*) c FROM tr_sessoes WHERE aluno_id=? AND status='concluida'
                  AND data>=DATE_SUB(CURDATE(),INTERVAL 12 WEEK) GROUP BY data ORDER BY data", [$EU]);
$total = (int) valor("SELECT COUNT(*) FROM tr_sessoes WHERE aluno_id=? AND status='concluida'", [$EU]);
$an = um('SELECT * FROM tr_anamnese WHERE aluno_id=?', [$EU]);
$r = $an ? (json_decode((string) $an['respostas'], true) ?: []) : [];
$perguntas = [
    'saude' => 'Possui alguma doença/condição de saúde?', 'lesao' => 'Tem ou já teve alguma lesão?',
    'medicamento' => 'Usa medicamento contínuo?', 'dor' => 'Sente dores ao treinar?',
    'experiencia' => 'Há quanto tempo treina?', 'frequencia' => 'Quantos dias por semana pode treinar?',
    'local' => 'Onde vai treinar?', 'sono' => 'Quantas horas dorme por noite?',
    'alimentacao' => 'Como é a sua alimentação hoje?', 'restricao' => 'Restrição/alergia alimentar?',
    'suplemento' => 'Usa suplementos? Quais?', 'objetivo' => 'Qual seu principal objetivo?',
];
$ult = end($avs) ?: null;

topo('Evolução', 'Acompanhe seu progresso');
?>
<div class="row g-3 mb-2">
  <?php
  card_stat('Treinos concluídos', (string) $total, 'fa-dumbbell');
  card_stat('Peso atual', $ult && $ult['peso'] ? number_format((float) $ult['peso'], 1, ',', '') . ' kg' : '—', 'fa-weight-scale');
  card_stat('% Gordura', $ult && $ult['gordura'] ? number_format((float) $ult['gordura'], 1, ',', '') . '%' : '—', 'fa-percent');
  card_stat('Avaliações', (string) count($avs), 'fa-ruler');
  ?>
</div>

<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-chart-line"></i> Peso e gordura corporal</div>
  <?php if (!$avs) { vazio('Nenhuma avaliação registrada pelo seu treinador.', 'fa-ruler'); } else { ?>
    <canvas id="g1" height="180"></canvas>
  <?php } ?>
</div>

<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-fire"></i> Frequência de treinos (12 semanas)</div>
  <?php if (!$sessoes) { vazio('Sem treinos registrados ainda.', 'fa-dumbbell'); } else { ?>
    <canvas id="g2" height="160"></canvas>
  <?php } ?>
</div>

<?php if ($avs): ?>
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-ruler"></i> Histórico de avaliações</div>
  <div class="tabela-wrap"><table class="tabela">
    <thead><tr><th>Data</th><th>Peso</th><th>%G</th><th>Cintura</th><th>Braço D</th></tr></thead>
    <tbody>
    <?php foreach (array_reverse($avs) as $v): $md = json_decode((string) $v['medidas'], true) ?: []; ?>
      <tr>
        <td><?= data_br($v['data']) ?></td>
        <td><?= $v['peso'] ? number_format((float) $v['peso'], 1, ',', '') : '—' ?></td>
        <td><?= $v['gordura'] ? number_format((float) $v['gordura'], 1, ',', '') . '%' : '—' ?></td>
        <td><?= isset($md['cintura']) ? $md['cintura'] . ' cm' : '—' ?></td>
        <td><?= isset($md['braco_d']) ? $md['braco_d'] . ' cm' : '—' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</div>
<?php endif; ?>

<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-clipboard-question"></i> Questionário / anamnese</div>
  <p style="color:#8B9099;font-size:.88rem">Seu feedback é fundamental para o treinador ajustar seu plano.</p>
  <form data-acao="anamnese_salvar">
    <div class="row g-3">
      <?php foreach ($perguntas as $k => $p): ?>
        <div class="col-md-6"><label class="form-label"><?= e($p) ?></label>
          <input name="r[<?= $k ?>]" class="form-control" value="<?= e($r[$k] ?? '') ?>"></div>
      <?php endforeach; ?>
    </div>
    <div class="text-end mt-3"><button class="btn-acao">Enviar respostas</button></div>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.color='#8B9099'; Chart.defaults.borderColor='#26282E';
var avs=<?= json_encode(array_map(function ($v) {
    return ['d' => date('d/m', strtotime($v['data'])), 'p' => (float) $v['peso'], 'g' => (float) $v['gordura']]; }, $avs)) ?>;
if(avs.length && document.getElementById('g1')){
  new Chart(document.getElementById('g1'),{type:'line',
    data:{labels:avs.map(function(a){return a.d}),datasets:[
      {label:'Peso (kg)',data:avs.map(function(a){return a.p}),borderColor:'#2D7FF9',tension:.35},
      {label:'% Gordura',data:avs.map(function(a){return a.g}),borderColor:'#F26522',tension:.35}]},
    options:{plugins:{legend:{position:'bottom'}}}});
}
var ss=<?= json_encode(array_map(function ($s) {
    return ['d' => date('d/m', strtotime($s['data'])), 'c' => (int) $s['c']]; }, $sessoes)) ?>;
if(ss.length && document.getElementById('g2')){
  new Chart(document.getElementById('g2'),{type:'bar',
    data:{labels:ss.map(function(s){return s.d}),datasets:[{label:'Treinos',data:ss.map(function(s){return s.c}),backgroundColor:'#157347'}]},
    options:{plugins:{legend:{display:false}},scales:{y:{ticks:{stepSize:1}}}}});
}
</script>
<?php rodape();
