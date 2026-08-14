<?php
/** Biblioteca de exercícios (globais + do personal) */
exige('treinador', 'master');
$T = tenant_id();
$busca = (string) get('q', '');
$grupo = (string) get('g', '');

$sql = eh_master() ? 'SELECT * FROM tr_exercicios WHERE tenant_id IS NULL' : 'SELECT * FROM tr_exercicios WHERE (tenant_id IS NULL OR tenant_id=?)';
$par = eh_master() ? [] : [$T];
if ($busca !== '') { $sql .= ' AND nome LIKE ?'; $par[] = "%$busca%"; }
if ($grupo !== '') { $sql .= ' AND grupo=?'; $par[] = $grupo; }
$sql .= ' ORDER BY grupo, nome';
$lista = todos($sql, $par);
$grupos = todos('SELECT DISTINCT grupo FROM tr_exercicios ORDER BY grupo');

topo('Exercícios', count($lista) . ' exercício(s) disponíveis');
?>
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-dumbbell"></i> Biblioteca
    <span class="acoes">
      <form method="get" class="d-flex gap-2">
        <input type="hidden" name="p" value="exercicios">
        <select name="g" class="form-select form-select-sm" style="width:150px" onchange="this.form.submit()">
          <option value="">Todos os grupos</option>
          <?php foreach ($grupos as $g): ?>
            <option value="<?= e($g['grupo']) ?>" <?= $grupo === $g['grupo'] ? 'selected' : '' ?>><?= e($g['grupo']) ?></option>
          <?php endforeach; ?>
        </select>
        <input name="q" class="form-control form-control-sm" placeholder="Buscar..." value="<?= e($busca) ?>" style="width:160px">
        <button class="btn-outline-soft"><i class="fa-solid fa-magnifying-glass"></i></button>
      </form>
      <button class="btn-acao" onclick="novoExercicio()"><i class="fa-solid fa-plus"></i> Novo</button>
    </span>
  </div>
  <?php if (!$lista) { vazio('Nenhum exercício encontrado.', 'fa-dumbbell'); } else { ?>
    <div class="tabela-wrap"><table class="tabela">
      <thead><tr><th>Exercício</th><th>Grupo</th><th>Equipamento</th><th>Vídeo</th><th>Origem</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $x): $global = $x['tenant_id'] === null; ?>
        <tr>
          <td><strong><?= e($x['nome']) ?></strong>
            <?php if ($x['instrucoes']): ?><br><small class="text-muted"><?= e(mb_strimwidth($x['instrucoes'], 0, 70, '...')) ?></small><?php endif; ?></td>
          <td><span class="chip"><?= e($x['grupo']) ?></span></td>
          <td><?= e($x['equipamento'] ?: '—') ?></td>
          <td>
            <?php if ($x['video_arquivo']): ?>
              <button class="btn-icone" data-video="<?= BASE_DIR ?>/<?= e($x['video_arquivo']) ?>"
                      data-titulo="<?= e($x['nome']) ?>" title="Ver vídeo"><i class="fa-solid fa-circle-play"></i></button>
            <?php elseif ($x['video_url']): ?>
              <button class="btn-icone" data-video="<?= e($x['video_url']) ?>"
                      data-titulo="<?= e($x['nome']) ?>" title="Ver vídeo"><i class="fa-solid fa-play"></i></button>
            <?php else: ?>—<?php endif; ?></td>
          <td><?= $global ? badge('Sistema', 'andamento') : badge('Meu', 'ok') ?></td>
          <td class="text-end">
            <?php if ($global && !eh_master()): ?>
              <button class="btn-icone" title="Copiar para minha biblioteca"
                      onclick='copiarEx(<?= json_encode($x, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-copy"></i></button>
            <?php else: ?>
              <button class="btn-icone" onclick='editarExercicio(<?= json_encode($x, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
              <button class="btn-icone perigo" data-click="exercicio_excluir" data-dados='{"id":<?= (int) $x['id'] ?>}' data-confirma="Excluir exercício?"><i class="fa-solid fa-trash"></i></button>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php } ?>
</div>

<div class="modal-bg" id="mExB"><div class="modal-cx">
  <button class="fechar" data-fecha="mExB"><i class="fa-solid fa-xmark"></i></button>
  <h3>Exercício</h3>
  <form data-acao="exercicio_salvar" id="formExB" enctype="multipart/form-data">
    <input type="hidden" name="id" id="b_id">
    <div class="row g-3">
      <div class="col-md-7"><label class="form-label">Nome *</label><input name="nome" id="b_nome" class="form-control" required></div>
      <div class="col-md-5"><label class="form-label">Grupo muscular</label>
        <input name="grupo" id="b_grupo" class="form-control" list="grupos" placeholder="Peito">
        <datalist id="grupos"><?php foreach ($grupos as $g): ?><option><?= e($g['grupo']) ?></option><?php endforeach; ?></datalist></div>
      <div class="col-md-5"><label class="form-label">Equipamento</label><input name="equipamento" id="b_equipamento" class="form-control" placeholder="Halteres"></div>
      <div class="col-md-7"><label class="form-label">Link do vídeo (YouTube, Drive...)</label>
        <input name="video_url" id="b_video_url" class="form-control" placeholder="https://youtube.com/..."></div>
      <div class="col-md-7"><label class="form-label">Ou envie o arquivo do vídeo</label>
        <input name="video_arquivo" type="file" accept="video/mp4,video/webm,video/quicktime" class="form-control">
        <small class="text-muted">MP4, WebM ou MOV — até 120 MB. Fica hospedado no seu próprio app.</small></div>
      <div class="col-md-5"><label class="form-label">Imagem de capa (opcional)</label>
        <input name="thumb" type="file" accept="image/*" class="form-control"></div>
      <div class="col-12" id="boxVideoAtual" style="display:none">
        <video id="b_video_prev" src="" controls style="max-width:260px;border-radius:10px"></video>
        <label class="ms-2"><input type="checkbox" name="remover_video" value="1"> remover vídeo enviado</label>
      </div>
      <div class="col-12"><label class="form-label">Instruções de execução</label><textarea name="instrucoes" id="b_instrucoes" class="form-control" rows="3"></textarea></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="button" class="btn-outline-soft" data-fecha="mExB">Cancelar</button><button class="btn-acao">Salvar</button>
    </div>
  </form>
</div></div>

<script>
var campos=['id','nome','grupo','equipamento','video_url','instrucoes'];
function novoExercicio(){ document.getElementById('formExB').reset(); document.getElementById('b_id').value=''; abrirModal('mExB'); }
function editarExercicio(x){
  campos.forEach(function(k){var el=document.getElementById('b_'+k); if(el) el.value=x[k]==null?'':x[k];});
  var box=document.getElementById('boxVideoAtual'), v=document.getElementById('b_video_prev');
  if (x.video_arquivo){ v.src = <?= json_encode(BASE_DIR . '/') ?> + x.video_arquivo; box.style.display=''; }
  else { v.src=''; box.style.display='none'; }
  abrirModal('mExB');
}
function copiarEx(x){ editarExercicio(x); document.getElementById('b_id').value=''; document.getElementById('b_nome').value=x.nome; }
</script>
<?php rodape();
