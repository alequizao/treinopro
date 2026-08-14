<?php
/**
 * Corpo & atividades: mapa muscular SVG, agrupamento de músculos,
 * cálculos de composição corporal e estatísticas de treino.
 */

/** Grupos musculares do sistema → região do mapa (frente/costas) */
function mapa_grupos(): array {
    return [
        'Peito'       => ['frente' => ['peito'], 'label' => 'Peitoral'],
        'Ombros'      => ['frente' => ['ombro'], 'costas' => ['ombro'], 'label' => 'Ombros'],
        'Bíceps'      => ['frente' => ['biceps'], 'label' => 'Bíceps'],
        'Tríceps'     => ['costas' => ['triceps'], 'label' => 'Tríceps'],
        'Costas'      => ['costas' => ['dorsal', 'trapezio', 'lombar'], 'frente' => ['trapezio_f'], 'label' => 'Dorsais'],
        'Abdômen'     => ['frente' => ['abdomen', 'obliquo'], 'label' => 'Abdômen'],
        'Pernas'      => ['frente' => ['quadriceps', 'tibial'], 'costas' => ['posterior'], 'label' => 'Pernas'],
        'Glúteos'     => ['costas' => ['gluteo'], 'label' => 'Glúteos'],
        'Panturrilha' => ['costas' => ['panturrilha'], 'frente' => ['tibial'], 'label' => 'Panturrilhas'],
        'Antebraço'   => ['frente' => ['antebraco'], 'costas' => ['antebraco'], 'label' => 'Antebraços'],
        'Cardio'      => ['label' => 'Cardio'],
    ];
}

/**
 * Boneco anatômico (frente ou costas) com os grupos musculares coloridos.
 * O lado esquerdo é desenhado e espelhado, garantindo simetria perfeita.
 * $cores = ['peito' => '#FF7A0F', ...] (id da parte => cor)
 */
function boneco(string $lado, array $cores, string $base = '#3A3A3C'): string {
    $c = function (string $id) use ($cores, $base) { return $cores[$id] ?? $base; };
    $uid = substr(md5($lado . implode(',', $cores) . $base), 0, 6);

    /** músculo com contorno anatômico fino */
    $m = function (string $d, string $fill, float $op = 1) {
        return '<path d="' . $d . '" fill="' . $fill . '" stroke="rgba(0,0,0,.30)" stroke-width=".7"'
             . ($op < 1 ? ' opacity="' . $op . '"' : '') . '/>';
    };

    // ---------------------------------------------------------- silhueta (V-taper)
    $centroSil =
        '<ellipse cx="100" cy="25" rx="14.5" ry="18"/>' .
        '<path d="M93 40 h14 v14 h-14 z"/>' .
        '<path d="M100 52 l20 6 c12 4 18 12 19 24 -1 15-5 30-9 45 -3 13-6 26-8 38 -1 11-2 21-2 30 H80
                  c0-9-1-19-2-30 -2-12-5-25-8-38 -4-15-8-30-9-45 1-12 7-20 19-24 z"/>' .
        '<path d="M80 195 h40 l-3 22 c-2 11-8 16-17 16 -9 0-15-5-17-16 z"/>';
    $ladoSil =
        // braço: deltoide → bíceps → antebraço → mão
        '<path d="M64 70 c-10 4-16 13-18 26 l-7 36 c-1 9 4 14 11 13 6-1 9-6 10-13 l7-36 z"/>' .
        '<path d="M43 138 c-5 15-8 32-9 51 -1 9 4 14 10 13 5-1 8-6 9-13 2-17 1-36-2-51 z"/>' .
        '<ellipse cx="43" cy="211" rx="8" ry="12"/>' .
        // perna: coxa → panturrilha → tornozelo → pé
        '<path d="M78 222 h20 l-1 66 c-1 17-8 26-17 25 -9-1-15-10-14-23 l5-45 z"/>' .
        '<path d="M74 305 h23 l-2 55 c-1 22-6 34-12 34 -6 0-12-12-13-34 z"/>' .
        '<path d="M77 392 h16 l-1 20 c0 6-4 9-8 9 -5 0-8-3-8-9 z"/>' .
        '<path d="M69 420 h25 c3 0 5 3 4 6 -1 3-4 5-9 5 H73 c-4 0-6-2-6-5 0-4 1-6 2-6 z"/>';

    // ---------------------------------------------------------- músculos
    if ($lado === 'frente') {
        $centro =
            $m('M100 52 l-16 5 c-8 3-11 8-11 15 0 4 3 6 8 6 h19 z', $c('trapezio_f')) .
            // reto abdominal: 4 pares (deixa a linha alba no centro)
            $m('M96 110 h-11 c-2 0-4 1-4 3 v11 c0 2 2 3 4 3 h11 z', $c('abdomen')) .
            $m('M96 129 h-11 c-2 0-4 1-4 3 v11 c0 2 2 3 4 3 h11 z', $c('abdomen')) .
            $m('M96 148 h-10 c-2 0-4 1-4 3 v11 c0 2 2 3 4 3 h10 z', $c('abdomen')) .
            $m('M96 167 h-9 c-3 0-5 2-6 5 -1 9 2 15 7 18 2 1 5 2 8 2 z', $c('abdomen'));
        $ladoEsq =
            $m('M65 63 c-11 5-17 15-18 28 9 5 17 1 21-8 3-8 3-18-3-20 z', $c('ombro')) .
            $m('M96 66 l-17 5 c-10 4-13 15-10 24 4 9 14 14 27 14 z', $c('peito')) .
            $m('M77 112 c-5 13-6 27-4 40 5 2 8-2 9-11 2-12 0-24-5-29 z', $c('obliquo')) .
            $m('M50 100 c-4 12-7 25-8 37 7 4 13-1 15-9 3-13 1-26-7-28 z', $c('biceps')) .
            $m('M44 148 c-4 13-6 27-7 40 6 4 11-1 12-9 2-13 1-25-5-31 z', $c('antebraco')) .
            $m('M96 236 l-14 3 c-7 11-9 27-8 44 1 16 5 27 11 31 6 1 9-6 10-17 2-19 2-42 1-61 z', $c('quadriceps')) .
            $m('M80 276 c-4 9-5 19-3 26 3 5 7 1 8-7 1-8-1-17-5-19 z', $c('quadriceps'), .75) .
            $m('M92 326 c-6 13-9 27-9 41 5 5 8 0 9-9 2-12 2-24 0-32 z', $c('tibial'));
    } else {
        $centro =
            $m('M100 54 l-17 6 c-8 5-10 13-7 23 5 15 13 25 24 29 z', $c('trapezio')) .
            $m('M100 156 l-14 5 c-6 6-6 16-1 22 4 5 9 7 15 7 z', $c('lombar')) .
            $m('M100 194 l-18 6 c-9 6-11 19-6 28 5 8 14 12 24 12 z', $c('gluteo'));
        $ladoEsq =
            $m('M65 63 c-11 5-17 15-18 28 9 5 17 1 21-8 3-8 3-18-3-20 z', $c('ombro')) .
            $m('M96 90 l-18 7 c-10 9-12 23-8 37 4 13 14 21 26 25 z', $c('dorsal')) .
            $m('M79 84 c-7 3-11 9-12 14 5 3 11 1 14-4 3-5 2-10-2-10 z', $c('dorsal'), .72) .
            $m('M50 100 c-4 12-7 25-8 37 7 4 13-1 15-9 3-13 1-26-7-28 z', $c('triceps')) .
            $m('M44 148 c-4 13-6 27-7 40 6 4 11-1 12-9 2-13 1-25-5-31 z', $c('antebraco')) .
            $m('M96 246 l-14 3 c-7 11-9 27-7 43 2 14 6 24 12 27 6 0 9-7 9-18 1-18 1-37 0-55 z', $c('posterior')) .
            $m('M96 326 c-8 3-13 15-14 29 -1 14 2 26 7 31 5 1 7-6 7-16 1-16 1-31 0-44 z', $c('panturrilha')) .
            $m('M80 332 c-5 9-6 19-5 29 1 8 4 13 7 12 3-2 3-10 2-18 -1-10-1-19-4-23 z', $c('panturrilha'), .75);
        }

    // linhas centrais (linha alba na frente, coluna nas costas)
    $centroLinha = $lado === 'frente'
        ? '<path d="M100 70 V188" stroke="rgba(0,0,0,.30)" stroke-width="1" fill="none"/>'
        : '<path d="M100 62 V190" stroke="rgba(0,0,0,.30)" stroke-width="1" fill="none"/>';

    $sil = $centroSil . $ladoSil . '<g transform="translate(200,0) scale(-1,1)">' . $ladoSil . '</g>';

    return '<svg viewBox="0 0 200 440" class="boneco" role="img">
      <defs>
        <clipPath id="cl' . $uid . '">' . $sil . '</clipPath>
        <linearGradient id="lz' . $uid . '" x1="0" y1="0" x2="1" y2="0">
          <stop offset="0%" stop-color="#fff" stop-opacity=".10"/>
          <stop offset="46%" stop-color="#fff" stop-opacity="0"/>
          <stop offset="100%" stop-color="#000" stop-opacity=".22"/>
        </linearGradient>
      </defs>

      <g fill="' . $base . '">' . $sil . '</g>

      <g clip-path="url(#cl' . $uid . ')">
        ' . $centro . $ladoEsq . '
        <g transform="translate(200,0) scale(-1,1)">' . $centro . $ladoEsq . '</g>
        ' . $centroLinha . '
        <rect x="0" y="0" width="200" height="440" fill="url(#lz' . $uid . ')"/>
      </g>

      <g fill="none" stroke="rgba(0,0,0,.30)" stroke-width=".9" stroke-linejoin="round">' . $sil . '</g>
    </svg>';
}

/** Monta o array de cores das partes a partir de valores por grupo muscular. */
function cores_do_mapa(array $porGrupo, callable $cor): array {
    $out = [];
    foreach (mapa_grupos() as $grupo => $cfg) {
        if (!isset($porGrupo[$grupo])) continue;
        $c = $cor($porGrupo[$grupo]);
        if (!$c) continue;
        foreach (['frente', 'costas'] as $lado) {
            foreach ($cfg[$lado] ?? [] as $parte) { $out[$parte] = $c; }
        }
    }
    return $out;
}

/** Volume de treino por grupo muscular no período (nº de séries concluídas). */
function volume_por_grupo(int $alunoId, int $dias = 7): array {
    $rows = todos("SELECT e.grupo, COUNT(*) AS series,
                   COALESCE(SUM(CAST(ss.carga AS DECIMAL(10,2)) * CAST(ss.reps AS DECIMAL(10,2))),0) AS volume,
                   MAX(s.data) AS ultimo
        FROM tr_sessao_series ss
        JOIN tr_sessoes s ON s.id=ss.sessao_id
        JOIN tr_ficha_exercicios fx ON fx.id=ss.ficha_exercicio_id
        JOIN tr_exercicios e ON e.id=fx.exercicio_id
        WHERE s.aluno_id=? AND ss.concluida=1 AND s.data >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        GROUP BY e.grupo ORDER BY series DESC", [$alunoId, $dias]);
    $out = [];
    foreach ($rows as $r) { $out[$r['grupo']] = $r; }
    return $out;
}

/**
 * Estado de recuperação por grupo: quantos dias desde o último estímulo.
 * 0-1 dia = fadigado; 2 dias = em recuperação; 3-6 = recuperado; 7+ ou nunca = enfraquecido.
 */
function recuperacao_muscular(int $alunoId): array {
    $rows = todos("SELECT e.grupo, MAX(s.data) AS ultimo
        FROM tr_sessao_series ss
        JOIN tr_sessoes s ON s.id=ss.sessao_id
        JOIN tr_ficha_exercicios fx ON fx.id=ss.ficha_exercicio_id
        JOIN tr_exercicios e ON e.id=fx.exercicio_id
        WHERE s.aluno_id=? AND ss.concluida=1 GROUP BY e.grupo", [$alunoId]);
    $ult = [];
    foreach ($rows as $r) { $ult[$r['grupo']] = $r['ultimo']; }

    $out = [];
    foreach (array_keys(mapa_grupos()) as $g) {
        if ($g === 'Cardio') continue;
        if (empty($ult[$g])) { $out[$g] = ['estado' => 'enfraquecido', 'dias' => null]; continue; }
        $dias = (int) ((strtotime('today') - strtotime($ult[$g])) / 86400);
        if ($dias <= 1)      { $e = 'fadigado'; }
        elseif ($dias === 2) { $e = 'recuperando'; }
        elseif ($dias <= 6)  { $e = 'recuperado'; }
        else                 { $e = 'enfraquecido'; }
        $out[$g] = ['estado' => $e, 'dias' => $dias];
    }
    return $out;
}

function cores_recuperacao(): array {
    return [
        'fadigado'     => ['#FF453A', 'Fadigado'],
        'recuperando'  => ['#FFD60A', 'Em recuperação'],
        'recuperado'   => ['#0A84FF', 'Recuperado'],
        'enfraquecido' => ['#32D74B', 'Enfraquecido'],
    ];
}

/** Estatísticas do período: tempo, kcal estimadas, exercícios, séries, reps, carga. */
function estatisticas_treino(int $alunoId, int $dias = 7): array {
    $ini = date('Y-m-d', strtotime("-$dias day"));
    $sessoes = todos("SELECT * FROM tr_sessoes WHERE aluno_id=? AND status='concluida' AND data>=?", [$alunoId, $ini]);
    $seg = 0;
    foreach ($sessoes as $s) {
        if ($s['inicio_em'] && $s['fim_em']) { $seg += max(0, strtotime($s['fim_em']) - strtotime($s['inicio_em'])); }
    }
    $ag = um("SELECT COUNT(*) AS series,
              COALESCE(SUM(CAST(ss.reps AS DECIMAL(10,2))),0) AS reps,
              COALESCE(SUM(CAST(ss.carga AS DECIMAL(10,2))),0) AS carga,
              COUNT(DISTINCT ss.ficha_exercicio_id) AS exercicios
        FROM tr_sessao_series ss JOIN tr_sessoes s ON s.id=ss.sessao_id
        WHERE s.aluno_id=? AND ss.concluida=1 AND s.data>=?", [$alunoId, $ini]);

    return [
        'treinos'    => count($sessoes),
        'segundos'   => $seg,
        'kcal'       => (int) round($seg / 60 * 7.5),          // ~7,5 kcal/min de musculação
        'exercicios' => (int) ($ag['exercicios'] ?? 0),
        'series'     => (int) ($ag['series'] ?? 0),
        'reps'       => (int) ($ag['reps'] ?? 0),
        'carga'      => (float) ($ag['carga'] ?? 0),
    ];
}

/** Série diária para os mini-gráficos (sparklines). */
function serie_diaria(int $alunoId, int $dias, string $metrica): array {
    $out = [];
    for ($i = $dias - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i day"));
        if ($metrica === 'tempo') {
            $v = (float) (valor("SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE,inicio_em,fim_em)),0)
                FROM tr_sessoes WHERE aluno_id=? AND data=? AND status='concluida'", [$alunoId, $d]) ?: 0);
        } elseif ($metrica === 'carga') {
            $v = (float) (valor("SELECT COALESCE(SUM(CAST(ss.carga AS DECIMAL(10,2))),0)
                FROM tr_sessao_series ss JOIN tr_sessoes s ON s.id=ss.sessao_id
                WHERE s.aluno_id=? AND s.data=? AND ss.concluida=1", [$alunoId, $d]) ?: 0);
        } elseif ($metrica === 'reps') {
            $v = (float) (valor("SELECT COALESCE(SUM(CAST(ss.reps AS DECIMAL(10,2))),0)
                FROM tr_sessao_series ss JOIN tr_sessoes s ON s.id=ss.sessao_id
                WHERE s.aluno_id=? AND s.data=? AND ss.concluida=1", [$alunoId, $d]) ?: 0);
        } else { // séries
            $v = (float) (valor("SELECT COUNT(*) FROM tr_sessao_series ss JOIN tr_sessoes s ON s.id=ss.sessao_id
                WHERE s.aluno_id=? AND s.data=? AND ss.concluida=1", [$alunoId, $d]) ?: 0);
        }
        $out[] = $v;
    }
    return $out;
}

/** Sparkline SVG (linha + área) a partir de uma série de números. */
function sparkline(array $v, string $cor = '#FF7A0F', int $w = 130, int $h = 46): string {
    if (count($v) < 2) { $v = array_pad($v, 2, 0); }
    $max = max($v) ?: 1;
    $n = count($v);
    $pts = [];
    foreach ($v as $i => $x) {
        $px = $i * ($w / ($n - 1));
        $py = $h - ($x / $max) * ($h - 6) - 3;
        $pts[] = round($px, 1) . ',' . round($py, 1);
    }
    $linha = 'M' . implode(' L', $pts);
    $area  = $linha . " L$w,$h L0,$h Z";
    $g = 'g' . substr(md5($cor . $w . $n . implode(',', $v)), 0, 6);
    return '<svg class="spark" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none">
      <defs><linearGradient id="' . $g . '" x1="0" y1="0" x2="0" y2="1">
        <stop offset="0%" stop-color="' . $cor . '" stop-opacity=".45"/>
        <stop offset="100%" stop-color="' . $cor . '" stop-opacity="0"/></linearGradient></defs>
      <path d="' . $area . '" fill="url(#' . $g . ')"/>
      <path d="' . $linha . '" fill="none" stroke="' . $cor . '" stroke-width="2.2"
            stroke-linecap="round" stroke-linejoin="round"/></svg>';
}

/** Tipos de equipamento usados no período (para o gráfico de pizza). */
function tipos_exercicio(int $alunoId, int $dias = 7): array {
    return todos("SELECT COALESCE(NULLIF(e.equipamento,''),'Outros') AS tipo, COUNT(*) AS qtd
        FROM tr_sessao_series ss
        JOIN tr_sessoes s ON s.id=ss.sessao_id
        JOIN tr_ficha_exercicios fx ON fx.id=ss.ficha_exercicio_id
        JOIN tr_exercicios e ON e.id=fx.exercicio_id
        WHERE s.aluno_id=? AND ss.concluida=1 AND s.data>=DATE_SUB(CURDATE(),INTERVAL ? DAY)
        GROUP BY tipo ORDER BY qtd DESC", [$alunoId, $dias]);
}

// ---------------------------------------------------------------- COMPOSIÇÃO CORPORAL
function imc(?float $peso, ?float $alturaCm): ?float {
    if (!$peso || !$alturaCm) return null;
    return round($peso / pow($alturaCm / 100, 2), 1);
}
function classificacao_imc(?float $i): array {
    if ($i === null) return ['—', '#8E8E93'];
    if ($i < 18.5) return ['Abaixo do peso', '#FFD60A'];
    if ($i < 25)   return ['Peso normal', '#32D74B'];
    if ($i < 30)   return ['Sobrepeso', '#FF9F0A'];
    if ($i < 35)   return ['Obesidade I', '#FF453A'];
    return ['Obesidade II+', '#FF453A'];
}
/** Faixa de peso ideal pelo IMC saudável (18,5–24,9). */
function peso_ideal(?float $alturaCm): ?array {
    if (!$alturaCm) return null;
    $m = $alturaCm / 100;
    return [round(18.5 * $m * $m), round(24.9 * $m * $m)];
}
/** Composição estimada quando o treinador não preencheu manualmente. */
function composicao(?float $peso, ?float $gorduraPct, array $manual = []): array {
    $gorda  = $manual['massa_gorda'] ?? (($peso && $gorduraPct) ? round($peso * $gorduraPct / 100, 1) : null);
    $magra  = $manual['massa_magra'] ?? (($peso && $gorda !== null) ? round($peso - $gorda, 1) : null);
    $ossea  = $manual['massa_ossea'] ?? ($peso ? round($peso * 0.15, 1) : null);
    $resid  = $manual['massa_residual'] ?? ($peso ? round($peso * 0.12, 1) : null);
    $muscul = $manual['massa_muscular'] ?? (($magra !== null && $ossea !== null && $resid !== null)
              ? round($magra - $ossea - $resid, 1) : null);
    return ['massa_gorda' => $gorda, 'massa_magra' => $magra, 'massa_ossea' => $ossea,
            'massa_residual' => $resid, 'massa_muscular' => $muscul];
}

/** Campos de medida corporal usados nos formulários e telas. */
function campos_medidas(): array {
    return [
        'ombros' => 'Ombros', 'torax' => 'Peitoral', 'cintura' => 'Cintura', 'abdomen' => 'Abdômen',
        'quadril' => 'Quadril', 'braco_e' => 'Braço (E)', 'braco_d' => 'Braço (D)',
        'antebraco_e' => 'Antebraço (E)', 'antebraco_d' => 'Antebraço (D)',
        'coxa_e' => 'Perna (E)', 'coxa_d' => 'Perna (D)',
        'panturrilha_e' => 'Panturrilha (E)', 'panturrilha' => 'Panturrilha (D)',
    ];
}
/** Dobras cutâneas (protocolo de 7 dobras). */
function campos_dobras(): array {
    return ['triciptal' => 'Tricipital', 'subescapular' => 'Subescapular', 'peitoral' => 'Peitoral',
            'axilar' => 'Axilar média', 'suprailiaca' => 'Supra-ilíaca', 'abdominal' => 'Abdominal',
            'coxa' => 'Coxa'];
}
