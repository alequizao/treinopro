<?php
/** Lista de alunos + cadastro */
exige('treinador');
$T = tenant_id();
$busca = (string) get('q', '');
$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM tr_fichas f WHERE f.aluno_id=u.id AND f.status='ativa') AS fichas,
        (SELECT COUNT(*) FROM tr_sessoes s WHERE s.aluno_id=u.id AND s.status='concluida'
           AND s.data>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)) AS treinos7,
        (SELECT COALESCE(SUM(c.valor),0) FROM tr_cobrancas c WHERE c.aluno_id=u.id AND c.status='aberta'
           AND c.vencimento<CURDATE()) AS devendo
        FROM tr_usuarios u WHERE u.tenant_id=? AND u.tipo='aluno'";
$par = [$T];
if ($busca !== '') { $sql .= ' AND (u.nome LIKE ? OR u.email LIKE ?)'; $par[] = "%$busca%"; $par[] = "%$busca%"; }
$sql .= ' ORDER BY u.status, u.nome';
$lista = todos($sql, $par);

topo('Alunos', count($lista) . ' aluno(s) cadastrado(s)');
?>
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-users"></i> Meus alunos
    <span class="acoes">
      <form method="get" class="d-flex gap-2">
        <input type="hidden" name="p" value="alunos">
        <input name="q" class="form-control form-control-sm" placeholder="Buscar aluno..." value="<?= e($busca) ?>" style="width:180px">
        <button class="btn-outline-soft"><i class="fa-solid fa-magnifying-glass"></i></button>
      </form>
      <button class="btn-acao" data-modal="mAluno" onclick="novoAluno()"><i class="fa-solid fa-plus"></i> Novo aluno</button>
    </span>
  </div>

  <?php if (!$lista) { vazio('Nenhum aluno ainda. Clique em "Novo aluno" para começar.', 'fa-user-plus'); } else { ?>
  <div class="tabela-wrap">
    <table class="tabela">
      <thead><tr><th>Aluno</th><th>Objetivo</th><th>Fichas</th><th>Treinos 7d</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $a): ?>
        <tr>
          <td>
            <a href="<?= url('aluno', ['id' => $a['id']]) ?>" class="d-flex align-items-center gap-2">
              <span class="avatar" style="width:34px;height:34px;font-size:.72rem"><?= e(iniciais($a['nome'])) ?></span>
              <span><strong><?= e($a['nome']) ?></strong><br><small class="text-muted"><?= e($a['email']) ?></small></span>
            </a>
          </td>
          <td><small><?= e($a['objetivo'] ?: '—') ?></small></td>
          <td><?= (int) $a['fichas'] ?></td>
          <td><?= (int) $a['treinos7'] ?></td>
          <td>
            <?= $a['status'] === 'ativo' ? badge('Ativo', 'ok') : badge('Inativo', 'erro') ?>
            <?php if ($a['devendo'] > 0): ?><br><?= badge('Em atraso', 'pendente') ?><?php endif; ?>
          </td>
          <td class="text-end">
            <a href="<?= url('entrar-como', ['id' => $a['id']]) ?>" class="btn-icone" title="Ver o app como este aluno"
               onclick="return confirm('Entrar no app como <?= e($a['nome']) ?>? Você poderá voltar com um clique.')"><i class="fa-solid fa-user-secret"></i></a>
            <a href="<?= url('aluno', ['id' => $a['id']]) ?>" class="btn-icone" title="Abrir"><i class="fa-solid fa-arrow-right"></i></a>
            <button class="btn-icone" title="Editar" onclick='editarAluno(<?= json_encode($a, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
            <button class="btn-icone perigo" title="Excluir" data-click="aluno_excluir"
                    data-dados='{"id":<?= (int) $a['id'] ?>}' data-confirma="Excluir <?= e($a['nome']) ?> e todos os dados?"><i class="fa-solid fa-trash"></i></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php } ?>
</div>

<!-- Modal aluno -->
<div class="modal-bg" id="mAluno">
  <div class="modal-cx">
    <button class="fechar" data-fecha="mAluno"><i class="fa-solid fa-xmark"></i></button>
    <h3 id="tituloAluno">Novo aluno</h3>
    <form data-acao="aluno_salvar" id="formAluno">
      <input type="hidden" name="id" id="a_id">
      <div class="row g-3">
        <div class="col-md-7"><label class="form-label">Nome completo *</label>
          <input name="nome" id="a_nome" class="form-control" required></div>
        <div class="col-md-5"><label class="form-label">Usuário / e-mail *</label>
          <input name="email" id="a_email" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Senha</label>
          <input name="senha" id="a_senha" class="form-control" placeholder="deixe vazio para manter"></div>
        <div class="col-md-4"><label class="form-label">Telefone</label>
          <input name="telefone" id="a_telefone" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Nascimento</label>
          <input name="nascimento" id="a_nascimento" type="date" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Sexo</label>
          <select name="sexo" id="a_sexo" class="form-select">
            <option value="">—</option><option value="M">Masculino</option><option value="F">Feminino</option><option value="O">Outro</option>
          </select></div>
        <div class="col-md-9"><label class="form-label">Objetivo</label>
          <input name="objetivo" id="a_objetivo" class="form-control" placeholder="Hipertrofia, emagrecimento..."></div>
        <div class="col-md-4"><label class="form-label">Meta de água (L/dia)</label>
          <input name="meta_agua" id="a_meta_agua" type="number" step="0.1" class="form-control" placeholder="3.0"></div>
        <div class="col-md-4"><label class="form-label">Meta treinos/semana</label>
          <input name="meta_treinos" id="a_meta_treinos" type="number" class="form-control" placeholder="5"></div>
        <div class="col-md-4"><label class="form-label">Status</label>
          <select name="status" id="a_status" class="form-select"><option value="ativo">Ativo</option><option value="inativo">Inativo</option></select></div>
        <div class="col-12"><label class="form-label">Observações</label>
          <textarea name="obs" id="a_obs" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="d-flex gap-2 justify-content-end mt-3">
        <button type="button" class="btn-outline-soft" data-fecha="mAluno">Cancelar</button>
        <button type="submit" class="btn-acao">Salvar aluno</button>
      </div>
    </form>
  </div>
</div>

<script>
function novoAluno(){
  document.getElementById('formAluno').reset();
  document.getElementById('a_id').value='';
  document.getElementById('tituloAluno').textContent='Novo aluno';
}
function editarAluno(a){
  ['id','nome','email','telefone','nascimento','sexo','objetivo','obs','status','meta_agua','meta_treinos'].forEach(function(k){
    var el=document.getElementById('a_'+k); if(el) el.value = a[k]==null?'':a[k];
  });
  document.getElementById('a_senha').value='';
  document.getElementById('tituloAluno').textContent='Editar aluno';
  abrirModal('mAluno');
}
<?php if (get('novo')): ?>novoAluno();abrirModal('mAluno');<?php endif; ?>
</script>
<?php rodape();
