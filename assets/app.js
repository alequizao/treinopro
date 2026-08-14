/* TreinoPro — comportamento AJAX padrão (fetch + JSON, sem reload) */
(function () {
  'use strict';

  // ---------- flash / toast ----------
  window.flash = function (msg, tipo) {
    var area = document.getElementById('flash');
    if (!area) { alert(msg); return; }
    var d = document.createElement('div');
    d.className = 'flash' + (tipo === 'erro' ? ' erro' : '');
    d.textContent = msg;
    area.appendChild(d);
    setTimeout(function () { d.style.opacity = '0'; setTimeout(function () { d.remove(); }, 300); }, 4000);
  };

  // ---------- fetch helper ----------
  window.api = function (acao, dados) {
    var fd = dados instanceof FormData ? dados : new FormData();
    if (!(dados instanceof FormData) && dados) {
      Object.keys(dados).forEach(function (k) { fd.append(k, dados[k]); });
    }
    fd.append('csrf', window.CSRF);
    fd.append('ajax', '1');
    return fetch((window.BASE.replace('index.php', 'api.php')) + '?acao=' + encodeURIComponent(acao), {
      method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) { return r.json().catch(function () { return { ok: false, msg: 'Erro de comunicação.' }; }); });
  };

  // =========================================================
  //  Navegação SPA: nada recarrega a página inteira
  // =========================================================
  // registra timers criados pelas telas para poder limpá-los na troca de tela
  var timers = [];
  var setIntervalOrig = window.setInterval;
  window.setInterval = function (fn, ms) { var id = setIntervalOrig(fn, ms); timers.push(id); return id; };
  function limparTimers() { timers.forEach(clearInterval); timers = []; }

  function rodarScripts(container) {
    container.querySelectorAll('script').forEach(function (s) {
      var novo = document.createElement('script');
      if (s.src) { novo.src = s.src; } else { novo.textContent = s.textContent; }
      document.body.appendChild(novo);
      if (!s.src) novo.remove();
    });
  }

  function trocarConteudo(html, url) {
    var doc = new DOMParser().parseFromString(html, 'text/html');
    var novoCorpo = doc.querySelector('.corpo');
    var corpoAtual = document.querySelector('.corpo');
    if (!novoCorpo || !corpoAtual) { location.href = url; return false; }

    limparTimers();
    corpoAtual.innerHTML = novoCorpo.innerHTML;

    // título e subtítulo
    var t1 = doc.querySelector('.topbar .titulo'), t2 = document.querySelector('.topbar .titulo');
    if (t1 && t2) t2.innerHTML = t1.innerHTML;
    if (doc.title) document.title = doc.title;

    // marca o item ativo no menu e na tab bar
    var alvo = url.split('?')[0].replace(/\/$/, '');
    document.querySelectorAll('.menu a, .tabbar a').forEach(function (a) {
      var href = (a.getAttribute('href') || '').split('?')[0].replace(/\/$/, '');
      a.classList.toggle('ativo', href !== '' && href === alvo);
    });
    // badges do menu (contadores) vindos da nova página
    var b1 = doc.getElementById('badgeMsg'), b2 = document.getElementById('badgeMsg');
    if (b1 && b2) { b2.textContent = b1.textContent; b2.style.display = b1.style.display; }

    rodarScripts(corpoAtual);
    window.scrollTo({ top: 0, behavior: 'smooth' });
    corpoAtual.style.animation = 'none'; void corpoAtual.offsetWidth; corpoAtual.style.animation = '';
    return true;
  }

  var carregando = false;
  window.irPara = function (url, push) {
    if (carregando) return;
    carregando = true;
    document.body.classList.add('carregando');
    return fetch(url, { headers: { 'X-Requested-With': 'fetch-parcial' }, credentials: 'same-origin' })
      .then(function (r) {
        if (r.redirected && r.url.indexOf('login') > -1) { location.href = r.url; return ''; }
        return r.text();
      })
      .then(function (html) {
        if (!html) return;
        if (trocarConteudo(html, url) && push !== false) { history.pushState({ url: url }, '', url); }
      })
      .catch(function () { location.href = url; })
      .finally(function () { carregando = false; document.body.classList.remove('carregando'); });
  };

  /** Recarrega a tela atual sem piscar (usado depois de salvar algo). */
  window.recarregarTela = function () { return window.irPara(location.href, false); };

  // intercepta os links internos
  document.addEventListener('click', function (ev) {
    var a = ev.target.closest('a');
    if (!a || ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.button !== 0) return;
    var href = a.getAttribute('href') || '';
    if (!href || href[0] === '#' || href.indexOf('javascript:') === 0) return;
    if (a.target === '_blank' || a.hasAttribute('download') || a.dataset.spa === 'nao') return;
    if (a.href.indexOf(location.origin) !== 0) return;                 // externo
    if (/\.(png|jpe?g|webp|svg|gif|mp4|webm|mov|pdf)$/i.test(a.pathname)) return;  // arquivos
    if (/\/(sair|voltar-conta|entrar-como)/.test(a.pathname)) return;  // trocam a sessão
    ev.preventDefault();
    // fecha a gaveta do menu no mobile
    var sb = document.getElementById('sidebar');
    if (sb && sb.classList.contains('open')) { sb.classList.remove('open');
      var bd2 = document.getElementById('backdrop'); if (bd2) bd2.classList.remove('on'); }
    window.irPara(a.href);
  });

  window.addEventListener('popstate', function () { window.irPara(location.href, false); });

  // atualização automática da tela (estilo AJAX): só quando ninguém está mexendo
  function podeAtualizar() {
    if (document.hidden || carregando) return false;
    if (document.querySelector('.modal-bg.on')) return false;
    var f = document.activeElement;
    if (f && /INPUT|TEXTAREA|SELECT/.test(f.tagName)) return false;
    if (document.querySelector('video:not([paused])')) return false;
    if (document.querySelector('[data-sem-refresh]')) return false;   // telas que se atualizam sozinhas
    return true;
  }
  setIntervalOrig(function () { if (podeAtualizar()) window.recarregarTela(); }, 20000);
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden && podeAtualizar()) window.recarregarTela();
  });

  // ---------- forms com data-acao ----------
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (!f.matches('form[data-acao]')) return;
    ev.preventDefault();
    var btn = f.querySelector('[type=submit]');
    var txt = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = 'Salvando...'; }
    window.api(f.dataset.acao, new FormData(f)).then(function (r) {
      flash(r.msg || (r.ok ? 'Salvo!' : 'Erro'), r.ok ? 'ok' : 'erro');
      if (r.ok) {
        if (f.dataset.redir) { irPara(r.redir || f.dataset.redir); return; }
        if (f.dataset.recarrega !== 'nao') { recarregarTela(); return; }
        if (f.dataset.limpa === 'sim') { f.reset(); }
      }
    }).catch(function () { flash('Falha de rede.', 'erro'); })
      .finally(function () { if (btn) { btn.disabled = false; btn.innerHTML = txt; } });
  });

  // ---------- botões com data-acao (ex: excluir) ----------
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-click]');
    if (!b) return;
    ev.preventDefault();
    if (b.dataset.confirma && !confirm(b.dataset.confirma)) return;
    var extra = {};
    try { extra = JSON.parse(b.dataset.dados || '{}'); } catch (e) {}
    b.disabled = true;
    window.api(b.dataset.click, extra).then(function (r) {
      flash(r.msg, r.ok ? 'ok' : 'erro');
      if (r.ok) {
        if (b.dataset.redir) { irPara(b.dataset.redir); return; }
        if (b.dataset.remove) { var el = b.closest(b.dataset.remove); if (el) el.remove(); return; }
        recarregarTela();
      }
    }).catch(function () { flash('Falha de rede.', 'erro'); })
      .finally(function () { b.disabled = false; });
  });

  // ---------- menu mobile ----------
  var side = document.getElementById('sidebar'), bd = document.getElementById('backdrop');
  function abrir() { if (side) { side.classList.add('open'); bd && bd.classList.add('on'); } }
  function fechar() { if (side) { side.classList.remove('open'); bd && bd.classList.remove('on'); } }
  var bm = document.getElementById('abrirMenu'); if (bm) bm.onclick = abrir;
  var bt = document.getElementById('abrirMenuTab'); if (bt) bt.onclick = function (e) { e.preventDefault(); abrir(); };
  if (bd) bd.onclick = fechar;

  // ---------- mostrar/ocultar senha ----------
  document.querySelectorAll('.senha-wrap .olho').forEach(function (o) {
    o.onclick = function () {
      var i = o.parentNode.querySelector('input');
      i.type = i.type === 'password' ? 'text' : 'password';
      o.innerHTML = i.type === 'password' ? '<i class="fa-solid fa-eye"></i>' : '<i class="fa-solid fa-eye-slash"></i>';
    };
  });

  // ---------- player de vídeo em popup ----------
  function idYoutube(u) {
    var m = u.match(/(?:youtu\.be\/|v=|embed\/|shorts\/)([A-Za-z0-9_-]{6,})/);
    return m ? m[1] : null;
  }
  window.abrirVideo = function (url, titulo) {
    var cx = document.getElementById('modalVideo');
    if (!cx) {
      cx = document.createElement('div');
      cx.className = 'modal-bg';
      cx.id = 'modalVideo';
      cx.innerHTML = '<div class="modal-cx video-cx">' +
        '<button class="fechar" data-fecha="modalVideo"><i class="fa-solid fa-xmark"></i></button>' +
        '<h3 id="videoTitulo"></h3><div id="videoAlvo"></div></div>';
      document.body.appendChild(cx);
    }
    document.getElementById('videoTitulo').textContent = titulo || 'Execução do exercício';
    var alvo = document.getElementById('videoAlvo');
    var yt = idYoutube(url);
    if (yt) {
      alvo.innerHTML = '<div class="video-wrap"><iframe src="https://www.youtube-nocookie.com/embed/' + yt +
        '?rel=0&autoplay=1" allow="accelerometer;autoplay;encrypted-media;picture-in-picture" ' +
        'allowfullscreen frameborder="0"></iframe></div>';
    } else if (/\.(mp4|webm|mov|m4v)(\?|$)/i.test(url)) {
      alvo.innerHTML = '<video src="' + url + '" controls autoplay playsinline style="width:100%;border-radius:12px"></video>';
    } else {
      alvo.innerHTML = '<div class="video-wrap"><iframe src="' + url + '" allowfullscreen frameborder="0"></iframe></div>';
    }
    abrirModal('modalVideo');
  };
  // qualquer link de vídeo abre no popup
  document.addEventListener('click', function (ev) {
    var a = ev.target.closest('[data-video], a.video-link');
    if (!a) return;
    ev.preventDefault();
    abrirVideo(a.dataset.video || a.href, a.dataset.titulo || a.getAttribute('title'));
  });
  // fecha o player parando a reprodução
  document.addEventListener('click', function (ev) {
    var f = ev.target.closest('[data-fecha="modalVideo"]');
    if (f || (ev.target.id === 'modalVideo')) {
      var alvo = document.getElementById('videoAlvo');
      if (alvo) alvo.innerHTML = '';
    }
  });

  // ---------- modais ----------
  window.abrirModal = function (id) { var m = document.getElementById(id); if (m) m.classList.add('on'); };
  window.fecharModal = function (id) { var m = document.getElementById(id); if (m) m.classList.remove('on'); };
  document.addEventListener('click', function (ev) {
    if (ev.target.classList && ev.target.classList.contains('modal-bg')) ev.target.classList.remove('on');
    var f = ev.target.closest('[data-modal]');
    if (f) { ev.preventDefault(); abrirModal(f.dataset.modal); }
    var x = ev.target.closest('[data-fecha]');
    if (x) { ev.preventDefault(); fecharModal(x.dataset.fecha); }
  });

  // ---------- polling opcional (elementos com data-refresh) ----------
  var alvos = document.querySelectorAll('[data-refresh]');
  if (alvos.length) {
    setInterval(function () {
      if (document.hidden) return;
      if (document.querySelector('.modal-bg.on')) return;
      alvos.forEach(function (el) {
        window.api(el.dataset.refresh, {}).then(function (r) {
          if (r.ok && r.html && r.html !== el.dataset.sig) { el.dataset.sig = r.html; el.innerHTML = r.html; }
        }).catch(function () {});
      });
    }, 6000);
  }

  // ---------- feedback visual: vibração ----------
  window.vibrar = function (p) { if (navigator.vibrate) { try { navigator.vibrate(p); } catch (e) {} } };

  // ---------- confete (canvas leve, sem biblioteca) ----------
  window.confete = function (qtd) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var cv = document.createElement('canvas');
    cv.className = 'confete-canvas';
    cv.width = innerWidth; cv.height = innerHeight;
    document.body.appendChild(cv);
    var ctx = cv.getContext('2d');
    var cores = ['#F26522', '#2D7FF9', '#1E9E57', '#D98E0B', '#7A28C7', '#ffffff'];
    var ps = [];
    for (var i = 0; i < (qtd || 90); i++) {
      ps.push({ x: Math.random() * cv.width, y: -20 - Math.random() * cv.height * 0.4,
        vx: (Math.random() - 0.5) * 3, vy: 2 + Math.random() * 4,
        s: 5 + Math.random() * 7, r: Math.random() * 6.28, vr: (Math.random() - 0.5) * 0.3,
        c: cores[(Math.random() * cores.length) | 0] });
    }
    var t0 = Date.now();
    (function tick() {
      ctx.clearRect(0, 0, cv.width, cv.height);
      ps.forEach(function (p) {
        p.x += p.vx; p.y += p.vy; p.vy += 0.06; p.r += p.vr;
        ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.r);
        ctx.fillStyle = p.c; ctx.fillRect(-p.s / 2, -p.s / 2, p.s, p.s * 0.6); ctx.restore();
      });
      if (Date.now() - t0 < 2600) { requestAnimationFrame(tick); } else { cv.remove(); }
    })();
  };

  // ---------- pop-up de conquista desbloqueada ----------
  window.conquista = function (lista) {
    if (!lista || !lista.length) return;
    lista.forEach(function (c, i) {
      setTimeout(function () {
        var d = document.createElement('div');
        d.className = 'conquista-pop';
        d.innerHTML = '<span class="ic" style="background:' + (c.cor || '#F26522') + '">' +
          '<i class="fa-solid ' + (c.icone || 'fa-trophy') + '"></i></span>' +
          '<div><small>Conquista desbloqueada</small><strong>' + c.nome + '</strong></div>';
        document.body.appendChild(d);
        vibrar([40, 60, 120]);
        confete(70);
        setTimeout(function () { d.classList.add('sai'); setTimeout(function () { d.remove(); }, 400); }, 3600);
      }, i * 900);
    });
  };

  // ---------- garrafa de água (SVG com onda animada) ----------
  window.encherGarrafa = function (pct) {
    var g = document.getElementById('garrafa');
    if (!g) return;
    pct = Math.max(0, Math.min(100, pct));
    var nivel = g.querySelector('.agua-grupo');
    // 0% => desce tudo (altura 190 do interior), 100% => topo
    if (nivel) nivel.style.transform = 'translateY(' + (190 - 190 * pct / 100) + 'px)';
    var lbl = document.getElementById('garrafaPct');
    if (lbl) lbl.textContent = pct + '%';
  };

  // ---------- anel de progresso (jejum) ----------
  window.anel = function (id, pct) {
    var c = document.getElementById(id);
    if (!c) return;
    var raio = c.r.baseVal.value, vol = 2 * Math.PI * raio;
    c.style.strokeDasharray = vol;
    c.style.strokeDashoffset = vol - vol * Math.max(0, Math.min(100, pct)) / 100;
  };

  // ---------- números que sobem ----------
  document.querySelectorAll('[data-conta]').forEach(function (el) {
    var alvo = parseFloat(el.dataset.conta) || 0, dec = (el.dataset.dec | 0), ini = Date.now();
    (function sobe() {
      var p = Math.min(1, (Date.now() - ini) / 700);
      el.textContent = (alvo * (1 - Math.pow(1 - p, 3))).toFixed(dec).replace('.', ',');
      if (p < 1) requestAnimationFrame(sobe);
    })();
  });

  // ---------- notificações push ----------
  function b64ToU8(b64) {
    var pad = '='.repeat((4 - b64.length % 4) % 4);
    var raw = atob((b64 + pad).replace(/-/g, '+').replace(/_/g, '/'));
    return Uint8Array.from([].map.call(raw, function (c) { return c.charCodeAt(0); }));
  }
  window.ativarPush = function (btn) {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
      flash('Seu navegador não suporta notificações.', 'erro'); return;
    }
    if (btn) { btn.disabled = true; btn.textContent = 'Ativando...'; }
    Notification.requestPermission().then(function (p) {
      if (p !== 'granted') { flash('Permissão negada nas configurações do navegador.', 'erro'); return; }
      return api('push_chave', {}).then(function (r) {
        return navigator.serviceWorker.ready.then(function (reg) {
          return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64ToU8(r.chave) });
        });
      }).then(function (sub) {
        var j = sub.toJSON();
        return api('push_inscrever', { endpoint: j.endpoint, p256dh: j.keys.p256dh, auth: j.keys.auth });
      }).then(function (r) {
        flash(r.msg, r.ok ? 'ok' : 'erro');
        if (r.ok && btn) { btn.textContent = 'Ativado ✓'; }
      });
    }).catch(function () { flash('Não foi possível ativar as notificações.', 'erro'); })
      .finally(function () { if (btn) btn.disabled = false; });
  };

  // ---------- contador de mensagens não lidas ----------
  var badge = document.getElementById('badgeMsg');
  if (badge) {
    var atualizaMsg = function () {
      if (document.hidden) return;
      api('msg_nao_lidas', {}).then(function (r) {
        if (!r.ok) return;
        badge.textContent = r.n > 0 ? r.n : '';
        badge.style.display = r.n > 0 ? '' : 'none';
      }).catch(function () {});
    };
    atualizaMsg();
    setInterval(atualizaMsg, 15000);
    document.addEventListener('visibilitychange', atualizaMsg);
  }

  // ---------- PWA + atualização forçada de versão ----------
  var V = window.APP_VERSAO || 'dev';
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register((window.BASE || '').replace('index.php', 'sw.js') + '?v=' + V)
      .catch(function () {});
    navigator.serviceWorker.addEventListener('message', function (e) {
      if (e.data && e.data.tipo === 'versao-nova' && e.data.versao !== localStorage.getItem('tp_v')) {
        localStorage.setItem('tp_v', e.data.versao);
        location.reload();
      }
    });
  }
  // se a versão do servidor mudou, limpa caches antigos e recarrega uma única vez
  (function () {
    var antiga = localStorage.getItem('tp_v');
    localStorage.setItem('tp_v', V);
    if (antiga && antiga !== V) {
      if (window.caches) { caches.keys().then(function (ks) { ks.forEach(function (k) { caches.delete(k); }); }); }
      flash('Atualizado para a versão ' + V);
    }
  })();
  var prompt = null, btnI = document.getElementById('btnInstalar');
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault(); prompt = e; if (btnI) btnI.hidden = false;
  });
  var ios = /iphone|ipad|ipod/i.test(navigator.userAgent) && !navigator.standalone;
  if (ios && btnI) { btnI.hidden = false; }
  if (btnI) {
    btnI.onclick = function () {
      if (prompt) { prompt.prompt(); prompt = null; btnI.hidden = true; }
      else { alert('No iPhone: toque em Compartilhar → "Adicionar à Tela de Início".'); }
    };
  }
})();
