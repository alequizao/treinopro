<?php /** JS compartilhado do chat (aluno e treinador) — polling com pausa na aba oculta */ ?>
<script>
var ALUNO_ID = <?= json_encode($alunoChat ?? null) ?>;   // null = sou o aluno
var caixa = document.getElementById('msgs');
var assinatura = caixa ? caixa.innerHTML.length : 0;

function noFim(){ return caixa.scrollHeight - caixa.scrollTop - caixa.clientHeight < 120; }
function descer(){ caixa.scrollTop = caixa.scrollHeight; }
descer();

function pintar(html){
  if (!html || html.length === assinatura) return;
  var estavaNoFim = noFim();
  assinatura = html.length;
  caixa.innerHTML = html;
  if (estavaNoFim) descer();
}

function carregar(){
  if (document.hidden) return;
  api('msg_listar', ALUNO_ID ? {aluno_id: ALUNO_ID} : {}).then(function(r){
    if (r.ok) pintar(r.html);
  }).catch(function(){});
}
setInterval(carregar, 5000);
document.addEventListener('visibilitychange', function(){ if (!document.hidden) carregar(); });

var campo = document.getElementById('txtMsg');
campo.addEventListener('input', function(){
  campo.style.height = 'auto';
  campo.style.height = Math.min(120, campo.scrollHeight) + 'px';
});
campo.addEventListener('keydown', function(e){
  if (e.key === 'Enter' && !e.shiftKey && window.innerWidth > 880) {
    e.preventDefault(); document.getElementById('formMsg').requestSubmit();
  }
});

document.getElementById('formMsg').addEventListener('submit', function(ev){
  ev.preventDefault();
  var txt = campo.value.trim();
  if (!txt) return;
  var dados = {texto: txt};
  if (ALUNO_ID) dados.aluno_id = ALUNO_ID;
  campo.value = ''; campo.style.height = 'auto';
  api('msg_enviar', dados).then(function(r){
    if (!r.ok) { flash(r.msg, 'erro'); campo.value = txt; return; }
    pintar(r.html); descer(); vibrar(20);
  }).catch(function(){ flash('Falha ao enviar.', 'erro'); campo.value = txt; });
});
</script>
