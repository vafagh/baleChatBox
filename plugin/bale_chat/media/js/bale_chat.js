/* global window, document, fetch, AbortController, FormData */
(function () {
  'use strict';

  var cfg = window.BaleChatConfig || {};
  var widgetOpen = false;
  var baleAvailable = null; // null | true | false

  function esc(str) {
    return String(str || '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function checkBaleReachability(done) {
    if (cfg.primaryService === 'telegram') {
      baleAvailable = false;
      done();
      return;
    }

    var controller = new AbortController();
    var timer = setTimeout(function () {
      controller.abort();
    }, cfg.fallbackTimeout || 4000);

    fetch('https://tapi.bale.ai/', { method: 'HEAD', mode: 'no-cors', signal: controller.signal })
      .then(function () {
        clearTimeout(timer);
        baleAvailable = true;
        done();
      })
      .catch(function () {
        clearTimeout(timer);
        baleAvailable = false;
        done();
      });
  }

  function serviceLabel() {
    if (cfg.primaryService === 'telegram') {
      return 'تلگرام';
    }

    if (baleAvailable === false) {
      return 'تلگرام';
    }

    return 'بال';
  }

  function buildHTML() {
    var color = esc(cfg.buttonColor || '#0088cc');
    var posCSS = cfg.position === 'bottom-left' ? 'left:24px' : 'right:24px';
    var welcome = esc(cfg.welcomeMessage || 'سلام! چطور می‌توانم کمک کنم؟');
    var token = esc(cfg.token || '');

    return (
      '<style>' +
        '#bale-chat-widget *{box-sizing:border-box;font-family:Tahoma,Arial,sans-serif}' +
        '#bc-btn{position:fixed;bottom:24px;' + posCSS + ';width:56px;height:56px;border-radius:50%;' +
          'background:' + color + ';border:none;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,.28);' +
          'display:flex;align-items:center;justify-content:center;z-index:9998;transition:transform .2s}' +
        '#bc-btn:hover{transform:scale(1.08)}' +
        '#bc-btn svg{width:28px;height:28px;fill:#fff}' +
        '#bc-panel{position:fixed;bottom:92px;' + posCSS + ';width:340px;max-width:calc(100vw - 28px);' +
          'height:520px;max-height:calc(100vh - 120px);background:#fff;border-radius:16px;' +
          'box-shadow:0 10px 32px rgba(0,0,0,.2);z-index:9999;display:none;flex-direction:column;overflow:hidden}' +
        '#bc-panel.bc-open{display:flex}' +
        '.bc-head{background:' + color + ';color:#fff;padding:12px 14px;display:flex;align-items:center;justify-content:space-between}' +
        '.bc-head h3{margin:0;font-size:15px;font-weight:700}' +
        '.bc-head button{background:none;border:none;color:#fff;cursor:pointer;font-size:22px;line-height:1;padding:0}' +
        '.bc-subhead{padding:8px 12px;background:#f6fbff;border-bottom:1px solid #e5edf5;font-size:12px;direction:rtl}' +
        '.bc-subhead strong{color:' + color + '}' +
        '.bc-subhead .bc-warn{display:none;color:#8a6d3b;background:#fff3cd;border:1px solid #faebcc;padding:5px 8px;border-radius:6px;margin-top:6px}' +
        '.bc-subhead .bc-warn.bc-show{display:block}' +
        '.bc-body{flex:1;padding:12px;overflow-y:auto;direction:rtl;background:#fafafa}' +
        '.bc-row{display:flex;gap:8px;margin-bottom:8px;align-items:flex-end}' +
        '.bc-row.bc-user{justify-content:flex-start}' +
        '.bc-row.bc-bot{justify-content:flex-end}' +
        '.bc-avatar{width:28px;height:28px;border-radius:50%;background:' + color + ';color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}' +
        '.bc-bubble{max-width:78%;padding:9px 12px;border-radius:14px;font-size:13px;line-height:1.55;word-break:break-word}' +
        '.bc-row.bc-user .bc-bubble{background:#e9f6ff;border:1px solid #cde9ff}' +
        '.bc-row.bc-bot .bc-bubble{background:#fff;border:1px solid #e5e5e5}' +
        '.bc-foot{padding:10px;border-top:1px solid #e5e5e5;background:#fff}' +
        '.bc-name,.bc-email,.bc-input{width:100%;border:1px solid #d9d9d9;border-radius:10px;padding:9px 10px;font-size:13px;outline:none;direction:rtl}' +
        '.bc-name:focus,.bc-email:focus,.bc-input:focus{border-color:' + color + '}' +
        '.bc-name,.bc-email{margin-bottom:8px}' +
        '.bc-send{margin-top:8px;width:100%;border:none;border-radius:10px;padding:10px;background:' + color + ';color:#fff;font-size:14px;cursor:pointer}' +
        '.bc-send:disabled{opacity:.55;cursor:not-allowed}' +
      '</style>' +

      '<button id="bc-btn" aria-label="پشتیبانی آنلاین">' +
        '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>' +
      '</button>' +

      '<div id="bc-panel" role="dialog" aria-label="چت پشتیبانی">' +
        '<div class="bc-head"><h3>💬 چت پشتیبانی</h3><button id="bc-close" aria-label="بستن">×</button></div>' +
        '<div class="bc-subhead">سرویس فعال: <strong id="bc-service-label"></strong><div id="bc-fallback-notice" class="bc-warn">⚠️ بال در دسترس نیست، ارسال با تلگرام انجام می‌شود.</div></div>' +
        '<div id="bc-log" class="bc-body">' +
          '<div class="bc-row bc-bot"><div class="bc-avatar">🤖</div><div class="bc-bubble">' + welcome + '</div></div>' +
        '</div>' +
        '<form id="bc-form" class="bc-foot" novalidate>' +
          '<input class="bc-name" type="text" name="name" maxlength="100" placeholder="نام شما *" required autocomplete="name" />' +
          '<input class="bc-email" type="email" name="email" maxlength="254" placeholder="ایمیل (اختیاری)" autocomplete="email" />' +
          '<textarea class="bc-input" name="message" maxlength="2000" rows="3" placeholder="پیام خود را بنویسید... *" required></textarea>' +
          '<input type="hidden" name="' + token + '" value="1" />' +
          '<button id="bc-send" class="bc-send" type="submit">ارسال پیام</button>' +
        '</form>' +
      '</div>'
    );
  }

  function addBubble(logEl, side, text) {
    var safe = esc(text);
    var row = document.createElement('div');
    row.className = 'bc-row ' + (side === 'user' ? 'bc-user' : 'bc-bot');
    row.innerHTML = side === 'user'
      ? '<div class="bc-bubble">' + safe + '</div><div class="bc-avatar">👤</div>'
      : '<div class="bc-avatar">🤖</div><div class="bc-bubble">' + safe + '</div>';
    logEl.appendChild(row);
    logEl.scrollTop = logEl.scrollHeight;
  }

  function updateServiceUi() {
    var labelEl = document.getElementById('bc-service-label');
    var warnEl = document.getElementById('bc-fallback-notice');
    if (!labelEl || !warnEl) {
      return;
    }

    labelEl.textContent = serviceLabel();
    var showWarn = cfg.primaryService !== 'telegram' && baleAvailable === false;
    warnEl.className = 'bc-warn' + (showWarn ? ' bc-show' : '');
  }

  function submitMessage(form, logEl, sendBtn) {
    var name = (form.elements.name || {}).value || '';
    var msg = (form.elements.message || {}).value || '';
    var email = (form.elements.email || {}).value || '';

    if (!name.trim() || !msg.trim()) {
      addBubble(logEl, 'bot', 'لطفاً نام و پیام را کامل وارد کنید.');
      return;
    }

    addBubble(logEl, 'user', msg.trim());

    sendBtn.disabled = true;
    sendBtn.textContent = 'در حال ارسال...';

    var body = new FormData();
    body.append('name', name);
    body.append('email', email);
    body.append('message', msg);

    var tokenField = cfg.token || '';
    if (tokenField) {
      body.append(tokenField, '1');
    }

    fetch(cfg.ajaxUrl || 'index.php?option=com_ajax&plugin=bale_chat&group=system&format=json', {
      method: 'POST',
      body: body,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var res = (data && data.data && data.data[0]) ? data.data[0] : {};
        if (res.success) {
          addBubble(logEl, 'bot', res.message || 'پیام شما با موفقیت ارسال شد.');
          form.elements.message.value = '';
        } else {
          addBubble(logEl, 'bot', res.message || 'ارسال پیام ناموفق بود.');
        }
      })
      .catch(function () {
        addBubble(logEl, 'bot', 'خطای شبکه. دوباره تلاش کنید.');
      })
      .finally(function () {
        sendBtn.disabled = false;
        sendBtn.textContent = 'ارسال پیام';
      });
  }

  function init() {
    var container = document.createElement('div');
    container.id = 'bale-chat-widget';
    container.innerHTML = buildHTML();
    document.body.appendChild(container);

    var btn = document.getElementById('bc-btn');
    var panel = document.getElementById('bc-panel');
    var close = document.getElementById('bc-close');
    var form = document.getElementById('bc-form');
    var logEl = document.getElementById('bc-log');
    var sendBtn = document.getElementById('bc-send');

    checkBaleReachability(updateServiceUi);

    btn.addEventListener('click', function () {
      widgetOpen = !widgetOpen;
      panel.className = widgetOpen ? 'bc-panel bc-open' : 'bc-panel';
    });

    close.addEventListener('click', function () {
      widgetOpen = false;
      panel.className = 'bc-panel';
    });

    document.addEventListener('click', function (e) {
      if (widgetOpen && !container.contains(e.target)) {
        widgetOpen = false;
        panel.className = 'bc-panel';
      }
    });

    if (form) {
      form.addEventListener('submit', function (e) {
        e.preventDefault();
        submitMessage(form, logEl, sendBtn);
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
}());
