<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** App do aluno — perfil completo (foto, contato, endereço, metas, notificações) */
exige('aluno');
$u = um('SELECT * FROM tr_usuarios WHERE id=?', [(int) usuario()['id']]);
$t = tenant();
topo('Meu perfil', $u['email'], true);
?>
<div class="card-ios text-center">
  <label style="cursor:pointer;display:inline-block;position:relative">
    <span class="foto-perfil" id="previa">
      <?php if ($u['foto']): ?><img src="<?= BASE_DIR ?>/<?= e($u['foto']) ?>" alt="">
      <?php else: ?><?= e(iniciais($u['nome'])) ?><?php endif; ?>
    </span>
    <span class="foto-edit"><i class="fa-solid fa-camera"></i></span>
    <input type="file" id="inpFoto" accept="image/*" hidden form="formPerfil" name="foto">
  </label>
  <h3 style="font-weight:800;margin-top:12px"><?= e($u['nome']) ?></h3>
  <small style="color:var(--ios-txt2)"><?= e($u['objetivo'] ?: 'Aluno') ?>
    <?php if ($t): ?>· <?= e($t['nome']) ?><?php endif; ?></small>
</div>

<?php
$treinador = um("SELECT nome, foto FROM tr_usuarios WHERE tenant_id=? AND tipo='treinador' ORDER BY id LIMIT 1",
                [(int) usuario()['tenant_id']]);
$naoLidas = (int) valor('SELECT COUNT(*) FROM tr_mensagens WHERE aluno_id=? AND de_id<>? AND lida=0',
                        [(int) usuario()['id'], (int) usuario()['id']]);
?>
<a href="<?= url('chat') ?>" class="card-treinador">
  <span class="av">
    <?php if (!empty($treinador['foto'])): ?><img src="<?= BASE_DIR ?>/<?= e($treinador['foto']) ?>" alt="">
    <?php else: ?><i class="fa-solid fa-user-tie"></i><?php endif; ?>
  </span>
  <span class="txt">
    <small>Seu treinador</small>
    <strong><?= e($treinador['nome'] ?? 'Equipe') ?></strong>
  </span>
  <span class="acao">
    <?php if ($naoLidas): ?><em class="badge-n2"><?= $naoLidas ?></em><?php endif; ?>
    <i class="fa-solid fa-comments"></i> Conversar
  </span>
</a>

<?php if (!empty($t['whatsapp'])): ?>
  <a class="btn-outline-soft w-100 justify-content-center mb-3" target="_blank"
     href="https://wa.me/<?= e(preg_replace('/\D/', '', $t['whatsapp'])) ?>">
    <i class="fa-brands fa-whatsapp" style="color:#25D366"></i> Chamar no WhatsApp</a>
<?php endif; ?>

<form data-acao="perfil_completo" id="formPerfil" enctype="multipart/form-data">
  <div class="tit-ios"><h3>Dados pessoais</h3></div>
  <div class="card-ios">
    <div class="row g-3">
      <div class="col-12"><label class="form-label">Nome completo</label>
        <input name="nome" class="form-control" value="<?= e($u['nome']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">E-mail / usuário de acesso</label>
        <input name="email" class="form-control" value="<?= e($u['email']) ?>" required></div>
      <div class="col-md-6"><label class="form-label">Telefone / WhatsApp</label>
        <input name="telefone" class="form-control" value="<?= e($u['telefone']) ?>" placeholder="(82) 99999-0000"></div>
      <div class="col-md-4"><label class="form-label">Nascimento</label>
        <input name="nascimento" type="date" class="form-control" value="<?= e($u['nascimento']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Sexo</label>
        <select name="sexo" class="form-select">
          <option value="">—</option>
          <?php foreach (['M' => 'Masculino', 'F' => 'Feminino', 'O' => 'Outro'] as $k => $lb): ?>
            <option value="<?= $k ?>" <?= $u['sexo'] === $k ? 'selected' : '' ?>><?= $lb ?></option>
          <?php endforeach; ?>
        </select></div>
      <div class="col-md-4"><label class="form-label">Instagram</label>
        <input name="instagram" class="form-control" value="<?= e($u['instagram']) ?>" placeholder="@seuperfil"></div>
      <div class="col-12"><label class="form-label">Meu objetivo</label>
        <input name="objetivo" class="form-control" value="<?= e($u['objetivo']) ?>"
               placeholder="Emagrecer, ganhar massa, saúde..."></div>
    </div>
  </div>

  <div class="tit-ios"><h3>Endereço</h3></div>
  <div class="card-ios">
    <div class="row g-3">
      <div class="col-md-3"><label class="form-label">CEP</label>
        <input name="cep" id="cep" class="form-control" value="<?= e($u['cep']) ?>" placeholder="57000-000"></div>
      <div class="col-md-7"><label class="form-label">Rua / logradouro</label>
        <input name="endereco" id="rua" class="form-control" value="<?= e($u['endereco']) ?>"></div>
      <div class="col-md-2"><label class="form-label">Número</label>
        <input name="numero" class="form-control" value="<?= e($u['numero']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Complemento</label>
        <input name="complemento" class="form-control" value="<?= e($u['complemento']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Bairro</label>
        <input name="bairro" id="bairro" class="form-control" value="<?= e($u['bairro']) ?>"></div>
      <div class="col-md-3"><label class="form-label">Cidade</label>
        <input name="cidade" id="cidade" class="form-control" value="<?= e($u['cidade']) ?>"></div>
      <div class="col-md-1"><label class="form-label">UF</label>
        <input name="uf" id="uf" class="form-control" maxlength="2" value="<?= e($u['uf']) ?>"></div>
    </div>
  </div>

  <div class="tit-ios"><h3>Minhas metas</h3></div>
  <div class="card-ios">
    <div class="row g-3">
      <div class="col-4"><label class="form-label">Água (L/dia)</label>
        <input type="number" step="0.1" class="form-control" value="<?= e($u['meta_agua'] ?: 3) ?>" readonly disabled>
        <div class="form-text">Definida pelo seu personal.</div></div>
      <div class="col-4"><label class="form-label">Treinos/semana</label>
        <input name="meta_treinos" type="number" class="form-control" value="<?= e($u['meta_treinos']) ?>"></div>
      <div class="col-4"><label class="form-label">Peso alvo (kg)</label>
        <input name="meta_peso" type="number" step="0.1" class="form-control" value="<?= e($u['meta_peso']) ?>"></div>
    </div>
  </div>

  <div class="tit-ios"><h3>Segurança</h3></div>
  <div class="card-ios">
    <label class="form-label">Nova senha</label>
    <div class="senha-wrap">
      <input name="senha" type="password" class="form-control" placeholder="deixe vazio para manter a atual">
      <button type="button" class="olho"><i class="fa-solid fa-eye"></i></button>
    </div>
  </div>

  <button class="btn-ios mt-2">Salvar perfil</button>
</form>

<div class="tit-ios"><h3>Notificações</h3></div>
<div class="lista-ios">
  <div class="li"><span class="lbl">Avisos do treinador no celular</span>
    <button class="btn-acao" id="btnPush" style="padding:7px 14px">Ativar</button></div>
  <div class="li"><span class="lbl">Testar notificação</span>
    <button class="btn-outline-soft" data-click="push_teste" style="padding:7px 14px">Enviar teste</button></div>
</div>

<div class="tit-ios"><h3>Conta</h3></div>
<div class="lista-ios">
  <div class="li"><span class="lbl">Aluno desde</span><span class="val"><?= data_br($u['criado_em']) ?></span></div>
  <div class="li"><span class="lbl">Versão do app</span><span class="val off">v<?= APP_VERSAO ?></span></div>
  <a class="li" href="<?= url('sair') ?>"><span class="lbl" style="color:var(--ios-vermelho)">
    <i class="fa-solid fa-right-from-bracket"></i> Sair da conta</span></a>
</div>

<style>
.foto-perfil{width:104px;height:104px;border-radius:50%;background:var(--cor-primaria);color:#fff;
  display:inline-grid;place-items:center;font-size:2rem;font-weight:800;overflow:hidden;
  border:3px solid rgba(255,255,255,.12)}
.foto-perfil img{width:100%;height:100%;object-fit:cover}
.card-treinador{display:flex;align-items:center;gap:13px;background:var(--ios-card);border-radius:16px;
  padding:14px 16px;margin-bottom:12px;border:1px solid rgba(255,122,15,.35)}
.card-treinador .av{width:48px;height:48px;border-radius:50%;background:var(--cor-primaria);color:#fff;
  display:grid;place-items:center;overflow:hidden;flex:0 0 auto;font-size:1.1rem}
.card-treinador .av img{width:100%;height:100%;object-fit:cover}
.card-treinador .txt{flex:1;min-width:0;text-align:left}
.card-treinador .txt small{display:block;color:var(--ios-txt2);font-size:.76rem}
.card-treinador .txt strong{font-size:1.02rem}
.card-treinador .acao{display:flex;align-items:center;gap:7px;color:var(--cor-primaria);font-weight:700;font-size:.88rem}
.card-treinador:active{transform:scale(.985)}
.foto-edit{position:absolute;right:-2px;bottom:2px;width:34px;height:34px;border-radius:50%;
  background:var(--cor-primaria);color:#fff;display:grid;place-items:center;border:3px solid #000;font-size:.85rem}
</style>

<script>
/* prévia da foto */
document.getElementById('inpFoto').addEventListener('change', function(){
  var f = this.files[0]; if (!f) return;
  var r = new FileReader();
  r.onload = function(e){ document.getElementById('previa').innerHTML = '<img src="'+e.target.result+'">'; };
  r.readAsDataURL(f);
});

/* CEP automático (ViaCEP) */
document.getElementById('cep').addEventListener('blur', function(){
  var c = this.value.replace(/\D/g,'');
  if (c.length !== 8) return;
  fetch('https://viacep.com.br/ws/'+c+'/json/').then(function(r){return r.json()}).then(function(d){
    if (d.erro) return;
    if (d.logradouro) document.getElementById('rua').value = d.logradouro;
    if (d.bairro) document.getElementById('bairro').value = d.bairro;
    if (d.localidade) document.getElementById('cidade').value = d.localidade;
    if (d.uf) document.getElementById('uf').value = d.uf;
  }).catch(function(){});
});

/* ativar push */
document.getElementById('btnPush').addEventListener('click', function(){ ativarPush(this); });
</script>
<?php rodape();
