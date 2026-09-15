<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/**
 * Completa a demonstração: perfis cheios, endereço, conversa do chat,
 * medidas/dobras/composição, fases do treino e vídeos de exercício.
 * Uso: php sql/seed_demo2.php
 */
require __DIR__ . '/../inc/boot.php';
require __DIR__ . '/../inc/chat.php';
if (PHP_SAPI !== 'cli') { exit('Somente CLI.'); }
function say(string $s): void { echo $s . PHP_EOL; }

$P   = um("SELECT * FROM tr_usuarios WHERE email='demo-personal'");
$A   = um("SELECT * FROM tr_usuarios WHERE email='demo-aluno'");
if (!$P || !$A) { exit("Rode primeiro: php sql/seed.php\n"); }
$T   = (int) $P['tenant_id'];

// ---------------------------------------------------------------- perfil do personal
atualizar('tr_usuarios', [
    'telefone' => '(82) 99999-0000', 'instagram' => 'studioperformance',
    'cep' => '57035-000', 'endereco' => 'Av. Doutor Antônio Gomes de Barros',
    'numero' => '120', 'complemento' => 'Sala 3', 'bairro' => 'Jatiúca',
    'cidade' => 'Maceió', 'uf' => 'AL',
    'obs' => 'Personal trainer há 12 anos, especialista em hipertrofia e emagrecimento. CREF 001234-G/AL.',
], 'id=:id', ['id' => $P['id']]);

// ---------------------------------------------------------------- perfil do aluno
atualizar('tr_usuarios', [
    'telefone' => '(82) 98888-1111', 'instagram' => 'ana.silva',
    'cep' => '57038-000', 'endereco' => 'Rua Jangadeiros Alagoanos',
    'numero' => '540', 'complemento' => 'Apto 802', 'bairro' => 'Pajuçara',
    'cidade' => 'Maceió', 'uf' => 'AL',
    'meta_peso' => 63.0, 'meta_agua' => 3.0, 'meta_treinos' => 5,
    'protocolo_jejum' => '16:8',
    'obs' => 'Prefere treinar de manhã. Intolerância leve a lactose.',
], 'id=:id', ['id' => $A['id']]);
say('✔ perfis completos (contato, endereço, metas)');

// ---------------------------------------------------------------- avaliação completa
$ult = um('SELECT * FROM tr_avaliacoes WHERE aluno_id=? ORDER BY data DESC LIMIT 1', [$A['id']]);
if ($ult) {
    $medidas = ['ombros' => 104, 'torax' => 88, 'cintura' => 71, 'abdomen' => 75, 'quadril' => 97,
                'braco_e' => 29.5, 'braco_d' => 30, 'antebraco_e' => 23.5, 'antebraco_d' => 24,
                'coxa_e' => 57.5, 'coxa_d' => 58, 'panturrilha_e' => 35.5, 'panturrilha' => 36];
    $dobras  = ['triciptal' => 18.5, 'subescapular' => 14.2, 'peitoral' => 9.8, 'axilar' => 12.4,
                'suprailiaca' => 16.1, 'abdominal' => 19.3, 'coxa' => 24.6];
    atualizar('tr_avaliacoes', [
        'medidas' => json_encode($medidas, JSON_UNESCAPED_UNICODE),
        'dobras'  => json_encode($dobras, JSON_UNESCAPED_UNICODE),
        'massa_gorda' => 16.5, 'massa_magra' => 50.7, 'massa_ossea' => 9.8,
        'massa_residual' => 8.1, 'massa_muscular' => 32.8,
        'obs' => 'Excelente evolução: −7,3 kg e −6,4% de gordura mantendo a massa magra. '
               . 'Manter proteína alta e seguir com o ABC por mais 4 semanas.',
    ], 'id=:id', ['id' => $ult['id']]);
    say('✔ avaliação com medidas, dobras e composição corporal');
}

// ---------------------------------------------------------------- fases do programa
$ficha = um("SELECT * FROM tr_fichas WHERE aluno_id=? AND status='ativa' ORDER BY id DESC LIMIT 1", [$A['id']]);
if ($ficha) {
    atualizar('tr_fichas', ['fase' => 'Fase 1: Resistência', 'semanas' => 4], 'id=:id', ['id' => $ficha['id']]);
    $dias = todos('SELECT * FROM tr_ficha_dias WHERE ficha_id=? ORDER BY ordem', [$ficha['id']]);
    $cfg = [[1, 0, 'facil', 53], [1, 2, 'medio', 45], [1, 4, 'medio', 39]];
    foreach ($dias as $i => $d) {
        $c = $cfg[$i % count($cfg)];
        atualizar('tr_ficha_dias', ['semana' => $c[0], 'dia_semana' => $c[1],
            'dificuldade' => $c[2], 'duracao_min' => $c[3]], 'id=:id', ['id' => $d['id']]);
    }
    say('✔ fase e metadados dos treinos (duração, dificuldade)');
}

// ---------------------------------------------------------------- conversa do chat
q('DELETE FROM tr_mensagens WHERE aluno_id=?', [$A['id']]);
$conversa = [
    ['p', 'Oi Ana! Bem-vinda ao Studio Performance 💪 Já deixei sua ficha ABC e a dieta no app.', '-6 day 09:12'],
    ['a', 'Oii! Vi aqui, ficou top. Posso trocar o agachamento livre pelo leg press nos dias que a academia estiver cheia?', '-6 day 09:40'],
    ['p', 'Pode sim, mas tenta manter o livre pelo menos 1x por semana — é o que mais te dá resultado no glúteo.', '-6 day 09:44'],
    ['a', 'Fechado! Ontem consegui 30 kg no supino, subi 2 kg 🎉', '-4 day 20:15'],
    ['p', 'Isso! Recorde registrado no app. Semana que vem a gente sobe as séries válidas pra 3.', '-4 day 20:31'],
    ['a', 'Uma dúvida: posso tomar o whey antes do treino em vez de depois?', '-2 day 07:05'],
    ['p', 'Pode. O importante é fechar a proteína do dia. Se treinar em jejum, toma logo depois.', '-2 day 07:22'],
    ['a', 'Perfeito. Hoje bati 3L de água pela primeira vez 💧', '-1 day 19:48'],
    ['p', 'Boa!! Continua assim que a definição vem rápido. Qualquer coisa me chama por aqui.', '-1 day 20:02'],
];
foreach ($conversa as $m) {
    $quando = date('Y-m-d H:i:s', strtotime($m[2]));
    inserir('tr_mensagens', ['tenant_id' => $T, 'aluno_id' => (int) $A['id'],
        'de_id' => (int) ($m[0] === 'p' ? $P['id'] : $A['id']),
        'texto' => $m[1], 'lida' => 1, 'criado_em' => $quando]);
}
// uma mensagem nova (não lida) do treinador, para aparecer o badge
inserir('tr_mensagens', ['tenant_id' => $T, 'aluno_id' => (int) $A['id'], 'de_id' => (int) $P['id'],
    'texto' => 'Ana, lembra de mandar as fotos de evolução hoje à noite pra eu comparar com as do mês passado 📸',
    'lida' => 0, 'criado_em' => date('Y-m-d H:i:s', strtotime('-40 minutes'))]);

// conversa com outro aluno (para o painel do treinador ficar cheio)
$carlos = um("SELECT * FROM tr_usuarios WHERE email='carlos.demo'");
if ($carlos) {
    q('DELETE FROM tr_mensagens WHERE aluno_id=?', [$carlos['id']]);
    inserir('tr_mensagens', ['tenant_id' => $T, 'aluno_id' => (int) $carlos['id'], 'de_id' => (int) $carlos['id'],
        'texto' => 'Professor, posso treinar sábado no lugar de sexta essa semana?', 'lida' => 0,
        'criado_em' => date('Y-m-d H:i:s', strtotime('-3 hours'))]);
}
say('✔ conversa do chat (10 mensagens + 2 não lidas)');

// ---------------------------------------------------------------- vídeos de referência
$videos = [
    'Supino reto com barra'   => 'https://www.youtube.com/watch?v=rT7DgCr-3pg',
    'Agachamento livre'       => 'https://www.youtube.com/watch?v=aclHkVaku9U',
    'Puxada frente na polia'  => 'https://www.youtube.com/watch?v=CAwf7n6Luuc',
    'Remada curvada com barra'=> 'https://www.youtube.com/watch?v=vT2GjY_Umpw',
    'Desenvolvimento com halteres' => 'https://www.youtube.com/watch?v=qEwKCR5JCog',
    'Elevação lateral'        => 'https://www.youtube.com/watch?v=3VcKaXpzqRo',
    'Rosca direta'            => 'https://www.youtube.com/watch?v=kwG2ipFRgfo',
    'Tríceps na polia (corda)'=> 'https://www.youtube.com/watch?v=vB5OHsJ3EME',
    'Leg press 45º'           => 'https://www.youtube.com/watch?v=IZxyjW7MPJQ',
    'Elevação pélvica'        => 'https://www.youtube.com/watch?v=SEdqd1n0cvg',
];
$n = 0;
foreach ($videos as $nome => $url) {
    $r = um('SELECT id FROM tr_exercicios WHERE nome=? LIMIT 1', [$nome]);
    if ($r) { q('UPDATE tr_exercicios SET video_url=? WHERE id=?', [$url, $r['id']]); $n++; }
}
say("✔ $n exercícios com vídeo de execução");

// ---------------------------------------------------------------- água e conquistas
for ($i = 0; $i < 10; $i++) {
    q('INSERT INTO tr_agua (aluno_id,data,litros) VALUES (?,?,?) ON DUPLICATE KEY UPDATE litros=VALUES(litros)',
      [$A['id'], date('Y-m-d', strtotime("-$i day")), $i === 0 ? 1.2 : (2.6 + random_int(0, 8) / 10)]);
}
checar_conquistas((int) $A['id']);
$xp = xp_total((int) $A['id']); $nv = nivel_do_xp($xp);
say("✔ água dos últimos 10 dias · XP $xp · nível {$nv['nivel']} ({$nv['titulo']})");
say('');
say('Demonstração completa. Personal: demo-personal/demo · Aluno: demo-aluno/demo');
