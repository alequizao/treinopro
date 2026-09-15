<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** App do aluno — dieta do dia */
exige('aluno');
$EU = (int) usuario()['id'];
$data = (string) get('d', date('Y-m-d'));
$dieta = um("SELECT * FROM tr_dietas WHERE aluno_id=? AND status='ativa' ORDER BY id DESC LIMIT 1", [$EU]);
$refs = $dieta ? todos('SELECT * FROM tr_dieta_refeicoes WHERE dieta_id=? ORDER BY ordem, id', [$dieta['id']]) : [];
$checks = [];
foreach (todos('SELECT refeicao_id FROM tr_dieta_check WHERE aluno_id=? AND data=?', [$EU, $data]) as $c) {
    $checks[(int) $c['refeicao_id']] = true;
}
$abrir = id_get('ref');
$dias = [];
for ($i = -3; $i <= 3; $i++) { $dias[] = date('Y-m-d', strtotime($data . " $i day")); }
$semana = ['Sun' => 'dom', 'Mon' => 'seg', 'Tue' => 'ter', 'Wed' => 'qua', 'Thu' => 'qui', 'Fri' => 'sex', 'Sat' => 'sáb'];

topo('Dieta do dia', $dieta ? $dieta['nome'] : '');
?>
<div class="dias-semana">
  <?php foreach ($dias as $d): ?>
    <a href="<?= url('minha-dieta', ['d' => $d]) ?>" class="<?= $d === $data ? 'ativo' : '' ?>">
      <strong><?= date('d', strtotime($d)) ?></strong>
      <small><?= $semana[date('D', strtotime($d))] ?></small>
    </a>
  <?php endforeach; ?>
</div>

<?php if (!$dieta) { vazio('Nenhuma dieta prescrita ainda.', 'fa-utensils'); } else { ?>
  <?php if ($dieta['obs']): ?>
    <div class="card-soft"><div class="ch"><i class="fa-solid fa-circle-info"></i> Orientações da dieta</div>
      <p class="mb-0" style="color:#B9BEC6;font-size:.9rem"><?= nl2br(e($dieta['obs'])) ?></p></div>
  <?php endif; ?>

  <?php foreach ($refs as $r):
      $itens = todos('SELECT * FROM tr_dieta_itens WHERE refeicao_id=? ORDER BY ordem, id', [$r['id']]);
      $kcal = 0; foreach ($itens as $i) { $kcal += (int) $i['kcal']; }
      $ok = !empty($checks[(int) $r['id']]); ?>
    <div class="linha-check <?= $ok ? 'ok' : '' ?>" id="lr<?= (int) $r['id'] ?>"
         onclick="abrirRef(<?= (int) $r['id'] ?>)" style="cursor:pointer">
      <button class="bola" onclick="event.stopPropagation();checar(<?= (int) $r['id'] ?>)"
              title="Marcar refeição"><i class="fa-solid fa-check"></i></button>
      <div class="flex-grow-1">
        <strong><?= e($r['nome']) ?></strong>
        <small style="display:block;color:#8B9099"><?= e($r['horario'] ?: '') ?> · <?= $kcal ?> kcal · <?= count($itens) ?> item(ns)</small>
      </div>
      <i class="fa-solid fa-chevron-down seta" id="sr<?= (int) $r['id'] ?>" style="color:#8B9099;transition:transform .25s"></i>
    </div>
    <div class="card-soft <?= $abrir === (int) $r['id'] ? '' : 'd-none' ?>" id="det<?= (int) $r['id'] ?>">
      <?php if (!$itens) { vazio('Sem alimentos cadastrados.'); } else { ?>
        <table class="tabela">
          <?php foreach ($itens as $i): ?>
            <tr>
              <td><strong><?= e($i['alimento']) ?></strong>
                <?php if ($i['substituto']): ?><br><small style="color:#8B9099">Sub.: <?= e($i['substituto']) ?></small><?php endif; ?></td>
              <td class="text-end"><?= e($i['quantidade'] ?: '') ?><br><small style="color:#8B9099"><?= (int) $i['kcal'] ?> kcal</small></td>
            </tr>
          <?php endforeach; ?>
        </table>
      <?php } ?>
    </div>
  <?php endforeach; ?>
<?php } ?>

<script>
/* abre/fecha os alimentos da refeição (linha inteira e seta são clicáveis) */
function abrirRef(id){
  var d = document.getElementById('det'+id), s = document.getElementById('sr'+id);
  var aberto = d.classList.toggle('d-none') === false;
  if (s) s.style.transform = aberto ? 'rotate(180deg)' : '';
  if (aberto) { vibrar(15); d.scrollIntoView({behavior:'smooth', block:'nearest'}); }
}
function checar(id){
  api('refeicao_check', {refeicao_id:id, data:<?= json_encode($data) ?>}).then(function(r){
    if(!r.ok){ flash(r.msg,'erro'); return; }
    var l = document.getElementById('lr'+id);
    l.classList.toggle('ok', r.marcado);
    if (r.marcado) { vibrar(30); l.classList.remove('pop'); void l.offsetWidth; l.classList.add('pop'); }
    var todas = document.querySelectorAll('.linha-check').length;
    if (todas && document.querySelectorAll('.linha-check.ok').length === todas) {
      confete(120); flash('Todas as refeições do dia marcadas! 🍽️'); vibrar([70,60,70]);
    }
  });
}
</script>
<?php rodape();
