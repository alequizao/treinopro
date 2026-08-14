<?php
/** App do aluno — executar o treino (marcar séries, carga, reps, cronômetro) */
exige('aluno');
$EU = (int) usuario()['id'];
$semRefresh = true;   // tela com cronômetro: não sofre auto-refresh
$dia = um('SELECT d.*, f.nome AS ficha, f.aluno_id FROM tr_ficha_dias d
           JOIN tr_fichas f ON f.id=d.ficha_id WHERE d.id=? AND f.aluno_id=?', [id_get('dia'), $EU]);
if (!$dia) { topo('Treino'); vazio('Treino não encontrado.'); rodape(); exit; }

$itens = todos('SELECT x.*, e.nome AS ex_nome, e.grupo, e.video_url, e.video_arquivo, e.instrucoes
                FROM tr_ficha_exercicios x JOIN tr_exercicios e ON e.id=x.exercicio_id
                WHERE x.dia_id=? ORDER BY x.ordem, x.id', [$dia['id']]);

// sessão de hoje (cria ao abrir)
$s = um("SELECT * FROM tr_sessoes WHERE aluno_id=? AND dia_id=? AND data=CURDATE() AND status='andamento'", [$EU, $dia['id']]);
if (!$s) {
    $sid = inserir('tr_sessoes', ['tenant_id' => (int) usuario()['tenant_id'], 'aluno_id' => $EU,
        'dia_id' => (int) $dia['id'], 'data' => date('Y-m-d'), 'inicio_em' => date('Y-m-d H:i:s')]);
    $s = um('SELECT * FROM tr_sessoes WHERE id=?', [$sid]);
}
$feitas = [];
foreach (todos('SELECT * FROM tr_sessao_series WHERE sessao_id=?', [$s['id']]) as $r) {
    $feitas[$r['ficha_exercicio_id'] . '_' . $r['serie_num']] = $r;
}
// última carga usada em cada exercício (histórico)
$ultimas = [];
foreach ($itens as $x) {
    $u = um("SELECT ss.carga, ss.reps FROM tr_sessao_series ss
             JOIN tr_sessoes se ON se.id=ss.sessao_id
             WHERE ss.ficha_exercicio_id=? AND se.aluno_id=? AND se.id<>? AND ss.concluida=1
             ORDER BY ss.id DESC LIMIT 1", [$x['id'], $EU, $s['id']]);
    if ($u) { $ultimas[$x['id']] = $u; }
}

topo($dia['nome'], $dia['ficha'], true);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <span class="chip"><?= count($itens) ?> exercícios</span>
  <div class="d-flex align-items-center gap-2">
    <span id="tempoTotal" style="font-weight:700">00:00</span>
    <button class="btn-acao" data-modal="mFim"><i class="fa-solid fa-flag-checkered"></i> Concluir</button>
  </div>
</div>

<?php if (!$itens) { vazio('Nenhum exercício cadastrado neste treino.', 'fa-dumbbell'); } ?>

<?php foreach ($itens as $i => $x):
    $nSeries = max(1, (int) preg_replace('/\D/', '', $x['series']) ?: 3);
    $okCount = 0;
    for ($n = 1; $n <= $nSeries; $n++) { if (!empty($feitas[$x['id'] . '_' . $n]['concluida'])) { $okCount++; } } ?>
  <div class="ex-card <?= $okCount === $nSeries ? 'feito' : '' ?>" id="ex<?= (int) $x['id'] ?>">
    <div class="ex-topo">
      <span class="n"><?= $i + 1 ?></span>
      <div class="flex-grow-1">
        <h4><?= e($x['ex_nome']) ?></h4>
        <small><?= e($x['grupo']) ?> · <?= e($x['series']) ?>x<?= e($x['repeticoes']) ?>
          <?= $x['descanso'] ? ' · descanso ' . e($x['descanso']) : '' ?></small>
        <?php if ($x['tecnica']): ?><br><span class="chip"><?= e($x['tecnica']) ?></span><?php endif; ?>
        <?php if ($x['obs']): ?><br><small style="color:var(--cor-primaria)"><i class="fa-solid fa-circle-info"></i> <?= e($x['obs']) ?></small><?php endif; ?>
        <?php if (!empty($ultimas[$x['id']])): ?>
          <br><small style="color:#8B9099">Última vez: <?= e($ultimas[$x['id']]['carga'] ?: '—') ?>
            × <?= e($ultimas[$x['id']]['reps'] ?: '—') ?> reps</small>
        <?php endif; ?>
      </div>
      <?php $vid = $x['video_arquivo'] ? BASE_DIR . '/' . $x['video_arquivo'] : $x['video_url'];
            if ($vid): ?>
        <button class="btn-outline-soft" data-video="<?= e($vid) ?>" data-titulo="<?= e($x['ex_nome']) ?>">
          <i class="fa-solid fa-play"></i></button>
      <?php endif; ?>
    </div>
    <div class="series-grid">
      <?php for ($n = 1; $n <= $nSeries; $n++):
          $f = $feitas[$x['id'] . '_' . $n] ?? null;
          $ok = !empty($f['concluida']); ?>
        <div class="serie <?= $ok ? 'ok' : '' ?>" id="s<?= (int) $x['id'] ?>_<?= $n ?>">
          <label><?= $n ?>ª série</label>
          <input placeholder="kg" value="<?= e($f['carga'] ?? ($x['carga'] ?? '')) ?>" id="c<?= (int) $x['id'] ?>_<?= $n ?>">
          <input placeholder="reps" value="<?= e($f['reps'] ?? '') ?>" id="r<?= (int) $x['id'] ?>_<?= $n ?>">
          <button class="check" onclick="marcar(<?= (int) $x['id'] ?>,<?= $n ?>,<?= (int) $nSeries ?>)">
            <?= $ok ? '✓ feita' : 'marcar' ?></button>
        </div>
      <?php endfor; ?>
    </div>
  </div>
<?php endforeach; ?>

<div class="card-soft text-center">
  <div class="ch justify-content-center"><i class="fa-solid fa-stopwatch"></i> Descanso</div>
  <div class="cronometro" id="crono">00:00</div>
  <div class="d-flex gap-2 justify-content-center mt-2">
    <button class="btn-outline-soft" onclick="iniciarDescanso(30)">30s</button>
    <button class="btn-outline-soft" onclick="iniciarDescanso(60)">60s</button>
    <button class="btn-outline-soft" onclick="iniciarDescanso(90)">90s</button>
    <button class="btn-acao" onclick="pararDescanso()"><i class="fa-solid fa-stop"></i></button>
  </div>
</div>

<div class="modal-bg" id="mFim"><div class="modal-cx">
  <button class="fechar" data-fecha="mFim"><i class="fa-solid fa-xmark"></i></button>
  <h3>Concluir treino</h3>
  <form id="formFim">
    <input type="hidden" name="sessao_id" value="<?= (int) $s['id'] ?>">
    <label class="form-label">Como foi o treino?</label>
    <select name="nota" class="form-select mb-3">
      <option value="5">⭐⭐⭐⭐⭐ Excelente</option><option value="4">⭐⭐⭐⭐ Bom</option>
      <option value="3">⭐⭐⭐ Normal</option><option value="2">⭐⭐ Fraco</option><option value="1">⭐ Ruim</option>
    </select>
    <label class="form-label">Observações para o treinador</label>
    <textarea name="feedback" class="form-control" rows="3" placeholder="Senti dor no ombro, aumentei a carga no supino..."></textarea>
    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="button" class="btn-outline-soft" data-fecha="mFim">Voltar</button>
      <button class="btn-acao">Finalizar treino</button>
    </div>
  </form>
</div></div>

<script>
var SESSAO = <?= (int) $s['id'] ?>;
function marcar(fex, n, total){
  var box = document.getElementById('s'+fex+'_'+n);
  var ok  = !box.classList.contains('ok');
  api('serie_salvar', {sessao_id:SESSAO, ficha_exercicio_id:fex, serie_num:n,
      carga:document.getElementById('c'+fex+'_'+n).value,
      reps:document.getElementById('r'+fex+'_'+n).value, concluida: ok?'1':'0'})
    .then(function(r){
      if(!r.ok){ flash(r.msg,'erro'); return; }
      box.classList.toggle('ok', ok);
      box.querySelector('.check').innerHTML = ok ? '<i class="fa-solid fa-check"></i> feita' : 'marcar';
      var card = document.getElementById('ex'+fex);
      var todasOk = card.querySelectorAll('.serie.ok').length === total;
      card.classList.toggle('feito', todasOk);
      if (ok) {
        vibrar(35);
        iniciarDescanso(60);
        if (todasOk) { card.classList.remove('pop'); void card.offsetWidth; card.classList.add('pop'); }
      }
      if (r.recorde) { flash('Novo recorde de carga! 🏆'); confete(110); vibrar([60,70,60,70,120]); }
      conquista(r.conquistas);
    });
}
// cronômetro de descanso
var td=null, restante=0;
function pinta(){ var m=Math.floor(restante/60), s=restante%60;
  document.getElementById('crono').textContent = (m<10?'0':'')+m+':'+(s<10?'0':'')+s; }
function iniciarDescanso(seg){
  clearInterval(td); restante=seg; pinta();
  td=setInterval(function(){ restante--; pinta();
    if(restante<=0){ clearInterval(td); flash('Descanso concluído! Bora.'); if(navigator.vibrate) navigator.vibrate(400); } },1000);
}
function pararDescanso(){ clearInterval(td); restante=0; pinta(); }
// concluir treino com celebração antes de sair
document.getElementById('formFim').addEventListener('submit', function(ev){
  ev.preventDefault();
  var b = ev.target.querySelector('button[type=submit],.btn-acao');
  if (b) { b.disabled = true; b.textContent = 'Salvando...'; }
  api('sessao_concluir', new FormData(ev.target)).then(function(r){
    if(!r.ok){ flash(r.msg,'erro'); if(b){b.disabled=false;b.textContent='Finalizar treino';} return; }
    fecharModal('mFim');
    confete(170); vibrar([80,60,80,60,200]);
    flash('Treino concluído! +50 XP' + (r.streak > 1 ? ' · ' + r.streak + ' dias seguidos 🔥' : ''));
    conquista(r.conquistas);
    setTimeout(function(){ location.href = <?= json_encode(url('inicio')) ?>; }, r.conquistas && r.conquistas.length ? 3200 : 1900);
  }).catch(function(){ flash('Falha de rede.','erro'); if(b){b.disabled=false;} });
});

// tempo total de treino
var inicio = new Date(<?= json_encode(strtotime($s['inicio_em'] ?: 'now') * 1000) ?>);
setInterval(function(){
  var d = Math.floor((Date.now()-inicio.getTime())/1000);
  var m = Math.floor(d/60), s = d%60;
  document.getElementById('tempoTotal').textContent = (m<10?'0':'')+m+':'+(s<10?'0':'')+s;
}, 1000);
</script>
<?php rodape();
