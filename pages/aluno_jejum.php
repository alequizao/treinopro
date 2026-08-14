<?php
/** App do aluno — cronômetro de jejum intermitente com fases biológicas */
exige('aluno');
$EU = (int) usuario()['id'];
$u  = um('SELECT * FROM tr_usuarios WHERE id=?', [$EU]);
$j  = jejum_ativo($EU);
$horas = $j ? (time() - strtotime($j['inicio'])) / 3600 : 0;
$fase  = fase_atual($horas);
$fases = fases_jejum();

$hist = todos("SELECT * FROM tr_jejum WHERE aluno_id=? AND status<>'andamento' ORDER BY inicio DESC LIMIT 20", [$EU]);
$total   = (int) valor("SELECT COUNT(*) FROM tr_jejum WHERE aluno_id=? AND status='concluido'", [$EU]);
$melhor  = (float) (valor("SELECT MAX(horas) FROM tr_jejum WHERE aluno_id=? AND status='concluido'", [$EU]) ?: 0);
$media   = (float) (valor("SELECT AVG(horas) FROM tr_jejum WHERE aluno_id=? AND status='concluido'", [$EU]) ?: 0);
$semana  = (int) valor("SELECT COUNT(*) FROM tr_jejum WHERE aluno_id=? AND status='concluido'
                        AND YEARWEEK(inicio,1)=YEARWEEK(CURDATE(),1)", [$EU]);

$semRefresh = true;
topo('Jejum intermitente', $j ? 'Jejum em andamento' : 'Escolha um protocolo e comece');
?>
<?php if ($j): ?>
  <!-- ================= EM JEJUM ================= -->
  <div class="card-soft text-center">
    <div class="anel-box">
      <svg width="230" height="230">
        <circle cx="115" cy="115" r="100" class="anel-trilho"></circle>
        <circle cx="115" cy="115" r="100" class="anel-prog" id="anelJejum" style="stroke:<?= e($fase[3]) ?>"></circle>
      </svg>
      <div class="anel-centro">
        <span class="tempo" id="tempo">00:00:00</span>
        <small>de <?= number_format((float) $j['meta_horas'], 0) ?>h (<?= e($j['protocolo']) ?>)</small>
        <span class="fase" id="faseTxt" style="color:<?= e($fase[3]) ?>">
          <i class="fa-solid <?= e($fase[2]) ?>"></i> <?= e($fase[1]) ?></span>
      </div>
    </div>

    <p style="color:#8B9099;font-size:.87rem;margin:16px 0 4px" id="faseDesc"><?= e($fase[4]) ?></p>
    <p style="color:#8B9099;font-size:.82rem">
      Começou <?= date('d/m \à\s H:i', strtotime($j['inicio'])) ?> ·
      previsão de término <strong style="color:#fff"><?= date('H:i', strtotime($j['inicio']) + (int) ((float) $j['meta_horas'] * 3600)) ?></strong>
    </p>

    <button class="btn-redondo" data-click="jejum_encerrar" id="btnEncerrar">
      <i class="fa-solid fa-flag-checkered"></i> Encerrar jejum</button>
    <button class="btn-redondo parar mt-2" data-click="jejum_cancelar"
            data-confirma="Cancelar o jejum? Ele não vai contar no histórico.">Cancelar</button>
  </div>
<?php else: ?>
  <!-- ================= INICIAR ================= -->
  <div class="card-soft">
    <div class="ch"><i class="fa-solid fa-hourglass-start"></i> Iniciar um jejum</div>
    <form data-acao="jejum_iniciar">
      <label class="form-label">Protocolo</label>
      <div class="row g-2 mb-3">
        <?php foreach (protocolos_jejum() as $k => $p): ?>
          <div class="col-6 col-md-3">
            <label class="proto-op">
              <input type="radio" name="protocolo" value="<?= e($k) ?>" hidden
                     <?= ($u['protocolo_jejum'] ?: '16:8') === $k ? 'checked' : '' ?>>
              <strong><?= e($p[0]) ?></strong>
              <small><?= (int) $p[1] ?>h de jejum</small>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <label class="form-label">Comecei em</label>
      <input type="datetime-local" name="inicio" class="form-control mb-3" value="<?= date('Y-m-d\TH:i') ?>">
      <button class="btn-redondo"><i class="fa-solid fa-play"></i> Começar jejum</button>
    </form>
  </div>
<?php endif; ?>

<!-- ================= FASES ================= -->
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-timeline"></i> O que acontece no seu corpo</div>
  <?php foreach ($fases as $i => $f):
      $prox = $fases[$i + 1][0] ?? null;
      $eAtual = $j && $horas >= $f[0] && ($prox === null || $horas < $prox);
      $futura = !$j || $horas < $f[0]; ?>
    <div class="fase-item <?= $eAtual ? 'atual' : ($futura ? 'futura' : '') ?>">
      <span class="fi" style="background:<?= e($f[3]) ?>"><i class="fa-solid <?= e($f[2]) ?>"></i></span>
      <div class="flex-grow-1">
        <strong><?= e($f[1]) ?> <?= $eAtual ? '<span class="chip">agora</span>' : '' ?></strong>
        <small><?= e($f[4]) ?></small>
      </div>
      <span class="h"><?= (int) $f[0] ?>h<?= $prox ? '–' . (int) $prox . 'h' : '+' ?></span>
    </div>
  <?php endforeach; ?>
</div>

<!-- ================= ESTATÍSTICAS ================= -->
<div class="row g-3 mb-2">
  <?php
  card_stat('Jejuns concluídos', (string) $total, 'fa-hourglass-end');
  card_stat('Melhor marca', $melhor ? number_format($melhor, 1, ',', '') . 'h' : '—', 'fa-trophy', '#D98E0B');
  card_stat('Média', $media ? number_format($media, 1, ',', '') . 'h' : '—', 'fa-chart-simple', '#2D7FF9');
  card_stat('Esta semana', (string) $semana, 'fa-calendar-week', '#1E9E57');
  ?>
</div>

<!-- ================= HISTÓRICO ================= -->
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-clock-rotate-left"></i> Histórico</div>
  <?php if (!$hist) { vazio('Nenhum jejum registrado ainda.', 'fa-hourglass-half'); } else { ?>
    <table class="tabela">
      <?php foreach ($hist as $h):
          $f = fase_atual((float) $h['horas']);
          $bateu = $h['status'] === 'concluido' && (float) $h['horas'] >= (float) $h['meta_horas']; ?>
        <tr>
          <td><span class="fi" style="background:<?= e($f[3]) ?>;width:34px;height:34px;border-radius:10px;
                display:inline-grid;place-items:center;color:#fff;font-size:.8rem">
                <i class="fa-solid <?= e($f[2]) ?>"></i></span></td>
          <td><strong><?= number_format((float) $h['horas'], 1, ',', '') ?>h</strong>
            <small style="display:block;color:#8B9099"><?= e($h['protocolo']) ?> ·
              <?= date('d/m H:i', strtotime($h['inicio'])) ?></small></td>
          <td class="text-end">
            <?= $h['status'] === 'cancelado' ? badge('Cancelado', 'erro') : ($bateu ? badge('Meta batida', 'ok') : badge('Parcial', 'pendente')) ?>
            <button class="btn-icone perigo" data-click="jejum_excluir" data-dados='{"id":<?= (int) $h['id'] ?>}'
                    data-confirma="Excluir este registro?"><i class="fa-solid fa-trash"></i></button>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php } ?>
</div>

<style>
.proto-op{display:block;text-align:center;padding:12px 6px;border-radius:14px;background:#101114;
  border:1.5px solid #2A2C33;cursor:pointer;transition:.15s}
.proto-op strong{display:block;font-size:1.05rem;font-weight:800}
.proto-op small{color:#8B9099;font-size:.72rem}
.proto-op:has(input:checked){border-color:var(--cor-primaria);background:rgba(242,101,34,.12)}
.proto-op:active{transform:scale(.96)}
</style>

<?php if ($j): ?>
<script>
var inicio = <?= json_encode(strtotime($j['inicio']) * 1000) ?>,
    metaH  = <?= json_encode((float) $j['meta_horas']) ?>,
    FASES  = <?= json_encode(array_map(function ($f) {
                  return ['h' => $f[0], 'nome' => $f[1], 'icone' => $f[2], 'cor' => $f[3], 'desc' => $f[4]]; }, $fases)) ?>,
    faseAnterior = null;

function tick(){
  var s = Math.floor((Date.now() - inicio) / 1000);
  var h = Math.floor(s/3600), m = Math.floor(s%3600/60), sec = s%60;
  document.getElementById('tempo').textContent =
    (h<10?'0':'')+h+':'+(m<10?'0':'')+m+':'+(sec<10?'0':'')+sec;

  var horas = s/3600;
  anel('anelJejum', Math.min(100, horas / metaH * 100));

  var f = FASES[0];
  FASES.forEach(function(x){ if (horas >= x.h) f = x; });
  document.getElementById('faseTxt').innerHTML = '<i class="fa-solid '+f.icone+'"></i> '+f.nome;
  document.getElementById('faseTxt').style.color = f.cor;
  document.getElementById('faseDesc').textContent = f.desc;
  document.getElementById('anelJejum').style.stroke = f.cor;

  // avisa quando muda de fase
  if (faseAnterior && faseAnterior !== f.nome) {
    flash('Nova fase do jejum: ' + f.nome + '!');
    confete(70); vibrar([60,80,60]);
  }
  faseAnterior = f.nome;
}
tick(); setInterval(tick, 1000);

// confete ao encerrar batendo a meta
document.getElementById('btnEncerrar').addEventListener('click', function(){
  if ((Date.now() - inicio)/3600000 >= metaH) { setTimeout(function(){ confete(140); }, 250); }
});
</script>
<?php endif; ?>
<?php rodape();
