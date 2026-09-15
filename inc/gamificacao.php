<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/**
 * Jejum intermitente + gamificação (XP, nível, streak, conquistas, recordes).
 * Referência de fases do jejum: protocolos e estágios usados pelos apps Zero / LIFE Fasting.
 */

// ---------------------------------------------------------------- JEJUM
/** Protocolos disponíveis: chave => [rótulo, horas de jejum, descrição] */
function protocolos_jejum(): array {
    return [
        '12:12' => ['12:12', 12, 'Iniciante — 12h de jejum, 12h de alimentação'],
        '14:10' => ['14:10', 14, 'Leve — bom para adaptação'],
        '16:8'  => ['16:8',  16, 'O mais usado — equilíbrio entre resultado e rotina'],
        '18:6'  => ['18:6',  18, 'Intermediário — entra em cetose com folga'],
        '20:4'  => ['20:4',  20, 'Avançado (Warrior) — janela de 4h'],
        'OMAD'  => ['OMAD',  23, 'Uma refeição por dia'],
        '24h'   => ['24h',   24, 'Jejum de um dia inteiro'],
        '36h'   => ['36h',   36, 'Prolongado — só com acompanhamento'],
    ];
}

/** Fases biológicas do jejum: [hora inicial, nome, ícone, cor, o que acontece] */
function fases_jejum(): array {
    return [
        [0,  'Saciado',          'fa-utensils',           '#7A828C', 'Digestão e absorção. A insulina ainda está alta.'],
        [4,  'Queima de glicose','fa-bolt',               '#2D7FF9', 'O corpo consome o glicogênio do fígado como combustível.'],
        [12, 'Lipólise',         'fa-fire-flame-curved',  '#F26522', 'Glicogênio baixo: o corpo começa a queimar gordura.'],
        [16, 'Cetose',           'fa-bolt-lightning',     '#D98E0B', 'Produção de corpos cetônicos. Energia estável e foco.'],
        [18, 'Autofagia',        'fa-recycle',            '#1E9E57', 'Reciclagem celular — o corpo faz a própria faxina.'],
        [24, 'Jejum profundo',   'fa-dna',                '#7A28C7', 'Autofagia intensa e pico de hormônio do crescimento.'],
    ];
}

function fase_atual(float $horas): array {
    $fases = fases_jejum();
    $atual = $fases[0];
    foreach ($fases as $f) { if ($horas >= $f[0]) { $atual = $f; } }
    return $atual;
}

/** Jejum em andamento do aluno (ou null). */
function jejum_ativo(int $alunoId): ?array {
    return um("SELECT * FROM tr_jejum WHERE aluno_id=? AND status='andamento' ORDER BY id DESC LIMIT 1", [$alunoId]);
}

// ---------------------------------------------------------------- GAMIFICAÇÃO
/** Sequência (streak) de dias seguidos com treino concluído. */
function streak_treinos(int $alunoId): int {
    $datas = array_column(todos("SELECT DISTINCT data FROM tr_sessoes
        WHERE aluno_id=? AND status='concluida' ORDER BY data DESC LIMIT 400", [$alunoId]), 'data');
    if (!$datas) return 0;
    $hoje = new DateTime('today');
    $primeira = new DateTime($datas[0]);
    if ((int) $hoje->diff($primeira)->days > 1) return 0;   // quebrou (mais de 1 dia sem treinar)
    $streak = 0;
    $ref = $primeira;
    foreach ($datas as $d) {
        $dt = new DateTime($d);
        if ($dt->format('Y-m-d') === $ref->format('Y-m-d')) {
            $streak++;
            $ref = (clone $dt)->modify('-1 day');
        } else { break; }
    }
    return $streak;
}

/** XP total: 50 por treino, 30 por jejum concluído, 10 por dia batendo a meta de água. */
function xp_total(int $alunoId): int {
    $treinos = (int) valor("SELECT COUNT(*) FROM tr_sessoes WHERE aluno_id=? AND status='concluida'", [$alunoId]);
    $jejuns  = (int) valor("SELECT COUNT(*) FROM tr_jejum WHERE aluno_id=? AND status='concluido'", [$alunoId]);
    $meta    = (float) (valor('SELECT meta_agua FROM tr_usuarios WHERE id=?', [$alunoId]) ?: 3);
    $aguas   = (int) valor('SELECT COUNT(*) FROM tr_agua WHERE aluno_id=? AND litros>=?', [$alunoId, $meta]);
    return $treinos * 50 + $jejuns * 30 + $aguas * 10;
}

/** Nível a partir do XP: cada nível custa 200 XP a mais que o anterior. */
function nivel_do_xp(int $xp): array {
    $nivel = 1; $base = 0; $custo = 200;
    while ($xp >= $base + $custo) { $base += $custo; $nivel++; $custo += 100; }
    return [
        'nivel' => $nivel,
        'atual' => $xp - $base,
        'proximo' => $custo,
        'pct' => $custo > 0 ? min(100, (int) round(($xp - $base) / $custo * 100)) : 0,
        'titulo' => titulo_nivel($nivel),
    ];
}
function titulo_nivel(int $n): string {
    if ($n >= 25) return 'Lenda';
    if ($n >= 18) return 'Elite';
    if ($n >= 12) return 'Avançado';
    if ($n >= 7)  return 'Consistente';
    if ($n >= 4)  return 'Dedicado';
    return 'Iniciante';
}

/** Catálogo de conquistas: chave => [nome, ícone, cor, descrição, função que verifica] */
function catalogo_conquistas(): array {
    return [
        'primeiro_treino' => ['Primeiro treino', 'fa-shoe-prints', '#2D7FF9', 'Concluiu o primeiro treino'],
        'treinos_10'      => ['10 treinos', 'fa-dumbbell', '#2D7FF9', 'Concluiu 10 treinos'],
        'treinos_50'      => ['50 treinos', 'fa-dumbbell', '#7A28C7', 'Concluiu 50 treinos'],
        'treinos_100'     => ['100 treinos', 'fa-crown', '#D98E0B', 'Concluiu 100 treinos'],
        'streak_3'        => ['3 dias seguidos', 'fa-fire', '#F26522', 'Treinou 3 dias seguidos'],
        'streak_7'        => ['Semana perfeita', 'fa-fire-flame-curved', '#D9433A', 'Treinou 7 dias seguidos'],
        'streak_30'       => ['Mês de fogo', 'fa-meteor', '#7A28C7', 'Treinou 30 dias seguidos'],
        'jejum_primeiro'  => ['Primeiro jejum', 'fa-hourglass-start', '#1E9E57', 'Concluiu o primeiro jejum'],
        'jejum_16'        => ['Cetose', 'fa-bolt-lightning', '#D98E0B', 'Passou de 16h de jejum'],
        'jejum_autofagia' => ['Autofagia', 'fa-recycle', '#1E9E57', 'Passou de 18h de jejum'],
        'jejum_10'        => ['10 jejuns', 'fa-hourglass-half', '#157347', 'Concluiu 10 jejuns'],
        'agua_meta'       => ['Hidratado', 'fa-droplet', '#2D7FF9', 'Bateu a meta de água em um dia'],
        'agua_7'          => ['Semana hidratada', 'fa-water', '#2D7FF9', 'Bateu a meta de água 7 vezes'],
        'dieta_dia'       => ['Dieta em dia', 'fa-utensils', '#F26522', 'Marcou todas as refeições de um dia'],
        'pr_carga'        => ['Novo recorde', 'fa-trophy', '#D98E0B', 'Bateu um recorde de carga'],
        'avaliacao_2'     => ['Evoluindo', 'fa-chart-line', '#1E9E57', 'Tem 2 ou mais avaliações físicas'],
    ];
}

/** Recalcula e concede conquistas. Retorna as chaves desbloqueadas AGORA. */
function checar_conquistas(int $alunoId): array {
    $ja = array_column(todos('SELECT chave FROM tr_conquistas WHERE aluno_id=?', [$alunoId]), 'chave');
    $treinos = (int) valor("SELECT COUNT(*) FROM tr_sessoes WHERE aluno_id=? AND status='concluida'", [$alunoId]);
    $jejuns  = (int) valor("SELECT COUNT(*) FROM tr_jejum WHERE aluno_id=? AND status='concluido'", [$alunoId]);
    $maxJejum = (float) (valor("SELECT MAX(horas) FROM tr_jejum WHERE aluno_id=? AND status='concluido'", [$alunoId]) ?: 0);
    $streak  = streak_treinos($alunoId);
    $meta    = (float) (valor('SELECT meta_agua FROM tr_usuarios WHERE id=?', [$alunoId]) ?: 3);
    $aguas   = (int) valor('SELECT COUNT(*) FROM tr_agua WHERE aluno_id=? AND litros>=?', [$alunoId, $meta]);
    $avs     = (int) valor('SELECT COUNT(*) FROM tr_avaliacoes WHERE aluno_id=?', [$alunoId]);

    $regras = [
        'primeiro_treino' => $treinos >= 1,   'treinos_10' => $treinos >= 10,
        'treinos_50' => $treinos >= 50,       'treinos_100' => $treinos >= 100,
        'streak_3' => $streak >= 3,           'streak_7' => $streak >= 7,
        'streak_30' => $streak >= 30,
        'jejum_primeiro' => $jejuns >= 1,     'jejum_16' => $maxJejum >= 16,
        'jejum_autofagia' => $maxJejum >= 18, 'jejum_10' => $jejuns >= 10,
        'agua_meta' => $aguas >= 1,           'agua_7' => $aguas >= 7,
        'avaliacao_2' => $avs >= 2,
    ];
    $novas = [];
    foreach ($regras as $chave => $ok) {
        if ($ok && !in_array($chave, $ja, true)) {
            q('INSERT IGNORE INTO tr_conquistas (aluno_id, chave) VALUES (?,?)', [$alunoId, $chave]);
            $novas[] = $chave;
        }
    }
    return $novas;
}

/** Concede uma conquista pontual (ex.: recorde de carga). Retorna true se for nova. */
function conceder(int $alunoId, string $chave): bool {
    if (um('SELECT id FROM tr_conquistas WHERE aluno_id=? AND chave=?', [$alunoId, $chave])) return false;
    q('INSERT IGNORE INTO tr_conquistas (aluno_id, chave) VALUES (?,?)', [$alunoId, $chave]);
    return true;
}

/** Melhor carga já registrada em um exercício (recorde pessoal). */
function recorde_exercicio(int $alunoId, int $exercicioId): float {
    return (float) (valor("SELECT MAX(CAST(ss.carga AS DECIMAL(10,2)))
        FROM tr_sessao_series ss
        JOIN tr_sessoes s ON s.id=ss.sessao_id
        JOIN tr_ficha_exercicios fx ON fx.id=ss.ficha_exercicio_id
        WHERE s.aluno_id=? AND fx.exercicio_id=? AND ss.concluida=1", [$alunoId, $exercicioId]) ?: 0);
}
