<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** Gestão de personais (tenants) — só o master */
exige('master');
$lista = todos("SELECT t.*,
                (SELECT COUNT(*) FROM tr_usuarios u WHERE u.tenant_id=t.id AND u.tipo='aluno') AS alunos,
                (SELECT u.email FROM tr_usuarios u WHERE u.tenant_id=t.id AND u.tipo='treinador' ORDER BY u.id LIMIT 1) AS login
                FROM tr_tenants t ORDER BY t.nome");
topo('Personais', count($lista) . ' conta(s) na plataforma');
?>
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-building"></i> Contas
    <span class="acoes"><button class="btn-acao" onclick="novoTenant()"><i class="fa-solid fa-plus"></i> Novo personal</button></span>
  </div>
  <?php if (!$lista) { vazio('Nenhum personal cadastrado ainda.', 'fa-building'); } else { ?>
    <div class="tabela-wrap"><table class="tabela">
      <thead><tr><th>Marca</th><th>Acesso</th><th>Alunos</th><th>Status</th><th>Expira</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $t): ?>
        <tr>
          <td><span class="d-flex align-items-center gap-2">
              <span class="avatar" style="width:32px;height:32px;font-size:.7rem;background:<?= e($t['cor_primaria']) ?>"><?= e(iniciais($t['nome'])) ?></span>
              <span><strong><?= e($t['nome']) ?></strong><br><small class="text-muted"><?= e($t['slug']) ?><?= $t['dominio'] ? ' · ' . e($t['dominio']) : '' ?></small></span>
            </span></td>
          <td><small><?= e($t['login'] ?: '—') ?></small><br>
              <a href="<?= BASE_URL ?>?t=<?= e($t['slug']) ?>" target="_blank" class="chip">abrir app</a></td>
          <td><?= (int) $t['alunos'] ?>/<?= (int) $t['limite_alunos'] ?></td>
          <td><?= $t['status'] === 'ativo' ? badge('Ativo', 'ok') : ($t['status'] === 'trial' ? badge('Trial', 'pendente') : badge('Suspenso', 'erro')) ?></td>
          <td><small><?= data_br($t['expira_em']) ?></small></td>
          <td class="text-end">
            <button class="btn-icone" onclick='editarTenant(<?= json_encode($t, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
            <button class="btn-icone perigo" data-click="tenant_excluir" data-dados='{"id":<?= (int) $t['id'] ?>}'
                    data-confirma="Excluir <?= e($t['nome']) ?> e TODOS os usuários dessa conta?"><i class="fa-solid fa-trash"></i></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php } ?>
</div>

<div class="modal-bg" id="mTen"><div class="modal-cx">
  <button class="fechar" data-fecha="mTen"><i class="fa-solid fa-xmark"></i></button>
  <h3 id="tTitulo">Novo personal</h3>
  <form data-acao="tenant_salvar" id="formTen">
    <input type="hidden" name="id" id="t_id">
    <div class="row g-3">
      <div class="col-md-7"><label class="form-label">Nome da marca *</label><input name="nome" id="t_nome" class="form-control" required></div>
      <div class="col-md-5"><label class="form-label">Slug (subdomínio) *</label><input name="slug" id="t_slug" class="form-control" required placeholder="fulano"></div>
      <div class="col-md-6"><label class="form-label">Domínio próprio</label><input name="dominio" id="t_dominio" class="form-control" placeholder="app.personalfulano.com.br"></div>
      <div class="col-md-6"><label class="form-label">WhatsApp</label><input name="whatsapp" id="t_whatsapp" class="form-control"></div>
      <div class="col-md-3"><label class="form-label">Cor principal</label><input name="cor_primaria" id="t_cor_primaria" type="color" class="form-control form-control-color" value="#F26522"></div>
      <div class="col-md-3"><label class="form-label">Cor escura</label><input name="cor_acao" id="t_cor_acao" type="color" class="form-control form-control-color" value="#22262B"></div>
      <div class="col-md-3"><label class="form-label">Limite de alunos</label><input name="limite_alunos" id="t_limite_alunos" type="number" class="form-control" value="30"></div>
      <div class="col-md-3"><label class="form-label">Status</label>
        <select name="status" id="t_status" class="form-select"><option value="trial">Trial</option><option value="ativo">Ativo</option><option value="suspenso">Suspenso</option></select></div>
      <div class="col-md-4"><label class="form-label">Expira em</label><input name="expira_em" id="t_expira_em" type="date" class="form-control"></div>
      <div class="col-12" id="boxAcesso"><hr><strong class="small text-muted">LOGIN DO TREINADOR (só na criação)</strong></div>
      <div class="col-md-4 novo-only"><label class="form-label">Nome do treinador</label><input name="nome_treinador" class="form-control"></div>
      <div class="col-md-4 novo-only"><label class="form-label">Usuário / e-mail</label><input name="email" class="form-control"></div>
      <div class="col-md-4 novo-only"><label class="form-label">Senha</label><input name="senha" class="form-control"></div>
    </div>
    <div class="d-flex justify-content-end gap-2 mt-3">
      <button type="button" class="btn-outline-soft" data-fecha="mTen">Cancelar</button><button class="btn-acao">Salvar</button>
    </div>
  </form>
</div></div>
<script>
function mostraNovo(v){ document.querySelectorAll('.novo-only,#boxAcesso').forEach(function(el){ el.style.display = v?'':'none'; }); }
function novoTenant(){ document.getElementById('formTen').reset(); document.getElementById('t_id').value='';
  document.getElementById('tTitulo').textContent='Novo personal'; mostraNovo(true); abrirModal('mTen'); }
function editarTenant(t){
  ['id','nome','slug','dominio','whatsapp','cor_primaria','cor_acao','limite_alunos','status','expira_em'].forEach(function(k){
    var el=document.getElementById('t_'+k); if(el) el.value=t[k]==null?'':t[k];
  });
  document.getElementById('tTitulo').textContent='Editar personal'; mostraNovo(false); abrirModal('mTen');
}
</script>
<?php rodape();
