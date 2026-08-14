<?php
/** Editor da ficha de treino — divisões (Treino A/B/C) + exercícios */
exige('treinador');
$T = tenant_id();
$f = do_tenant(um('SELECT * FROM tr_fichas WHERE id=?', [id_get()]));
$aluno = aluno_do_tenant((int) $f['aluno_id']);
$dias = todos('SELECT * FROM tr_ficha_dias WHERE ficha_id=? ORDER BY ordem, id', [$f['id']]);
$exs = todos('SELECT * FROM tr_exercicios WHERE tenant_id IS NULL OR tenant_id=? ORDER BY grupo, nome', [$T]);

topo($f['nome'], 'Ficha de ' . $aluno['nome'], true);
?>
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-clipboard-list"></i> Dados da ficha
    <span class="acoes">
      <?= $f['status'] === 'ativa' ? badge('Ativa', 'ok') : badge('Arquivada', 'pausa') ?>
      <button class="btn-outline-soft" onclick="window.print()"><i class="fa-solid fa-print"></i> Imprimir</button>
    </span>
  </div>
  <form data-acao="ficha_salvar" data-recarrega="nao">
    <input type="hidden" name="id" value="<?= (int) $f['id'] ?>">
    <div class="row g-3">
      <div class="col-md-5"><label class="form-label">Nome</label><input name="nome" class="form-control" value="<?= e($f['nome']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Objetivo</label><input name="objetivo" class="form-control" value="<?= e($f['objetivo']) ?>"></div>
      <div class="col-md-3"><label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="ativa" <?= $f['status'] === 'ativa' ? 'selected' : '' ?>>Ativa</option>
          <option value="arquivada" <?= $f['status'] === 'arquivada' ? 'selected' : '' ?>>Arquivada</option>
        </select></div>
      <div class="col-md-3"><label class="form-label">Início</label><input name="inicio" type="date" class="form-control" value="<?= e($f['inicio']) ?>"></div>
      <div class="col-md-3"><label class="form-label">Fim</label><input name="fim" type="date" class="form-control" value="<?= e($f['fim']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Observações</label><input name="obs" class="form-control" value="<?= e($f['obs']) ?>"></div>
    </div>
    <div class="text-end mt-3"><button class="btn-acao">Salvar dados</button></div>
  </form>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 class="secao-titulo mb-0">Divisões de treino</h2>
  <button class="btn-outline-soft" data-click="dia_add" data-dados='{"ficha_id":<?= (int) $f['id'] ?>}'>
    <i class="fa-solid fa-plus"></i> Adicionar treino</button>
</div>

<?php foreach ($dias as $d):
    $itens = todos('SELECT x.*, e.nome AS ex_nome, e.grupo, e.video_url
                    FROM tr_ficha_exercicios x JOIN tr_exercicios e ON e.id=x.exercicio_id
                    WHERE x.dia_id=? ORDER BY x.ordem, x.id', [$d['id']]); ?>
  <div class="card-soft">
    <div class="ch">
      <i class="fa-solid fa-dumbbell"></i>
      <input class="form-control form-control-sm" style="max-width:260px;font-weight:700"
             value="<?= e($d['nome']) ?>" onchange="api('dia_renomear',{id:<?= (int) $d['id'] ?>,nome:this.value}).then(function(r){flash(r.msg,r.ok?'ok':'erro')})">
      <span class="acoes">
        <span class="chip cinza"><?= count($itens) ?> exercício(s)</span>
        <button class="btn-acao" onclick="novoEx(<?= (int) $d['id'] ?>)"><i class="fa-solid fa-plus"></i> Exercício</button>
        <button class="btn-icone perigo" data-click="dia_del" data-dados='{"id":<?= (int) $d['id'] ?>}' data-confirma="Remover esta divisão?"><i class="fa-solid fa-trash"></i></button>
      </span>
    </div>
    <?php if (!$itens) { vazio('Nenhum exercício neste treino.', 'fa-dumbbell'); } else { ?>
      <div class="tabela-wrap"><table class="tabela">
        <thead><tr><th>#</th><th>Exercício</th><th>Séries</th><th>Reps</th><th>Carga</th><th>Descanso</th><th>Técnica</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($itens as $i => $x): ?>
          <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= e($x['ex_nome']) ?></strong>
              <?php if ($x['video_url']): ?><button class="btn-icone" data-video="<?= e($x['video_url']) ?>"
                data-titulo="<?= e($x['ex_nome']) ?>" title="Ver vídeo"><i class="fa-solid fa-play"></i></button><?php endif; ?>
              <br><small class="text-muted"><?= e($x['grupo']) ?><?= $x['obs'] ? ' · ' . e($x['obs']) : '' ?></small></td>
            <td><?= e($x['series']) ?></td><td><?= e($x['repeticoes']) ?></td>
            <td><?= e($x['carga'] ?: '—') ?></td><td><?= e($x['descanso'] ?: '—') ?></td>
            <td><?= $x['tecnica'] ? '<span class="chip">' . e($x['tecnica']) . '</span>' : '—' ?></td>
            <td class="text-end">
              <button class="btn-icone" onclick='editarEx(<?= json_encode($x, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
              <button class="btn-icone perigo" data-click="fex_del" data-dados='{"id":<?= (int) $x['id'] ?>}'><i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php } ?>
  </div>
<?php endforeach; ?>

<!-- Modal exercício -->
<div class="modal-bg" id="mEx"><div class="modal-cx">
  <button class="fechar" data-fecha="mEx"><i class="fa-solid fa-xmark"></i></button>
  <h3>Exercício do treino</h3>
  <form data-acao="fex_salvar" id="formEx">
    <input type="hidden" name="id" id="x_id"><input type="hidden" name="dia_id" id="x_dia_id">
    <div class="row g-3">
      <div class="col-12"><label class="form-label">Exercício *</label>
        <select name="exercicio_id" id="x_exercicio_id" class="form-select" required>
          <option value="">Selecione...</option>
          <?php $g = ''; foreach ($exs as $ex):
              if ($g !== $ex['grupo']) { if ($g !== '') echo '</optgroup>'; $g = $ex['grupo']; echo '<optgroup label="' . e($g) . '">'; } ?>
            <option value="<?= (int) $ex['id'] ?>"><?= e($ex['nome']) ?><?= $ex['equipamento'] ? ' (' . e($ex['equipamento']) . ')' : '' ?></option>
          <?php endforeach; if ($g !== '') echo '</optgroup>'; ?>
        </select>
        <small class="text-muted">Não achou? Cadastre em <a href="<?= url('exercicios') ?>">Exercícios</a>.</small>
      </div>
      <div class="col-6 col-md-3"><label class="form-label">Séries</label><input name="series" id="x_series" class="form-control" value="3"></div>
      <div class="col-6 col-md-3"><label class="form-label">Repetições</label><input name="repeticoes" id="x_repeticoes" class="form-control" value="10-12"></div>
      <div class="col-6 col-md-3"><label class="form-label">Carga</label><input name="carga" id="x_carga" class="form-control" placeholder="20 kg"></div>
      <div class="col-6 col-md-3"><label class="form-label">Descanso</label><input name="descanso" id="x_descanso" class="form-control" placeholder="60s"></div>
      <div class="col-md-4"><label class="form-label">Técnica</label>
        <input name="tecnica" id="x_tecnica" class="form-control" list="tecnicas" placeholder="—">
        <datalist id="tecnicas"><option>Bi-set</option><option>Tri-set</option><option>Drop-set</option>
          <option>Rest-pause</option><option>Back off set</option><option>Isometria</option></datalist></div>
      <div class="col-md-8"><label class="form-label">Observação para o aluno</label><input name="obs" id="x_obs" class="form-control"></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="button" class="btn-outline-soft" data-fecha="mEx">Cancelar</button>
      <button class="btn-acao">Salvar exercício</button>
    </div>
  </form>
</div></div>

<script>
function novoEx(dia){
  document.getElementById('formEx').reset();
  document.getElementById('x_id').value='';
  document.getElementById('x_dia_id').value=dia;
  abrirModal('mEx');
}
function editarEx(x){
  ['id','dia_id','exercicio_id','series','repeticoes','carga','descanso','tecnica','obs'].forEach(function(k){
    var el=document.getElementById('x_'+k); if(el) el.value = x[k]==null?'':x[k];
  });
  abrirModal('mEx');
}
</script>
<?php rodape();
