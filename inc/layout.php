<?php
/** Layout base — sidebar escura (treinador/master) e app escuro (aluno) */

function contadores_menu(): array {
    require_once __DIR__ . '/chat.php';
    if (eh_aluno()) {
        return ['msgs' => nao_lidas((int) usuario()['id'], 'aluno', (int) usuario()['tenant_id'])];
    }
    if (!eh_treinador()) return [];
    $t = tenant_id();
    return [
        'msgs'    => nao_lidas((int) usuario()['id'], 'treinador', $t),
        'alunos'  => (int) valor("SELECT COUNT(*) FROM tr_usuarios WHERE tenant_id=? AND tipo='aluno' AND status='ativo'", [$t]),
        'treinos' => (int) valor("SELECT COUNT(*) FROM tr_fichas WHERE tenant_id=? AND status='ativa'", [$t]),
        'dietas'  => (int) valor("SELECT COUNT(*) FROM tr_dietas WHERE tenant_id=? AND status='ativa'", [$t]),
    ];
}

/** [pagina, label, icone, chave_contador|null] ; string = título de seção */
function menu_do_usuario(): array {
    if (eh_master()) {
        return [
            ['inicio', 'Home', 'fa-house', null],
            ['tenants', 'Personais', 'fa-building', null],
            'Sistema',
            ['exercicios', 'Biblioteca global', 'fa-dumbbell', null],
            ['logs', 'Logs', 'fa-clock-rotate-left', null],
        ];
    }
    if (eh_treinador()) {
        return [
            ['inicio', 'Home', 'fa-house', null],
            ['alunos', 'Alunos', 'fa-users', 'alunos'],
            ['chat', 'Mensagens', 'fa-comments', 'msgs'],
            ['dietas', 'Fichas de dieta', 'fa-apple-whole', 'dietas'],
            ['treinos', 'Fichas de treino', 'fa-clipboard-list', 'treinos'],
            ['financeiro', 'Financeiro', 'fa-dollar-sign', null],
            'Configurações',
            ['marca', 'Configurações do app', 'fa-palette', null],
            ['exercicios', 'Exercícios', 'fa-dumbbell', null],
            ['planos', 'Planos e valores', 'fa-tags', null],
        ];
    }
    return [
        ['inicio', 'Home', 'fa-house', null],
        ['meu-treino', 'Treinos', 'fa-table-cells-large', null],
        ['atividades', 'Atividades', 'fa-chart-simple', null],
        ['minha-dieta', 'Dieta', 'fa-utensils', null],
        ['corpo', 'Corpo', 'fa-person', null],
        ['jejum', 'Jejum', 'fa-hourglass-half', null],
        ['evolucao', 'Evolução', 'fa-chart-line', null],
        ['conquistas', 'Conquistas', 'fa-medal', null],
        ['chat', 'Mensagens', 'fa-comments', 'msgs'],
        ['meus-pagamentos', 'Pagamentos', 'fa-receipt', null],
        ['meu-perfil', 'Meu perfil', 'fa-user', null],
    ];
}

function topo(string $titulo, string $subtitulo = '', bool $voltar = false): void {
    $m = marca();
    $u = usuario();
    $pAtual = (string) get('p', 'inicio');
    $cont = contadores_menu();
    $ehApp = eh_aluno();
    ?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title><?= e($titulo) ?> · <?= e($m['nome']) ?></title>
<meta name="theme-color" content="<?= $ehApp ? '#0D0D0F' : e($m['cor']) ?>">
<link rel="manifest" href="<?= BASE_DIR ?>/manifest.php?t=<?= e(tenant()['slug'] ?? '') ?>">
<link rel="apple-touch-icon" href="<?= BASE_DIR ?>/<?= e($m['logo'] ?: 'assets/icone-180.png') ?>">
<link rel="icon" type="image/png" href="<?= BASE_DIR ?>/assets/favicon.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= BASE_DIR ?>/assets/app.css?v=<?= APP_VERSAO ?>" rel="stylesheet">
<style>:root{--cor-primaria:<?= e($m['cor']) ?>;--cor-acao:<?= e($m['acao']) ?>}</style>
</head>
<body class="<?= $ehApp ? 'aluno' : '' ?><?= personificando() ? ' personificando' : '' ?>">
<div id="flash" class="flash-area"></div>

<?php if (personificando()): ?>
  <div class="faixa-person">
    <i class="fa-solid fa-user-secret"></i>
    Você está vendo o app como <strong><?= e($u['nome'] ?? '') ?></strong>
    <a href="<?= url('voltar-conta') ?>"><i class="fa-solid fa-right-from-bracket"></i> Voltar para minha conta</a>
  </div>
<?php endif; ?>

<aside class="sidebar" id="sidebar">
  <div class="marca">
    <img src="<?= BASE_DIR ?>/<?= e($m['logo'] ?: 'assets/icone.png') ?>" alt="<?= e($m['nome']) ?>">
    <strong><?= e($m['nome']) ?></strong>
  </div>
  <nav class="menu">
    <?php foreach (menu_do_usuario() as $it):
        if (is_string($it)) { echo '<div class="menu-sec">' . e($it) . '</div>'; continue; } ?>
      <a href="<?= url($it[0]) ?>" class="<?= $pAtual === $it[0] ? 'ativo' : '' ?>">
        <i class="fa-solid <?= $it[2] ?>"></i><span><?= e($it[1]) ?></span>
        <?php if ($it[3] === 'msgs'): ?>
          <em class="badge-n msg" id="badgeMsg" <?= empty($cont['msgs']) ? 'style="display:none"' : '' ?>><?= (int) ($cont['msgs'] ?? 0) ?></em>
        <?php elseif ($it[3] !== null && !empty($cont[$it[3]])): ?><em class="badge-n"><?= (int) $cont[$it[3]] ?></em><?php endif; ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="menu-rodape">
    <button type="button" id="btnInstalar" class="btn-instalar" hidden><i class="fa-solid fa-mobile-screen"></i> Instalar aplicativo</button>
    <a href="<?= url('sair') ?>" class="sair"><i class="fa-solid fa-right-from-bracket"></i> Sair</a>
    <small>v<?= APP_VERSAO ?></small>
  </div>
</aside>
<div class="backdrop" id="backdrop"></div>

<main class="content">
  <header class="topbar">
    <?php if (!$ehApp): ?>
      <button class="hamburguer" id="abrirMenu" aria-label="Menu"><i class="fa-solid fa-bars"></i></button>
    <?php endif; ?>
    <div class="titulo">
      <?php if ($voltar): ?>
        <a href="javascript:history.back()" class="btn-outline-soft mb-2 d-inline-flex"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
      <?php endif; ?>
      <h1><?= e($titulo) ?></h1>
      <?php if ($subtitulo): ?><p><?= e($subtitulo) ?></p><?php endif; ?>
    </div>
    <?php if ($ehApp): ?>
      <a href="<?= url('meu-perfil') ?>" class="avatar"><?= e(iniciais($u['nome'] ?? '?')) ?></a>
    <?php else: ?>
      <div class="usuario">
        <div class="d-none d-md-block">
          <strong><?= e($u['nome'] ?? '') ?></strong>
          <small><?= eh_master() ? 'Administrador' : 'Personal Trainer' ?></small>
        </div>
        <span class="avatar"><?= e(iniciais($u['nome'] ?? '?')) ?></span>
      </div>
    <?php endif; ?>
  </header>
  <div class="corpo"<?= !empty($GLOBALS['semRefresh']) ? ' data-sem-refresh' : '' ?>>
<?php }

function rodape(): void {
    $pAtual = (string) get('p', 'inicio');
    if (eh_aluno()) {
        $tabs = [['meu-treino', 'Treinos', 'fa-table-cells-large'], ['atividades', 'Atividades', 'fa-chart-simple'],
                 ['inicio', 'Home', 'fa-house'], ['minha-dieta', 'Dieta', 'fa-utensils'],
                 ['corpo', 'Corpo', 'fa-person']];
    } else {
        $m = array_values(array_filter(menu_do_usuario(), 'is_array'));
        $tabs = array_slice($m, 0, 3);
    }
    ?>
  </div>
</main>

<?php if (eh_aluno() && $pAtual !== 'chat' && $pAtual !== 'meu-chat'): ?>
  <a href="<?= url('chat') ?>" class="fab-chat" title="Falar com o treinador">
    <i class="fa-solid fa-comments"></i>
    <?php if (!empty($cont['msgs'])): ?><em><?= (int) $cont['msgs'] ?></em><?php endif; ?>
  </a>
<?php endif; ?>

<nav class="tabbar">
  <?php foreach ($tabs as $i => $it): ?>
    <a href="<?= url($it[0]) ?>" class="<?= $pAtual === $it[0] ? 'ativo' : '' ?>">
      <i class="fa-solid <?= $it[2] ?>"></i><span><?= e($it[1]) ?></span>
    </a>
    <?php if ($i === 0 && !eh_aluno()): ?>
      <a href="<?= url(eh_master() ? 'tenants' : 'alunos', ['novo' => 1]) ?>" class="fab"><i class="fa-solid fa-plus"></i></a>
    <?php endif; ?>
  <?php endforeach; ?>
  <?php if (!eh_aluno()): ?>
    <a href="#" id="abrirMenuTab"><i class="fa-solid fa-bars"></i><span>Menu</span></a>
  <?php endif; ?>
</nav>

<script>window.CSRF=<?= json_encode(csrf()) ?>;window.BASE=<?= json_encode(BASE_URL) ?>;
window.APP_VERSAO=<?= json_encode(APP_VERSAO) ?>;</script>
<script src="<?= BASE_DIR ?>/assets/app.js?v=<?= APP_VERSAO ?>"></script>
</body>
</html>
<?php }

function card_stat(string $label, string $num, string $icone, string $cor = ''): void {
    $cor = $cor ?: 'var(--cor-primaria)'; ?>
  <div class="col-6 col-lg-3">
    <div class="card-soft stat">
      <i class="fa-solid <?= $icone ?>" style="color:<?= $cor ?>"></i>
      <div><span class="num"><?= e($num) ?></span><small><?= e($label) ?></small></div>
    </div>
  </div>
<?php }

function badge(string $texto, string $estado): string {
    $mapa = ['pendente' => '#fff4e0;#c47f12', 'andamento' => '#e7f0ff;#2d7ff9',
             'ok' => '#e6f7ee;#1e9e57', 'erro' => '#fdecec;#d9433a', 'pausa' => '#f0eefe;#6a4cff'];
    $par = explode(';', $mapa[$estado] ?? $mapa['pausa']);
    return '<span class="badge-status" style="background:' . $par[0] . ';color:' . $par[1] . '">' . e($texto) . '</span>';
}

function vazio(string $msg, string $icone = 'fa-inbox'): void {
    echo '<div class="vazio"><i class="fa-solid ' . $icone . '"></i>' . e($msg) . '</div>';
}
