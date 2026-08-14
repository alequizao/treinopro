<?php
/** Logs de auditoria (master) */
exige('master');
$lista = todos('SELECT l.*, u.nome AS usuario, t.nome AS marca FROM tr_logs l
                LEFT JOIN tr_usuarios u ON u.id=l.usuario_id
                LEFT JOIN tr_tenants t ON t.id=l.tenant_id
                ORDER BY l.id DESC LIMIT 200');
topo('Logs', 'Últimas 200 ações registradas');
?>
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-clock-rotate-left"></i> Auditoria</div>
  <?php if (!$lista) { vazio('Nenhum log registrado.'); } else { ?>
    <div class="tabela-wrap"><table class="tabela">
      <thead><tr><th>Quando</th><th>Conta</th><th>Usuário</th><th>Ação</th><th>Detalhe</th></tr></thead>
      <tbody>
      <?php foreach ($lista as $l): ?>
        <tr>
          <td><small><?= date('d/m/Y H:i', strtotime($l['criado_em'])) ?></small></td>
          <td><?= e($l['marca'] ?: '—') ?></td>
          <td><?= e($l['usuario'] ?: '—') ?></td>
          <td><span class="chip"><?= e($l['acao']) ?></span></td>
          <td><small class="text-muted"><?= e($l['detalhe']) ?></small></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php } ?>
</div>
<?php rodape();
