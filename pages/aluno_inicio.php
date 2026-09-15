<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** Home do app do aluno — treino, dieta, jejum, água e gamificação */
exige('aluno');
$EU = (int) usuario()['id'];
$u  = um('SELECT * FROM tr_usuarios WHERE id=?', [$EU]);

$meta      = (int) ($u['meta_treinos'] ?: 5);
$metaAgua  = (float) ($u['meta_agua'] ?: 3);
$feitos    = (int) valor("SELECT COUNT(*) FROM tr_sessoes WHERE aluno_id=? AND status='concluida'
                          AND YEARWEEK(data,1)=YEARWEEK(CURDATE(),1)", [$EU]);
$agua      = (float) valor('SELECT COALESCE(litros,0) FROM tr_agua WHERE aluno_id=? AND data=CURDATE()', [$EU]);

$ficha = um("SELECT * FROM tr_fichas WHERE aluno_id=? AND status='ativa' ORDER BY id DESC LIMIT 1", [$EU]);
$prox = null;
if ($ficha) {
    $prox = um("SELECT d.*, (SELECT MAX(s.data) FROM tr_sessoes s WHERE s.dia_id=d.id AND s.status='concluida') AS ultimo
                FROM tr_ficha_dias d WHERE d.ficha_id=?
                ORDER BY ultimo IS NOT NULL, ultimo ASC, d.ordem LIMIT 1", [$ficha['id']]);
}
$dieta = um("SELECT * FROM tr_dietas WHERE aluno_id=? AND status='ativa' ORDER BY id DESC LIMIT 1", [$EU]);
$proxRef = null;
if ($dieta) {
    $proxRef = um("SELECT * FROM tr_dieta_refeicoes WHERE dieta_id=? AND (horario IS NULL OR horario>=?)
                   ORDER BY horario IS NULL, horario LIMIT 1", [$dieta['id'], date('H:i')])
            ?: um('SELECT * FROM tr_dieta_refeicoes WHERE dieta_id=? ORDER BY ordem LIMIT 1', [$dieta['id']]);
}
$pct = $metaAgua > 0 ? (int) min(100, round($agua / $metaAgua * 100)) : 0;

// gamificação
$xp     = xp_total($EU);
$nivel  = nivel_do_xp($xp);
$streak = streak_treinos($EU);
$medalhas = (int) valor('SELECT COUNT(*) FROM tr_conquistas WHERE aluno_id=?', [$EU]);

// jejum
$jejum = jejum_ativo($EU);
$hJejum = $jejum ? (time() - strtotime($jejum['inicio'])) / 3600 : 0;
$pctJejum = $jejum && $jejum['meta_horas'] > 0 ? min(100, (int) round($hJejum / (float) $jejum['meta_horas'] * 100)) : 0;
$fase = fase_atual($hJejum);

topo('Bem vindo(a),', '');
?>
<h2 style="font-size:2rem;font-weight:800;margin:-6px 0 14px"><?= e(explode(' ', $u['nome'])[0]) ?></h2>

<!-- ============ NÍVEL / XP / STREAK ============ -->
<div class="card-soft xp-card">
  <span class="xp-nivel"><?= (int) $nivel['nivel'] ?></span>
  <div class="flex-grow-1">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <strong style="font-size:.98rem"><?= e($nivel['titulo']) ?></strong>
      <?php if ($streak > 0): ?>
        <span class="streak-chip"><i class="fa-solid fa-fire"></i> <?= $streak ?> dia<?= $streak > 1 ? 's' : '' ?></span>
      <?php endif; ?>
      <a href="<?= url('conquistas') ?>" class="chip ms-auto"><i class="fa-solid fa-medal"></i> <?= $medalhas ?></a>
    </div>
    <div class="barra-xp"><span style="width:<?= (int) $nivel['pct'] ?>%"></span></div>
    <small style="color:#8B9099;font-size:.75rem"><?= (int) $nivel['atual'] ?>/<?= (int) $nivel['proximo'] ?> XP para o nível <?= (int) $nivel['nivel'] + 1 ?></small>
  </div>
</div>

<!-- ============ TREINOS DA SEMANA ============ -->
<div class="card-soft d-flex align-items-center gap-3">
  <i class="fa-solid fa-dumbbell" style="font-size:1.4rem;color:var(--cor-primaria)"></i>
  <div class="flex-grow-1">
    <strong style="font-size:.95rem">Treinos da semana</strong>
    <div class="barra mt-2">
      <div class="preenche" style="width:<?= $meta ? min(100, round($feitos / $meta * 100)) : 0 ?>%"></div>
      <span class="txt"><?= $feitos ?>/<?= $meta ?></span>
    </div>
  </div>
</div>

<!-- ============ PRÓXIMO TREINO / REFEIÇÃO ============ -->
<div class="card-destaque">
  <small><i class="fa-solid fa-dumbbell"></i> Próximo treino</small>
  <span class="valor"><?= e($prox['nome'] ?? 'Sem treino') ?></span>
  <?php if ($prox): ?>
    <a href="<?= url('executar', ['dia' => $prox['id']]) ?>" class="btn-acao">Ir para o treino</a>
  <?php else: ?>
    <small class="text-muted d-block">Seu treinador ainda não montou sua ficha.</small>
  <?php endif; ?>
</div>

<div class="card-destaque">
  <small><i class="fa-solid fa-utensils"></i> Próxima refeição</small>
  <span class="valor"><?= e($proxRef['nome'] ?? 'Sem dieta') ?></span>
  <?php if ($proxRef): ?>
    <a href="<?= url('minha-dieta') ?>" class="btn-acao">Ir para refeição</a>
  <?php else: ?>
    <small class="text-muted d-block">Nenhuma dieta prescrita ainda.</small>
  <?php endif; ?>
</div>

<!-- ============ JEJUM ============ -->
<div class="d-flex justify-content-between align-items-center mt-4 mb-2">
  <h3 style="font-weight:800;font-size:1.5rem;margin:0"><i class="fa-solid fa-hourglass-half" style="color:var(--cor-primaria)"></i> Jejum</h3>
  <a href="<?= url('jejum') ?>" class="chip">ver tudo</a>
</div>
<div class="card-soft">
  <?php if ($jejum): ?>
    <div class="d-flex align-items-center gap-3">
      <div style="position:relative;width:74px;height:74px;flex:0 0 auto">
        <svg width="74" height="74" style="transform:rotate(-90deg)">
          <circle cx="37" cy="37" r="30" class="anel-trilho" style="stroke-width:8"></circle>
          <circle cx="37" cy="37" r="30" class="anel-prog" id="anelMini" style="stroke-width:8;stroke:<?= e($fase[3]) ?>"></circle>
        </svg>
        <span style="position:absolute;inset:0;display:grid;place-items:center;font-size:1.1rem;color:<?= e($fase[3]) ?>">
          <i class="fa-solid <?= e($fase[2]) ?>"></i></span>
      </div>
      <div class="flex-grow-1">
        <strong style="font-size:1.35rem;font-variant-numeric:tabular-nums" id="miniTempo">--:--:--</strong>
        <small style="display:block;color:#8B9099">Meta <?= number_format((float) $jejum['meta_horas'], 0) ?>h ·
          protocolo <?= e($jejum['protocolo']) ?></small>
        <span class="chip" style="background:<?= e($fase[3]) ?>22;color:<?= e($fase[3]) ?>">
          <i class="fa-solid <?= e($fase[2]) ?>"></i> <?= e($fase[1]) ?></span>
      </div>
    </div>
    <a href="<?= url('jejum') ?>" class="btn-redondo parar mt-3 d-block text-center">Ver / encerrar jejum</a>
  <?php else: ?>
    <p class="mb-2" style="color:#8B9099;font-size:.9rem">
      Nenhum jejum em andamento. Escolha o protocolo e comece agora.</p>
    <form data-acao="jejum_iniciar" class="d-flex gap-2">
      <select name="protocolo" class="form-select">
        <?php foreach (protocolos_jejum() as $k => $p): ?>
          <option value="<?= e($k) ?>" <?= ($u['protocolo_jejum'] ?: '16:8') === $k ? 'selected' : '' ?>>
            <?= e($p[0]) ?> — <?= (int) $p[1] ?>h</option>
        <?php endforeach; ?>
      </select>
      <button class="btn-acao" style="white-space:nowrap"><i class="fa-solid fa-play"></i> Iniciar</button>
    </form>
  <?php endif; ?>
</div>

<!-- ============ ÁGUA (garrafa animada) ============ -->
<div class="d-flex justify-content-between align-items-center mt-4 mb-2">
  <h3 style="font-weight:800;font-size:1.5rem;margin:0"><i class="fa-solid fa-droplet" style="color:#2D7FF9"></i> Água</h3>
  <span class="chip" style="background:rgba(45,127,249,.16);color:#5EA6FF">
    <i class="fa-solid fa-bullseye"></i> <?= number_format($metaAgua, 1, ',', '') ?> L</span>
</div>
<div class="card-soft garrafa-box">
  <!-- garrafa SVG: o grupo .agua-grupo sobe conforme o percentual -->
  <svg class="garrafa-svg" id="garrafa" viewBox="0 0 120 260">
    <defs>
      <!-- corpo da garrafa: gargalo estreito centrado em x=60 -->
      <clipPath id="corpoGarrafa">
        <path d="M50 44 h20 v18 c0 9 20 13 20 30 v134 c0 11-9 20-20 20 H50 c-11 0-20-9-20-20 V92
                 c0-17 20-21 20-30 z"/>
      </clipPath>
      <linearGradient id="gAgua" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="#5EA6FF"/><stop offset="100%" stop-color="#1B63C9"/>
      </linearGradient>
    </defs>

    <!-- tampa (mesma largura do gargalo) -->
    <rect x="46" y="12" width="28" height="22" rx="6" fill="#F26522"/>
    <rect x="44" y="32" width="32" height="10" rx="4" fill="#C64F14"/>

    <!-- interior + água -->
    <g clip-path="url(#corpoGarrafa)">
      <rect x="28" y="40" width="66" height="220" fill="#101114"/>
      <g class="agua-grupo" id="aguaGrupo">
        <path class="onda" d="M-80 70 q20 -9 40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 t40 0 v520 h-360 z" fill="url(#gAgua)"/>
        <circle class="bolha" cx="55" cy="250" r="4" fill="#fff"/>
        <circle class="bolha" cx="70" cy="280" r="3" fill="#fff"/>
        <circle class="bolha" cx="45" cy="300" r="2.5" fill="#fff"/>
      </g>
    </g>

    <!-- contorno, brilho e marcações -->
    <path d="M50 44 h20 v18 c0 9 20 13 20 30 v134 c0 11-9 20-20 20 H50 c-11 0-20-9-20-20 V92
             c0-17 20-21 20-30 z" fill="none" stroke="#3A3D45" stroke-width="4"/>
    <rect x="38" y="110" width="7" height="90" rx="3.5" fill="#fff" opacity=".12"/>
    <line x1="78" y1="130" x2="86" y2="130" stroke="#3A3D45" stroke-width="3"/>
    <line x1="78" y1="160" x2="86" y2="160" stroke="#3A3D45" stroke-width="3"/>
    <line x1="78" y1="190" x2="86" y2="190" stroke="#3A3D45" stroke-width="3"/>
    <line x1="78" y1="220" x2="86" y2="220" stroke="#3A3D45" stroke-width="3"/>
  </svg>

  <div class="flex-grow-1">
    <span class="garrafa-pct" id="garrafaPct">0%</span>
    <div style="color:#8B9099;font-size:.86rem;margin-top:2px">
      <strong id="litrosTxt" style="color:#fff;font-size:1.05rem"><?= number_format($agua, 2, ',', '') ?> L</strong>
      de <?= number_format($metaAgua, 1, ',', '') ?> L
    </div>
    <div class="copos">
      <button class="copo-btn" onclick="addAgua(0.2)"><i class="fa-solid fa-whiskey-glass"></i> Copo 200ml</button>
      <button class="copo-btn" onclick="addAgua(0.5)"><i class="fa-solid fa-bottle-water"></i> 500ml</button>
      <button class="copo-btn" onclick="addAgua(1)"><i class="fa-solid fa-jug-detergent"></i> 1 L</button>
      <button class="copo-btn" onclick="setAgua(0)" title="Zerar"><i class="fa-solid fa-rotate-left"></i></button>
    </div>
  </div>
</div>

<script>
/* ---------- água ---------- */
var aguaAtual = <?= json_encode((float) $agua) ?>, metaAgua = <?= json_encode((float) $metaAgua) ?>;
function pintaAgua(){
  var pct = metaAgua > 0 ? Math.min(100, Math.round(aguaAtual / metaAgua * 100)) : 0;
  encherGarrafa(pct);
  document.getElementById('litrosTxt').textContent = aguaAtual.toFixed(2).replace('.', ',') + ' L';
  // brilho discreto quando a meta é atingida
  document.getElementById('garrafa').classList.toggle('cheia', pct >= 100);
}
function setAgua(v){
  var antes = aguaAtual;
  aguaAtual = Math.max(0, Math.round(v * 100) / 100);
  pintaAgua();
  var g = document.getElementById('garrafa');
  g.classList.remove('splash'); void g.offsetWidth; g.classList.add('splash');
  vibrar(25);
  api('agua_registrar', {litros: aguaAtual}).then(function(r){
    if(!r.ok){ aguaAtual = antes; pintaAgua(); flash(r.msg, 'erro'); return; }
    if(r.meta_batida && antes < metaAgua){ confete(80); flash('Meta de água batida! 💧'); }
    conquista(r.conquistas);
  });
}
function addAgua(inc){ setAgua(aguaAtual + inc); }
setTimeout(pintaAgua, 120);

<?php if ($jejum): ?>
/* ---------- jejum em andamento (mini) ---------- */
var inicioJejum = <?= json_encode(strtotime($jejum['inicio']) * 1000) ?>,
    metaJejum   = <?= json_encode((float) $jejum['meta_horas']) ?>;
function tickMini(){
  var s = Math.floor((Date.now() - inicioJejum) / 1000);
  var h = Math.floor(s/3600), m = Math.floor(s%3600/60), sec = s%60;
  document.getElementById('miniTempo').textContent =
    (h<10?'0':'')+h+':'+(m<10?'0':'')+m+':'+(sec<10?'0':'')+sec;
  anel('anelMini', Math.min(100, (s/3600) / metaJejum * 100));
}
tickMini(); setInterval(tickMini, 1000);
<?php endif; ?>
</script>
<?php rodape();
