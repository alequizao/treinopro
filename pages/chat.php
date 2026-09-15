<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** Painel do treinador — mensagens com os alunos */
exige('treinador');
require_once RAIZ . '/inc/chat.php';
$T  = tenant_id();
$EU = (int) usuario()['id'];
$convs = conversas_do_treinador($T);
$alunoChat = id_get();
if (!$alunoChat && $convs) { $alunoChat = (int) $convs[0]['id']; }
$aluno = $alunoChat ? aluno_do_tenant($alunoChat) : null;
if ($aluno) { marcar_lidas((int) $aluno['id'], $EU); }
$msgs = $aluno ? conversa((int) $aluno['id']) : [];

$semRefresh = true;
topo('Mensagens', 'Converse com seus alunos');
?>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card-soft" style="padding:8px">
      <div class="lista-convs">
        <?php if (!$convs) { vazio('Nenhum aluno cadastrado.', 'fa-comments'); } ?>
        <?php foreach ($convs as $c): ?>
          <a href="<?= url('chat', ['id' => $c['id']]) ?>"
             class="conv <?= $aluno && (int) $c['id'] === (int) $aluno['id'] ? 'ativa' : '' ?>">
            <span class="avatar" style="width:42px;height:42px;font-size:.8rem">
              <?php if ($c['foto']): ?><img src="<?= BASE_DIR ?>/<?= e($c['foto']) ?>" alt="">
              <?php else: ?><?= e(iniciais($c['nome'])) ?><?php endif; ?></span>
            <span class="txt">
              <strong><?= e($c['nome']) ?></strong>
              <small><?= e(mb_strimwidth((string) ($c['ultima'] ?? 'Nenhuma mensagem'), 0, 38, '...')) ?></small>
            </span>
            <span class="meta">
              <?php if ($c['ultima_em']): ?>
                <small><?= date('d/m H:i', strtotime($c['ultima_em'])) ?></small><?php endif; ?>
              <?php if ($c['nao_lidas']): ?><em class="badge-n2"><?= (int) $c['nao_lidas'] ?></em><?php endif; ?>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <?php if (!$aluno) { echo '<div class="card-soft">'; vazio('Escolha um aluno para conversar.', 'fa-comments'); echo '</div>'; } else { ?>
      <div class="card-soft" style="padding:0;overflow:hidden">
        <div class="chat-topo">
          <span class="avatar" style="width:40px;height:40px;font-size:.78rem">
            <?php if ($aluno['foto']): ?><img src="<?= BASE_DIR ?>/<?= e($aluno['foto']) ?>" alt="">
            <?php else: ?><?= e(iniciais($aluno['nome'])) ?><?php endif; ?></span>
          <div class="flex-grow-1">
            <strong><?= e($aluno['nome']) ?></strong>
            <small class="text-muted d-block"><?= e($aluno['objetivo'] ?: 'Aluno') ?></small>
          </div>
          <span class="aovivo">ao vivo</span>
          <a href="<?= url('aluno', ['id' => $aluno['id']]) ?>" class="btn-outline-soft">Ver ficha</a>
        </div>
        <div class="chat-wrap no-app">
          <div class="chat-msgs" id="msgs"><?= html_mensagens($msgs, $EU) ?></div>
          <form class="chat-barra" id="formMsg">
            <textarea id="txtMsg" rows="1" placeholder="Escreva uma mensagem para <?= e(explode(' ', $aluno['nome'])[0]) ?>..." maxlength="2000"></textarea>
            <button type="submit" class="env"><i class="fa-solid fa-paper-plane"></i></button>
          </form>
        </div>
      </div>
      <?php require RAIZ . '/inc/chat_js.php'; ?>
    <?php } ?>
  </div>
</div>
<?php rodape();
