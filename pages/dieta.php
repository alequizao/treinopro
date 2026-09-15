<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** Editor da ficha de dieta — refeições + alimentos */
exige('treinador');
$T = tenant_id();
$d = do_tenant(um('SELECT * FROM tr_dietas WHERE id=?', [id_get()]));
$aluno = aluno_do_tenant((int) $d['aluno_id']);
$refs = todos('SELECT * FROM tr_dieta_refeicoes WHERE dieta_id=? ORDER BY ordem, id', [$d['id']]);

$tot = ['kcal' => 0, 'prot' => 0, 'carb' => 0, 'gord' => 0];
$itensPorRef = [];
foreach ($refs as $r) {
    $itens = todos('SELECT * FROM tr_dieta_itens WHERE refeicao_id=? ORDER BY ordem, id', [$r['id']]);
    $itensPorRef[$r['id']] = $itens;
    foreach ($itens as $i) {
        $tot['kcal'] += (int) $i['kcal']; $tot['prot'] += (float) $i['prot'];
        $tot['carb'] += (float) $i['carb']; $tot['gord'] += (float) $i['gord'];
    }
}
topo($d['nome'], 'Dieta de ' . $aluno['nome'], true);
?>
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-apple-whole"></i> Informações da dieta
    <span class="acoes"><?= $d['status'] === 'ativa' ? badge('Ativa', 'ok') : badge('Arquivada', 'pausa') ?>
      <button class="btn-outline-soft" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir</button></span>
  </div>
  <form data-acao="dieta_salvar" data-recarrega="nao">
    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label">Nome</label><input name="nome" class="form-control" value="<?= e($d['nome']) ?>"></div>
      <div class="col-md-3"><label class="form-label">Objetivo</label><input name="objetivo" class="form-control" value="<?= e($d['objetivo']) ?>"></div>
      <div class="col-md-2"><label class="form-label">Meta kcal</label><input name="kcal_alvo" type="number" class="form-control" value="<?= e($d['kcal_alvo']) ?>"></div>
      <div class="col-md-3"><label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="ativa" <?= $d['status'] === 'ativa' ? 'selected' : '' ?>>Ativa</option>
          <option value="arquivada" <?= $d['status'] === 'arquivada' ? 'selected' : '' ?>>Arquivada</option>
        </select></div>
      <div class="col-12"><label class="form-label">Orientações gerais</label><textarea name="obs" class="form-control" rows="2"><?= e($d['obs']) ?></textarea></div>
    </div>
    <div class="text-end mt-3"><button class="btn-acao">Salvar</button></div>
  </form>
</div>

<div class="row g-3 mb-2">
  <?php
  card_stat('Calorias', (string) $tot['kcal'], 'fa-fire');
  card_stat('Proteína', number_format($tot['prot'], 1, ',', '') . 'g', 'fa-drumstick-bite', '#D9433A');
  card_stat('Carboidrato', number_format($tot['carb'], 1, ',', '') . 'g', 'fa-bread-slice', '#2D7FF9');
  card_stat('Gordura', number_format($tot['gord'], 1, ',', '') . 'g', 'fa-droplet', '#D98E0B');
  ?>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="secao-titulo mb-0">Refeições</h2>
  <button class="btn-outline-soft" onclick="novaRef()"><i class="fa-solid fa-plus"></i> Adicionar refeição</button>
</div>

<?php foreach ($refs as $r):
    $itens = $itensPorRef[$r['id']];
    $kcalRef = 0; foreach ($itens as $i) { $kcalRef += (int) $i['kcal']; } ?>
  <div class="card-soft">
    <div class="ch"><i class="fa-solid fa-utensils"></i> <?= e($r['nome']) ?>
      <?php if ($r['horario']): ?><span class="chip cinza"><?= e($r['horario']) ?></span><?php endif; ?>
      <span class="acoes">
        <span class="chip"><?= $kcalRef ?> kcal</span>
        <button class="btn-acao" onclick="novoItem(<?= (int) $r['id'] ?>)"><i class="fa-solid fa-plus"></i> Alimento</button>
        <button class="btn-icone" onclick='editarRef(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
        <button class="btn-icone perigo" data-click="refeicao_del" data-dados='{"id":<?= (int) $r['id'] ?>}' data-confirma="Remover refeição?"><i class="fa-solid fa-trash"></i></button>
      </span>
    </div>
    <?php if (!$itens) { vazio('Nenhum alimento nesta refeição.', 'fa-utensils'); } else { ?>
      <div class="tabela-wrap"><table class="tabela">
        <thead><tr><th>Alimento</th><th>Qtd</th><th>Kcal</th><th>P</th><th>C</th><th>G</th><th>Substituição</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($itens as $i): ?>
          <tr>
            <td><strong><?= e($i['alimento']) ?></strong></td>
            <td><?= e($i['quantidade'] ?: '—') ?></td>
            <td><?= (int) $i['kcal'] ?></td>
            <td><?= $i['prot'] !== null ? number_format((float) $i['prot'], 1, ',', '') : '—' ?></td>
            <td><?= $i['carb'] !== null ? number_format((float) $i['carb'], 1, ',', '') : '—' ?></td>
            <td><?= $i['gord'] !== null ? number_format((float) $i['gord'], 1, ',', '') : '—' ?></td>
            <td><small class="text-muted"><?= e($i['substituto'] ?: '—') ?></small></td>
            <td class="text-end">
              <button class="btn-icone" onclick='editarItem(<?= json_encode($i, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
              <button class="btn-icone perigo" data-click="item_del" data-dados='{"id":<?= (int) $i['id'] ?>}'><i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php } ?>
  </div>
<?php endforeach; ?>

<div class="modal-bg" id="mRef"><div class="modal-cx">
  <button class="fechar" data-fecha="mRef"><i class="fa-solid fa-xmark"></i></button>
  <h3>Refeição</h3>
  <form data-acao="refeicao_salvar" id="formRef">
    <input type="hidden" name="dieta_id" value="<?= (int) $d['id'] ?>"><input type="hidden" name="id" id="r_id">
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label">Nome</label><input name="nome" id="r_nome" class="form-control" required placeholder="Café da manhã"></div>
      <div class="col-md-4"><label class="form-label">Horário</label><input name="horario" id="r_horario" class="form-control" placeholder="07:00"></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="button" class="btn-outline-soft" data-fecha="mRef">Cancelar</button><button class="btn-acao">Salvar</button>
    </div>
  </form>
</div></div>

<div class="modal-bg" id="mItem"><div class="modal-cx">
  <button class="fechar" data-fecha="mItem"><i class="fa-solid fa-xmark"></i></button>
  <h3>Alimento</h3>
  <form data-acao="item_salvar" id="formItem">
    <input type="hidden" name="id" id="i_id"><input type="hidden" name="refeicao_id" id="i_refeicao_id">
    <div class="row g-3">
      <div class="col-md-7"><label class="form-label">Alimento *</label><input name="alimento" id="i_alimento" class="form-control" required></div>
      <div class="col-md-5"><label class="form-label">Quantidade</label><input name="quantidade" id="i_quantidade" class="form-control" placeholder="100g / 2 unidades"></div>
      <div class="col-3"><label class="form-label">Kcal</label><input name="kcal" id="i_kcal" type="number" class="form-control"></div>
      <div class="col-3"><label class="form-label">Prot (g)</label><input name="prot" id="i_prot" type="number" step="0.1" class="form-control"></div>
      <div class="col-3"><label class="form-label">Carb (g)</label><input name="carb" id="i_carb" type="number" step="0.1" class="form-control"></div>
      <div class="col-3"><label class="form-label">Gord (g)</label><input name="gord" id="i_gord" type="number" step="0.1" class="form-control"></div>
      <div class="col-12"><label class="form-label">Substituições</label><input name="substituto" id="i_substituto" class="form-control" placeholder="2 ovos ou 1 scoop de whey"></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="button" class="btn-outline-soft" data-fecha="mItem">Cancelar</button><button class="btn-acao">Salvar</button>
    </div>
  </form>
</div></div>

<script>
function novaRef(){ document.getElementById('formRef').reset(); document.getElementById('r_id').value=''; abrirModal('mRef'); }
function editarRef(r){ ['id','nome','horario'].forEach(function(k){var el=document.getElementById('r_'+k); if(el) el.value=r[k]==null?'':r[k];}); abrirModal('mRef'); }
function novoItem(ref){ document.getElementById('formItem').reset(); document.getElementById('i_id').value=''; document.getElementById('i_refeicao_id').value=ref; abrirModal('mItem'); }
function editarItem(i){ ['id','refeicao_id','alimento','quantidade','kcal','prot','carb','gord','substituto'].forEach(function(k){var el=document.getElementById('i_'+k); if(el) el.value=i[k]==null?'':i[k];}); abrirModal('mItem'); }
</script>
<?php rodape();
