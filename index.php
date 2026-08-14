<?php
/** TreinoPro — front controller */
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/layout.php';

$p = (string) get('p', '');

if ($p === 'sair') {
    $_SESSION = []; session_destroy();
    header('Location: ' . BASE_URL . '?p=login'); exit;
}

// Personificação: entrar como aluno / voltar para a própria conta
if ($p === 'entrar-como' && logado()) {
    if (personificar(id_get())) { redir('inicio'); }
    redir('alunos');
}
if ($p === 'voltar-conta') {
    voltar_conta();
    redir('inicio');
}

if (!logado()) {
    if ($p !== 'login') { redir('login'); }
    require __DIR__ . '/pages/login.php'; exit;
}
if ($p === '' || $p === 'login') { redir('inicio'); }

// Página inicial depende do perfil
if ($p === 'inicio') {
    $p = eh_master() ? 'master_inicio' : (eh_treinador() ? 'treinador_inicio' : 'aluno_inicio');
}
if ($p === 'chat' && eh_aluno()) { $p = 'meu-chat'; }

$rotas = [
    // master
    'master_inicio' => 'master_inicio.php', 'tenants' => 'tenants.php', 'logs' => 'logs.php',
    // treinador
    'treinador_inicio' => 'treinador_inicio.php',
    'alunos' => 'alunos.php', 'aluno' => 'aluno.php',
    'treinos' => 'treinos.php', 'ficha' => 'ficha.php',
    'dietas' => 'dietas.php', 'dieta' => 'dieta.php',
    'exercicios' => 'exercicios.php', 'financeiro' => 'financeiro.php',
    'planos' => 'planos.php', 'marca' => 'marca.php', 'avaliacao' => 'avaliacao.php',
    'chat' => 'chat.php',
    // aluno
    'aluno_inicio' => 'aluno_inicio.php', 'meu-treino' => 'aluno_treino.php',
    'executar' => 'aluno_executar.php', 'minha-dieta' => 'aluno_dieta.php',
    'evolucao' => 'aluno_evolucao.php', 'meus-pagamentos' => 'aluno_pagamentos.php',
    'jejum' => 'aluno_jejum.php', 'conquistas' => 'aluno_conquistas.php',
    'atividades' => 'aluno_atividades.php', 'corpo' => 'aluno_corpo.php',
    'meu-chat' => 'aluno_chat.php', 'treino-dia' => 'aluno_treino_dia.php',
    'meu-perfil' => 'aluno_perfil.php',
];

if (!isset($rotas[$p]) || !is_file(__DIR__ . '/pages/' . $rotas[$p])) {
    http_response_code(404);
    topo('Página não encontrada');
    vazio('A página que você tentou abrir não existe.', 'fa-triangle-exclamation');
    rodape(); exit;
}
require __DIR__ . '/pages/' . $rotas[$p];
