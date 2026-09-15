<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** Planos e valores que o personal cobra dos alunos */
exige('treinador');
$T = tenant_id();
$lista = todos('SELECT * FROM tr_planos WHERE tenant_id=? ORDER BY ativo DESC, nome', [$T]);
topo('Planos e valores', 'Modelos de cobrança usados nas mensalidades');
?>
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-tags"></i> Meus planos
    <span class="acoes"><button class="btn-acao" onclick="novoPlano()"><i class="fa-solid fa-plus"></i> Novo plano</button></span>
  </div>
  <?php if (!$lista) { vazio('Nenhum plano cadastrado. Ex.: "Consultoria mensal — R$ 250".', 'fa-tags'); } else { ?>
    <table class="tabela">
      <thead><tr><th>Plano</th><th>Valor</th><th>Ciclo</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $p): ?>
        <tr>
          <td><strong><?= e($p['nome']) ?></strong></td>
          <td><?= dinheiro($p['valor']) ?></td>
          <td><?= (int) $p['ciclo_dias'] ?> dias</td>
          <td><?= $p['ativo'] ? badge('Ativo', 'ok') : badge('Inativo', 'erro') ?></td>
          <td class="text-end">
            <button class="btn-icone" onclick='editarPlano(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
            <button class="btn-icone perigo" data-click="plano_excluir" data-dados='{"id":<?= (int) $p['id'] ?>}' data-confirma="Excluir plano?"><i class="fa-solid fa-trash"></i></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php } ?>
</div>

<div class="modal-bg" id="mPlano"><div class="modal-cx">
  <button class="fechar" data-fecha="mPlano"><i class="fa-solid fa-xmark"></i></button>
  <h3>Plano</h3>
  <form data-acao="plano_salvar" id="formPlano">
    <input type="hidden" name="id" id="pl_id">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label">Nome *</label><input name="nome" id="pl_nome" class="form-control" required placeholder="Consultoria mensal"></div>
      <div class="col-md-3"><label class="form-label">Valor (R$)</label><input name="valor" id="pl_valor" class="form-control" placeholder="250,00"></div>
      <div class="col-md-3"><label class="form-label">Ciclo (dias)</label><input name="ciclo_dias" id="pl_ciclo_dias" type="number" class="form-control" value="30"></div>
      <div class="col-md-4"><label class="form-label">Status</label>
        <select name="ativo" id="pl_ativo" class="form-select"><option value="1">Ativo</option><option value="0">Inativo</option></select></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="button" class="btn-outline-soft" data-fecha="mPlano">Cancelar</button><button class="btn-acao">Salvar</button>
    </div>
  </form>
</div></div>
<script>
function novoPlano(){ document.getElementById('formPlano').reset(); document.getElementById('pl_id').value=''; abrirModal('mPlano'); }
function editarPlano(p){ ['id','nome','valor','ciclo_dias','ativo'].forEach(function(k){var el=document.getElementById('pl_'+k); if(el) el.value=p[k]==null?'':p[k];}); abrirModal('mPlano'); }
</script>
<?php rodape();
