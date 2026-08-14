<?php
/** Login — split-screen, whitelabel por tenant */
$m = marca();
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = tentar_login((string) post('login'), (string) post('senha'));
    if ($u) {
        if (eh_ajax()) responde_ok('Bem-vindo!', ['redir' => url('inicio')]);
        redir('inicio');
    }
    $erro = 'Usuário ou senha inválidos.';
    if (eh_ajax()) responde_erro($erro);
}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>Entrar · <?= e($m['nome']) ?></title>
<meta name="theme-color" content="<?= e($m['cor']) ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= BASE_DIR ?>/assets/app.css?v=<?= APP_VERSAO ?>" rel="stylesheet">
<style>:root{--cor-primaria:<?= e($m['cor']) ?>;--cor-acao:<?= e($m['acao']) ?>}</style>
</head>
<body class="tela-login">
<div id="flash" class="flash-area"></div>
<div class="login-wrap">
  <div class="login-form">
    <div class="inner">
      <div class="login-logo">
        <img src="<?= BASE_DIR ?>/<?= e($m['logo'] ?: 'assets/icone.png') ?>" alt="<?= e($m['nome']) ?>">
        <span><?= e($m['nome']) ?></span>
      </div>
      <h2>Olá! Entre com a sua conta</h2>
      <p class="sub">Acesse o painel do treinador ou o seu app de aluno.</p>

      <?php if ($erro): ?><div class="alert alert-danger py-2 small"><?= e($erro) ?></div><?php endif; ?>

      <form method="post" action="<?= url('login') ?>">
        <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
        <div class="mb-3">
          <label class="form-label">Usuário ou e-mail</label>
          <input name="login" class="form-control" autocomplete="username" required autofocus
                 value="<?= e(post('login')) ?>" placeholder="seu@email.com">
        </div>
        <div class="mb-2">
          <label class="form-label">Senha</label>
          <div class="senha-wrap">
            <input name="senha" type="password" class="form-control" autocomplete="current-password" required placeholder="••••••••">
            <button type="button" class="olho"><i class="fa-solid fa-eye"></i></button>
          </div>
        </div>
        <div class="text-end mb-3">
          <a href="<?= e($m['nome']) ? '#' : '#' ?>" class="small text-muted"
             onclick="alert('Peça a redefinição de senha ao seu treinador.');return false">Esqueceu a senha?</a>
        </div>
        <button class="btn-escuro w-100 py-2">Entrar</button>
      </form>

      <div class="demo-box">
        <span>Quer só conhecer? Entre na demonstração:</span>
        <div class="d-flex gap-2 mt-2">
          <button type="button" class="btn-outline-soft flex-grow-1" onclick="entrarDemo('demo-personal')">
            <i class="fa-solid fa-user-tie"></i> Sou o personal</button>
          <button type="button" class="btn-outline-soft flex-grow-1" onclick="entrarDemo('demo-aluno')">
            <i class="fa-solid fa-dumbbell"></i> Sou o aluno</button>
        </div>
      </div>

      <p class="text-muted small mt-4 mb-0">
        <?= e($m['nome']) ?> · Plataforma de treino e dieta
      </p>
    </div>
  </div>
  <div class="login-arte" style="background-image:url('<?= BASE_DIR ?>/assets/login-arte.jpg')">
    <div class="frase">Treine com <span>método</span>.<br>Evolua com <span>dados</span>.</div>
  </div>
</div>
<script>window.CSRF=<?= json_encode(csrf()) ?>;window.BASE=<?= json_encode(BASE_URL) ?>;
window.APP_VERSAO=<?= json_encode(APP_VERSAO) ?>;</script>
<script src="<?= BASE_DIR ?>/assets/app.js?v=<?= APP_VERSAO ?>"></script>
<script>
/* entra direto nas contas de demonstração */
function entrarDemo(usuario){
  var f = document.querySelector('form');
  f.login.value = usuario; f.senha.value = 'demo';
  flash('Entrando na demonstração como ' + usuario + '...');
  f.submit();
}
</script>
</body>
</html>
