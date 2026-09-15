<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** Financeiro do personal — cobranças dos alunos */
exige('treinador');
$T = tenant_id();
$mes = (string) get('mes', date('Y-m'));
$ini = $mes . '-01';
$fim = date('Y-m-t', strtotime($ini));

$cobs = todos("SELECT c.*,u.nome FROM tr_cobrancas c JOIN tr_usuarios u ON u.id=c.aluno_id
               WHERE c.tenant_id=? AND c.vencimento BETWEEN ? AND ?
               ORDER BY c.status, c.vencimento", [$T, $ini, $fim]);
$recebido = (float) valor("SELECT COALESCE(SUM(valor),0) FROM tr_cobrancas WHERE tenant_id=? AND status='paga' AND pago_em BETWEEN ? AND ?", [$T, $ini, $fim]);
$aberto   = (float) valor("SELECT COALESCE(SUM(valor),0) FROM tr_cobrancas WHERE tenant_id=? AND status='aberta' AND vencimento BETWEEN ? AND ?", [$T, $ini, $fim]);
$atraso   = (float) valor("SELECT COALESCE(SUM(valor),0) FROM tr_cobrancas WHERE tenant_id=? AND status='aberta' AND vencimento<CURDATE()", [$T]);
$alunos   = todos("SELECT id,nome FROM tr_usuarios WHERE tenant_id=? AND tipo='aluno' AND status='ativo' ORDER BY nome", [$T]);
$planos   = todos('SELECT * FROM tr_planos WHERE tenant_id=? AND ativo=1 ORDER BY nome', [$T]);

topo('Financeiro', 'Competência ' . date('m/Y', strtotime($ini)));
?>
<div class="row g-3 mb-2">
  <?php
  card_stat('Recebido no mês', dinheiro($recebido), 'fa-circle-check', '#1E9E57');
  card_stat('A receber no mês', dinheiro($aberto), 'fa-hourglass-half', '#D98E0B');
  card_stat('Em atraso (total)', dinheiro($atraso), 'fa-triangle-exclamation', '#D9433A');
  card_stat('Alunos ativos', (string) count($alunos), 'fa-users');
  ?>
</div>

<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-receipt"></i> Cobranças
    <span class="acoes">
      <form method="get" class="d-flex gap-2">
        <input type="hidden" name="p" value="financeiro">
        <input type="month" name="mes" value="<?= e($mes) ?>" class="form-control form-control-sm" onchange="this.form.submit()">
      </form>
      <button class="btn-acao" data-modal="mCobF"><i class="fa-solid fa-plus"></i> Nova cobrança</button>
    </span>
  </div>
  <?php if (!$cobs) { vazio('Nenhuma cobrança neste mês.', 'fa-receipt'); } else { ?>
    <div class="tabela-wrap"><table class="tabela">
      <thead><tr><th>Aluno</th><th>Descrição</th><th>Vencimento</th><th>Valor</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($cobs as $c): $venc = $c['status'] === 'aberta' && $c['vencimento'] < date('Y-m-d'); ?>
        <tr>
          <td><a href="<?= url('aluno', ['id' => $c['aluno_id'], 'aba' => 'financeiro']) ?>"><strong><?= e($c['nome']) ?></strong></a></td>
          <td><?= e($c['descricao']) ?></td>
          <td><?= data_br($c['vencimento']) ?></td>
          <td><?= dinheiro($c['valor']) ?></td>
          <td><?= $c['status'] === 'paga' ? badge('Paga ' . data_br($c['pago_em']), 'ok') : ($venc ? badge('Vencida', 'erro') : badge('Em aberto', 'pendente')) ?></td>
          <td class="text-end">
            <button class="btn-icone" data-click="cobranca_pagar" data-dados='{"id":<?= (int) $c['id'] ?>}'
                    title="<?= $c['status'] === 'paga' ? 'Desfazer pagamento' : 'Marcar como paga' ?>"><i class="fa-solid fa-circle-check"></i></button>
            <button class="btn-icone perigo" data-click="cobranca_excluir" data-dados='{"id":<?= (int) $c['id'] ?>}' data-confirma="Excluir cobrança?"><i class="fa-solid fa-trash"></i></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php } ?>
</div>

<div class="modal-bg" id="mCobF"><div class="modal-cx">
  <button class="fechar" data-fecha="mCobF"><i class="fa-solid fa-xmark"></i></button>
  <h3>Nova cobrança</h3>
  <form data-acao="cobranca_salvar">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Aluno *</label>
        <select name="aluno_id" class="form-select" required>
          <option value="">Selecione...</option>
          <?php foreach ($alunos as $al): ?><option value="<?= (int) $al['id'] ?>"><?= e($al['nome']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-6"><label class="form-label">Plano</label>
        <select name="plano_id" class="form-select" onchange="var o=this.selectedOptions[0];if(o.dataset.valor){this.form.valor.value=o.dataset.valor;this.form.descricao.value=o.textContent.trim()}">
          <option value="">Avulso</option>
          <?php foreach ($planos as $pl): ?><option value="<?= (int) $pl['id'] ?>" data-valor="<?= e($pl['valor']) ?>"><?= e($pl['nome']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="col-md-6"><label class="form-label">Descrição</label><input name="descricao" class="form-control" value="Mensalidade"></div>
      <div class="col-md-3"><label class="form-label">Valor *</label><input name="valor" class="form-control" required></div>
      <div class="col-md-3"><label class="form-label">Vencimento</label><input name="vencimento" type="date" class="form-control" value="<?= date('Y-m-d', strtotime('+5 days')) ?>"></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="button" class="btn-outline-soft" data-fecha="mCobF">Cancelar</button><button class="btn-acao">Lançar</button>
    </div>
  </form>
</div></div>
<?php rodape();
