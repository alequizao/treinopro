<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/**
 * TreinoPro — núcleo do sistema (config, banco, sessão, tenant, helpers)
 * SaaS whitelabel para personal trainers.
 */
declare(strict_types=1);

define('APP_NOME',   'TreinoPro');
define('APP_VERSAO', '1.4.2');   // ver CHANGELOG.md — subir a cada alteração
define('DB_HOST', 'SEU_VALOR_AQUI');
define('DB_NAME', 'SEU_VALOR_AQUI');
define('DB_USER', 'SEU_VALOR_AQUI');
define('DB_PASS', 'SEU_VALOR_AQUI');
define('RAIZ', dirname(__DIR__));
define('UPLOADS', RAIZ . '/uploads');

date_default_timezone_set('America/Maceio');
mb_internal_encoding('UTF-8');

ini_set('display_errors', '0');
error_reporting(E_ALL);

// ---------------------------------------------------------------- banco
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => false]
        );
    }
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement {
    $st = db()->prepare($sql); $st->execute($p); return $st;
}
function um(string $sql, array $p = []) { $r = q($sql, $p)->fetch(); return $r === false ? null : $r; }
function todos(string $sql, array $p = []): array { return q($sql, $p)->fetchAll(); }
function valor(string $sql, array $p = []) { $r = q($sql, $p)->fetch(PDO::FETCH_NUM); return $r ? $r[0] : null; }
function inserir(string $tabela, array $dados): int {
    $cols = array_keys($dados);
    $sql = 'INSERT INTO ' . $tabela . ' (`' . implode('`,`', $cols) . '`) VALUES (:' . implode(',:', $cols) . ')';
    q($sql, $dados);
    return (int) db()->lastInsertId();
}
function atualizar(string $tabela, array $dados, string $where, array $wp = []): void {
    $sets = [];
    foreach (array_keys($dados) as $c) { $sets[] = "`$c`=:$c"; }
    q('UPDATE ' . $tabela . ' SET ' . implode(',', $sets) . ' WHERE ' . $where, array_merge($dados, $wp));
}

// ---------------------------------------------------------------- sessão
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
session_name('treinopro');
session_start();

function csrf(): string {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
    return $_SESSION['csrf'];
}
function checa_csrf(): void {
    $t = $_POST['csrf'] ?? $_GET['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) $t)) { responde_erro('Sessão expirada. Recarregue a página.', 419); }
}

// ---------------------------------------------------------------- helpers
function e($v): string { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function post(string $k, $d = '') { return isset($_POST[$k]) ? (is_string($_POST[$k]) ? trim($_POST[$k]) : $_POST[$k]) : $d; }
function get(string $k, $d = '') { return isset($_GET[$k]) ? (is_string($_GET[$k]) ? trim($_GET[$k]) : $_GET[$k]) : $d; }
function id_get(string $k = 'id'): int { return (int) get($k, 0); }
function dinheiro($v): string { return 'R$ ' . number_format((float) $v, 2, ',', '.'); }
function data_br(?string $d): string { return $d ? date('d/m/Y', strtotime($d)) : '—'; }
function eh_ajax(): bool {
    return !empty($_POST['ajax']) || !empty($_GET['ajax'])
        || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}
function json_saida(array $d, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}
function responde_ok(string $msg = 'Salvo!', array $extra = []): void { json_saida(array_merge(['ok' => true, 'msg' => $msg], $extra)); }
function responde_erro(string $msg, int $code = 400): void { json_saida(['ok' => false, 'msg' => $msg], $code); }
function idade(?string $nasc): ?int {
    if (!$nasc) return null;
    return (int) (new DateTime($nasc))->diff(new DateTime('today'))->y;
}
function iniciais(string $nome): string {
    $p = preg_split('/\s+/', trim($nome));
    $s = mb_substr($p[0] ?? '', 0, 1);
    if (count($p) > 1) { $s .= mb_substr(end($p), 0, 1); }
    return mb_strtoupper($s);
}
/** URL amigável: /treinos, /aluno/12 … (cai para ?p= se o rewrite não estiver ativo) */
function url(string $p = '', array $args = []): string {
    $raiz = BASE_DIR === '' ? '/' : BASE_DIR . '/';
    if (defined('URLS_AMIGAVEIS') && URLS_AMIGAVEIS) {
        $u = $raiz . ($p ?: '');
        if ($args) { $u .= '?' . http_build_query($args); }
        return $u;
    }
    $u = BASE_URL . ($p ? ('?p=' . $p) : '');
    if ($args) { $u .= ($p ? '&' : '?') . http_build_query($args); }
    return $u;
}
function redir(string $p = '', array $args = []): void { header('Location: ' . url($p, $args)); exit; }
function log_acao(string $acao, string $detalhe = ''): void {
    inserir('tr_logs', ['tenant_id' => tenant_id() ?: null, 'usuario_id' => usuario()['id'] ?? null,
                        'acao' => $acao, 'detalhe' => mb_substr($detalhe, 0, 250)]);
}

// URL base (funciona tanto em treino.alequizao.com quanto em /treino/)
$dirBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
define('BASE_URL', ($dirBase === '' ? '/' : $dirBase . '/') . 'index.php');
define('BASE_DIR', $dirBase === '' ? '' : $dirBase);
// URLs amigáveis quando o mod_rewrite/.htaccess está ativo
define('URLS_AMIGAVEIS', is_file(RAIZ . '/.htaccess') && function_exists('apache_get_modules')
    ? in_array('mod_rewrite', apache_get_modules(), true)
    : is_file(RAIZ . '/.htaccess'));

// ---------------------------------------------------------------- tenant (whitelabel)
function resolve_tenant(): ?array {
    static $t = false;
    if ($t !== false) return $t;

    $host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    $t = null;

    // 1) domínio próprio do personal
    if ($host) { $t = um('SELECT * FROM tr_tenants WHERE dominio=?', [$host]); }

    // 2) subdomínio: fulano.treino.alequizao.com  /  fulano.seudominio.com
    if (!$t && $host && substr_count($host, '.') >= 2) {
        $sub = explode('.', $host)[0];
        if (!in_array($sub, ['www', 'treino', 'painel', 'app'], true)) {
            $t = um('SELECT * FROM tr_tenants WHERE slug=?', [$sub]);
        }
    }
    // 3) ?t=slug (usado no domínio principal e em publishdev.com.br/treino/)
    $slug = (string) get('t', '');
    if (!$t && $slug !== '') {
        $t = um('SELECT * FROM tr_tenants WHERE slug=?', [$slug]);
        if ($t) { $_SESSION['tenant_slug'] = $t['slug']; }
    }
    // 4) sessão (usuário já logado)
    if (!$t && !empty($_SESSION['usuario']['tenant_id'])) {
        $t = um('SELECT * FROM tr_tenants WHERE id=?', [$_SESSION['usuario']['tenant_id']]);
    }
    if (!$t && !empty($_SESSION['tenant_slug'])) {
        $t = um('SELECT * FROM tr_tenants WHERE slug=?', [$_SESSION['tenant_slug']]);
    }
    return $t;
}
function tenant(): ?array { return resolve_tenant(); }
function tenant_id(): int { $t = resolve_tenant(); return $t ? (int) $t['id'] : 0; }
function marca(): array {
    $t = resolve_tenant();
    return [
        'nome'  => $t['nome']  ?? APP_NOME,
        'logo'  => $t['logo']  ?? '',
        'cor'   => $t['cor_primaria'] ?? '#2563EB',
        'acao'  => $t['cor_acao'] ?? '#2B2B2B',
    ];
}

// ---------------------------------------------------------------- autenticação
function usuario(): ?array { return $_SESSION['usuario'] ?? null; }
function logado(): bool { return !empty($_SESSION['usuario']); }
function tipo(): string { return $_SESSION['usuario']['tipo'] ?? ''; }
function eh_master(): bool { return tipo() === 'master'; }
function eh_treinador(): bool { return tipo() === 'treinador'; }
function eh_aluno(): bool { return tipo() === 'aluno'; }

function exige_login(): void { if (!logado()) { redir('login'); } }
function exige(string ...$tipos): void {
    exige_login();
    if (!in_array(tipo(), $tipos, true)) {
        if (eh_ajax()) responde_erro('Sem permissão.', 403);
        redir('inicio');
    }
}
/** Garante que o registro pertence ao tenant logado (isolamento multi-tenant). */
function do_tenant(?array $reg): array {
    if (!$reg || (int) ($reg['tenant_id'] ?? 0) !== (int) (usuario()['tenant_id'] ?? -1)) {
        if (eh_ajax()) responde_erro('Registro não encontrado.', 404);
        http_response_code(404); exit('Registro não encontrado.');
    }
    return $reg;
}
function aluno_do_tenant(int $id): array {
    return do_tenant(um("SELECT * FROM tr_usuarios WHERE id=? AND tipo='aluno'", [$id]));
}

// ------------- personificação (personal entra na conta do aluno e volta) -------------
function personificando(): bool { return !empty($_SESSION['origem_uid']); }

/** O treinador assume a sessão de um aluno seu, guardando a conta de origem. */
function personificar(int $alvoId): bool {
    $eu = usuario();
    if (!$eu || !in_array($eu['tipo'], ['treinador', 'master'], true)) return false;
    $alvo = um('SELECT * FROM tr_usuarios WHERE id=?', [$alvoId]);
    if (!$alvo) return false;
    // treinador só entra em aluno do próprio tenant
    if ($eu['tipo'] === 'treinador'
        && !($alvo['tipo'] === 'aluno' && (int) $alvo['tenant_id'] === (int) $eu['tenant_id'])) return false;

    $_SESSION['origem_uid'] = $_SESSION['origem_uid'] ?? (int) $eu['id'];
    unset($alvo['senha']);
    $_SESSION['usuario'] = $alvo;
    session_regenerate_id(true);
    return true;
}

/** Volta para a conta que iniciou a personificação. */
function voltar_conta(): bool {
    if (!personificando()) return false;
    $u = um('SELECT * FROM tr_usuarios WHERE id=?', [(int) $_SESSION['origem_uid']]);
    unset($_SESSION['origem_uid']);
    if (!$u) return false;
    unset($u['senha']);
    $_SESSION['usuario'] = $u;
    session_regenerate_id(true);
    return true;
}

function tentar_login(string $login, string $senha): ?array {
    $login = trim($login);
    // master (sem tenant) primeiro
    $u = um("SELECT * FROM tr_usuarios WHERE email=? AND tipo='master'", [$login]);
    if (!$u) {
        $t = resolve_tenant();
        if ($t) {
            $u = um('SELECT * FROM tr_usuarios WHERE email=? AND tenant_id=?', [$login, $t['id']]);
        } else {
            // domínio principal: aceita login único no sistema todo
            $ls = todos('SELECT * FROM tr_usuarios WHERE email=?', [$login]);
            if (count($ls) === 1) { $u = $ls[0]; }
        }
    }
    if (!$u || $u['status'] !== 'ativo' || !password_verify($senha, $u['senha'])) { return null; }

    if ($u['tenant_id']) {
        $t = um('SELECT * FROM tr_tenants WHERE id=?', [$u['tenant_id']]);
        if (!$t || $t['status'] === 'suspenso') { return null; }
        $_SESSION['tenant_slug'] = $t['slug'];
    }
    unset($u['senha']);
    $_SESSION['usuario'] = $u;
    q('UPDATE tr_usuarios SET ultimo_acesso=NOW() WHERE id=?', [$u['id']]);
    return $u;
}

require_once __DIR__ . '/gamificacao.php';
require_once __DIR__ . '/corpo.php';

// ---------------------------------------------------------------- upload
function salvar_upload(string $campo, string $prefixo, string $tipo = 'imagem'): ?string {
    if (empty($_FILES[$campo]['tmp_name']) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) return null;
    $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));
    $permitidas = $tipo === 'video'
        ? ['mp4', 'webm', 'mov', 'm4v']
        : ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
    if (!in_array($ext, $permitidas, true)) return null;
    $limite = $tipo === 'video' ? 120 * 1024 * 1024 : 8 * 1024 * 1024;
    if (($_FILES[$campo]['size'] ?? 0) > $limite) return null;
    $sub = $tipo === 'video' ? '/videos' : '';
    if (!is_dir(UPLOADS . $sub)) { @mkdir(UPLOADS . $sub, 0775, true); }
    $nome = $prefixo . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (!move_uploaded_file($_FILES[$campo]['tmp_name'], UPLOADS . $sub . '/' . $nome)) return null;
    return 'uploads' . $sub . '/' . $nome;
}
/** Motivo da falha de upload, para mensagem amigável. */
function erro_upload(string $campo): ?string {
    if (empty($_FILES[$campo]['name'])) return null;
    $e = $_FILES[$campo]['error'] ?? UPLOAD_ERR_OK;
    if ($e === UPLOAD_ERR_INI_SIZE || $e === UPLOAD_ERR_FORM_SIZE) return 'Arquivo maior que o limite do servidor.';
    if ($e !== UPLOAD_ERR_OK) return 'Falha no envio do arquivo (código ' . $e . ').';
    return 'Formato ou tamanho não aceitos.';
}
