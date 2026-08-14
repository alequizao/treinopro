<?php
/**
 * Seed do TreinoPro — biblioteca global de exercícios + master + conta DEMO completa.
 * Uso: php sql/seed.php
 */
require __DIR__ . '/../inc/boot.php';
if (PHP_SAPI !== 'cli') { exit('Somente CLI.'); }

function say(string $s): void { echo $s . PHP_EOL; }
$hash = function ($s) { return password_hash($s, PASSWORD_DEFAULT); };

// ---------------------------------------------------------------- master
$m = um("SELECT id FROM tr_usuarios WHERE tipo='master' AND email='alequizao'");
if (!$m) {
    inserir('tr_usuarios', ['tenant_id' => null, 'tipo' => 'master', 'nome' => 'Alequizão',
        'email' => 'alequizao', 'senha' => $hash(getenv('SEED_MASTER_PASS') ?: 'trocar-esta-senha')]);
    say('✔ master criado: alequizao / (senha definida em SEED_MASTER_PASS, padrão: trocar-esta-senha)');
} else { say('· master já existia'); }

// ---------------------------------------------------------------- biblioteca global
$biblioteca = [
    ['Supino reto com barra', 'Peito', 'Barra', 'https://www.youtube.com/results?search_query=supino+reto+barra'],
    ['Supino inclinado com halteres', 'Peito', 'Halteres', ''],
    ['Crucifixo máquina (voador)', 'Peito', 'Máquina', ''],
    ['Crossover na polia', 'Peito', 'Polia', ''],
    ['Flexão de braço', 'Peito', 'Peso corporal', ''],
    ['Puxada frente na polia', 'Costas', 'Polia', ''],
    ['Remada curvada com barra', 'Costas', 'Barra', ''],
    ['Remada unilateral com halter', 'Costas', 'Halteres', ''],
    ['Barra fixa', 'Costas', 'Peso corporal', ''],
    ['Pulldown com corda', 'Costas', 'Polia', ''],
    ['Desenvolvimento com halteres', 'Ombros', 'Halteres', ''],
    ['Elevação lateral', 'Ombros', 'Halteres', ''],
    ['Elevação frontal', 'Ombros', 'Halteres', ''],
    ['Crucifixo inverso', 'Ombros', 'Máquina', ''],
    ['Encolhimento de ombros', 'Ombros', 'Halteres', ''],
    ['Rosca direta', 'Bíceps', 'Barra', ''],
    ['Rosca alternada', 'Bíceps', 'Halteres', ''],
    ['Rosca martelo', 'Bíceps', 'Halteres', ''],
    ['Rosca scott', 'Bíceps', 'Máquina', ''],
    ['Tríceps na polia (corda)', 'Tríceps', 'Polia', ''],
    ['Tríceps testa', 'Tríceps', 'Barra W', ''],
    ['Tríceps francês', 'Tríceps', 'Halteres', ''],
    ['Mergulho no banco', 'Tríceps', 'Peso corporal', ''],
    ['Agachamento livre', 'Pernas', 'Barra', ''],
    ['Leg press 45º', 'Pernas', 'Máquina', ''],
    ['Cadeira extensora', 'Pernas', 'Máquina', ''],
    ['Mesa flexora', 'Pernas', 'Máquina', ''],
    ['Stiff', 'Pernas', 'Barra', ''],
    ['Afundo (passada)', 'Pernas', 'Halteres', ''],
    ['Cadeira adutora', 'Pernas', 'Máquina', ''],
    ['Elevação pélvica', 'Glúteos', 'Barra', ''],
    ['Glúteo na polia', 'Glúteos', 'Polia', ''],
    ['Coice na máquina', 'Glúteos', 'Máquina', ''],
    ['Panturrilha em pé', 'Panturrilha', 'Máquina', ''],
    ['Panturrilha sentado', 'Panturrilha', 'Máquina', ''],
    ['Abdominal supra', 'Abdômen', 'Peso corporal', ''],
    ['Prancha isométrica', 'Abdômen', 'Peso corporal', ''],
    ['Abdominal infra', 'Abdômen', 'Peso corporal', ''],
    ['Elevação de pernas suspenso', 'Abdômen', 'Barra fixa', ''],
    ['Esteira (caminhada/corrida)', 'Cardio', 'Esteira', ''],
    ['Bicicleta ergométrica', 'Cardio', 'Bike', ''],
    ['Elíptico', 'Cardio', 'Elíptico', ''],
    ['Escada (stair)', 'Cardio', 'Máquina', ''],
    ['Corda naval', 'Cardio', 'Corda', ''],
];
$novos = 0;
foreach ($biblioteca as $b) {
    if (!um('SELECT id FROM tr_exercicios WHERE nome=? AND tenant_id IS NULL', [$b[0]])) {
        inserir('tr_exercicios', ['tenant_id' => null, 'nome' => $b[0], 'grupo' => $b[1],
            'equipamento' => $b[2], 'video_url' => $b[3] ?: null,
            'instrucoes' => 'Execute com controle, 2s na fase excêntrica. Mantenha a postura estável.']);
        $novos++;
    }
}
say("✔ biblioteca global: $novos exercício(s) inserido(s)");

// ---------------------------------------------------------------- conta DEMO
$t = um("SELECT * FROM tr_tenants WHERE slug='demo'");
if ($t) {
    say('· conta demo já existe — recriando do zero');
    $ids = array_column(todos('SELECT id FROM tr_usuarios WHERE tenant_id=?', [$t['id']]), 'id');
    foreach ($ids as $aid) {
        foreach (todos('SELECT id FROM tr_fichas WHERE aluno_id=?', [$aid]) as $f) {
            foreach (todos('SELECT id FROM tr_ficha_dias WHERE ficha_id=?', [$f['id']]) as $d) {
                q('DELETE FROM tr_ficha_exercicios WHERE dia_id=?', [$d['id']]);
            }
            q('DELETE FROM tr_ficha_dias WHERE ficha_id=?', [$f['id']]);
        }
        foreach (todos('SELECT id FROM tr_dietas WHERE aluno_id=?', [$aid]) as $dt) {
            foreach (todos('SELECT id FROM tr_dieta_refeicoes WHERE dieta_id=?', [$dt['id']]) as $r) {
                q('DELETE FROM tr_dieta_itens WHERE refeicao_id=?', [$r['id']]);
                q('DELETE FROM tr_dieta_check WHERE refeicao_id=?', [$r['id']]);
            }
            q('DELETE FROM tr_dieta_refeicoes WHERE dieta_id=?', [$dt['id']]);
        }
        foreach (todos('SELECT id FROM tr_sessoes WHERE aluno_id=?', [$aid]) as $s) {
            q('DELETE FROM tr_sessao_series WHERE sessao_id=?', [$s['id']]);
        }
        q('DELETE FROM tr_sessoes WHERE aluno_id=?', [$aid]);
        q('DELETE FROM tr_agua WHERE aluno_id=?', [$aid]);
        q('DELETE FROM tr_anamnese WHERE aluno_id=?', [$aid]);
        q('DELETE FROM tr_avaliacoes WHERE aluno_id=?', [$aid]);
        q('DELETE FROM tr_fichas WHERE aluno_id=?', [$aid]);
        q('DELETE FROM tr_dietas WHERE aluno_id=?', [$aid]);
    }
    q('DELETE FROM tr_cobrancas WHERE tenant_id=?', [$t['id']]);
    q('DELETE FROM tr_planos WHERE tenant_id=?', [$t['id']]);
    q('DELETE FROM tr_exercicios WHERE tenant_id=?', [$t['id']]);
    q('DELETE FROM tr_usuarios WHERE tenant_id=?', [$t['id']]);
    q('DELETE FROM tr_tenants WHERE id=?', [$t['id']]);
}

$TID = inserir('tr_tenants', [
    'slug' => 'demo', 'nome' => 'Studio Performance', 'cor_primaria' => '#F26522',
    'cor_acao' => '#22262B', 'whatsapp' => '5582999999999', 'status' => 'ativo',
    'limite_alunos' => 100, 'expira_em' => date('Y-m-d', strtotime('+1 year')),
]);
say("✔ tenant demo criado (id $TID)");

$PID = inserir('tr_usuarios', ['tenant_id' => $TID, 'tipo' => 'treinador', 'nome' => 'Eduardo Bernardi',
    'email' => 'demo-personal', 'senha' => $hash('demo'), 'telefone' => '(82) 99999-0000',
    'nascimento' => '1990-04-12', 'sexo' => 'M']);
say('✔ personal demo: demo-personal / demo');

// ------------------------- alunos
$alunosDemo = [
    ['Ana Silva', 'demo-aluno', 'demo', '1996-03-22', 'F', 'Emagrecimento e definição', '(82) 98888-1111', 3.0, 5],
    ['Carlos Mendes', 'carlos.demo', 'demo', '1988-11-05', 'M', 'Hipertrofia', '(82) 98888-2222', 3.5, 4],
    ['Juliana Rocha', 'juliana.demo', 'demo', '1999-07-14', 'F', 'Condicionamento físico', '(82) 98888-3333', 2.5, 3],
    ['Rafael Torres', 'rafael.demo', 'demo', '1993-01-30', 'M', 'Força e performance', '(82) 98888-4444', 4.0, 5],
];
$alunoIds = [];
foreach ($alunosDemo as $a) {
    $alunoIds[] = inserir('tr_usuarios', ['tenant_id' => $TID, 'tipo' => 'aluno', 'nome' => $a[0],
        'email' => $a[1], 'senha' => $hash($a[2]), 'nascimento' => $a[3], 'sexo' => $a[4],
        'objetivo' => $a[5], 'telefone' => $a[6], 'meta_agua' => $a[7], 'meta_treinos' => $a[8],
        'obs' => 'Aluno de demonstração da plataforma.']);
}
$ANA = $alunoIds[0];
say('✔ 4 alunos criados — principal: demo-aluno / demo');

// ------------------------- exercícios auxiliares
function exId(string $nome): int { $r = um('SELECT id FROM tr_exercicios WHERE nome=? LIMIT 1', [$nome]); return (int) ($r['id'] ?? 0); }

// ------------------------- fichas de treino
$treinos = [
    'Treino A — Peito, Ombro e Tríceps' => [
        ['Supino reto com barra', '4', '8-10', '30 kg', '90s', '', 'Desça controlado até o peito'],
        ['Supino inclinado com halteres', '3', '10-12', '14 kg', '60s', '', ''],
        ['Crucifixo máquina (voador)', '3', '12-15', '26 kg', '45s', 'Drop-set', 'Última série em drop-set'],
        ['Desenvolvimento com halteres', '4', '10', '12 kg', '60s', '', ''],
        ['Elevação lateral', '3', '15', '6 kg', '45s', 'Bi-set', 'Bi-set com elevação frontal'],
        ['Tríceps na polia (corda)', '4', '12', '25 kg', '45s', '', ''],
        ['Tríceps testa', '3', '10-12', '20 kg', '60s', '', ''],
    ],
    'Treino B — Costas e Bíceps' => [
        ['Puxada frente na polia', '4', '10-12', '45 kg', '60s', '', 'Puxe com os cotovelos'],
        ['Remada curvada com barra', '4', '8-10', '35 kg', '90s', '', ''],
        ['Remada unilateral com halter', '3', '12', '18 kg', '45s', '', ''],
        ['Pulldown com corda', '3', '15', '20 kg', '45s', '', ''],
        ['Rosca direta', '4', '10', '20 kg', '60s', '', ''],
        ['Rosca martelo', '3', '12', '12 kg', '45s', 'Bi-set', 'Bi-set com rosca alternada'],
    ],
    'Treino C — Pernas e Glúteos' => [
        ['Agachamento livre', '4', '8-10', '40 kg', '120s', '', 'Profundidade até 90º'],
        ['Leg press 45º', '4', '12', '120 kg', '90s', '', ''],
        ['Cadeira extensora', '3', '15', '35 kg', '45s', 'Isometria', '2s de isometria no topo'],
        ['Mesa flexora', '3', '12', '30 kg', '45s', '', ''],
        ['Elevação pélvica', '4', '12', '50 kg', '60s', '', ''],
        ['Panturrilha em pé', '4', '20', '60 kg', '30s', '', ''],
        ['Prancha isométrica', '3', '45s', '', '30s', '', 'Abdômen contraído'],
    ],
];
$fichaId = inserir('tr_fichas', ['tenant_id' => $TID, 'aluno_id' => $ANA,
    'nome' => 'Hipertrofia ABC — Fase 1', 'objetivo' => 'Emagrecimento e definição',
    'inicio' => date('Y-m-d', strtotime('-45 days')), 'fim' => date('Y-m-d', strtotime('+45 days')),
    'obs' => 'Treinar 5x na semana (ABC + 2 cardios). Beber bastante água e dormir 8h.', 'status' => 'ativa']);
$diaIds = [];
$ordem = 0;
foreach ($treinos as $nome => $exs) {
    $did = inserir('tr_ficha_dias', ['ficha_id' => $fichaId, 'nome' => $nome, 'ordem' => $ordem++]);
    $diaIds[] = $did;
    foreach ($exs as $o => $x) {
        $eid = exId($x[0]);
        if (!$eid) continue;
        inserir('tr_ficha_exercicios', ['dia_id' => $did, 'exercicio_id' => $eid, 'ordem' => $o,
            'series' => $x[1], 'repeticoes' => $x[2], 'carga' => $x[3] ?: null, 'descanso' => $x[4] ?: null,
            'tecnica' => $x[5] ?: null, 'obs' => $x[6] ?: null]);
    }
}
// ficha antiga arquivada
$antiga = inserir('tr_fichas', ['tenant_id' => $TID, 'aluno_id' => $ANA, 'nome' => 'Adaptação AB — Iniciante',
    'objetivo' => 'Adaptação neuromuscular', 'inicio' => date('Y-m-d', strtotime('-120 days')),
    'fim' => date('Y-m-d', strtotime('-46 days')), 'status' => 'arquivada']);
foreach (['Treino A — Superiores', 'Treino B — Inferiores'] as $i => $n) {
    $d = inserir('tr_ficha_dias', ['ficha_id' => $antiga, 'nome' => $n, 'ordem' => $i]);
    foreach (['Flexão de braço', 'Remada unilateral com halter', 'Agachamento livre'] as $o => $exn) {
        if ($eid = exId($exn)) {
            inserir('tr_ficha_exercicios', ['dia_id' => $d, 'exercicio_id' => $eid, 'ordem' => $o,
                'series' => '3', 'repeticoes' => '12', 'descanso' => '60s']);
        }
    }
}
// fichas para os outros alunos
foreach (array_slice($alunoIds, 1) as $k => $aid) {
    $fid = inserir('tr_fichas', ['tenant_id' => $TID, 'aluno_id' => $aid,
        'nome' => ['Full Body 3x', 'Upper/Lower', 'Força 5x5'][$k],
        'objetivo' => $alunosDemo[$k + 1][5], 'inicio' => date('Y-m-d', strtotime('-20 days')),
        'fim' => date('Y-m-d', strtotime('+70 days')), 'status' => 'ativa']);
    foreach (['Treino A', 'Treino B'] as $i => $n) {
        $d = inserir('tr_ficha_dias', ['ficha_id' => $fid, 'nome' => $n, 'ordem' => $i]);
        foreach (['Agachamento livre', 'Supino reto com barra', 'Remada curvada com barra', 'Elevação lateral'] as $o => $exn) {
            if ($eid = exId($exn)) {
                inserir('tr_ficha_exercicios', ['dia_id' => $d, 'exercicio_id' => $eid, 'ordem' => $o,
                    'series' => '4', 'repeticoes' => '8-10', 'carga' => (20 + $o * 5) . ' kg', 'descanso' => '90s']);
            }
        }
    }
}
say('✔ fichas de treino criadas');

// ------------------------- histórico de treinos (8 semanas da Ana)
$hist = 0;
for ($semana = 8; $semana >= 0; $semana--) {
    foreach ([1, 3, 5] as $k => $diaSemana) {
        $data = date('Y-m-d', strtotime("-$semana week " . ($diaSemana - (int) date('N')) . ' day'));
        if ($data > date('Y-m-d')) continue;
        $did = $diaIds[$k % count($diaIds)];
        $sid = inserir('tr_sessoes', ['tenant_id' => $TID, 'aluno_id' => $ANA, 'dia_id' => $did,
            'data' => $data, 'inicio_em' => $data . ' 07:00:00', 'fim_em' => $data . ' 08:05:00',
            'nota' => random_int(3, 5), 'status' => 'concluida',
            'feedback' => ['Treino puxado, mas consegui completar.', 'Aumentei a carga no supino.',
                           'Senti um leve desconforto no ombro.', 'Ótimo treino hoje!'][random_int(0, 3)]]);
        foreach (todos('SELECT * FROM tr_ficha_exercicios WHERE dia_id=? ORDER BY ordem', [$did]) as $x) {
            $ns = max(1, (int) preg_replace('/\D/', '', $x['series']));
            $base = (int) preg_replace('/\D/', '', (string) $x['carga']);
            for ($n = 1; $n <= $ns; $n++) {
                inserir('tr_sessao_series', ['sessao_id' => $sid, 'ficha_exercicio_id' => (int) $x['id'],
                    'serie_num' => $n, 'reps' => (string) random_int(8, 12),
                    'carga' => $base ? ($base + (8 - $semana) * 2) . ' kg' : null, 'concluida' => 1]);
            }
        }
        $hist++;
    }
}
say("✔ $hist sessões de treino no histórico da aluna demo");

// ------------------------- dieta
$dietaId = inserir('tr_dietas', ['tenant_id' => $TID, 'aluno_id' => $ANA, 'nome' => 'Cutting Feminino — 1.800 kcal',
    'objetivo' => 'Déficit calórico moderado', 'kcal_alvo' => 1800, 'status' => 'ativa',
    'obs' => 'Beber 3L de água por dia. Evitar frituras e refrigerantes. Refeição livre 1x por semana.']);
$refeicoes = [
    ['Café da manhã', '07:00', [
        ['Ovos mexidos', '3 unidades', 210, 18, 1.5, 15, '1 scoop de whey + 1 fatia de pão'],
        ['Pão integral', '2 fatias', 140, 6, 24, 2, '40g de aveia'],
        ['Café preto sem açúcar', '200 ml', 5, 0, 1, 0, 'Chá verde'],
    ]],
    ['Lanche da manhã', '10:00', [
        ['Iogurte natural desnatado', '170 g', 90, 15, 6, 0, '1 copo de leite desnatado'],
        ['Banana', '1 unidade', 90, 1, 23, 0, '1 maçã'],
    ]],
    ['Almoço', '12:30', [
        ['Peito de frango grelhado', '150 g', 240, 45, 0, 5, '150g de tilápia ou patinho'],
        ['Arroz integral', '4 colheres', 160, 3, 34, 1, 'Batata doce 150g'],
        ['Feijão', '1 concha', 110, 7, 20, 0.5, 'Lentilha'],
        ['Salada verde à vontade', 'à vontade', 40, 2, 6, 0.5, 'Legumes cozidos'],
    ]],
    ['Lanche da tarde', '16:00', [
        ['Whey protein', '1 scoop', 120, 24, 3, 1, '3 claras de ovo'],
        ['Castanha de caju', '20 g', 115, 3, 6, 9, '1 col. de pasta de amendoim'],
    ]],
    ['Janta', '19:30', [
        ['Tilápia grelhada', '150 g', 190, 35, 0, 4, '150g de frango'],
        ['Legumes no vapor', '200 g', 80, 4, 14, 1, 'Salada crua'],
        ['Batata doce', '100 g', 90, 2, 20, 0.1, 'Arroz integral 3 colheres'],
    ]],
    ['Ceia', '22:00', [
        ['Iogurte grego zero', '100 g', 60, 10, 4, 0, 'Caseína'],
    ]],
];
foreach ($refeicoes as $o => $r) {
    $rid = inserir('tr_dieta_refeicoes', ['dieta_id' => $dietaId, 'nome' => $r[0], 'horario' => $r[1], 'ordem' => $o]);
    foreach ($r[2] as $io => $i) {
        inserir('tr_dieta_itens', ['refeicao_id' => $rid, 'alimento' => $i[0], 'quantidade' => $i[1],
            'kcal' => $i[2], 'prot' => $i[3], 'carb' => $i[4], 'gord' => $i[5], 'substituto' => $i[6], 'ordem' => $io]);
    }
    if ($o < 3) { inserir('tr_dieta_check', ['aluno_id' => $ANA, 'refeicao_id' => $rid, 'data' => date('Y-m-d')]); }
}
// dieta para o Carlos
$d2 = inserir('tr_dietas', ['tenant_id' => $TID, 'aluno_id' => $alunoIds[1], 'nome' => 'Bulking Masculino — 3.000 kcal',
    'objetivo' => 'Ganho de massa', 'kcal_alvo' => 3000, 'status' => 'ativa']);
foreach ([['Café da manhã', '07:00'], ['Almoço', '12:00'], ['Pós-treino', '17:00'], ['Janta', '20:00']] as $o => $r) {
    $rid = inserir('tr_dieta_refeicoes', ['dieta_id' => $d2, 'nome' => $r[0], 'horario' => $r[1], 'ordem' => $o]);
    inserir('tr_dieta_itens', ['refeicao_id' => $rid, 'alimento' => 'Arroz, frango e feijão', 'quantidade' => '1 prato',
        'kcal' => 750, 'prot' => 50, 'carb' => 90, 'gord' => 12, 'ordem' => 0]);
}
say('✔ dietas criadas');

// ------------------------- água (últimos 7 dias)
for ($i = 0; $i < 7; $i++) {
    q('INSERT INTO tr_agua (aluno_id,data,litros) VALUES (?,?,?) ON DUPLICATE KEY UPDATE litros=VALUES(litros)',
      [$ANA, date('Y-m-d', strtotime("-$i day")), $i === 0 ? 1.5 : (2 + random_int(0, 10) / 10)]);
}

// ------------------------- avaliações físicas (evolução real)
$avals = [
    [-120, 74.5, 165, 31.0, 51.4, ['torax' => 92, 'cintura' => 82, 'abdomen' => 86, 'quadril' => 104, 'braco_d' => 28, 'braco_e' => 27.5, 'coxa_d' => 56, 'coxa_e' => 55.5, 'panturrilha' => 35]],
    [-90,  72.8, 165, 29.5, 51.3, ['torax' => 91, 'cintura' => 79, 'abdomen' => 83, 'quadril' => 102, 'braco_d' => 28.5, 'braco_e' => 28, 'coxa_d' => 56.5, 'coxa_e' => 56, 'panturrilha' => 35]],
    [-60,  70.4, 165, 27.8, 50.8, ['torax' => 90, 'cintura' => 76, 'abdomen' => 80, 'quadril' => 100, 'braco_d' => 29, 'braco_e' => 28.5, 'coxa_d' => 57, 'coxa_e' => 56.5, 'panturrilha' => 35.5]],
    [-30,  68.9, 165, 26.1, 50.9, ['torax' => 89, 'cintura' => 73, 'abdomen' => 77, 'quadril' => 98, 'braco_d' => 29.5, 'braco_e' => 29, 'coxa_d' => 57.5, 'coxa_e' => 57, 'panturrilha' => 36]],
    [-3,   67.2, 165, 24.6, 50.7, ['torax' => 88, 'cintura' => 71, 'abdomen' => 75, 'quadril' => 97, 'braco_d' => 30, 'braco_e' => 29.5, 'coxa_d' => 58, 'coxa_e' => 57.5, 'panturrilha' => 36]],
];
foreach ($avals as $i => $v) {
    inserir('tr_avaliacoes', ['tenant_id' => $TID, 'aluno_id' => $ANA,
        'data' => date('Y-m-d', strtotime($v[0] . ' day')), 'peso' => $v[1], 'altura' => $v[2],
        'gordura' => $v[3], 'massa_magra' => $v[4], 'medidas' => json_encode($v[5]),
        'obs' => $i === count($avals) - 1 ? 'Excelente evolução! Perdeu 7,3 kg e 6,4% de gordura mantendo massa magra.' : 'Seguindo o planejado.']);
}
foreach (array_slice($alunoIds, 1) as $k => $aid) {
    inserir('tr_avaliacoes', ['tenant_id' => $TID, 'aluno_id' => $aid, 'data' => date('Y-m-d', strtotime('-15 days')),
        'peso' => [86.4, 61.2, 78.0][$k], 'altura' => [180, 168, 175][$k],
        'gordura' => [19.5, 24.0, 15.2][$k], 'massa_magra' => [69.5, 46.5, 66.1][$k],
        'medidas' => json_encode(['cintura' => [88, 70, 80][$k], 'braco_d' => [36, 27, 38][$k]])]);
}
say('✔ avaliações físicas criadas');

// ------------------------- anamnese
inserir('tr_anamnese', ['tenant_id' => $TID, 'aluno_id' => $ANA, 'respostas' => json_encode([
    'saude' => 'Nenhuma condição de saúde relevante.',
    'lesao' => 'Tendinite no ombro direito em 2023, já recuperada.',
    'medicamento' => 'Anticoncepcional.',
    'cirurgia' => 'Não.',
    'dor' => 'Leve desconforto no ombro direito em elevações acima da cabeça.',
    'experiencia' => 'Treino há 1 ano e meio, com pausas.',
    'frequencia' => '5 dias por semana, pela manhã.',
    'local' => 'Academia (Studio Performance).',
    'sono' => '7 a 8 horas.',
    'alimentacao' => 'Boa durante a semana, desregrada no fim de semana.',
    'restricao' => 'Intolerância leve a lactose.',
    'suplemento' => 'Whey protein e creatina.',
    'alcool' => 'Socialmente, 1x por mês.',
    'objetivo' => 'Emagrecer e definir mantendo massa muscular.',
], JSON_UNESCAPED_UNICODE)]);

// ------------------------- financeiro
$pMensal = inserir('tr_planos', ['tenant_id' => $TID, 'nome' => 'Consultoria mensal', 'valor' => 250.00, 'ciclo_dias' => 30]);
inserir('tr_planos', ['tenant_id' => $TID, 'nome' => 'Consultoria trimestral', 'valor' => 650.00, 'ciclo_dias' => 90]);
inserir('tr_planos', ['tenant_id' => $TID, 'nome' => 'Presencial 2x/semana', 'valor' => 480.00, 'ciclo_dias' => 30]);

foreach ($alunoIds as $k => $aid) {
    for ($mes = 3; $mes >= 0; $mes--) {
        $venc = date('Y-m-05', strtotime("-$mes month"));
        $pago = $mes > 0 || $k < 2;
        inserir('tr_cobrancas', ['tenant_id' => $TID, 'aluno_id' => $aid, 'plano_id' => $pMensal,
            'descricao' => 'Consultoria mensal — ' . date('m/Y', strtotime($venc)),
            'valor' => 250.00, 'vencimento' => $venc,
            'pago_em' => $pago ? date('Y-m-d', strtotime($venc . ' +1 day')) : null,
            'forma' => $pago ? 'PIX' : null, 'status' => $pago ? 'paga' : 'aberta']);
    }
}
say('✔ planos e cobranças criados');

say('');
say('=======================================================');
say('  ACESSOS DA DEMONSTRAÇÃO');
say('  Master (dono do SaaS): alequizao / alequizao');
say('  Personal:              demo-personal / demo');
say('  Aluno:                 demo-aluno / demo');
say('  Outros alunos:         carlos.demo, juliana.demo, rafael.demo (senha: demo)');
say('=======================================================');
