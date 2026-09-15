<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** App do aluno — conquistas, nível e recordes pessoais */
exige('aluno');
$EU = (int) usuario()['id'];
checar_conquistas($EU);

$xp     = xp_total($EU);
$nivel  = nivel_do_xp($xp);
$streak = streak_treinos($EU);
$ganhas = array_column(todos('SELECT chave,criado_em FROM tr_conquistas WHERE aluno_id=?', [$EU]), 'criado_em', 'chave');
$cat    = catalogo_conquistas();

$recordes = todos("SELECT e.nome, MAX(CAST(ss.carga AS DECIMAL(10,2))) AS carga
    FROM tr_sessao_series ss
    JOIN tr_sessoes s ON s.id=ss.sessao_id
    JOIN tr_ficha_exercicios fx ON fx.id=ss.ficha_exercicio_id
    JOIN tr_exercicios e ON e.id=fx.exercicio_id
    WHERE s.aluno_id=? AND ss.concluida=1 AND ss.carga IS NOT NULL AND ss.carga<>''
    GROUP BY e.id ORDER BY carga DESC LIMIT 12", [$EU]);

topo('Conquistas', count($ganhas) . ' de ' . count($cat) . ' medalhas');
?>
<div class="card-soft xp-card">
  <span class="xp-nivel"><?= (int) $nivel['nivel'] ?></span>
  <div class="flex-grow-1">
    <strong style="font-size:1.05rem"><?= e($nivel['titulo']) ?> · nível <?= (int) $nivel['nivel'] ?></strong>
    <div class="barra-xp"><span style="width:<?= (int) $nivel['pct'] ?>%"></span></div>
    <small style="color:#8B9099;font-size:.76rem">
      <span data-conta="<?= $xp ?>">0</span> XP no total ·
      faltam <?= (int) ($nivel['proximo'] - $nivel['atual']) ?> para o próximo nível</small>
  </div>
  <?php if ($streak > 0): ?>
    <span class="streak-chip"><i class="fa-solid fa-fire"></i> <?= $streak ?></span>
  <?php endif; ?>
</div>

<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-medal"></i> Medalhas
    <span class="acoes"><span class="chip"><?= count($ganhas) ?>/<?= count($cat) ?></span></span>
  </div>
  <div class="medalhas">
    <?php foreach ($cat as $chave => $c): $tem = isset($ganhas[$chave]); ?>
      <div class="medalha <?= $tem ? '' : 'bloqueada' ?>" title="<?= e($c[3]) ?>">
        <span class="mi" style="background:<?= e($c[2]) ?>"><i class="fa-solid <?= e($c[1]) ?>"></i></span>
        <small><?= e($c[0]) ?></small>
        <?php if ($tem): ?>
          <small style="color:#8B9099;font-weight:500;font-size:.63rem"><?= date('d/m/y', strtotime($ganhas[$chave])) ?></small>
        <?php else: ?>
          <small style="color:#8B9099;font-weight:500;font-size:.63rem"><i class="fa-solid fa-lock"></i></small>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-trophy" style="color:#D98E0B"></i> Meus recordes de carga</div>
  <?php if (!$recordes) { vazio('Registre a carga nas séries para começar a marcar recordes.', 'fa-trophy'); } else { ?>
    <table class="tabela">
      <?php foreach ($recordes as $i => $r): ?>
        <tr>
          <td style="width:34px"><span class="chip" style="<?= $i === 0 ? 'background:#D98E0B22;color:#D98E0B' : '' ?>">
            <?= $i + 1 ?>º</span></td>
          <td><strong><?= e($r['nome']) ?></strong></td>
          <td class="text-end"><strong style="font-size:1.05rem;color:var(--cor-primaria)">
            <?= number_format((float) $r['carga'], 1, ',', '') ?> kg</strong></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php } ?>
</div>

<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-circle-info"></i> Como ganhar XP</div>
  <table class="tabela">
    <tr><td><i class="fa-solid fa-dumbbell" style="color:var(--cor-primaria)"></i> Concluir um treino</td><td class="text-end"><strong>+50 XP</strong></td></tr>
    <tr><td><i class="fa-solid fa-hourglass-end" style="color:#1E9E57"></i> Concluir um jejum</td><td class="text-end"><strong>+30 XP</strong></td></tr>
    <tr><td><i class="fa-solid fa-droplet" style="color:#2D7FF9"></i> Bater a meta de água no dia</td><td class="text-end"><strong>+10 XP</strong></td></tr>
  </table>
</div>
<?php rodape();
