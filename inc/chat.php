<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** Chat treinador ↔ aluno + notificações push */
require_once __DIR__ . '/push.php';

/** Mensagens de uma conversa (sempre identificada pelo aluno). */
function conversa(int $alunoId, int $limite = 200): array {
    $rows = todos('SELECT m.*, u.nome, u.tipo, u.foto FROM tr_mensagens m
                   JOIN tr_usuarios u ON u.id=m.de_id
                   WHERE m.aluno_id=? ORDER BY m.id DESC LIMIT ' . (int) $limite, [$alunoId]);
    return array_reverse($rows);   // cronológico: mais antiga em cima
}

/** Marca como lidas as mensagens que o usuário logado recebeu. */
function marcar_lidas(int $alunoId, int $usuarioId): void {
    q('UPDATE tr_mensagens SET lida=1 WHERE aluno_id=? AND de_id<>? AND lida=0', [$alunoId, $usuarioId]);
}

/** Quantas mensagens não lidas o usuário logado tem. */
function nao_lidas(int $usuarioId, string $tipo, int $tenantId): int {
    if ($tipo === 'aluno') {
        return (int) valor('SELECT COUNT(*) FROM tr_mensagens WHERE aluno_id=? AND de_id<>? AND lida=0',
                           [$usuarioId, $usuarioId]);
    }
    return (int) valor('SELECT COUNT(*) FROM tr_mensagens m JOIN tr_usuarios u ON u.id=m.aluno_id
                        WHERE m.tenant_id=? AND m.de_id=m.aluno_id AND m.lida=0', [$tenantId]);
}

/** Lista de conversas do treinador com prévia e não lidas. */
function conversas_do_treinador(int $tenantId): array {
    return todos("SELECT u.id, u.nome, u.foto,
        (SELECT texto FROM tr_mensagens m WHERE m.aluno_id=u.id ORDER BY m.id DESC LIMIT 1) AS ultima,
        (SELECT criado_em FROM tr_mensagens m WHERE m.aluno_id=u.id ORDER BY m.id DESC LIMIT 1) AS ultima_em,
        (SELECT COUNT(*) FROM tr_mensagens m WHERE m.aluno_id=u.id AND m.de_id=u.id AND m.lida=0) AS nao_lidas
        FROM tr_usuarios u
        WHERE u.tenant_id=? AND u.tipo='aluno' AND u.status='ativo'
        ORDER BY nao_lidas DESC, ultima_em IS NULL, ultima_em DESC, u.nome", [$tenantId]);
}

/** Envia notificação push para um usuário (todas as inscrições dele). */
function push_para(int $usuarioId, string $titulo, string $corpo, string $url = ''): int {
    $subs = todos('SELECT * FROM tr_push WHERE usuario_id=?', [$usuarioId]);
    if (!$subs) return 0;
    $payload = json_encode(['titulo' => $titulo, 'corpo' => mb_substr($corpo, 0, 160), 'url' => $url],
                           JSON_UNESCAPED_UNICODE);
    $ok = 0;
    foreach ($subs as $s) {
        $r = push_enviar($s['endpoint'], $s['p256dh'], $s['auth'], $payload);
        if (!empty($r['ok'])) { $ok++; }
        elseif (in_array((int) ($r['status'] ?? 0), [404, 410], true)) {
            q('DELETE FROM tr_push WHERE id=?', [$s['id']]);   // inscrição morta
        }
    }
    return $ok;
}

/** Renderiza a lista de mensagens (usada no load inicial e no polling). */
function html_mensagens(array $msgs, int $euId): string {
    if (!$msgs) {
        return '<div class="vazio"><i class="fa-solid fa-comments"></i>Nenhuma mensagem ainda. Diga um oi!</div>';
    }
    $h = ''; $diaAtual = '';
    foreach ($msgs as $m) {
        $dia = date('Y-m-d', strtotime($m['criado_em']));
        if ($dia !== $diaAtual) {
            $diaAtual = $dia;
            $rot = $dia === date('Y-m-d') ? 'Hoje'
                 : ($dia === date('Y-m-d', strtotime('-1 day')) ? 'Ontem' : date('d/m/Y', strtotime($dia)));
            $h .= '<div class="msg-dia"><span>' . e($rot) . '</span></div>';
        }
        $meu = (int) $m['de_id'] === $euId;
        $h .= '<div class="msg ' . ($meu ? 'eu' : 'ele') . '">'
            . '<div class="bolha">' . nl2br(e($m['texto']))
            . '<time>' . date('H:i', strtotime($m['criado_em']))
            . ($meu && $m['lida'] ? ' <i class="fa-solid fa-check-double"></i>' : '')
            . '</time></div></div>';
    }
    return $h;
}
