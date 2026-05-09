/* global window, document, fetch, AbortController, FormData */
(function () {
  'use strict';

  const cfg = window.BaleChatConfig || {};

  // ── runtime state ──────────────────────────────────────────────────────────
  let widgetOpen    = false;
  let baleAvailable = null; // null = not yet checked | true | false

  // ── deep-link templates ────────────────────────────────────────────────────
  const BALE_BASE_URL  = 'https://tapi.bale.ai/';           // reachability probe
  const BALE_CHAT_URL  = 'https://ble.ir/' + (cfg.baleUsername || '');
  const TG_CHAT_URL    = 'https://t.me/'   + (cfg.tgUsername   || '');

  // ── helpers ────────────────────────────────────────────────────────────────
  function esc(str) {
    return String(str || '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  // ── widget HTML ───────────────────────────────────────────────────────────
  function buildHTML() {
    const color    = esc(cfg.buttonColor   || '#0088cc');
    const posCSS   = cfg.position === 'bottom-left' ? 'left:24px' : 'right:24px';
    const welcome  = esc(cfg.welcomeMessage || 'سلام! چطور می‌توانم کمک کنم؟');
    const token    = esc(cfg.token || '');
    const baleSrc  = esc(BALE_CHAT_URL);
    const tgSrc    = esc(TG_CHAT_URL);

    return (
      '<style>' +
        '#bale-chat-widget *{box-sizing:border-box;font-family:Tahoma,Arial,sans-serif}' +
        '#bc-btn{position:fixed;bottom:24px;' + posCSS + ';width:56px;height:56px;' +
          'border-radius:50%;background:' + color + ';border:none;cursor:pointer;' +
          'box-shadow:0 4px 16px rgba(0,0,0,.28);display:flex;align-items:center;' +
          'justify-content:center;z-index:9998;transition:transform .2s}' +
        '#bc-btn:hover{transform:scale(1.1)}' +
        '#bc-btn svg{width:28px;height:28px;fill:#fff}' +
        '#bc-panel{position:fixed;bottom:92px;' + posCSS + ';width:320px;' +
          'background:#fff;border-radius:16px;' +
          'box-shadow:0 8px 32px rgba(0,0,0,.18);z-index:9999;display:none;' +
          'flex-direction:column;overflow:hidden;max-height:500px}' +
        '#bc-panel.bc-open{display:flex}' +
        '.bc-head{background:' + color + ';color:#fff;padding:14px 16px;' +
          'display:flex;justify-content:space-between;align-items:center}' +
        '.bc-head h3{margin:0;font-size:15px;font-weight:700}' +
        '.bc-head button{background:none;border:none;color:#fff;cursor:pointer;font-size:22px;line-height:1;padding:0}' +
        '.bc-body{padding:16px;overflow-y:auto;flex:1;direction:rtl}' +
        '.bc-welcome{font-size:14px;color:#444;margin:0 0 14px;text-align:center;line-height:1.6}' +
        '.bc-tabs{display:flex;gap:8px;margin-bottom:14px}' +
        '.bc-tab{flex:1;padding:8px 4px;border:2px solid #ddd;border-radius:8px;' +
          'background:#f8f8f8;cursor:pointer;font-size:13px;text-align:center;transition:all .2s}' +
        '.bc-tab.bc-active,.bc-tab:hover{border-color:' + color + ';background:' + color + ';color:#fff}' +
        '.bc-sec{display:none}.bc-sec.bc-active{display:block}' +
        '.bc-link{display:flex;align-items:center;justify-content:center;gap:8px;' +
          'width:100%;padding:12px;border-radius:10px;border:none;cursor:pointer;' +
          'font-size:14px;margin-bottom:10px;text-decoration:none;color:#fff;' +
          'background:' + color + ';transition:opacity .2s}' +
        '.bc-link:hover{opacity:.88}' +
        '.bc-link.bc-tg{background:#229ED9}' +
        '.bc-notice{font-size:12px;color:#856404;background:#fff3cd;border-radius:6px;' +
          'padding:7px 10px;margin-bottom:10px;display:none}' +
        '.bc-notice.bc-show{display:block}' +
        '.bc-form input,.bc-form textarea{width:100%;padding:9px 12px;border:1px solid #ddd;' +
          'border-radius:8px;font-size:14px;margin-bottom:10px;outline:none;direction:rtl}' +
        '.bc-form input:focus,.bc-form textarea:focus{border-color:' + color + '}' +
        '.bc-form textarea{min-height:80px;resize:vertical}' +
        '.bc-submit{width:100%;padding:10px;background:' + color + ';color:#fff;' +
          'border:none;border-radius:8px;font-size:14px;cursor:pointer;transition:opacity .2s}' +
        '.bc-submit:disabled{opacity:.55;cursor:not-allowed}' +
        '.bc-msg{font-size:13px;padding:8px 10px;border-radius:6px;margin-top:6px;display:none}' +
        '.bc-msg.bc-ok{background:#d4edda;color:#155724;display:block}' +
        '.bc-msg.bc-err{background:#f8d7da;color:#721c24;display:block}' +
      '</style>' +

      '<button id="bc-btn" aria-label="پشتیبانی آنلاین">' +
        '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">' +
          '<path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>' +
        '</svg>' +
      '</button>' +

      '<div id="bc-panel" role="dialog" aria-label="چت پشتیبانی">' +
        '<div class="bc-head">' +
          '<h3>💬 پشتیبانی آنلاین</h3>' +
          '<button id="bc-close" aria-label="بستن">×</button>' +
        '</div>' +
        '<div class="bc-body">' +
          '<p id="bc-fallback-notice" class="bc-notice">⚠️ سرویس Bale در دسترس نیست. اتصال به Telegram…</p>' +
          '<p class="bc-welcome">' + welcome + '</p>' +
          '<div class="bc-tabs">' +
            '<div class="bc-tab bc-active" data-tab="messenger">پیام‌رسان</div>' +
            '<div class="bc-tab" data-tab="form">فرم ارتباطی</div>' +
          '</div>' +
          '<div class="bc-sec bc-active" id="bc-tab-messenger">' +
            '<a id="bc-bale-link" class="bc-link" href="' + baleSrc + '" target="_blank" rel="noopener noreferrer">📱 چت در Bale</a>' +
            '<a id="bc-tg-link" class="bc-link bc-tg" href="' + tgSrc + '" target="_blank" rel="noopener noreferrer" style="display:none">✈️ چت در Telegram</a>' +
          '</div>' +
          '<div class="bc-sec" id="bc-tab-form">' +
            '<form class="bc-form" id="bc-contact-form" novalidate>' +
              '<input type="text"  name="name"    placeholder="نام شما *"           required maxlength="100" autocomplete="name" />' +
              '<input type="email" name="email"   placeholder="ایمیل (اختیاری)"              maxlength="254" autocomplete="email" />' +
              '<textarea           name="message" placeholder="پیام شما *"           required maxlength="2000"></textarea>' +
              '<input type="hidden" name="' + token + '" value="1" />' +
              '<button type="submit" class="bc-submit">ارسال پیام</button>' +
              '<div class="bc-msg" id="bc-form-msg"></div>' +
            '</form>' +
          '</div>' +
        '</div>' +
      '</div>'
    );
  }

  // ── Bale reachability check ────────────────────────────────────────────────
  function checkBale() {
    if (!cfg.baleUsername) {
      baleAvailable = false;
      applyServiceState();
      return;
    }

    var controller = new AbortController();
    var timer      = setTimeout(function () { controller.abort(); }, cfg.fallbackTimeout || 4000);

    fetch(BALE_BASE_URL, { method: 'HEAD', signal: controller.signal, mode: 'no-cors' })
      .then(function () {
        clearTimeout(timer);
        baleAvailable = true;
        applyServiceState();
      })
      .catch(function () {
        clearTimeout(timer);
        baleAvailable = false;
        applyServiceState();
      });
  }

  // ── update UI based on availability ──────────────────────────────────────
  function applyServiceState() {
    var baleLink = document.getElementById('bc-bale-link');
    var tgLink   = document.getElementById('bc-tg-link');
    var notice   = document.getElementById('bc-fallback-notice');

    var useBale =
      cfg.primaryService !== 'telegram' &&
      cfg.baleUsername &&
      baleAvailable !== false;

    var useTg =
      cfg.tgUsername &&
      (cfg.primaryService === 'telegram' || baleAvailable === false || !cfg.baleUsername);

    if (baleLink) baleLink.style.display = useBale ? 'flex' : 'none';
    if (tgLink)   tgLink.style.display   = useTg   ? 'flex' : 'none';

    if (notice) {
      var showNotice = cfg.primaryService !== 'telegram' && baleAvailable === false && cfg.tgUsername;
      notice.className = 'bc-notice' + (showNotice ? ' bc-show' : '');
    }

    // If nothing to show in messenger tab, activate form tab instead
    if (!useBale && !useTg) {
      switchTab('form');
    }
  }

  // ── tab switching ─────────────────────────────────────────────────────────
  function switchTab(name) {
    var tabs = document.querySelectorAll('#bale-chat-widget .bc-tab');
    var secs = document.querySelectorAll('#bale-chat-widget .bc-sec');

    tabs.forEach(function (t) {
      t.className = 'bc-tab' + (t.dataset.tab === name ? ' bc-active' : '');
    });
    secs.forEach(function (s) {
      s.className = 'bc-sec' + (s.id === 'bc-tab-' + name ? ' bc-active' : '');
    });
  }

  // ── form submission ───────────────────────────────────────────────────────
  function submitForm(form) {
    var msgEl  = document.getElementById('bc-form-msg');
    var btn    = form.querySelector('.bc-submit');
    var name   = (form.elements.name    || {}).value;
    var msg    = (form.elements.message || {}).value;

    if (!name || !name.trim() || !msg || !msg.trim()) {
      showMsg(msgEl, 'لطفاً نام و پیام را وارد کنید.', 'bc-err');
      return;
    }

    btn.disabled    = true;
    btn.textContent = 'در حال ارسال…';

    fetch(cfg.ajaxUrl || '', {
      method:  'POST',
      body:    new FormData(form),
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var res = (data && data.data && data.data[0]) ? data.data[0] : {};

        if (res.success) {
          showMsg(msgEl, res.message || 'پیام ارسال شد.', 'bc-ok');
          form.reset();
        } else {
          showMsg(msgEl, res.message || 'خطا در ارسال پیام.', 'bc-err');
        }
      })
      .catch(function () {
        showMsg(msgEl, 'خطای شبکه. لطفاً دوباره امتحان کنید.', 'bc-err');
      })
      .finally(function () {
        btn.disabled    = false;
        btn.textContent = 'ارسال پیام';
      });
  }

  function showMsg(el, text, cls) {
    if (!el) return;
    el.className  = 'bc-msg ' + cls;
    el.textContent = text;
    clearTimeout(el._hideTimer);
    el._hideTimer = setTimeout(function () { el.className = 'bc-msg'; }, 6000);
  }

  // ── bootstrap ─────────────────────────────────────────────────────────────
  function init() {
    var container   = document.createElement('div');
    container.id    = 'bale-chat-widget';
    container.innerHTML = buildHTML();
    document.body.appendChild(container);

    var btn   = document.getElementById('bc-btn');
    var panel = document.getElementById('bc-panel');
    var close = document.getElementById('bc-close');
    var form  = document.getElementById('bc-contact-form');

    // Apply initial state (may change once checkBale resolves)
    applyServiceState();

    // Toggle panel visibility
    btn.addEventListener('click', function () {
      widgetOpen = !widgetOpen;
      panel.className = 'bc-panel' + (widgetOpen ? ' bc-open' : '');
      // Probe Bale on first open
      if (widgetOpen && baleAvailable === null && cfg.primaryService !== 'telegram') {
        checkBale();
      }
    });

    close.addEventListener('click', function () {
      widgetOpen      = false;
      panel.className = 'bc-panel';
    });

    // Close when clicking outside
    document.addEventListener('click', function (e) {
      if (widgetOpen && !container.contains(e.target)) {
        widgetOpen      = false;
        panel.className = 'bc-panel';
      }
    });

    // Tab clicks
    container.querySelectorAll('.bc-tab').forEach(function (tab) {
      tab.addEventListener('click', function () { switchTab(tab.dataset.tab); });
    });

    // Form submission
    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitForm(form);
      });
    }

    // If Telegram is the primary service, no need to probe Bale
    if (cfg.primaryService === 'telegram') {
      baleAvailable = false;
      applyServiceState();
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
