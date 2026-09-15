<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** App do aluno — programa de treino (fases, semanas e dias) */
exige('aluno');
$EU = (int) usuario()['id'];
$fichas = todos('SELECT * FROM tr_fichas WHERE aluno_id=? ORDER BY status, id DESC', [$EU]);
$fid = id_get('ficha') ?: (int) ($fichas[0]['id'] ?? 0);
$ficha = null;
foreach ($fichas as $f) { if ((int) $f['id'] === $fid) { $ficha = $f; } }

$dias = $ficha ? todos("SELECT d.*,
    (SELECT COUNT(*) FROM tr_ficha_exercicios x WHERE x.dia_id=d.id) AS qtd,
    (SELECT MAX(s.data) FROM tr_sessoes s WHERE s.dia_id=d.id AND s.aluno_id=? AND s.status='concluida') AS ultimo,
    (SELECT COUNT(*) FROM tr_sessoes s WHERE s.dia_id=d.id AND s.aluno_id=? AND s.status='concluida'
      AND YEARWEEK(s.data,1)=YEARWEEK(CURDATE(),1)) AS feito_semana
    FROM tr_ficha_dias d WHERE d.ficha_id=? ORDER BY d.ordem, d.id", [$EU, $EU, $ficha['id']]) : [];

$totalSem = count($dias) ?: 1;
$feitosSem = 0;
foreach ($dias as $d) { if ($d['feito_semana']) { $feitosSem++; } }
$pct = (int) round($feitosSem / $totalSem * 100);
$difLabel = ['facil' => 'Fácil', 'medio' => 'Médio', 'dificil' => 'Difícil'];

topo($ficha['nome'] ?? 'Meus treinos', $ficha['fase'] ?? '');
?>
<?php if (!$fichas) { vazio('Seu treinador ainda não montou sua ficha.', 'fa-dumbbell'); } else { ?>

  <?php if (count($fichas) > 1): ?>
    <select class="form-select mb-3" onchange="location.href='<?= url('meu-treino') ?>?ficha='+this.value">
      <?php foreach ($fichas as $f): ?>
        <option value="<?= (int) $f['id'] ?>" <?= (int) $f['id'] === $fid ? 'selected' : '' ?>>
          <?= e($f['nome']) ?> (<?= $f['status'] === 'ativa' ? 'Ativa' : 'Arquivada' ?>)</option>
      <?php endforeach; ?>
    </select>
  <?php endif; ?>

  <div class="card-ios">
    <div class="d-flex align-items-center gap-3">
      <div class="flex-grow-1">
        <strong style="font-size:1.15rem"><?= e($ficha['fase'] ?: 'Programa atual') ?></strong>
        <small style="display:block;color:var(--ios-txt2)">
          <?= $ficha['semanas'] ? (int) $ficha['semanas'] . ' semanas · ' : '' ?>
          <?= $feitosSem ?>/<?= $totalSem ?> treinos nesta semana</small>
      </div>
      <div style="position:relative;width:56px;height:56px;flex:0 0 auto">
        <svg width="56" height="56" style="transform:rotate(-90deg)">
          <circle cx="28" cy="28" r="23" class="anel-trilho" style="stroke-width:6"></circle>
          <circle cx="28" cy="28" r="23" class="anel-prog" id="anelProg" style="stroke-width:6"></circle>
        </svg>
        <span style="position:absolute;inset:0;display:grid;place-items:center;font-size:.78rem;font-weight:800">
          <?= $pct ?>%</span>
      </div>
    </div>
    <div class="prog-linha mt-3"><span style="width:<?= $pct ?>%"></span></div>
  </div>

  <?php if ($ficha['obs']): ?>
    <div class="card-ios"><div class="ch"><i class="fa-solid fa-circle-info" style="color:var(--ios-laranja)"></i> Orientações</div>
      <p class="mb-0" style="color:var(--ios-txt2);font-size:.9rem"><?= nl2br(e($ficha['obs'])) ?></p></div>
  <?php endif; ?>

  <div class="tit-ios"><h3>Treinos da semana</h3></div>
  <div class="timeline">
    <?php foreach ($dias as $d):
        $feito = (bool) $d['feito_semana'];
        $min = (int) ($d['duracao_min'] ?: max(20, (int) $d['qtd'] * 6)); ?>
      <div class="tl-item <?= $feito ? 'feito' : '' ?>" style="position:relative">
        <a href="<?= url('treino-dia', ['dia' => $d['id']]) ?>" class="tl-card">
          <div class="flex-grow-1">
            <span class="dia"><?= $d['ultimo'] ? 'Último em ' . data_br($d['ultimo']) : 'Nunca realizado' ?></span>
            <strong><?= e($d['nome']) ?></strong>
            <small><i class="fa-solid fa-signal"></i> <?= $difLabel[$d['dificuldade'] ?? ''] ?? 'Moderado' ?>
              • <?= $min ?> min • <?= (int) $d['qtd'] ?> exercícios</small>
          </div>
          <?php if ($feito): ?><i class="fa-solid fa-check tl-ok"></i><?php endif; ?>
        </a>
        <a href="<?= url('executar', ['dia' => $d['id']]) ?>" class="tl-play" title="Iniciar treino agora">
          <i class="fa-solid fa-play"></i></a>
      </div>
    <?php endforeach; ?>
  </div>
<?php } ?>

<script>anel('anelProg', <?= $pct ?>);</script>
<?php rodape();
