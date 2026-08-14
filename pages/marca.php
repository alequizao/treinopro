<?php
/** Whitelabel — identidade visual do personal */
exige('treinador');
$t = tenant();
$host = $_SERVER['HTTP_HOST'] ?? 'treino.alequizao.com';
$hostBase = preg_replace('/^[^.]+\./', '', $host);
$linkApp = 'https://' . $t['slug'] . '.' . (substr_count($host, '.') >= 3 ? $hostBase : $host) . '/';
$linkAlt = 'https://' . $host . BASE_DIR . '/?t=' . $t['slug'];

topo('Configurações do app', 'Deixe o app com a sua marca');
?>
<div class="row g-3">
  <div class="col-lg-7">
    <div class="card-soft">
      <div class="ch"><i class="fa-solid fa-palette"></i> Identidade visual</div>
      <form data-acao="marca_salvar" enctype="multipart/form-data">
        <div class="row g-3">
          <div class="col-md-7"><label class="form-label">Nome da marca *</label>
            <input name="nome" class="form-control" value="<?= e($t['nome']) ?>" required>
            <small class="text-muted">Aparece no topo do app, no login e nas fichas.</small></div>
          <div class="col-md-5"><label class="form-label">WhatsApp de contato</label>
            <input name="whatsapp" class="form-control" value="<?= e($t['whatsapp']) ?>" placeholder="5582999999999"></div>
          <div class="col-md-4"><label class="form-label">Cor principal</label>
            <input name="cor_primaria" type="color" class="form-control form-control-color" value="<?= e($t['cor_primaria']) ?>"></div>
          <div class="col-md-4"><label class="form-label">Cor de botão escuro</label>
            <input name="cor_acao" type="color" class="form-control form-control-color" value="<?= e($t['cor_acao']) ?>"></div>
          <div class="col-md-4"><label class="form-label">Logo (PNG/SVG)</label>
            <input name="logo" type="file" accept="image/*" class="form-control"></div>
        </div>
        <div class="text-end mt-3"><button class="btn-acao">Salvar identidade</button></div>
      </form>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card-soft">
      <div class="ch"><i class="fa-solid fa-link"></i> Endereços do seu app</div>
      <p class="small text-muted mb-2">Envie este link para seus alunos instalarem o app:</p>
      <div class="input-group mb-3">
        <input class="form-control" id="lk1" value="<?= e($linkAlt) ?>" readonly>
        <button class="btn-outline-soft" onclick="navigator.clipboard.writeText(document.getElementById('lk1').value);flash('Link copiado!')"><i class="fa-solid fa-copy"></i></button>
      </div>
      <p class="small text-muted mb-2">Subdomínio exclusivo (requer DNS wildcard configurado):</p>
      <div class="input-group">
        <input class="form-control" id="lk2" value="<?= e($linkApp) ?>" readonly>
        <button class="btn-outline-soft" onclick="navigator.clipboard.writeText(document.getElementById('lk2').value);flash('Link copiado!')"><i class="fa-solid fa-copy"></i></button>
      </div>
      <?php if ($t['dominio']): ?>
        <p class="small text-muted mt-3 mb-0">Domínio próprio ativo: <strong><?= e($t['dominio']) ?></strong></p>
      <?php endif; ?>
    </div>

    <div class="card-soft">
      <div class="ch"><i class="fa-solid fa-eye"></i> Prévia</div>
      <div style="border-radius:14px;overflow:hidden;border:1px solid var(--cor-borda)">
        <div style="background:#1C1F24;padding:14px;display:flex;gap:10px;align-items:center">
          <img src="<?= BASE_DIR ?>/<?= e($t['logo'] ?: 'assets/icone.png') ?>"
               style="max-height:34px;max-width:34px;border-radius:8px">
          <strong style="color:#fff"><?= e($t['nome']) ?></strong>
        </div>
        <div style="padding:16px;background:#0D0D0F">
          <div class="card-destaque" style="margin:0">
            <small>Próximo treino</small><span class="valor">Treino A</span>
            <span class="btn-acao">Ir para o treino</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php rodape();
