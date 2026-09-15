<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** App do aluno — conversa com o treinador */
exige('aluno');
require_once RAIZ . '/inc/chat.php';
$EU = (int) usuario()['id'];
$t  = tenant();
$treinador = um("SELECT * FROM tr_usuarios WHERE tenant_id=? AND tipo='treinador' ORDER BY id LIMIT 1",
                [(int) usuario()['tenant_id']]);
marcar_lidas($EU, $EU);
$msgs = conversa($EU);

$semRefresh = true;
topo($treinador['nome'] ?? 'Meu treinador', 'Fale direto com quem monta seu treino');
?>
<div class="chat-wrap">
  <div class="chat-msgs" id="msgs"><?= html_mensagens($msgs, $EU) ?></div>
  <form class="chat-barra" id="formMsg">
    <textarea id="txtMsg" rows="1" placeholder="Escreva uma mensagem..." maxlength="2000"></textarea>
    <button type="submit" class="env"><i class="fa-solid fa-paper-plane"></i></button>
  </form>
</div>

<?php require RAIZ . '/inc/chat_js.php'; ?>
<?php rodape();
