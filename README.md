# TreinoPro — SaaS whitelabel para personal trainers

Plataforma no estilo treino.io: painel do treinador (web) + app do aluno (PWA escuro),
com marca própria de cada personal (logo, cores, nome, domínio).

- **URL:** https://treino.alequizao.com (também acessível em publishdev.com.br/treino)
- **Stack:** PHP 8.3 + MySQL + Bootstrap 5 + Chart.js (sem build, sem dependências)
- **Banco:** `banco` (usuário `banco`), tabelas com prefixo `tr_`
- **Versão:** ver `APP_VERSAO` em `inc/boot.php` e o `CHANGELOG.md`

## Acessos de demonstração

| Perfil | Usuário | Senha |
|---|---|---|
| Master (dono do SaaS) | `alequizao` | definida em `SEED_MASTER_PASS` na instalação |
| Personal | `demo-personal` | `demo` |
| Aluno | `demo-aluno` | `demo` |
| Outros alunos | `carlos.demo`, `juliana.demo`, `rafael.demo` | `demo` |

A conta demo (**Studio Performance**) vem com 4 alunos, ficha ABC completa com 20
exercícios, dieta de 6 refeições com macros e substituições, 26 treinos no histórico,
5 avaliações físicas com evolução real, anamnese preenchida, 3 planos e 16 cobranças.

## Estrutura

```
index.php          front controller (rotas ?p=...)
api.php            todas as ações AJAX (?acao=...)
manifest.php       manifest PWA dinâmico por tenant
sw.js              service worker (cache de assets)
inc/boot.php       banco, sessão, tenant, auth, personificação, helpers
inc/layout.php     topo()/rodape(), menu, cards, badges
pages/             uma página por tela
assets/            app.css + app.js
sql/schema.sql     esquema do banco
sql/seed.php       biblioteca de exercícios + master + conta demo (php sql/seed.php)
uploads/           logos dos personais
```

## Multi-tenant (whitelabel)

Um único banco, tudo isolado por `tenant_id`. O tenant é resolvido nesta ordem:

1. domínio próprio do personal (`tr_tenants.dominio`);
2. subdomínio (`fulano.treino.alequizao.com` → slug `fulano`) — o vhost já aceita
   `*.treino.alequizao.com`, falta apenas o DNS wildcard e um SSL que cubra o curinga;
3. `?t=slug` (funciona já, sem DNS extra);
4. sessão do usuário logado.

O personal edita logo, cores e nome em **Configurações do app**; isso muda o painel,
o app do aluno, a tela de login e o ícone/nome do PWA.

## Ver o app como o aluno

Em **Alunos**, o ícone 🕵 entra na conta do aluno (mesma visão que ele tem, incluindo
o que ele preencheu). Uma faixa roxa fica fixa no topo com "Voltar para minha conta",
que devolve a sessão do personal com um clique. Rotas: `?p=entrar-como&id=` e
`?p=voltar-conta`.

## Regras fixas do projeto

- **Toda alteração sobe a versão** em `inc/boot.php` e vira uma linha no `CHANGELOG.md`.
- Ações sempre por `fetch` + JSON (nada de reload de página inteira); CSRF em todo POST.
- Isolamento multi-tenant checado no backend em **toda** ação (`meu()`, `meu_aluno()`).
- App do aluno: tema escuro com o laranja da marca; painel: sidebar escura + conteúdo claro.

---

## 📸 Tela

[![TreinoPro — SaaS whitelabel para personal trainers, com painel do treinador e app do aluno, desenvolvido por Alex Junior (alequizao)](https://image.thum.io/get/width/700/https://treino.alequizao.com)](https://treino.alequizao.com)

---

## 👨‍💻 Desenvolvedor

Projetado e desenvolvido **100% por Alex Junior (alequizao)** — da ideia ao deploy:
levantamento, modelagem do banco, backend, interface e publicação em produção.
Analista e Desenvolvedor de Sistemas em **Maceió, Alagoas**, Brasil. Programador na
**Publish Digital**.

- **E-mail:** alequizao.dev@gmail.com
- **WhatsApp:** [(82) 98871-7072](https://wa.me/5582988717072)
- **Instagram:** [@alequizao](https://instagram.com/alequizao)
- **GitHub:** [@alequizao](https://github.com/alequizao) · [perfil completo](https://github.com/alequizao/alequizao)
- **Site:** [alequizao.com](https://alequizao.com)

---

© Código proprietário, desenvolvido sob encomenda.
