<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** Home do treinador */
exige('treinador');
$T = tenant_id();

$alunos    = (int) valor("SELECT COUNT(*) FROM tr_usuarios WHERE tenant_id=? AND tipo='aluno' AND status='ativo'", [$T]);
$fichas    = (int) valor("SELECT COUNT(*) FROM tr_fichas WHERE tenant_id=? AND status='ativa'", [$T]);
$treinosSemana = (int) valor("SELECT COUNT(*) FROM tr_sessoes WHERE tenant_id=? AND status='concluida' AND data>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)", [$T]);
$aReceber  = (float) valor("SELECT COALESCE(SUM(valor),0) FROM tr_cobrancas WHERE tenant_id=? AND status='aberta'", [$T]);
$vencidas  = todos("SELECT c.*,u.nome FROM tr_cobrancas c JOIN tr_usuarios u ON u.id=c.aluno_id
                    WHERE c.tenant_id=? AND c.status='aberta' AND c.vencimento<CURDATE()
                    ORDER BY c.vencimento LIMIT 6", [$T]);
$ultimos   = todos("SELECT s.*,u.nome,d.nome AS treino FROM tr_sessoes s
                    JOIN tr_usuarios u ON u.id=s.aluno_id JOIN tr_ficha_dias d ON d.id=s.dia_id
                    WHERE s.tenant_id=? AND s.status='concluida' ORDER BY s.fim_em DESC LIMIT 8", [$T]);
$semTreino = todos("SELECT u.* FROM tr_usuarios u
                    WHERE u.tenant_id=? AND u.tipo='aluno' AND u.status='ativo'
                      AND NOT EXISTS (SELECT 1 FROM tr_fichas f WHERE f.aluno_id=u.id AND f.status='ativa')
                    LIMIT 6", [$T]);

topo('Olá, ' . explode(' ', usuario()['nome'])[0] . '!', 'Resumo da sua consultoria hoje');
?>
<div class="row g-3 mb-2">
  <?php
  card_stat('Alunos ativos', (string) $alunos, 'fa-users');
  card_stat('Fichas ativas', (string) $fichas, 'fa-clipboard-list');
  card_stat('Treinos (7 dias)', (string) $treinosSemana, 'fa-fire', '#1E9E57');
  card_stat('A receber', dinheiro($aReceber), 'fa-dollar-sign', '#2D7FF9');
  ?>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-soft">
      <div class="ch"><i class="fa-solid fa-clock-rotate-left"></i> Últimos treinos realizados
        <span class="acoes"><span class="aovivo">ao vivo</span></span>
      </div>
      <?php if (!$ultimos) { vazio('Nenhum treino registrado ainda.', 'fa-dumbbell'); } else { ?>
        <div class="tabela-wrap"><table class="tabela">
          <tbody>
          <?php foreach ($ultimos as $s): ?>
            <tr>
              <td><span class="avatar" style="width:32px;height:32px;font-size:.72rem"><?= e(iniciais($s['nome'])) ?></span></td>
              <td><strong><?= e($s['nome']) ?></strong><br><small class="text-muted"><?= e($s['treino']) ?></small></td>
              <td class="text-end"><small class="text-muted"><?= data_br($s['data']) ?></small>
                <?php if ($s['nota']): ?><br><small>⭐ <?= (int) $s['nota'] ?>/5</small><?php endif; ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table></div>
      <?php } ?>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card-soft">
      <div class="ch"><i class="fa-solid fa-triangle-exclamation" style="color:#D9433A"></i> Mensalidades vencidas</div>
      <?php if (!$vencidas) { vazio('Nada vencido. Tudo em dia!', 'fa-circle-check'); } else { ?>
        <?php foreach ($vencidas as $c): ?>
          <div class="d-flex align-items-center gap-2 mb-2">
            <div class="flex-grow-1">
              <strong class="d-block" style="font-size:.9rem"><?= e($c['nome']) ?></strong>
              <small class="text-muted"><?= e($c['descricao']) ?> · venc. <?= data_br($c['vencimento']) ?></small>
            </div>
            <strong><?= dinheiro($c['valor']) ?></strong>
            <button class="btn-icone" data-click="cobranca_pagar" data-dados='{"id":<?= (int) $c['id'] ?>}'
                    title="Marcar como paga"><i class="fa-solid fa-circle-check"></i></button>
          </div>
        <?php endforeach; ?>
        <a href="<?= url('financeiro') ?>" class="btn-outline-soft w-100 mt-2 justify-content-center">Ver financeiro</a>
      <?php } ?>
    </div>

    <div class="card-soft">
      <div class="ch"><i class="fa-solid fa-user-clock"></i> Alunos sem ficha ativa</div>
      <?php if (!$semTreino) { vazio('Todos os alunos têm treino montado.', 'fa-circle-check'); } else { ?>
        <?php foreach ($semTreino as $a): ?>
          <a href="<?= url('aluno', ['id' => $a['id'], 'aba' => 'treinos']) ?>"
             class="d-flex align-items-center gap-2 mb-2">
            <span class="avatar" style="width:32px;height:32px;font-size:.72rem"><?= e(iniciais($a['nome'])) ?></span>
            <span class="flex-grow-1" style="font-size:.9rem"><?= e($a['nome']) ?></span>
            <span class="chip">montar treino</span>
          </a>
        <?php endforeach; ?>
      <?php } ?>
    </div>
  </div>
</div>
<?php rodape();
