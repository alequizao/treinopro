# TreinoPro — Changelog

Regra fixa: **toda alteração no sistema sobe a versão** em `inc/boot.php`
(`APP_VERSAO`) e ganha uma linha aqui. A versão aparece no rodapé do menu lateral.

## 1.4.2 — 07/08/2026
- **Meta de água agora é definida só pelo personal.** No perfil do aluno o campo
  virou somente leitura ("Definida pelo seu personal") e a ação de salvar perfil
  não grava mais `meta_agua`. Metas de treinos/semana e peso alvo continuam do aluno.

## 1.4.1 — 06/08/2026
- No programa de treino, o botão ▶ agora **inicia o treino direto** (antes só abria o resumo).
- Na tela do treino, **a linha inteira do exercício abre o vídeo** e mostra o rótulo "ver";
  exercícios sem vídeo aparecem marcados.

## 1.4.0 — 06/08/2026
- **Mapa muscular refeito**: figura anatômica com proporção correta (deltoide, peitoral,
  reto abdominal em 4 blocos, oblíquos, bíceps/tríceps, antebraço, quadríceps, tibial,
  trapézio, dorsais, lombar, glúteo, posterior de coxa e panturrilhas), simétrica por
  espelhamento, músculos recortados dentro da silhueta e com sombreado de volume.
- **Vídeos abrem em popup** (YouTube ou arquivo enviado) em vez de sair do app.
- **Chat em destaque no perfil do aluno**: card do treinador com foto, contador de
  mensagens não lidas e botão de WhatsApp; **botão flutuante de chat** em todas as telas.
- Ícone do app refinado (mais respiro, melhor leitura em tamanho pequeno).
- Correção: `<meta mobile-web-app-capable>` (aviso de depreciação no console).

## 1.3.0 — 06/08/2026
Visual e funções inspirados no app de referência enviado (pasta `modelo/novo`).
- **Novo visual do app do aluno**: preto puro + laranja #FF7A0F, segmented controls,
  listas agrupadas e cards estilo iOS; **login agora é escuro** como o app, com arte
  do fisiculturista no desktop.
- **Ícone oficial do app** (silhueta laranja) em PWA, favicon, apple-touch e como logo
  padrão de quem ainda não subiu a própria.
- **Tela Atividades**: estatísticas 7/14/28 dias com sparklines (tempo, calorias,
  exercícios, séries, reps, carga), **regiões mais treinadas** e **recuperação muscular**
  em mapa corporal (frente/costas), tipos de exercício em pizza e semana de treinos.
- **Tela Corpo**: abas Peso (atual/meta/diferença, gráfico com linha de meta, IMC,
  gordura e peso ideal), Medidas (13 medidas com variação) e Avançado (composição
  corporal e dobras cutâneas). O aluno pode registrar a própria medição.
- **Programa de treino** com fase, progresso semanal em anel, timeline de dias
  (dificuldade • duração • nº de exercícios) e tela de resumo com “Iniciar Treino”.
- **Chat treinador ↔ aluno** com **notificações push** (Web Push VAPID nativo),
  badge de não lidas em tempo real e conversa por polling.
- **Perfil completo do aluno**: foto, e-mail, telefone, Instagram, endereço com busca
  automática de CEP, metas e troca de senha.
- **Upload de vídeo do exercício** (MP4/WebM/MOV até 120 MB) além do link.
- **Navegação SPA**: nenhuma ação recarrega a página; telas trocam por fetch, mantêm
  histórico do navegador e se atualizam sozinhas quando ninguém está digitando.
- **URLs sem .php** (`/alunos`, `/meu-treino`, `/corpo`).
- **Atualização forçada de versão** para todos os usuários (service worker versionado,
  limpeza de cache e reload automático).
- Botões de acesso à demonstração na tela de login.
- Correções: seta da dieta agora abre a refeição; água da garrafa não “acaba” mais ao
  descer; tampa alinhada ao gargalo; brilho discreto na garrafa ao bater a meta.

## 1.2.0 — 06/08/2026
Baseado em pesquisa dos apps de referência (Zero, Fastic, LIFE Fasting, Nexur,
Trainer Connect) — jejum, hidratação visual e gamificação.
- **Cronômetro de jejum intermitente**: protocolos 12:12, 14:10, 16:8, 18:6, 20:4,
  OMAD, 24h e 36h; anel de progresso que muda de cor conforme a **fase biológica**
  (saciado → queima de glicose → lipólise → cetose → autofagia → jejum profundo),
  com explicação de cada fase, aviso + confete na troca de fase, previsão de término,
  histórico, estatísticas (melhor marca, média, semana) e mini-card na home.
- **Garrafa de água animada**: SVG com onda em movimento, bolhas subindo, enchimento
  suave conforme a meta, "splash" ao registrar e botões de copo 200 ml / 500 ml / 1 L.
- **Gamificação**: XP (treino +50, jejum +30, meta de água +10), níveis com título
  (Iniciante → Lenda), streak de dias seguidos com chama animada, 16 medalhas e
  recordes pessoais de carga detectados automaticamente na execução do treino.
- **Feedback visual**: confete ao concluir treino/jejum/dieta e ao bater recorde,
  pop-up de conquista desbloqueada, vibração (háptico), animação de pop nos cards
  concluídos, contadores que sobem e ícones temáticos em todas as funções.
- Painel do treinador: bloco **Engajamento** na ficha do aluno com jejum em andamento,
  streak, nível, metas de água da semana e medalhas conquistadas.

## 1.1.0 — 06/08/2026
- **Personificação**: botão "Ver o app como este aluno" na lista de alunos e na ficha
  do aluno. O personal entra na conta do aluno, vê exatamente o que ele vê/preenche e
  volta com um clique pela faixa roxa no topo (`?p=entrar-como&id=` / `?p=voltar-conta`).
- Faixa fixa de aviso enquanto estiver personificando.

## 1.0.0 — 06/08/2026
- Primeira versão do SaaS whitelabel para personal trainers.
- Multi-tenant (1 banco, `tenant_id`), resolução por domínio próprio, subdomínio ou `?t=slug`.
- Painel master: contas de personais, biblioteca global de exercícios, logs.
- Painel do treinador: dashboard, alunos, fichas de treino (divisões A/B/C, séries,
  reps, carga, descanso, técnicas), fichas de dieta (refeições, macros, substituições),
  avaliação física com gráficos, anamnese, financeiro (planos e cobranças),
  biblioteca de exercícios e configurações de marca (logo, cores).
- App do aluno (PWA, tema escuro): home com treinos da semana / próximo treino /
  próxima refeição / água, execução do treino com registro de carga e reps por série,
  cronômetro de descanso, dieta do dia com check por refeição, evolução com gráficos,
  questionário e pagamentos.
- Whitelabel: logo, cor principal, cor de botão, nome da marca, manifest PWA dinâmico.
- Dados de demonstração completos (Studio Performance).
