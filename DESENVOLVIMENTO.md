# TreinoPro — Desenvolvimento

Documento vivo: o que já está pronto, como está construído e o que vem a seguir.
Toda alteração sobe a versão em `inc/boot.php` e ganha uma linha no `CHANGELOG.md`.

- **Produção:** https://treino.alequizao.com (também em publishdev.com.br/treino)
- **Versão atual:** 1.3.0
- **Stack:** PHP 8.3 + MySQL (`banco`, prefixo `tr_`), Bootstrap 5, Chart.js, sem build

---

## 1. Estado atual (o que está pronto)

### 1.1 Núcleo / SaaS
| Item | Situação |
|---|---|
| Multi-tenant (1 banco, `tenant_id`) | ✅ |
| Resolução de tenant por domínio próprio, subdomínio e `?t=slug` | ✅ |
| Painel master (contas, biblioteca global, logs) | ✅ |
| Whitelabel (logo, cores, nome, manifest PWA por personal) | ✅ |
| Personificação (personal entra como aluno e volta em 1 clique) | ✅ |
| URLs amigáveis sem `.php` (`/alunos`, `/meu-treino`) | ✅ |
| Atualização de versão forçada para todos (SW versionado + limpeza de cache) | ✅ |
| Ícone do app (silhueta fisiculturista laranja) em PWA, favicon e logo padrão | ✅ |

### 1.2 Painel do treinador
Dashboard · Alunos · Fichas de treino (divisões, séries, reps, carga, descanso, técnicas)
· Fichas de dieta (refeições, macros, substituições) · Avaliação física com gráficos
· Anamnese · Financeiro (planos, mensalidades, atrasos) · Biblioteca de exercícios
(**com upload de vídeo próprio** ou link) · Configurações de marca · **Mensagens (chat)**
· Bloco de **engajamento** na ficha do aluno (jejum, streak, nível, medalhas, água).

### 1.3 App do aluno (PWA, tema escuro estilo iOS)
| Tela | Conteúdo |
|---|---|
| Home | nível/XP/streak, treinos da semana, próximo treino, próxima refeição, jejum ativo, **garrafa de água animada com brilho ao bater a meta** |
| Treinos | programa com fase, progresso semanal em anel, timeline de dias (dificuldade • duração • nº de exercícios) |
| Treino do dia | resumo (min, kcal, tonelagem), botão “Iniciar Treino”, lista de exercícios com vídeo |
| Execução | série a série com carga/reps, histórico da última carga, **detecção de recorde**, cronômetro de descanso, conclusão com nota e feedback |
| Atividades | estatísticas 7/14/28 dias com sparklines, **regiões mais treinadas** e **recuperação muscular** em mapa corporal, tipos de exercício (pizza), semana |
| Dieta | dia a dia, check por refeição, macros, substituições |
| Corpo | abas Peso (meta + gráfico + diagnóstico IMC/gordura/peso ideal), Medidas (13 medidas com variação), Avançado (composição corporal + dobras) |
| Jejum | protocolos 12:12 → 36h, anel com as 6 fases biológicas, histórico e estatísticas |
| Conquistas | 16 medalhas, níveis, recordes de carga |
| Chat | conversa com o treinador + push |
| Perfil | foto, contato, endereço com busca de CEP, metas, senha, notificações |

### 1.4 Engajamento
Gamificação (XP, níveis, streak, 16 medalhas, recordes) · Jejum intermitente com fases
· Água com garrafa animada · Confete, vibração e pop-up de conquista ·
**Web Push** (VAPID nativo, sem dependências) no chat.

---

## 2. Arquitetura

```
index.php            front controller (rotas amigáveis via .htaccess)
api.php              todas as ações AJAX (?acao=…), CSRF em todo POST
manifest.php         manifest PWA por tenant
sw.js                service worker (cache versionado + push)
inc/boot.php         banco, sessão, tenant, auth, personificação, uploads
inc/layout.php       topo()/rodape(), menus, tab bar, badges
inc/gamificacao.php  jejum, XP, níveis, streak, conquistas, recordes
inc/corpo.php        mapa muscular SVG, recuperação, estatísticas, composição corporal
inc/chat.php         conversas, não lidas, push_para()
inc/push.php         Web Push VAPID (aes128gcm, RFC 8291/8188/8292)
pages/               1 arquivo por tela
assets/              app.css, app.js, ícones, arte do login
sql/schema.sql       esquema · sql/seed.php + seed_demo2.php  demonstração
```

**Regras fixas do projeto**
1. Toda ação por `fetch` + JSON (nada de reload de página inteira).
2. Isolamento multi-tenant checado no backend em **toda** ação (`meu()`, `meu_aluno()`).
3. Painel = sidebar escura + conteúdo claro. App do aluno = preto puro + laranja da marca.
4. Versão sobe a cada alteração; o service worker força a atualização em todos.

---

## 3. Contas de demonstração

| Perfil | Usuário | Senha |
|---|---|---|
| Master | `alequizao` | `alequizao` |
| Personal | `demo-personal` | `demo` |
| Aluno | `demo-aluno` | `demo` |
| Outros alunos | `carlos.demo`, `juliana.demo`, `rafael.demo` | `demo` |

A tela de login tem botões “Sou o personal” / “Sou o aluno” que entram direto na demo.
A demo tem: 4 alunos, ficha ABC com 20 exercícios, dieta de 6 refeições, 26 treinos no
histórico, 15 jejuns, 5 avaliações com evolução real, medidas + dobras + composição,
conversa de chat com 11 mensagens, 3 planos, 16 cobranças e perfis completos com endereço.

Recriar tudo: `php sql/seed.php && php sql/seed_demo2.php`

---

## 4. Próximas etapas

### 4.1 Prioridade alta
- [ ] **SSL do domínio** `treino.alequizao.com` (hoje usa o certificado do publishdev) e
      wildcard `*.treino.alequizao.com` para os subdomínios de cada personal.
- [ ] **Cobrança da assinatura SaaS** (personal pagando o dono): Mercado Pago/Asaas com
      webhook liberando/suspendendo o tenant automaticamente.
- [ ] **Fotos de evolução** do aluno (upload, linha do tempo e comparação lado a lado) —
      existe no app de referência e ainda não foi feito.
- [ ] **Anexos no chat** (foto/vídeo) — hoje só texto.

### 4.2 Prioridade média
- [ ] Programa em **mesociclos** completo: várias fases (Resistência → Força → Choque)
      com semanas e liberação progressiva; hoje a ficha guarda 1 fase.
- [ ] **Treinos pré-definidos / modelos** para o personal montar ficha em 1 clique.
- [ ] **Lembretes automáticos** por push (hora do treino, água, refeição, fim do jejum).
- [ ] **Relatório em PDF** da avaliação física e da ficha para enviar ao aluno.
- [ ] **Cardio e aulas** como tipo de sessão (hoje só musculação).
- [ ] **Ranking entre alunos** (opcional por tenant) usando o XP que já existe.

### 4.3 Prioridade baixa / futuro
- [ ] Integração com Apple Health / Google Fit.
- [ ] App nativo empacotado (Capacitor) para publicar nas lojas.
- [ ] Multi-treinador por conta (equipe, com permissões).
- [ ] IA para sugerir progressão de carga a partir do histórico.

---

## 5. Manutenção

```bash
cd /www/wwwroot/publishdev.com.br/treino

php -l inc/boot.php                 # lint de um arquivo
php sql/seed.php                    # recria a demonstração do zero
php sql/seed_demo2.php              # completa a demo (chat, endereço, medidas)
mysql -ubanco -pbanco banco         # acesso ao banco
tail -f /www/wwwlogs/treino.alequizao.com-error_log
```

Vhost: `/www/server/panel/vhost/apache/treino.alequizao.com.conf` (PHP 8.3, aceita
`*.treino.alequizao.com`). Após alterar: `/www/server/apache/bin/apachectl -k graceful`.
