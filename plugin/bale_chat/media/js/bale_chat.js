/* global window, document, fetch, FormData */
(function () {
  'use strict';

  var cfg = window.BaleChatConfig || {};
  var widgetOpen = false;
  var activeService = 'joomla'; // bale | telegram | joomla

  function esc(str) {
    return String(str || '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function detectActiveService(done) {
    // Browser-side reachability checks are unreliable under CORS/network policies.
    // Keep UI aligned with strict configured priority, and let server decide fallback.
    if (cfg.baleConfigured) {
      activeService = 'bale';
      done();
      return;
    }

    if (cfg.telegramConfigured) {
      activeService = 'telegram';
      done();
      return;
    }

    activeService = 'joomla';
    done();
  }

  function serviceLabel() {
    if (activeService === 'bale') {
      return 'بله';
    }

    if (activeService === 'telegram') {
      return 'تلگرام';
    }

    return 'فرم تماس جوملا';
  }

  function contactLabel() {
    if (activeService === 'bale') {
      return 'شناسه بله شما';
    }

    if (activeService === 'telegram') {
      return 'شناسه تلگرام شما';
    }

    return 'ایمیل شما';
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
          'height:calc(90vh - 108px);max-height:calc(90vh - 108px);background:#fff;border-radius:16px;' +
          'box-shadow:0 10px 32px rgba(0,0,0,.2);z-index:9999;display:none;flex-direction:column;overflow:hidden}' +
        '#bc-panel.bc-open{display:flex}' +
        '#bc-panel.bc-bale::before{content:"بله";position:absolute;inset:auto 16px 18px auto;font-size:74px;font-weight:700;letter-spacing:.04em;color:rgba(0,136,204,.06);transform:rotate(-12deg);pointer-events:none;z-index:0}' +
        '#bc-panel.bc-telegram::before{content:"تلگرام";position:absolute;inset:auto 16px 18px auto;font-size:54px;font-weight:700;letter-spacing:.04em;color:rgba(34,157,217,.05);transform:rotate(-12deg);pointer-events:none;z-index:0}' +
        '#bc-panel.bc-joomla::before{content:"Joomla";position:absolute;inset:auto 16px 18px auto;font-size:52px;font-weight:700;letter-spacing:.04em;color:rgba(100,100,100,.05);transform:rotate(-12deg);pointer-events:none;z-index:0}' +
        '.bc-head{background:' + color + ';color:#fff;padding:12px 14px;display:flex;align-items:center;justify-content:space-between}' +
        '.bc-head h3{margin:0;font-size:15px;font-weight:700;position:relative;z-index:1}' +
        '.bc-head button{background:none;border:none;color:#fff;cursor:pointer;font-size:22px;line-height:1;padding:0}' +
        '.bc-body{flex:1;padding:12px;overflow-y:auto;direction:rtl;background:#fafafa;position:relative;z-index:1}' +
        '.bc-row{display:flex;gap:8px;margin-bottom:8px;align-items:flex-end}' +
        '.bc-row.bc-user{justify-content:flex-start}' +
        '.bc-row.bc-bot{justify-content:flex-end}' +
        '.bc-avatar{width:28px;height:28px;border-radius:50%;background:' + color + ';color:#fff;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0}' +
        '.bc-bubble{max-width:78%;padding:9px 12px;border-radius:14px;font-size:13px;line-height:1.55;word-break:break-word;display:flex;align-items:flex-start;gap:6px}' +
        '.bc-row.bc-user .bc-bubble{background:#e9f6ff;border:1px solid #cde9ff}' +
        '.bc-row.bc-bot .bc-bubble{background:#fff;border:1px solid #e5e5e5}' +
        '.bc-bubble.bc-sending{opacity:.75}' +
        '.bc-check{display:none;color:#1ea85a;font-size:13px;line-height:1;flex-shrink:0;margin-top:1px}' +
        '.bc-row.bc-sent .bc-check{display:inline-flex}' +
        '.bc-text{display:inline-block}' +
        '.bc-foot{padding:10px;border-top:1px solid #e5e5e5;background:#fff;direction:rtl}' +
        '.bc-name,.bc-contact,.bc-input,.bc-phone{width:100%;border:1px solid #d9d9d9;border-radius:10px;padding:9px 10px;font-size:13px;outline:none;direction:rtl;text-align:right}' +
        '.bc-name:focus,.bc-contact:focus,.bc-input:focus,.bc-phone:focus{border-color:' + color + '}' +
        '.bc-name,.bc-email,.bc-phone{margin-bottom:8px}' +
        '.bc-contact-row{display:flex;gap:6px;align-items:stretch;margin-bottom:8px;direction:rtl}' +
        '.bc-contact-row .bc-contact{flex:1;margin-bottom:0}' +
        '.bc-select{border:1px solid #d9d9d9;border-radius:10px;padding:9px 8px;font-size:13px;outline:none;direction:rtl;text-align:right;background:#fff;cursor:pointer;flex:0 0 38%;max-width:40%;min-width:72px}' +
        '.bc-select:focus{border-color:' + color + '}' +
        '#bc-captcha-wrap{margin-bottom:8px;overflow:hidden}' +
        '.bc-file-label{display:block;margin-bottom:8px;font-size:12px;color:#555;cursor:pointer;direction:rtl;text-align:right}' +
        '.bc-file{display:block;margin-top:3px;max-width:100%;font-size:12px}' +
        '.bc-send{margin-top:4px;width:100%;border:none;border-radius:10px;padding:10px;background:' + color + ';color:#fff;font-size:14px;cursor:pointer}' +
        '.bc-send:disabled{opacity:.55;cursor:not-allowed}' +
        '.bc-success-card{padding:24px 16px 20px;text-align:center;direction:rtl;border-top:1px solid #e5e5e5;background:#fff}' +
        '.bc-success-icon{width:56px;height:56px;border-radius:50%;background:#e6f9ed;color:#1ea85a;font-size:28px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 14px}' +
        '.bc-success-title{font-size:15px;font-weight:700;color:#1a1a1a;margin-bottom:6px}' +
        '.bc-success-sub{font-size:12px;color:#666;line-height:1.65;margin-bottom:18px}' +
        '.bc-send-another{border:1px solid #d9d9d9;border-radius:10px;padding:9px 22px;background:#fff;font-size:13px;color:#555;cursor:pointer;transition:border-color .15s,color .15s}' +
        '.bc-send-another:hover{border-color:' + color + ';color:' + color + '}' +
      '</style>' +

      '<button id="bc-btn" aria-label="پشتیبانی آنلاین">' +
        '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>' +
      '</button>' +

      '<div id="bc-panel" role="dialog" aria-label="چت پشتیبانی">' +
        '<div class="bc-head"><h3>💬 چت پشتیبانی</h3><button id="bc-close" aria-label="بستن">×</button></div>' +
        '<div id="bc-log" class="bc-body">' +
          '<div class="bc-row bc-bot"><div class="bc-avatar">🤖</div><div class="bc-bubble">' + welcome + '</div></div>' +
        '</div>' +
        '<form id="bc-form" class="bc-foot" novalidate>' +
          '<input class="bc-name" type="text" name="name" maxlength="100" placeholder="نام شما *" required autocomplete="name" />' +
          '<div class="bc-contact-row">' +
            '<input class="bc-contact" type="text" name="contact_id" maxlength="120" placeholder="شناسه بله شما *" required autocomplete="off" />' +
            '<select class="bc-select" name="contact_type">' +
              '<option value="bale">📱 بله</option>' +
              '<option value="telegram">✈️ تلگرام</option>' +
              '<option value="whatsapp">💬 واتساپ</option>' +
            '</select>' +
          '</div>' +
          '<input class="bc-email" type="email" name="email" maxlength="150" placeholder="ایمیل شما (اختیاری)" autocomplete="email" />' +
          '<input class="bc-phone" type="tel" name="phone" maxlength="30" placeholder="+1 432-222-1111" autocomplete="tel" />' +
          '<textarea class="bc-input" name="message" maxlength="2000" rows="3" placeholder="پیام خود را بنویسید... *" required></textarea>' +
          '<input type="hidden" name="' + token + '" value="1" />' +
          (cfg.captchaSiteKey ? '<div id="bc-captcha-wrap"></div>' : '') +
          '<label class="bc-file-label">📎 پیوست (اختیاری)<input class="bc-file" type="file" name="attachment" accept="image/*,application/pdf,.doc,.docx,.zip" /></label>' +
          '<button id="bc-send" class="bc-send" type="submit">ارسال پیام</button>' +
        '</form>' +
      '</div>'
    );
  }

  function addBubble(logEl, side, text, sentState) {
    var safe = esc(text)
      .replace(/https?:\/\/[^\s<>"]+/g, function (u) {
        return '<a href="' + u + '" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline">' + u + '</a>';
      })
      .replace(/\n/g, '<br>');
    var row = document.createElement('div');
    row.className = 'bc-row ' + (side === 'user' ? 'bc-user' : 'bc-bot') + (sentState === 'sent' ? ' bc-sent' : '');
    row.innerHTML = side === 'user'
      ? '<div class="bc-bubble' + (sentState === 'sending' ? ' bc-sending' : '') + '"><span class="bc-check">✓</span><span class="bc-text">' + safe + '</span></div><div class="bc-avatar">👤</div>'
      : '<div class="bc-avatar">🤖</div><div class="bc-bubble">' + safe + '</div>';
    logEl.appendChild(row);
    logEl.scrollTop = logEl.scrollHeight;
    return row;
  }

  function updateServiceUi() {
    var panel = document.getElementById('bc-panel');
    var select = document.querySelector('#bc-form select[name="contact_type"]');
    var svc = select ? select.value : activeService;

    if (panel) {
      panel.classList.remove('bc-bale', 'bc-telegram', 'bc-joomla');
      panel.classList.add(svc === 'bale' ? 'bc-bale' : (svc === 'telegram' ? 'bc-telegram' : 'bc-joomla'));
    }
  }

  function submitMessage(form, logEl, sendBtn) {
    var name = (form.elements.name || {}).value || '';
    var msg = (form.elements.message || {}).value || '';
    var contactId = (form.elements.contact_id || {}).value || '';
    var contactType = (form.elements.contact_type || {}).value || 'telegram';

    var email = (form.elements.email || {}).value || '';
    var phone = (form.elements.phone || {}).value || '';

    if (!name.trim() || !msg.trim()) {
      addBubble(logEl, 'bot', 'لطفاً نام و پیام را کامل وارد کنید.');
      return;
    }

    var contactCount = (contactId.trim() ? 1 : 0) + (email.trim() ? 1 : 0) + (phone.trim() ? 1 : 0);
    if (contactCount < 2) {
      addBubble(logEl, 'bot', 'لطفاً حداقل دو روش تماس از شناسه کاربری، ایمیل یا تلفن وارد کنید.');
      return;
    }

    var captchaToken = '';
    if (cfg.captchaSiteKey) {
      if (cfg.captchaProvider === 'turnstile' && window.turnstile) {
        captchaToken = window.turnstile.getResponse();
      } else if (cfg.captchaProvider === 'recaptcha' && window.grecaptcha) {
        captchaToken = window.grecaptcha.getResponse();
      }
      if (!captchaToken) {
        addBubble(logEl, 'bot', 'لطفاً تأیید امنیتی (کپچا) را تکمیل کنید.');
        return;
      }
    }

    var userRow = addBubble(logEl, 'user', msg.trim(), 'sending');

    sendBtn.disabled = true;
    sendBtn.textContent = 'در حال ارسال...';

    var fileInput = form.querySelector('input[name="attachment"]');

    var body = new FormData();
    body.append('name', name);
    body.append('contact_id', contactId);
    body.append('contact_type', contactType);
    body.append('message', msg);
    body.append('page_url', window.location.href);
    body.append('page_title', document.title);
    if (email.trim()) {
      body.append('email', email.trim());
    }
    if (phone.trim()) {
      body.append('phone', phone.trim());
    }
    if (fileInput && fileInput.files && fileInput.files[0]) {
      body.append('attachment', fileInput.files[0]);
    }

    var tokenField = cfg.token || '';
    if (tokenField) {
      body.append(tokenField, '1');
    }

    if (captchaToken) {
      var captchaField = cfg.captchaProvider === 'turnstile' ? 'cf-turnstile-response' : 'g-recaptcha-response';
      body.append(captchaField, captchaToken);
    }

    fetch(cfg.ajaxUrl || 'index.php?option=com_ajax&plugin=bale_chat&group=system&format=json', {
      method: 'POST',
      body: body,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var res = (data && data.data && data.data[0]) ? data.data[0] : {};

        if (res.provider === 'bale' || res.provider === 'telegram' || res.provider === 'joomla') {
          activeService = res.provider;
          updateServiceUi();
        }

        if (res.success) {
          if (userRow) {
            userRow.className = 'bc-row bc-user bc-sent';
            var bubble = userRow.querySelector('.bc-bubble');
            if (bubble) bubble.classList.remove('bc-sending');
          }
          form.elements.message.value = '';

          var siteName = cfg.siteName || '';
          var normalizedCid = contactId.trim().replace(/^@/, '');
          var handlerUrl = '';
          if (normalizedCid) {
            if (contactType === 'bale') {
              handlerUrl = 'https://ble.ir/' + normalizedCid;
            } else if (contactType === 'telegram' && !normalizedCid.includes('+')) {
              handlerUrl = 'https://t.me/' + normalizedCid;
            } else if (contactType === 'whatsapp') {
              handlerUrl = 'https://wa.me/' + normalizedCid.replace(/\D/g, '');
            }
          }
          var typeLabel = {bale: '📱 بله', telegram: '✈️ تلگرام', whatsapp: '💬 واتساپ'}[contactType] || '📱';
          var msgLines = [];
          if (siteName) { msgLines.push(siteName); }
          msgLines.push('به زودی با شما تماس خواهد گرفت:');
          if (contactId.trim()) {
            msgLines.push(typeLabel + ': ' + contactId.trim());
            if (handlerUrl) { msgLines.push(handlerUrl); }
          }
          if (email.trim()) { msgLines.push('✉️ ' + email.trim()); }
          if (phone.trim()) { msgLines.push('📞 ' + phone.trim()); }
          var confirmMsg = msgLines.join('\n');
          addBubble(logEl, 'bot', confirmMsg);

          // Replace form with a success card
          form.style.display = 'none';
          var successCard = document.createElement('div');
          successCard.className = 'bc-success-card';
          successCard.innerHTML =
            '<div class="bc-success-icon">✓</div>' +
            '<div class="bc-success-title">پیام شما ارسال شد!</div>' +
            '<div class="bc-success-sub">به زودی با شما تماس خواهیم گرفت.</div>' +
            '<button class="bc-send-another" type="button">ارسال پیام دیگری</button>';
          form.parentNode.insertBefore(successCard, form.nextSibling);
          successCard.querySelector('.bc-send-another').addEventListener('click', function () {
            successCard.remove();
            form.reset();
            form.style.display = '';
          });
        } else {
          if (userRow) {
            userRow.className = 'bc-row bc-user';
            var bubbleFail = userRow.querySelector('.bc-bubble');
            if (bubbleFail) bubbleFail.classList.remove('bc-sending');
          }
          addBubble(logEl, 'bot', res.message || 'ارسال پیام ناموفق بود.');
        }
      })
      .catch(function () {
        addBubble(logEl, 'bot', 'خطای شبکه. دوباره تلاش کنید.');
      })
      .finally(function () {
        sendBtn.disabled = false;
        sendBtn.textContent = 'ارسال پیام';
        if (cfg.captchaSiteKey) {
          if (cfg.captchaProvider === 'turnstile' && window.turnstile) {
            window.turnstile.reset();
          } else if (cfg.captchaProvider === 'recaptcha' && window.grecaptcha) {
            window.grecaptcha.reset();
          }
        }
      });
  }

  function init() {
    var container = document.createElement('div');
    container.id = 'bale-chat-widget';
    container.innerHTML = buildHTML();
    document.body.appendChild(container);

    // Load captcha widget based on configured provider
    if (cfg.captchaSiteKey && cfg.captchaProvider !== 'none') {
      if (cfg.captchaProvider === 'recaptcha' && !document.getElementById('bc-recaptcha-script')) {
        var rcScript = document.createElement('script');
        rcScript.id = 'bc-recaptcha-script';
        rcScript.src = 'https://www.google.com/recaptcha/api.js?render=explicit&hl=fa';
        rcScript.async = true;
        rcScript.defer = true;
        rcScript.onload = function () {
          window.grecaptcha.ready(function () {
            var wrap = document.getElementById('bc-captcha-wrap');
            if (wrap && !wrap.hasAttribute('data-rc')) {
              window.grecaptcha.render(wrap, { sitekey: cfg.captchaSiteKey });
              wrap.setAttribute('data-rc', '1');
            }
          });
        };
        document.head.appendChild(rcScript);
      } else if (cfg.captchaProvider === 'turnstile' && !document.getElementById('bc-turnstile-script')) {
        window.bcTurnstileReady = function () {
          var wrap = document.getElementById('bc-captcha-wrap');
          if (wrap && !wrap.hasAttribute('data-rc')) {
            window.turnstile.render(wrap, { sitekey: cfg.captchaSiteKey });
            wrap.setAttribute('data-rc', '1');
          }
        };
        var tsScript = document.createElement('script');
        tsScript.id = 'bc-turnstile-script';
        tsScript.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=bcTurnstileReady';
        tsScript.async = true;
        tsScript.defer = true;
        document.head.appendChild(tsScript);
      }
    }

    var btn = document.getElementById('bc-btn');
    var panel = document.getElementById('bc-panel');
    var close = document.getElementById('bc-close');
    var form = document.getElementById('bc-form');
    var logEl = document.getElementById('bc-log');
    var sendBtn = document.getElementById('bc-send');

    detectActiveService(function () {
      updateServiceUi();
    });

    var serviceSelect = form ? form.querySelector('select[name="contact_type"]') : null;
    if (serviceSelect) {
      serviceSelect.addEventListener('change', function () {
        updateServiceUi();
        var contactInput = form.querySelector('input[name="contact_id"]');
        if (contactInput) {
          var phMap = {bale: 'شناسه بله شما *', telegram: 'شناسه تلگرام شما *', whatsapp: 'شماره واتساپ شما *'};
          contactInput.placeholder = phMap[serviceSelect.value] || 'شناسه تماس شما *';
        }
      });
    }

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

    // Allow any page link with href="#bale-chat" to open the widget
    document.addEventListener('click', function (e) {
      var a = e.target.closest ? e.target.closest('a[href="#bale-chat"]') : null;
      if (a) {
        e.preventDefault();
        e.stopPropagation();
        widgetOpen = true;
        panel.className = 'bc-panel bc-open';
        updateServiceUi();
      }
    }, true); // capture phase — runs before outside-click-close handler

    // Open widget when page loads or navigates with #bale-chat hash
    function checkHash() {
      if (window.location.hash === '#bale-chat') {
        history.replaceState(null, '', window.location.pathname + window.location.search);
        widgetOpen = true;
        panel.className = 'bc-panel bc-open';
        updateServiceUi();
      }
    }
    checkHash();
    window.addEventListener('hashchange', checkHash);

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
