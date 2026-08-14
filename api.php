<?php
/** TreinoPro — endpoints AJAX (todas as ações passam por aqui) */
require __DIR__ . '/inc/boot.php';

if (!logado()) responde_erro('Sessão expirada.', 401);
checa_csrf();

require_once __DIR__ . '/inc/chat.php';

$acao = (string) get('acao', '');
$T    = (int) (usuario()['tenant_id'] ?? 0);
$EU   = (int) usuario()['id'];

/** Confere se a ficha/dieta/etc pertence ao tenant logado */
function meu(string $tabela, int $id): array {
    global $T;
    $r = um("SELECT * FROM $tabela WHERE id=?", [$id]);
    if (!$r || (int) $r['tenant_id'] !== $T) responde_erro('Registro não encontrado.', 404);
    return $r;
}
/** Converte chaves de conquista em objetos prontos para o toast do app. */
function nomes_conquistas(array $chaves): array {
    $cat = catalogo_conquistas();
    $out = [];
    foreach ($chaves as $c) {
        if (isset($cat[$c])) { $out[] = ['nome' => $cat[$c][0], 'icone' => $cat[$c][1], 'cor' => $cat[$c][2]]; }
    }
    return $out;
}
function meu_aluno(int $id): array {
    global $T;
    $a = um("SELECT * FROM tr_usuarios WHERE id=? AND tipo='aluno' AND tenant_id=?", [$id, $T]);
    if (!$a) responde_erro('Aluno não encontrado.', 404);
    return $a;
}

switch ($acao) {

// ============================================================ MASTER
case 'tenant_salvar':
    if (!eh_master()) responde_erro('Sem permissão.', 403);
    $id   = (int) post('id');
    $slug = strtolower(preg_replace('/[^a-z0-9\-]/i', '', (string) post('slug')));
    if ($slug === '' || post('nome') === '') responde_erro('Informe nome e slug.');
    $dados = [
        'slug' => $slug, 'nome' => post('nome'),
        'dominio' => post('dominio') ?: null, 'whatsapp' => post('whatsapp') ?: null,
        'cor_primaria' => post('cor_primaria', '#F26522'), 'cor_acao' => post('cor_acao', '#22262B'),
        'status' => in_array(post('status'), ['ativo', 'trial', 'suspenso'], true) ? post('status') : 'trial',
        'limite_alunos' => (int) post('limite_alunos', 30),
        'expira_em' => post('expira_em') ?: null,
    ];
    $dup = um('SELECT id FROM tr_tenants WHERE slug=? AND id<>?', [$slug, $id]);
    if ($dup) responde_erro('Já existe um personal com esse slug.');

    if ($id) {
        atualizar('tr_tenants', $dados, 'id=:wid', ['wid' => $id]);
    } else {
        $id = inserir('tr_tenants', $dados);
        // cria o treinador dono
        $email = (string) post('email');
        $senha = (string) post('senha');
        if ($email && $senha) {
            inserir('tr_usuarios', ['tenant_id' => $id, 'tipo' => 'treinador', 'nome' => post('nome_treinador') ?: post('nome'),
                'email' => $email, 'senha' => password_hash($senha, PASSWORD_DEFAULT)]);
        }
    }
    log_acao('tenant_salvar', 'tenant ' . $id);
    responde_ok('Personal salvo!');

case 'tenant_excluir':
    if (!eh_master()) responde_erro('Sem permissão.', 403);
    $id = (int) post('id');
    q('DELETE FROM tr_usuarios WHERE tenant_id=?', [$id]);
    q('DELETE FROM tr_tenants WHERE id=?', [$id]);
    responde_ok('Personal removido.');

// ============================================================ ALUNOS
case 'aluno_salvar':
    exige('treinador');
    $id = (int) post('id');
    $dados = [
        'nome' => post('nome'), 'email' => post('email'), 'telefone' => post('telefone') ?: null,
        'nascimento' => post('nascimento') ?: null, 'sexo' => in_array(post('sexo'), ['M', 'F', 'O'], true) ? post('sexo') : null,
        'objetivo' => post('objetivo') ?: null, 'obs' => post('obs') ?: null,
        'status' => post('status') === 'inativo' ? 'inativo' : 'ativo',
        'meta_agua' => post('meta_agua') !== '' ? (float) post('meta_agua') : null,
        'meta_treinos' => post('meta_treinos') !== '' ? (int) post('meta_treinos') : null,
    ];
    if ($dados['nome'] === '' || $dados['email'] === '') responde_erro('Nome e usuário/e-mail são obrigatórios.');
    $dup = um('SELECT id FROM tr_usuarios WHERE email=? AND tenant_id=? AND id<>?', [$dados['email'], $T, $id]);
    if ($dup) responde_erro('Já existe um usuário com esse e-mail.');

    if ($id) {
        meu_aluno($id);
        if (post('senha') !== '') { $dados['senha'] = password_hash((string) post('senha'), PASSWORD_DEFAULT); }
        atualizar('tr_usuarios', $dados, 'id=:wid AND tenant_id=:wt', ['wid' => $id, 'wt' => $T]);
    } else {
        $lim = (int) valor('SELECT limite_alunos FROM tr_tenants WHERE id=?', [$T]);
        $qtd = (int) valor("SELECT COUNT(*) FROM tr_usuarios WHERE tenant_id=? AND tipo='aluno'", [$T]);
        if ($lim && $qtd >= $lim) responde_erro('Limite de ' . $lim . ' alunos do seu plano atingido.');
        $dados['tenant_id'] = $T; $dados['tipo'] = 'aluno';
        $dados['senha'] = password_hash(post('senha') !== '' ? (string) post('senha') : 'mudar123', PASSWORD_DEFAULT);
        $id = inserir('tr_usuarios', $dados);
    }
    log_acao('aluno_salvar', 'aluno ' . $id);
    responde_ok('Aluno salvo!', ['id' => $id]);

case 'aluno_excluir':
    exige('treinador');
    $a = meu_aluno((int) post('id'));
    q('DELETE FROM tr_usuarios WHERE id=?', [$a['id']]);
    responde_ok('Aluno removido.');

// ============================================================ FICHAS DE TREINO
case 'ficha_salvar':
    exige('treinador');
    $id = (int) post('id');
    $dados = ['nome' => post('nome'), 'objetivo' => post('objetivo') ?: null,
              'inicio' => post('inicio') ?: null, 'fim' => post('fim') ?: null,
              'obs' => post('obs') ?: null,
              'status' => post('status') === 'arquivada' ? 'arquivada' : 'ativa'];
    if ($dados['nome'] === '') responde_erro('Dê um nome para a ficha.');
    if ($id) { meu('tr_fichas', $id); atualizar('tr_fichas', $dados, 'id=:wid', ['wid' => $id]); }
    else {
        $aluno = meu_aluno((int) post('aluno_id'));
        $dados['tenant_id'] = $T; $dados['aluno_id'] = (int) $aluno['id'];
        $id = inserir('tr_fichas', $dados);
        foreach (['Treino A', 'Treino B'] as $i => $n) { inserir('tr_ficha_dias', ['ficha_id' => $id, 'nome' => $n, 'ordem' => $i]); }
    }
    responde_ok('Ficha salva!', ['id' => $id, 'redir' => url('ficha', ['id' => $id])]);

case 'ficha_excluir':
    exige('treinador');
    $f = meu('tr_fichas', (int) post('id'));
    $dias = todos('SELECT id FROM tr_ficha_dias WHERE ficha_id=?', [$f['id']]);
    foreach ($dias as $d) { q('DELETE FROM tr_ficha_exercicios WHERE dia_id=?', [$d['id']]); }
    q('DELETE FROM tr_ficha_dias WHERE ficha_id=?', [$f['id']]);
    q('DELETE FROM tr_fichas WHERE id=?', [$f['id']]);
    responde_ok('Ficha excluída.');

case 'ficha_duplicar':
    exige('treinador');
    $f = meu('tr_fichas', (int) post('id'));
    $novo = inserir('tr_fichas', ['tenant_id' => $T, 'aluno_id' => (int) post('aluno_id', $f['aluno_id']),
        'nome' => $f['nome'] . ' (cópia)', 'objetivo' => $f['objetivo'], 'inicio' => date('Y-m-d'),
        'obs' => $f['obs'], 'status' => 'ativa']);
    foreach (todos('SELECT * FROM tr_ficha_dias WHERE ficha_id=? ORDER BY ordem', [$f['id']]) as $d) {
        $nd = inserir('tr_ficha_dias', ['ficha_id' => $novo, 'nome' => $d['nome'], 'ordem' => $d['ordem']]);
        foreach (todos('SELECT * FROM tr_ficha_exercicios WHERE dia_id=? ORDER BY ordem', [$d['id']]) as $x) {
            unset($x['id']); $x['dia_id'] = $nd;
            inserir('tr_ficha_exercicios', $x);
        }
    }
    responde_ok('Ficha duplicada!', ['redir' => url('ficha', ['id' => $novo])]);

case 'dia_add':
    exige('treinador');
    $f = meu('tr_fichas', (int) post('ficha_id'));
    $o = (int) valor('SELECT COALESCE(MAX(ordem),-1)+1 FROM tr_ficha_dias WHERE ficha_id=?', [$f['id']]);
    inserir('tr_ficha_dias', ['ficha_id' => $f['id'], 'nome' => post('nome') ?: ('Treino ' . chr(65 + $o)), 'ordem' => $o]);
    responde_ok('Treino adicionado.');

case 'dia_renomear':
    exige('treinador');
    $d = um('SELECT d.*,f.tenant_id FROM tr_ficha_dias d JOIN tr_fichas f ON f.id=d.ficha_id WHERE d.id=?', [(int) post('id')]);
    if (!$d || (int) $d['tenant_id'] !== $T) responde_erro('Não encontrado.', 404);
    atualizar('tr_ficha_dias', ['nome' => post('nome')], 'id=:wid', ['wid' => $d['id']]);
    responde_ok('Renomeado.');

case 'dia_del':
    exige('treinador');
    $d = um('SELECT d.*,f.tenant_id FROM tr_ficha_dias d JOIN tr_fichas f ON f.id=d.ficha_id WHERE d.id=?', [(int) post('id')]);
    if (!$d || (int) $d['tenant_id'] !== $T) responde_erro('Não encontrado.', 404);
    q('DELETE FROM tr_ficha_exercicios WHERE dia_id=?', [$d['id']]);
    q('DELETE FROM tr_ficha_dias WHERE id=?', [$d['id']]);
    responde_ok('Treino removido.');

case 'fex_salvar':
    exige('treinador');
    $diaId = (int) post('dia_id');
    $d = um('SELECT d.*,f.tenant_id FROM tr_ficha_dias d JOIN tr_fichas f ON f.id=d.ficha_id WHERE d.id=?', [$diaId]);
    if (!$d || (int) $d['tenant_id'] !== $T) responde_erro('Treino não encontrado.', 404);
    $dados = ['exercicio_id' => (int) post('exercicio_id'), 'series' => post('series', '3'),
              'repeticoes' => post('repeticoes', '12'), 'carga' => post('carga') ?: null,
              'descanso' => post('descanso') ?: null, 'tecnica' => post('tecnica') ?: null,
              'obs' => post('obs') ?: null];
    if (!$dados['exercicio_id']) responde_erro('Escolha o exercício.');
    $id = (int) post('id');
    if ($id) { atualizar('tr_ficha_exercicios', $dados, 'id=:wid AND dia_id=:wd', ['wid' => $id, 'wd' => $diaId]); }
    else {
        $dados['dia_id'] = $diaId;
        $dados['ordem'] = (int) valor('SELECT COALESCE(MAX(ordem),-1)+1 FROM tr_ficha_exercicios WHERE dia_id=?', [$diaId]);
        inserir('tr_ficha_exercicios', $dados);
    }
    responde_ok('Exercício salvo!');

case 'fex_del':
    exige('treinador');
    $x = um('SELECT x.*,f.tenant_id FROM tr_ficha_exercicios x JOIN tr_ficha_dias d ON d.id=x.dia_id
             JOIN tr_fichas f ON f.id=d.ficha_id WHERE x.id=?', [(int) post('id')]);
    if (!$x || (int) $x['tenant_id'] !== $T) responde_erro('Não encontrado.', 404);
    q('DELETE FROM tr_ficha_exercicios WHERE id=?', [$x['id']]);
    responde_ok('Exercício removido.');

// ============================================================ BIBLIOTECA DE EXERCÍCIOS
case 'exercicio_salvar':
    exige('treinador', 'master');
    $id = (int) post('id');
    $dados = ['nome' => post('nome'), 'grupo' => post('grupo') ?: 'Geral',
              'equipamento' => post('equipamento') ?: null, 'video_url' => post('video_url') ?: null,
              'instrucoes' => post('instrucoes') ?: null];
    if ($dados['nome'] === '') responde_erro('Informe o nome do exercício.');

    // vídeo enviado do computador/celular (opcional) e imagem de capa
    $video = salvar_upload('video_arquivo', 'ex', 'video');
    if ($video) { $dados['video_arquivo'] = $video; }
    elseif (($er = erro_upload('video_arquivo')) !== null) responde_erro('Vídeo: ' . $er);
    $thumb = salvar_upload('thumb', 'exthumb');
    if ($thumb) { $dados['thumb'] = $thumb; }
    if (post('remover_video') === '1') { $dados['video_arquivo'] = null; }
    if ($id) {
        $ex = um('SELECT * FROM tr_exercicios WHERE id=?', [$id]);
        if (!$ex) responde_erro('Não encontrado.', 404);
        if (!eh_master() && (int) $ex['tenant_id'] !== $T) responde_erro('Exercício da biblioteca global — copie para editar.', 403);
        atualizar('tr_exercicios', $dados, 'id=:wid', ['wid' => $id]);
    } else {
        $dados['tenant_id'] = eh_master() ? null : $T;
        $id = inserir('tr_exercicios', $dados);
    }
    responde_ok('Exercício salvo!', ['id' => $id]);

case 'exercicio_excluir':
    exige('treinador', 'master');
    $ex = um('SELECT * FROM tr_exercicios WHERE id=?', [(int) post('id')]);
    if (!$ex) responde_erro('Não encontrado.', 404);
    if (!eh_master() && (int) $ex['tenant_id'] !== $T) responde_erro('Sem permissão.', 403);
    q('DELETE FROM tr_exercicios WHERE id=?', [$ex['id']]);
    responde_ok('Exercício removido.');

// ============================================================ DIETA
case 'dieta_salvar':
    exige('treinador');
    $id = (int) post('id');
    $dados = ['nome' => post('nome'), 'objetivo' => post('objetivo') ?: null,
              'kcal_alvo' => post('kcal_alvo') !== '' ? (int) post('kcal_alvo') : null,
              'obs' => post('obs') ?: null, 'status' => post('status') === 'arquivada' ? 'arquivada' : 'ativa'];
    if ($dados['nome'] === '') responde_erro('Dê um nome para a dieta.');
    if ($id) { meu('tr_dietas', $id); atualizar('tr_dietas', $dados, 'id=:wid', ['wid' => $id]); }
    else {
        $aluno = meu_aluno((int) post('aluno_id'));
        $dados['tenant_id'] = $T; $dados['aluno_id'] = (int) $aluno['id'];
        $id = inserir('tr_dietas', $dados);
        foreach ([['Café da manhã', '07:00'], ['Almoço', '12:00'], ['Lanche da tarde', '16:00'], ['Janta', '19:30']] as $i => $r) {
            inserir('tr_dieta_refeicoes', ['dieta_id' => $id, 'nome' => $r[0], 'horario' => $r[1], 'ordem' => $i]);
        }
    }
    responde_ok('Dieta salva!', ['id' => $id, 'redir' => url('dieta', ['id' => $id])]);

case 'dieta_excluir':
    exige('treinador');
    $d = meu('tr_dietas', (int) post('id'));
    foreach (todos('SELECT id FROM tr_dieta_refeicoes WHERE dieta_id=?', [$d['id']]) as $r) {
        q('DELETE FROM tr_dieta_itens WHERE refeicao_id=?', [$r['id']]);
    }
    q('DELETE FROM tr_dieta_refeicoes WHERE dieta_id=?', [$d['id']]);
    q('DELETE FROM tr_dietas WHERE id=?', [$d['id']]);
    responde_ok('Dieta excluída.');

case 'refeicao_salvar':
    exige('treinador');
    $id = (int) post('id');
    if ($id) {
        $r = um('SELECT r.*,d.tenant_id FROM tr_dieta_refeicoes r JOIN tr_dietas d ON d.id=r.dieta_id WHERE r.id=?', [$id]);
        if (!$r || (int) $r['tenant_id'] !== $T) responde_erro('Não encontrada.', 404);
        atualizar('tr_dieta_refeicoes', ['nome' => post('nome'), 'horario' => post('horario') ?: null], 'id=:wid', ['wid' => $id]);
    } else {
        $d = meu('tr_dietas', (int) post('dieta_id'));
        $o = (int) valor('SELECT COALESCE(MAX(ordem),-1)+1 FROM tr_dieta_refeicoes WHERE dieta_id=?', [$d['id']]);
        inserir('tr_dieta_refeicoes', ['dieta_id' => $d['id'], 'nome' => post('nome') ?: 'Nova refeição',
            'horario' => post('horario') ?: null, 'ordem' => $o]);
    }
    responde_ok('Refeição salva!');

case 'refeicao_del':
    exige('treinador');
    $r = um('SELECT r.*,d.tenant_id FROM tr_dieta_refeicoes r JOIN tr_dietas d ON d.id=r.dieta_id WHERE r.id=?', [(int) post('id')]);
    if (!$r || (int) $r['tenant_id'] !== $T) responde_erro('Não encontrada.', 404);
    q('DELETE FROM tr_dieta_itens WHERE refeicao_id=?', [$r['id']]);
    q('DELETE FROM tr_dieta_refeicoes WHERE id=?', [$r['id']]);
    responde_ok('Refeição removida.');

case 'item_salvar':
    exige('treinador');
    $rid = (int) post('refeicao_id');
    $r = um('SELECT r.*,d.tenant_id FROM tr_dieta_refeicoes r JOIN tr_dietas d ON d.id=r.dieta_id WHERE r.id=?', [$rid]);
    if (!$r || (int) $r['tenant_id'] !== $T) responde_erro('Refeição não encontrada.', 404);
    $dados = ['alimento' => post('alimento'), 'quantidade' => post('quantidade') ?: null,
              'kcal' => post('kcal') !== '' ? (int) post('kcal') : null,
              'prot' => post('prot') !== '' ? (float) post('prot') : null,
              'carb' => post('carb') !== '' ? (float) post('carb') : null,
              'gord' => post('gord') !== '' ? (float) post('gord') : null,
              'substituto' => post('substituto') ?: null];
    if ($dados['alimento'] === '') responde_erro('Informe o alimento.');
    $id = (int) post('id');
    if ($id) { atualizar('tr_dieta_itens', $dados, 'id=:wid AND refeicao_id=:wr', ['wid' => $id, 'wr' => $rid]); }
    else {
        $dados['refeicao_id'] = $rid;
        $dados['ordem'] = (int) valor('SELECT COALESCE(MAX(ordem),-1)+1 FROM tr_dieta_itens WHERE refeicao_id=?', [$rid]);
        inserir('tr_dieta_itens', $dados);
    }
    responde_ok('Alimento salvo!');

case 'item_del':
    exige('treinador');
    $i = um('SELECT i.*,d.tenant_id FROM tr_dieta_itens i JOIN tr_dieta_refeicoes r ON r.id=i.refeicao_id
             JOIN tr_dietas d ON d.id=r.dieta_id WHERE i.id=?', [(int) post('id')]);
    if (!$i || (int) $i['tenant_id'] !== $T) responde_erro('Não encontrado.', 404);
    q('DELETE FROM tr_dieta_itens WHERE id=?', [$i['id']]);
    responde_ok('Alimento removido.');

// ============================================================ AVALIAÇÃO / ANAMNESE
case 'avaliacao_salvar':
    exige('treinador');
    $aluno = meu_aluno((int) post('aluno_id'));
    $medidas = [];
    foreach (['torax', 'cintura', 'abdomen', 'quadril', 'braco_d', 'braco_e', 'coxa_d', 'coxa_e', 'panturrilha'] as $k) {
        if (post('m_' . $k) !== '') { $medidas[$k] = (float) post('m_' . $k); }
    }
    $dados = ['data' => post('data') ?: date('Y-m-d'),
              'peso' => post('peso') !== '' ? (float) post('peso') : null,
              'altura' => post('altura') !== '' ? (float) post('altura') : null,
              'gordura' => post('gordura') !== '' ? (float) post('gordura') : null,
              'massa_magra' => post('massa_magra') !== '' ? (float) post('massa_magra') : null,
              'medidas' => json_encode($medidas, JSON_UNESCAPED_UNICODE),
              'obs' => post('obs') ?: null];
    $id = (int) post('id');
    if ($id) { meu('tr_avaliacoes', $id); atualizar('tr_avaliacoes', $dados, 'id=:wid', ['wid' => $id]); }
    else { $dados['tenant_id'] = $T; $dados['aluno_id'] = (int) $aluno['id']; $id = inserir('tr_avaliacoes', $dados); }
    responde_ok('Avaliação salva!', ['id' => $id]);

case 'avaliacao_excluir':
    exige('treinador');
    $a = meu('tr_avaliacoes', (int) post('id'));
    q('DELETE FROM tr_avaliacoes WHERE id=?', [$a['id']]);
    responde_ok('Avaliação removida.');

case 'anamnese_salvar':
    $alunoId = eh_aluno() ? $EU : (int) post('aluno_id');
    if (!eh_aluno()) { exige('treinador'); meu_aluno($alunoId); }
    $resp = [];
    foreach ((array) ($_POST['r'] ?? []) as $k => $v) { $resp[substr((string) $k, 0, 60)] = trim((string) $v); }
    $ex = um('SELECT id FROM tr_anamnese WHERE aluno_id=?', [$alunoId]);
    if ($ex) { q('UPDATE tr_anamnese SET respostas=?, atualizado_em=NOW() WHERE id=?', [json_encode($resp, JSON_UNESCAPED_UNICODE), $ex['id']]); }
    else { inserir('tr_anamnese', ['tenant_id' => $T ?: (int) usuario()['tenant_id'], 'aluno_id' => $alunoId,
                                   'respostas' => json_encode($resp, JSON_UNESCAPED_UNICODE)]); }
    responde_ok('Anamnese salva!');

// ============================================================ FINANCEIRO
case 'plano_salvar':
    exige('treinador');
    $id = (int) post('id');
    $dados = ['nome' => post('nome'), 'valor' => (float) str_replace(',', '.', (string) post('valor')),
              'ciclo_dias' => (int) post('ciclo_dias', 30), 'ativo' => post('ativo') === '0' ? 0 : 1];
    if ($dados['nome'] === '') responde_erro('Informe o nome do plano.');
    if ($id) { meu('tr_planos', $id); atualizar('tr_planos', $dados, 'id=:wid', ['wid' => $id]); }
    else { $dados['tenant_id'] = $T; $id = inserir('tr_planos', $dados); }
    responde_ok('Plano salvo!');

case 'plano_excluir':
    exige('treinador');
    $p = meu('tr_planos', (int) post('id'));
    q('DELETE FROM tr_planos WHERE id=?', [$p['id']]);
    responde_ok('Plano removido.');

case 'cobranca_salvar':
    exige('treinador');
    $aluno = meu_aluno((int) post('aluno_id'));
    $dados = ['descricao' => post('descricao') ?: 'Mensalidade',
              'valor' => (float) str_replace(',', '.', (string) post('valor')),
              'vencimento' => post('vencimento') ?: date('Y-m-d'),
              'plano_id' => post('plano_id') !== '' ? (int) post('plano_id') : null];
    $id = (int) post('id');
    if ($id) { meu('tr_cobrancas', $id); atualizar('tr_cobrancas', $dados, 'id=:wid', ['wid' => $id]); }
    else { $dados['tenant_id'] = $T; $dados['aluno_id'] = (int) $aluno['id']; $id = inserir('tr_cobrancas', $dados); }
    responde_ok('Cobrança salva!');

case 'cobranca_pagar':
    exige('treinador');
    $c = meu('tr_cobrancas', (int) post('id'));
    if ($c['status'] === 'paga') {
        q("UPDATE tr_cobrancas SET status='aberta', pago_em=NULL, forma=NULL WHERE id=?", [$c['id']]);
        responde_ok('Pagamento desfeito.');
    }
    q("UPDATE tr_cobrancas SET status='paga', pago_em=?, forma=? WHERE id=?",
      [post('pago_em') ?: date('Y-m-d'), post('forma') ?: 'PIX', $c['id']]);
    responde_ok('Pagamento registrado!');

case 'cobranca_excluir':
    exige('treinador');
    $c = meu('tr_cobrancas', (int) post('id'));
    q('DELETE FROM tr_cobrancas WHERE id=?', [$c['id']]);
    responde_ok('Cobrança removida.');

// ============================================================ WHITELABEL
case 'marca_salvar':
    exige('treinador');
    $dados = ['nome' => post('nome'), 'cor_primaria' => post('cor_primaria', '#F26522'),
              'cor_acao' => post('cor_acao', '#22262B'), 'whatsapp' => post('whatsapp') ?: null];
    if ($dados['nome'] === '') responde_erro('Informe o nome da sua marca.');
    $logo = salvar_upload('logo', 'logo' . $T);
    if ($logo) { $dados['logo'] = $logo; }
    atualizar('tr_tenants', $dados, 'id=:wid', ['wid' => $T]);
    responde_ok('Identidade visual atualizada!');

// ============================================================ APP DO ALUNO
case 'sessao_iniciar':
    exige('aluno');
    $diaId = (int) post('dia_id');
    $d = um('SELECT d.*,f.aluno_id FROM tr_ficha_dias d JOIN tr_fichas f ON f.id=d.ficha_id
             WHERE d.id=? AND f.aluno_id=?', [$diaId, $EU]);
    if (!$d) responde_erro('Treino não encontrado.', 404);
    $s = um("SELECT * FROM tr_sessoes WHERE aluno_id=? AND dia_id=? AND data=CURDATE() AND status='andamento'", [$EU, $diaId]);
    if (!$s) {
        $id = inserir('tr_sessoes', ['tenant_id' => (int) usuario()['tenant_id'], 'aluno_id' => $EU,
            'dia_id' => $diaId, 'data' => date('Y-m-d'), 'inicio_em' => date('Y-m-d H:i:s')]);
    } else { $id = (int) $s['id']; }
    responde_ok('Treino iniciado!', ['sessao_id' => $id]);

case 'serie_salvar':
    exige('aluno');
    $s = um('SELECT * FROM tr_sessoes WHERE id=? AND aluno_id=?', [(int) post('sessao_id'), $EU]);
    if (!$s) responde_erro('Sessão não encontrada.', 404);
    $fex = (int) post('ficha_exercicio_id'); $n = (int) post('serie_num');
    $ex = um('SELECT id FROM tr_sessao_series WHERE sessao_id=? AND ficha_exercicio_id=? AND serie_num=?', [$s['id'], $fex, $n]);
    $dados = ['reps' => post('reps') ?: null, 'carga' => post('carga') ?: null,
              'concluida' => post('concluida') === '1' ? 1 : 0];

    // recorde pessoal: compara com a melhor carga anterior nesse exercício
    $recorde = false;
    $fxRow = um('SELECT exercicio_id FROM tr_ficha_exercicios WHERE id=?', [$fex]);
    $cargaNova = (float) str_replace(',', '.', preg_replace('/[^\d,.]/', '', (string) post('carga')));
    if ($dados['concluida'] && $fxRow && $cargaNova > 0) {
        $antes = recorde_exercicio($EU, (int) $fxRow['exercicio_id']);
        $recorde = $cargaNova > $antes && $antes > 0;
    }
    if ($ex) { atualizar('tr_sessao_series', $dados, 'id=:wid', ['wid' => $ex['id']]); }
    else {
        $dados['sessao_id'] = (int) $s['id']; $dados['ficha_exercicio_id'] = $fex; $dados['serie_num'] = $n;
        inserir('tr_sessao_series', $dados);
    }
    $conq = [];
    if ($recorde && conceder($EU, 'pr_carga')) { $conq = nomes_conquistas(['pr_carga']); }
    responde_ok($recorde ? 'Novo recorde de carga! 🏆' : 'Série registrada.',
                ['recorde' => $recorde, 'conquistas' => $conq]);

case 'sessao_concluir':
    exige('aluno');
    $s = um('SELECT * FROM tr_sessoes WHERE id=? AND aluno_id=?', [(int) post('sessao_id'), $EU]);
    if (!$s) responde_erro('Sessão não encontrada.', 404);
    q("UPDATE tr_sessoes SET status='concluida', fim_em=NOW(), nota=?, feedback=? WHERE id=?",
      [post('nota') !== '' ? (int) post('nota') : null, post('feedback') ?: null, $s['id']]);
    $novas  = checar_conquistas($EU);
    $streak = streak_treinos($EU);
    responde_ok('Treino concluído! 💪', [
        'xp' => 50, 'streak' => $streak, 'nivel' => nivel_do_xp(xp_total($EU)),
        'conquistas' => nomes_conquistas($novas),
    ]);

case 'refeicao_check':
    exige('aluno');
    $rid = (int) post('refeicao_id');
    $ok = um('SELECT r.id FROM tr_dieta_refeicoes r JOIN tr_dietas d ON d.id=r.dieta_id
              WHERE r.id=? AND d.aluno_id=?', [$rid, $EU]);
    if (!$ok) responde_erro('Refeição não encontrada.', 404);
    $data = post('data') ?: date('Y-m-d');
    $ja = um('SELECT id FROM tr_dieta_check WHERE aluno_id=? AND refeicao_id=? AND data=?', [$EU, $rid, $data]);
    if ($ja) { q('DELETE FROM tr_dieta_check WHERE id=?', [$ja['id']]); responde_ok('Desmarcado.', ['marcado' => false]); }
    inserir('tr_dieta_check', ['aluno_id' => $EU, 'refeicao_id' => $rid, 'data' => $data]);
    responde_ok('Refeição marcada!', ['marcado' => true]);

case 'agua_registrar':
    exige('aluno');
    $litros = max(0, min(20, (float) str_replace(',', '.', (string) post('litros'))));
    q('INSERT INTO tr_agua (aluno_id,data,litros) VALUES (?,CURDATE(),?)
       ON DUPLICATE KEY UPDATE litros=VALUES(litros)', [$EU, $litros]);
    $meta = (float) (valor('SELECT meta_agua FROM tr_usuarios WHERE id=?', [$EU]) ?: 3);
    $novas = checar_conquistas($EU);
    responde_ok($litros >= $meta ? 'Meta de água batida! 💧' : 'Água registrada!', [
        'litros' => $litros,
        'pct' => $meta > 0 ? min(100, (int) round($litros / $meta * 100)) : 0,
        'meta_batida' => $litros >= $meta,
        'conquistas' => nomes_conquistas($novas),
    ]);

// ============================================================ JEJUM INTERMITENTE
case 'jejum_iniciar':
    exige('aluno');
    if (jejum_ativo($EU)) responde_erro('Você já tem um jejum em andamento.');
    $protos = protocolos_jejum();
    $p = (string) post('protocolo', '16:8');
    if (!isset($protos[$p])) responde_erro('Protocolo inválido.');
    $inicio = post('inicio') ? date('Y-m-d H:i:s', strtotime((string) post('inicio'))) : date('Y-m-d H:i:s');
    if ($inicio > date('Y-m-d H:i:s')) responde_erro('O início não pode ser no futuro.');
    $id = inserir('tr_jejum', ['tenant_id' => (int) usuario()['tenant_id'], 'aluno_id' => $EU,
        'protocolo' => $p, 'meta_horas' => $protos[$p][1], 'inicio' => $inicio]);
    q('UPDATE tr_usuarios SET protocolo_jejum=? WHERE id=?', [$p, $EU]);
    responde_ok('Jejum iniciado! Bora. ⏳', ['id' => $id]);

case 'jejum_encerrar':
    exige('aluno');
    $j = jejum_ativo($EU);
    if (!$j) responde_erro('Nenhum jejum em andamento.');
    $horas = round((time() - strtotime($j['inicio'])) / 3600, 2);
    q("UPDATE tr_jejum SET status='concluido', fim=NOW(), horas=? WHERE id=?", [$horas, $j['id']]);
    $novas = checar_conquistas($EU);
    $fase = fase_atual($horas);
    responde_ok('Jejum de ' . number_format($horas, 1, ',', '') . 'h concluído! Fase: ' . $fase[1], [
        'horas' => $horas, 'meta' => (float) $j['meta_horas'],
        'bateu' => $horas >= (float) $j['meta_horas'],
        'conquistas' => nomes_conquistas($novas),
    ]);

case 'jejum_cancelar':
    exige('aluno');
    $j = jejum_ativo($EU);
    if (!$j) responde_erro('Nenhum jejum em andamento.');
    q("UPDATE tr_jejum SET status='cancelado', fim=NOW(), horas=? WHERE id=?",
      [round((time() - strtotime($j['inicio'])) / 3600, 2), $j['id']]);
    responde_ok('Jejum cancelado. Sem problema, recomece quando quiser.');

case 'jejum_excluir':
    exige('aluno');
    q("DELETE FROM tr_jejum WHERE id=? AND aluno_id=? AND status<>'andamento'", [(int) post('id'), $EU]);
    responde_ok('Registro removido.');

case 'perfil_meta':
    exige('aluno');
    q('UPDATE tr_usuarios SET meta_peso=? WHERE id=?',
      [post('meta_peso') !== '' ? (float) str_replace(',', '.', (string) post('meta_peso')) : null, $EU]);
    responde_ok('Meta de peso salva!');

case 'medicao_aluno':
    exige('aluno');
    $medidas = [];
    foreach (array_keys(campos_medidas()) as $k) {
        if (post('m_' . $k) !== '') { $medidas[$k] = (float) str_replace(',', '.', (string) post('m_' . $k)); }
    }
    $data = post('data') ?: date('Y-m-d');
    $ja = um('SELECT * FROM tr_avaliacoes WHERE aluno_id=? AND data=?', [$EU, $data]);
    $dados = [
        'peso' => post('peso') !== '' ? (float) str_replace(',', '.', (string) post('peso')) : null,
        'altura' => post('altura') !== '' ? (float) str_replace(',', '.', (string) post('altura')) : null,
        'medidas' => json_encode($medidas, JSON_UNESCAPED_UNICODE),
    ];
    if ($ja) {
        // preserva o que o treinador já preencheu
        if ($dados['peso'] === null)   { unset($dados['peso']); }
        if ($dados['altura'] === null) { unset($dados['altura']); }
        if (!$medidas) { unset($dados['medidas']); }
        if ($dados) { atualizar('tr_avaliacoes', $dados, 'id=:wid', ['wid' => $ja['id']]); }
    } else {
        $dados['tenant_id'] = (int) usuario()['tenant_id'];
        $dados['aluno_id'] = $EU; $dados['data'] = $data; $dados['origem'] = 'aluno';
        inserir('tr_avaliacoes', $dados);
    }
    $novas = checar_conquistas($EU);
    responde_ok('Medição registrada!', ['conquistas' => nomes_conquistas($novas)]);

case 'perfil_salvar':
    exige('aluno');
    $dados = ['nome' => post('nome') ?: usuario()['nome'], 'telefone' => post('telefone') ?: null];
    if (post('senha') !== '') { $dados['senha'] = password_hash((string) post('senha'), PASSWORD_DEFAULT); }
    atualizar('tr_usuarios', $dados, 'id=:wid', ['wid' => $EU]);
    $_SESSION['usuario']['nome'] = $dados['nome'];
    responde_ok('Perfil atualizado!');

// ============================================================ CHAT
case 'msg_enviar':
    exige_login();
    require_once __DIR__ . '/inc/chat.php';
    $texto = trim((string) post('texto'));
    if ($texto === '') responde_erro('Escreva uma mensagem.');
    if (mb_strlen($texto) > 2000) { $texto = mb_substr($texto, 0, 2000); }

    if (eh_aluno()) {
        $alunoId = $EU;
        $tenantId = (int) usuario()['tenant_id'];
        $destino = um("SELECT id,nome FROM tr_usuarios WHERE tenant_id=? AND tipo='treinador' ORDER BY id LIMIT 1", [$tenantId]);
    } else {
        exige('treinador');
        $al = meu_aluno((int) post('aluno_id'));
        $alunoId = (int) $al['id'];
        $tenantId = $T;
        $destino = $al;
    }
    inserir('tr_mensagens', ['tenant_id' => $tenantId, 'aluno_id' => $alunoId,
                             'de_id' => $EU, 'texto' => $texto]);
    if ($destino) {
        $marca = tenant()['nome'] ?? APP_NOME;
        push_para((int) $destino['id'], usuario()['nome'] . ' · ' . $marca, $texto,
                  url(eh_aluno() ? 'chat' : 'chat', eh_aluno() ? [] : ['id' => $alunoId]));
    }
    $msgs = conversa($alunoId);
    responde_ok('Enviada!', ['html' => html_mensagens($msgs, $EU)]);

case 'msg_listar':
    exige_login();
    require_once __DIR__ . '/inc/chat.php';
    $alunoId = eh_aluno() ? $EU : (int) meu_aluno((int) post('aluno_id', get('aluno_id', 0)))['id'];
    marcar_lidas($alunoId, $EU);
    responde_ok('', ['html' => html_mensagens(conversa($alunoId), $EU)]);

case 'msg_nao_lidas':
    exige_login();
    require_once __DIR__ . '/inc/chat.php';
    responde_ok('', ['n' => nao_lidas($EU, tipo(), $T)]);

// ============================================================ PUSH
case 'push_chave':
    exige_login();
    require_once __DIR__ . '/inc/push.php';
    responde_ok('', ['chave' => push_vapid_public()]);

case 'push_inscrever':
    exige_login();
    $end = (string) post('endpoint');
    $p256 = (string) post('p256dh');
    $auth = (string) post('auth');
    if ($end === '' || $p256 === '' || $auth === '') responde_erro('Inscrição inválida.');
    q('INSERT INTO tr_push (usuario_id,endpoint,p256dh,auth) VALUES (?,?,?,?)
       ON DUPLICATE KEY UPDATE usuario_id=VALUES(usuario_id),p256dh=VALUES(p256dh),auth=VALUES(auth)',
      [$EU, $end, $p256, $auth]);
    responde_ok('Notificações ativadas!');

case 'push_teste':
    exige_login();
    require_once __DIR__ . '/inc/chat.php';
    $n = push_para($EU, tenant()['nome'] ?? APP_NOME, 'Notificações funcionando! 💪', url('inicio'));
    responde_ok($n ? 'Enviamos uma notificação de teste.' : 'Nenhum dispositivo inscrito ainda.');

// ============================================================ PERFIL COMPLETO DO ALUNO
case 'perfil_completo':
    exige('aluno');
    $dados = [
        'nome' => post('nome') ?: usuario()['nome'],
        'email' => post('email') ?: usuario()['email'],
        'telefone' => post('telefone') ?: null,
        'nascimento' => post('nascimento') ?: null,
        'sexo' => in_array(post('sexo'), ['M', 'F', 'O'], true) ? post('sexo') : null,
        'instagram' => ltrim((string) post('instagram'), '@') ?: null,
        'objetivo' => post('objetivo') ?: null,
        'cep' => post('cep') ?: null, 'endereco' => post('endereco') ?: null,
        'numero' => post('numero') ?: null, 'complemento' => post('complemento') ?: null,
        'bairro' => post('bairro') ?: null, 'cidade' => post('cidade') ?: null,
        'uf' => strtoupper(substr((string) post('uf'), 0, 2)) ?: null,
        // meta_agua é definida apenas pelo personal (ação aluno_salvar)
        'meta_treinos' => post('meta_treinos') !== '' ? (int) post('meta_treinos') : null,
        'meta_peso' => post('meta_peso') !== '' ? (float) str_replace(',', '.', (string) post('meta_peso')) : null,
    ];
    $dup = um('SELECT id FROM tr_usuarios WHERE email=? AND tenant_id=? AND id<>?',
              [$dados['email'], (int) usuario()['tenant_id'], $EU]);
    if ($dup) responde_erro('Esse e-mail já está em uso na sua academia.');
    if (post('senha') !== '') {
        if (mb_strlen((string) post('senha')) < 4) responde_erro('A senha precisa ter ao menos 4 caracteres.');
        $dados['senha'] = password_hash((string) post('senha'), PASSWORD_DEFAULT);
    }
    $foto = salvar_upload('foto', 'aluno' . $EU);
    if ($foto) { $dados['foto'] = $foto; }
    atualizar('tr_usuarios', $dados, 'id=:wid', ['wid' => $EU]);
    $_SESSION['usuario']['nome'] = $dados['nome'];
    $_SESSION['usuario']['email'] = $dados['email'];
    if ($foto) { $_SESSION['usuario']['foto'] = $foto; }
    responde_ok('Perfil atualizado!');

default:
    responde_erro('Ação desconhecida: ' . $acao, 404);
}
