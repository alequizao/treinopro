<?php
/** Home do super admin (dono do SaaS) */
exige('master');
$tenants = (int) valor('SELECT COUNT(*) FROM tr_tenants');
$ativos  = (int) valor("SELECT COUNT(*) FROM tr_tenants WHERE status='ativo'");
$alunos  = (int) valor("SELECT COUNT(*) FROM tr_usuarios WHERE tipo='aluno'");
$sessoes = (int) valor("SELECT COUNT(*) FROM tr_sessoes WHERE status='concluida' AND data>=DATE_SUB(CURDATE(),INTERVAL 30 DAY)");
$lista = todos("SELECT t.*,
                (SELECT COUNT(*) FROM tr_usuarios u WHERE u.tenant_id=t.id AND u.tipo='aluno') AS alunos
                FROM tr_tenants t ORDER BY t.criado_em DESC LIMIT 10");
topo('Painel do SaaS', 'Visão geral da plataforma');
?>
<div class="row g-3 mb-2">
  <?php
  card_stat('Personais', (string) $tenants, 'fa-building');
  card_stat('Contas ativas', (string) $ativos, 'fa-circle-check', '#1E9E57');
  card_stat('Alunos na plataforma', (string) $alunos, 'fa-users', '#2D7FF9');
  card_stat('Treinos (30 dias)', (string) $sessoes, 'fa-fire', '#D98E0B');
  ?>
</div>
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-building"></i> Últimos personais cadastrados
    <span class="acoes"><a href="<?= url('tenants') ?>" class="btn-acao">Gerenciar</a></span>
  </div>
  <?php if (!$lista) { vazio('Nenhum personal cadastrado.', 'fa-building'); } else { ?>
    <table class="tabela">
      <thead><tr><th>Marca</th><th>Slug</th><th>Alunos</th><th>Status</th><th>Cadastro</th></tr></thead>
      <tbody>
      <?php foreach ($lista as $t): ?>
        <tr>
          <td><strong><?= e($t['nome']) ?></strong></td>
          <td><code><?= e($t['slug']) ?></code></td>
          <td><?= (int) $t['alunos'] ?>/<?= (int) $t['limite_alunos'] ?></td>
          <td><?= $t['status'] === 'ativo' ? badge('Ativo', 'ok') : ($t['status'] === 'trial' ? badge('Trial', 'pendente') : badge('Suspenso', 'erro')) ?></td>
          <td><?= data_br($t['criado_em']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php } ?>
</div>
<?php rodape();
