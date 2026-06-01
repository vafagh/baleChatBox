<?php

declare(strict_types=1);

namespace Joomla\Plugin\System\BaleChat;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

defined('_JEXEC') || die;

/**
 * Bale Chat system plugin – injects a floating support-chat widget that uses
 * Bale Messenger as primary channel and Telegram as automatic fallback.
 *
 * AUTO-UPDATE MECHANISM
 * ---------------------
 * This plugin registers an update server in bale_chat.xml pointing to:
 *   https://raw.githubusercontent.com/vafagh/baleChatBox/master/updates.xml
 *
 * Every time build-plugin.py runs it rewrites updates.xml with the new
 * version number and the matching GitHub release download URL.  After the
 * built ZIP is pushed to GitHub, Joomla admins will see an update badge
 * under Extensions › Manage › Update and can one-click upgrade without
 * leaving the admin panel.
 *
 * IMPORTANT — when bumping the version:
 *   1. Update <version> in bale_chat.xml
 *   2. Run build-plugin.py  (auto-rewrites updates.xml)
 *   3. Copy updates.xml + changed plugin files to baleChatBox/plugin/
 *   4. git commit -m "feat: ..." && git push origin master
 *      → GitHub Actions publishes the release asset automatically
 *      → Joomla sites pick up the new version on next update check
 */
final class BaleChat extends CMSPlugin implements SubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeCompileHead' => 'injectWidget',
            'onAjaxBale_chat'     => 'handleAjax',
        ];
    }

    // -------------------------------------------------------------------------
    // Widget and admin page injection
    // -------------------------------------------------------------------------

    public function injectWidget(Event $event): void
    {
        $app = $this->getApplication();
        $document = $app->getDocument();

        if ($document->getType() !== 'html') {
            return;
        }

        if ($app->isClient('site')) {
            $this->injectFrontendWidget();
            return;
        }

        if ($app->isClient('administrator')) {
            $this->injectAdminBotTester();
        }
    }

    /**
     * Inject frontend widget configuration and script into site pages.
     */
    private function injectFrontendWidget(): void
    {
        $app      = $this->getApplication();
        $document = $app->getDocument();
        $params   = $this->params;

        /** @noinspection PhpUnhandledExceptionInspection */
        $config = json_encode(
            [
                'primaryService'  => $params->get('primary_service', 'bale'),
                'baleUsername'    => $this->sanitizeUsername((string) $params->get('bale_bot_username', '')),
                'tgUsername'      => $this->sanitizeUsername((string) $params->get('telegram_bot_username', '')),
                'baleConfigured'  => (string) $params->get('bale_bot_token', '') !== '' && (string) $params->get('bale_chat_id', '') !== '',
                'telegramConfigured' => (string) $params->get('telegram_bot_token', '') !== '' && (string) $params->get('telegram_chat_id', '') !== '',
                'buttonColor'     => $this->sanitizeColor((string) $params->get('button_color', '#0088cc')),
                'welcomeMessage'  => (string) $params->get('welcome_message', 'سلام! چطور می‌توانم کمک کنم؟'),
                'position'        => in_array($params->get('widget_position', 'bottom-right'), ['bottom-right', 'bottom-left'], true)
                    ? $params->get('widget_position', 'bottom-right')
                    : 'bottom-right',
                'fallbackTimeout' => max(1000, min(10000, (int) $params->get('fallback_timeout', 4000))),
                // Keep endpoint relative to avoid cross-origin/CORS issues.
                'ajaxUrl'         => Uri::root(true) . '/index.php?option=com_ajax&plugin=bale_chat&group=system&format=json',
                'token'           => Session::getFormToken(),
                'siteName'        => (string) $app->get('sitename', ''),
                'captchaProvider'  => (string) $params->get('captcha_provider', 'none'),
                'captchaSiteKey'   => $this->getCaptchaSiteKey((string) $params->get('captcha_provider', 'none')),
            ],
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE,
        );

        $wa = $document->getWebAssetManager();
        $wa->addInlineScript('window.BaleChatConfig = ' . $config . ';');

        $scriptPath = dirname(__DIR__) . '/media/js/bale_chat.js';

        if (is_file($scriptPath)) {
            $widgetScript = file_get_contents($scriptPath);
            $wa->addInlineScript($widgetScript ?: '');
        }
    }

    /**
     * Inject admin-only bot testing panel on this plugin edit page.
     */
    private function injectAdminBotTester(): void
    {
        if (!$this->isBalePluginEditPage()) {
            return;
        }

        $document = $this->getApplication()->getDocument();
        $wa = $document->getWebAssetManager();
        $formToken = Session::getFormToken();
        $frontendAjaxUrl = Uri::root() . 'index.php?option=com_ajax&plugin=bale_chat&group=system&format=json';

        $script = <<<JS
(function () {
  'use strict';

  function byName(name) {
    return document.querySelector('[name="' + name + '"]');
  }

  function ensurePanel() {
    var existing = document.getElementById('bc-admin-tester');
    if (existing) {
      return existing;
    }

    var anchor = document.querySelector('#jform_params') || document.querySelector('form') || document.body;
    var wrap = document.createElement('div');
    wrap.id = 'bc-admin-tester';
    wrap.style.marginTop = '16px';
    wrap.style.padding = '14px';
    wrap.style.border = '1px solid #d0d7de';
    wrap.style.borderRadius = '8px';
    wrap.style.background = '#f8fafc';
    wrap.style.direction = 'ltr';
    wrap.style.textAlign = 'left';
    wrap.innerHTML = '' +
      '<h3 style="margin:0 0 8px 0;font-size:15px;direction:ltr;text-align:left;">Bale/Telegram Bot Test</h3>' +
      '<p style="margin:0 0 10px 0;color:#333;">Runs real API probe + send for both providers using current saved values. Full credentials and raw API responses are shown below.</p>' +
      '<label for="bc-admin-test-message" style="display:block;margin-bottom:6px;font-weight:600;">Test message</label>' +
      '<textarea id="bc-admin-test-message" rows="2" style="width:100%;max-width:820px;direction:ltr;text-align:left;">Admin diagnostic test ' + Date.now() + '</textarea>' +
      '<div style="margin-top:10px;display:flex;gap:8px;align-items:center;">' +
        '<button id="bc-admin-run-test" type="button" class="btn btn-primary">Run Bot Test</button>' +
        '<span id="bc-admin-test-status" style="font-size:12px;color:#555;"></span>' +
      '</div>' +
      '<pre id="bc-admin-test-log" style="margin-top:12px;max-height:480px;overflow:auto;background:#0f172a;color:#e2e8f0;padding:10px;border-radius:6px;white-space:pre-wrap;direction:ltr;text-align:left;"></pre>' +
      '<hr style="margin:14px 0;border:none;border-top:1px solid #d0d7de"/>' +
      '<h3 style="margin:0 0 6px 0;font-size:15px;direction:ltr;text-align:left;">Visitor Form Simulation</h3>' +
      '<p style="margin:0 0 10px 0;color:#555;font-size:13px;direction:ltr;text-align:left;">Same form fields &amp; AJAX endpoint as visitor widget &mdash; reproduces visitor-side errors.</p>' +
      '<div style="display:flex;gap:6px;margin-bottom:6px">' +
        '<input id="bc-vsim-name" type="text" style="flex:1;padding:7px 9px;border:1px solid #ccc;border-radius:6px;font-size:13px" placeholder="Name *" value="Visitor Test" />' +
      '</div>' +
      '<div style="display:flex;gap:6px;margin-bottom:6px">' +
        '<input id="bc-vsim-contact-id" type="text" style="flex:1;padding:7px 9px;border:1px solid #ccc;border-radius:6px;font-size:13px" placeholder="contact_id username (optional)" />' +
        '<select id="bc-vsim-contact-type" style="padding:7px 9px;border:1px solid #ccc;border-radius:6px;font-size:13px;background:#fff">' +
          '<option value="telegram">Telegram</option>' +
          '<option value="bale">Bale</option>' +
        '</select>' +
      '</div>' +
      '<div style="display:flex;gap:6px;margin-bottom:6px">' +
        '<input id="bc-vsim-email" type="text" style="flex:1;padding:7px 9px;border:1px solid #ccc;border-radius:6px;font-size:13px" placeholder="email@example.com (optional)" />' +
        '<input id="bc-vsim-phone" type="text" style="flex:1;padding:7px 9px;border:1px solid #ccc;border-radius:6px;font-size:13px" placeholder="+1 234 567 8900 (optional)" />' +
      '</div>' +
      '<textarea id="bc-vsim-message" rows="2" style="width:100%;max-width:820px;margin-bottom:6px;padding:7px 9px;border:1px solid #ccc;border-radius:6px;font-size:13px;box-sizing:border-box">Test visitor form message</textarea>' +
      '<button id="bc-admin-run-visitor" type="button" class="btn btn-warning">Test Visitor Form</button>' +
      '<pre id="bc-admin-visitor-log" style="margin-top:12px;max-height:320px;overflow:auto;background:#0f172a;color:#e2e8f0;padding:10px;border-radius:6px;white-space:pre-wrap;direction:ltr;text-align:left;font-size:12px"></pre>';

    if (anchor.parentNode) {
      anchor.parentNode.insertBefore(wrap, anchor.nextSibling);
    } else {
      document.body.appendChild(wrap);
    }

    return wrap;
  }

  function getValue(name) {
    var el = byName(name);
    return el ? String(el.value || '').trim() : '';
  }

  function onReady() {
    var panel = ensurePanel();
    var runBtn = panel.querySelector('#bc-admin-run-test');
    var status = panel.querySelector('#bc-admin-test-status');
    var log = panel.querySelector('#bc-admin-test-log');
    var msgField = panel.querySelector('#bc-admin-test-message');

    if (!runBtn || !status || !log || !msgField) {
      return;
    }

    runBtn.addEventListener('click', function () {
      var baleToken = getValue('jform[params][bale_bot_token]');
      var baleChatId = getValue('jform[params][bale_chat_id]');
      var tgToken = getValue('jform[params][telegram_bot_token]');
      var tgChatId = getValue('jform[params][telegram_chat_id]');
      var testMessage = msgField.value || ('Admin diagnostic test ' + Date.now());

      var body = new FormData();
      body.append('action', 'admin_test_bots');
      body.append('bale_bot_token', baleToken);
      body.append('bale_chat_id', baleChatId);
      body.append('telegram_bot_token', tgToken);
      body.append('telegram_chat_id', tgChatId);
      body.append('test_message', testMessage);
      body.append('{$formToken}', '1');

      status.textContent = 'Running...';
      runBtn.disabled = true;
      log.textContent = JSON.stringify({
        startedAt: new Date().toISOString(),
        request: {
          baleToken: baleToken,
          baleChatId: baleChatId,
          telegramToken: tgToken,
          telegramChatId: tgChatId,
          configured: {
            bale: !!(baleToken && baleChatId),
            telegram: !!(tgToken && tgChatId)
          }
        }
      }, null, 2);

      fetch('index.php?option=com_ajax&plugin=bale_chat&group=system&format=json&action=admin_test_bots', {
        method: 'POST',
        body: body,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          var res = (data && data.data && data.data[0]) ? data.data[0] : {};
          status.textContent = res.success ? 'Done (OK)' : 'Done (with errors)';
          log.textContent = JSON.stringify(res, null, 2);
        })
        .catch(function (err) {
          status.textContent = 'Failed';
          log.textContent = JSON.stringify({ error: err && err.message ? err.message : 'network_error' }, null, 2);
        })
        .finally(function () {
          runBtn.disabled = false;
        });
    });

    var visitorBtn = panel.querySelector('#bc-admin-run-visitor');
    var visitorLog = panel.querySelector('#bc-admin-visitor-log');
    if (visitorBtn && visitorLog) {
      visitorBtn.addEventListener('click', function () {
        var vName = (document.getElementById('bc-vsim-name') || {}).value || 'Visitor Test';
        var vContactId = (document.getElementById('bc-vsim-contact-id') || {}).value || '';
        var vContactType = (document.getElementById('bc-vsim-contact-type') || {}).value || 'telegram';
        var vEmail = (document.getElementById('bc-vsim-email') || {}).value || '';
        var vPhone = (document.getElementById('bc-vsim-phone') || {}).value || '';
        var vMsg = (document.getElementById('bc-vsim-message') || {}).value || 'Test';

        var fd = new FormData();
        fd.append('name', vName);
        fd.append('contact_id', vContactId);
        fd.append('contact_type', vContactType);
        fd.append('email', vEmail);
        fd.append('phone', vPhone);
        fd.append('message', vMsg);
        fd.append('page_url', window.location.href);
        fd.append('page_title', document.title);
        fd.append('{$formToken}', '1');

        visitorBtn.disabled = true;
        visitorLog.textContent = 'Sending to: {$frontendAjaxUrl}\n\nPlease wait...';

        fetch('{$frontendAjaxUrl}', {
          method: 'POST',
          body: fd,
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function (r) {
            var httpStatus = r.status;
            var respUrl = r.url;
            return r.text().then(function (raw) {
              var out = '=== HTTP ' + httpStatus + ' \u2014 ' + respUrl + ' ===\n\n' + raw;
              try {
                var parsed = JSON.parse(raw);
                out += '\n\n=== PARSED JSON ===\n' + JSON.stringify(parsed, null, 2);
              } catch (e) {
                out += '\n\n=== JSON PARSE FAILED: ' + e.message + ' ===';
              }
              visitorLog.textContent = out;
            });
          })
          .catch(function (err) {
            visitorLog.textContent = 'NETWORK ERROR (fetch rejected): ' + (err && err.message ? err.message : String(err));
          })
          .finally(function () {
            visitorBtn.disabled = false;
          });
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }
}());
JS;

        $wa->addInlineScript($script);
    }

    private function isBalePluginEditPage(): bool
    {
        $app = $this->getApplication();
        $input = $app->getInput();

        if ($input->getCmd('option') !== 'com_plugins') {
            return false;
        }

        $view = $input->getCmd('view', '');
        $task = $input->getCmd('task', '');
        $isPluginEditPage = ($view === 'plugin')
            || ($task === 'plugin.edit')
            || ($task === 'plugin.apply')
            || ($task === 'plugin.save');

        if (!$isPluginEditPage) {
            return false;
        }

        $extensionId = (int) $input->getInt('extension_id', $input->getInt('id', 0));

        if ($extensionId <= 0) {
            return false;
        }

        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName(['type', 'folder', 'element']))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('extension_id') . ' = ' . $extensionId)
            ->setLimit(1);

        $db->setQuery($query);
        $row = $db->loadAssoc();

        if (!is_array($row)) {
            return false;
        }

        return ($row['type'] ?? '') === 'plugin'
            && ($row['folder'] ?? '') === 'system'
            && ($row['element'] ?? '') === 'bale_chat';
    }

    // -------------------------------------------------------------------------
    // AJAX handler (web contact form + admin diagnostics)
    // -------------------------------------------------------------------------

    public function handleAjax(Event $event): void
    {
        $input = $this->getApplication()->getInput();

        if (strtolower((string) $input->getCmd('action', '')) === 'admin_test_bots') {
            $this->handleAdminTestAjax($event);
            return;
        }

        $this->handleFrontendMessageAjax($event);
    }

    /**
     * Receives the contact-form POST from the widget and forwards it to the
     * configured Bale and/or Telegram bots.
     */
    private function handleFrontendMessageAjax(Event $event): void
    {
        $app   = $this->getApplication();
        $input = $app->getInput();

        $formToken = Session::getFormToken();

        if ($input->post->get($formToken, 0, 'int') !== 1) {
            $this->ajaxReturn($event, false, 'Security check failed.', ['provider' => 'none']);
            return;
        }

        // Verify captcha if a provider is configured
        $captchaProvider = (string) $this->params->get('captcha_provider', 'none');
        if ($captchaProvider !== 'none') {
            $captchaSecret = $this->getCaptchaSecretKey($captchaProvider);
            $captchaField  = $captchaProvider === 'turnstile' ? 'cf-turnstile-response' : 'g-recaptcha-response';
            $captchaToken  = trim((string) $input->post->get($captchaField, '', 'string'));
            if ($captchaToken === '' || !$this->verifyCaptcha($captchaProvider, $captchaSecret, $captchaToken)) {
                $this->ajaxReturn($event, false, 'تأیید امنیتی ناموفق بود. لطفاً کپچا را تکمیل کنید.', ['provider' => 'none']);
                return;
            }
        }

        $name = mb_substr(trim(strip_tags($input->post->get('name', '', 'string'))), 0, 100);
        $contactType = strtolower(trim((string) $input->post->get('contact_type', 'telegram', 'string')));
        $contactId = mb_substr(trim(strip_tags((string) $input->post->get('contact_id', '', 'string'))), 0, 120);
        $message = mb_substr(trim(strip_tags($input->post->get('message', '', 'string'))), 0, 2000);

        $email     = mb_substr(trim(strip_tags($input->post->get('email', '', 'string'))), 0, 150);
        $phone     = mb_substr(trim(strip_tags($input->post->get('phone', '', 'string'))), 0, 30);
        $pageUrl   = mb_substr(trim($input->post->get('page_url', '', 'string')), 0, 500);
        $pageTitle = mb_substr(trim(strip_tags($input->post->get('page_title', '', 'string'))), 0, 200);

        if ($name === '' || $message === '') {
            $this->ajaxReturn($event, false, 'نام و پیام الزامی است.', ['provider' => 'none']);
            return;
        }

        $contactPointCount = ($contactId !== '' ? 1 : 0) + ($email !== '' ? 1 : 0) + ($phone !== '' ? 1 : 0);
        if ($contactPointCount < 2) {
            $this->ajaxReturn($event, false, 'حداقل دو روش تماس از میان شناسه کاربری، ایمیل یا تلفن لازم است.', ['provider' => 'none']);
            return;
        }

        $baleToken = (string) $this->params->get('bale_bot_token', '');
        $baleChatId = (string) $this->params->get('bale_chat_id', '');
        $tgToken = (string) $this->params->get('telegram_bot_token', '');
        $tgChatId = (string) $this->params->get('telegram_chat_id', '');

        $baleConfigured = $baleToken !== '' && $baleChatId !== '';
        $tgConfigured = $tgToken !== '' && $tgChatId !== '';

        $serviceLabel = match ($contactType) {
            'bale'      => 'بله',
            'telegram'  => 'تلگرام',
            'whatsapp'  => 'واتساپ',
            default     => 'تماس',
        };

        $contactLink  = '';
        $normalizedId = ltrim($contactId, '@');

        if ($contactType === 'bale' && $normalizedId !== '' && !str_contains($contactId, '@')) {
            $contactLink = ' (https://ble.ir/' . $normalizedId . ')';
        }

        if ($contactType === 'telegram' && $normalizedId !== '' && !str_contains($contactId, '@')) {
            $contactLink = ' (https://t.me/' . $normalizedId . ')';
        }

        if ($contactType === 'whatsapp' && $normalizedId !== '') {
            $waNumber = preg_replace('/\D/', '', $normalizedId);
            if ($waNumber !== '') {
                $contactLink = ' (https://wa.me/' . $waNumber . ')';
            }
        }

        $siteName = (string) $this->getApplication()->get('sitename', '');

        $text = "🌐 پیام از وب‌سایت " . $siteName . "\n"
            . ($pageTitle !== '' ? "📄 " . $pageTitle . "\n" : '')
            . ($pageUrl !== '' ? "🔗 " . $pageUrl . "\n" : '')
            . "👤 نام: " . $name . "\n"
            . ($contactId !== '' ? "🆔 " . $serviceLabel . " ID: " . $contactId . $contactLink . "\n" : '')
            . ($email !== '' ? "📧 Email: " . $email . "\n" : '')
            . ($phone !== '' ? "📞 Phone: " . $phone . "\n" : '')
            . "💬 پیام:\n" . $message;

        $providerQueue = [];

        $baleProbe = $baleConfigured ? $this->probeProviderOnline('bale', $baleToken) : ['online' => false, 'httpCode' => 0, 'apiOk' => false, 'error' => 'not_configured'];
        if ($baleConfigured && ($baleProbe['online'] ?? false)) {
            $providerQueue[] = ['type' => 'bale', 'token' => $baleToken, 'chatId' => $baleChatId];
        }

        $tgProbe = $tgConfigured ? $this->probeProviderOnline('telegram', $tgToken) : ['online' => false, 'httpCode' => 0, 'apiOk' => false, 'error' => 'not_configured'];
        if ($tgConfigured && ($tgProbe['online'] ?? false)) {
            $providerQueue[] = ['type' => 'telegram', 'token' => $tgToken, 'chatId' => $tgChatId];
        }

        $errors = [];

        $hasAttachment = isset($_FILES['attachment'])
            && is_array($_FILES['attachment'])
            && (int) ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
            && (int) ($_FILES['attachment']['size'] ?? 0) > 0
            && (int) ($_FILES['attachment']['size'] ?? 0) <= 10 * 1024 * 1024;

        foreach ($providerQueue as $provider) {
            $result = $this->sendToProvider($provider['type'], $provider['token'], $provider['chatId'], $text);

            if ($result['ok'] === true) {
                if ($hasAttachment) {
                    $this->sendDocumentToProvider(
                        $provider['type'],
                        $provider['token'],
                        $provider['chatId'],
                        (string) ($_FILES['attachment']['tmp_name'] ?? ''),
                        (string) ($_FILES['attachment']['name'] ?? 'attachment')
                    );
                }
                $this->ajaxReturn($event, true, 'ارسال پیام انجام شد.', ['provider' => $provider['type']]);
                return;
            }

            $errors[] = $result['error'];
        }

        if ($this->sendViaJoomlaContactFallback($name, $contactId, $email, $message, Uri::current())) {
            $this->ajaxReturn(
                $event,
                true,
                'درخواست شما ثبت شد. همکاران ما به‌زودی از طریق شناسه تماس اعلام‌شده با شما ارتباط می‌گیرند.',
                ['provider' => 'joomla']
            );
            return;
        }

        $detail = count($errors) > 0 ? (' (' . implode(' | ', $errors) . ')') : '';
        $this->ajaxReturn($event, false, 'ارسال پیام ناموفق بود. لطفاً دوباره تلاش کنید.' . $detail, ['provider' => 'none']);
    }

    /**
     * Admin-only diagnostics endpoint: probes and sends test message to both providers.
     */
    private function handleAdminTestAjax(Event $event): void
    {
        $app = $this->getApplication();
        $input = $app->getInput();

        if (!$app->isClient('administrator') || !$app->getIdentity()->authorise('core.manage', 'com_plugins')) {
            $this->ajaxReturn($event, false, 'Access denied.', ['provider' => 'admin-test']);
            return;
        }

        $formToken = Session::getFormToken();

        if ($input->post->get($formToken, 0, 'int') !== 1) {
            $this->ajaxReturn($event, false, 'Security check failed.', ['provider' => 'admin-test']);
            return;
        }

        $baleToken = trim((string) $input->post->get('bale_bot_token', (string) $this->params->get('bale_bot_token', ''), 'raw'));
        $baleChatId = trim((string) $input->post->get('bale_chat_id', (string) $this->params->get('bale_chat_id', ''), 'raw'));
        $tgToken = trim((string) $input->post->get('telegram_bot_token', (string) $this->params->get('telegram_bot_token', ''), 'raw'));
        $tgChatId = trim((string) $input->post->get('telegram_chat_id', (string) $this->params->get('telegram_chat_id', ''), 'raw'));
        $testMessage = mb_substr(trim((string) $input->post->get('test_message', 'Admin diagnostic test', 'raw')), 0, 1500);

        if ($testMessage === '') {
            $testMessage = 'Admin diagnostic test';
        }

        $details = [
            'timestamp' => gmdate('c'),
            'request' => [
                'baleToken' => $baleToken,
                'baleChatId' => $baleChatId,
                'telegramToken' => $tgToken,
                'telegramChatId' => $tgChatId,
            ],
            'configured' => [
                'bale' => $baleToken !== '' && $baleChatId !== '',
                'telegram' => $tgToken !== '' && $tgChatId !== '',
            ],
            'providers' => [],
        ];

        $baleInfo = $this->runSingleProviderDiagnostic('bale', $baleToken, $baleChatId, $testMessage);
        $tgInfo = $this->runSingleProviderDiagnostic('telegram', $tgToken, $tgChatId, $testMessage);

        $details['providers']['bale'] = $baleInfo;
        $details['providers']['telegram'] = $tgInfo;

        $sentAny = (($baleInfo['send']['ok'] ?? false) === true) || (($tgInfo['send']['ok'] ?? false) === true);
        $details['summary'] = [
            'sentAny' => $sentAny,
            'baleSent' => (bool) ($baleInfo['send']['ok'] ?? false),
            'telegramSent' => (bool) ($tgInfo['send']['ok'] ?? false),
        ];

        $message = $sentAny
            ? 'Bot test finished. At least one provider delivered.'
            : 'Bot test finished. No provider delivered.';

        $this->ajaxReturn($event, $sentAny, $message, [
            'provider' => 'admin-test',
            'details' => $details,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function runSingleProviderDiagnostic(string $provider, string $token, string $chatId, string $testMessage): array
    {
        $configured = $token !== '' && $chatId !== '';
        $result = [
            'configured' => $configured,
            'probe' => [
                'url' => '',
                'online' => false,
                'httpCode' => 0,
                'apiOk' => false,
                'error' => $configured ? '' : 'not_configured',
                'rawBody' => '',
            ],
            'send' => [
                'ok' => false,
                'code' => 0,
                'description' => $configured ? '' : 'not_configured',
                'error' => $configured ? '' : ucfirst($provider) . ': not configured',
                'rawBody' => '',
            ],
        ];

        if (!$configured) {
            return $result;
        }

        $probe = $this->probeProviderOnline($provider, $token);
        $result['probe'] = $probe;

        $text = "🔎 Admin bot test\n"
            . "Provider: " . $provider . "\n"
            . "Time (UTC): " . gmdate('Y-m-d H:i:s') . "\n"
            . "Message: " . $testMessage;

        // Always attempt send in admin diagnostics regardless of probe result
        $send = $this->sendToProvider($provider, $token, $chatId, $text);
        $result['send'] = [
            'ok' => (bool) ($send['ok'] ?? false),
            'code' => (int) ($send['code'] ?? 0),
            'description' => (string) ($send['description'] ?? ''),
            'error' => (string) ($send['error'] ?? ''),
            'rawBody' => (string) ($send['rawBody'] ?? ''),
        ];

        return $result;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function ajaxReturn(Event $event, bool $success, string $message, array $extra = []): void
    {
        $payload = array_merge(['success' => $success, 'message' => $message], $extra);
        $event->setArgument('result', [$payload]);
    }

    /** Strips everything except alphanumerics and underscores from a username. */
    private function sanitizeUsername(string $value): string
    {
        return preg_replace('/[^a-zA-Z0-9_]/', '', $value) ?? '';
    }

    /** Validates a CSS hex-colour; returns the fallback if invalid. */
    private function sanitizeColor(string $value): string
    {
        return preg_match('/^#[0-9A-Fa-f]{3}(?:[0-9A-Fa-f]{3})?$/', $value) ? $value : '#0088cc';
    }

    /**
     * @return array{url: string, online: bool, httpCode: int, apiOk: bool, error: string, rawBody: string}
     */
    private function probeProviderOnline(string $provider, string $token): array
    {
        if ($token === '') {
            return ['online' => false, 'httpCode' => 0, 'apiOk' => false, 'error' => 'token_empty'];
        }

        try {
            $http = HttpFactory::getHttp();
            $url = $provider === 'bale'
                ? sprintf('https://tapi.bale.ai/bot%s/getMe', $token)
                : sprintf('https://api.telegram.org/bot%s/getMe', $token);
            $response = $http->get($url);
            $rawBody = (string) $response->body;
            $payload = json_decode($rawBody, true);

            $apiOk = is_array($payload)
                && (!array_key_exists('ok', $payload) || (bool) ($payload['ok'] ?? false));
            $desc = is_array($payload) && isset($payload['description'])
                ? (string) $payload['description']
                : '';

            return [
                'url' => $url,
                'online' => $response->code === 200 && $apiOk,
                'httpCode' => (int) $response->code,
                'apiOk' => $apiOk,
                'error' => $desc,
                'rawBody' => $rawBody,
            ];
        } catch (\Throwable $e) {
            return ['url' => '', 'online' => false, 'httpCode' => 0, 'apiOk' => false, 'error' => $e->getMessage(), 'rawBody' => ''];
        }
    }

    /**
     * @return array{ok: bool, error: string, code: int, description: string}
     */
    private function sendToProvider(string $provider, string $token, string $chatId, string $text): array
    {
        try {
            $http = HttpFactory::getHttp();
            $url = $provider === 'bale'
                ? sprintf('https://tapi.bale.ai/bot%s/sendMessage', $token)
                : sprintf('https://api.telegram.org/bot%s/sendMessage', $token);

            $response = $http->post(
                $url,
                [
                    'chat_id'                  => $chatId,
                    'text'                     => $text,
                    'disable_web_page_preview' => 'true',
                ],
            );

            $rawBody = (string) $response->body;
            $payload = json_decode($rawBody, true);
            $ok = $response->code === 200 && (!is_array($payload) || !array_key_exists('ok', $payload) || (bool) $payload['ok'] === true);

            if ($ok) {
                return ['ok' => true, 'error' => '', 'code' => (int) $response->code, 'description' => '', 'rawBody' => $rawBody];
            }

            $desc = is_array($payload) && isset($payload['description']) ? (string) $payload['description'] : '';

            return [
                'ok' => false,
                'error' => ucfirst($provider) . ': ' . $response->code . ($desc !== '' ? (' - ' . $desc) : ''),
                'code' => (int) $response->code,
                'description' => $desc,
                'rawBody' => $rawBody,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'error' => ucfirst($provider) . ': ' . $e->getMessage(),
                'code' => 0,
                'description' => 'request_exception',
                'rawBody' => '',
            ];
        }
    }

    private function sendDocumentToProvider(string $provider, string $token, string $chatId, string $tmpPath, string $fileName): void
    {
        if (!function_exists('curl_init') || !is_readable($tmpPath)) {
            return;
        }

        try {
            $url = $provider === 'bale'
                ? sprintf('https://tapi.bale.ai/bot%s/sendDocument', $token)
                : sprintf('https://api.telegram.org/bot%s/sendDocument', $token);

            $mime = function_exists('mime_content_type') ? (mime_content_type($tmpPath) ?: 'application/octet-stream') : 'application/octet-stream';

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => [
                    'chat_id'  => $chatId,
                    'document' => new \CURLFile($tmpPath, $mime, $fileName),
                ],
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable) {
            // Best-effort: silently ignore errors
        }
    }

    private function getCaptchaSiteKey(string $provider): string
    {
        if ($provider === 'none') {
            return '';
        }
        $plugin = PluginHelper::getPlugin('captcha', $provider);
        if (!$plugin) {
            return '';
        }
        $params = new Registry($plugin->params);
        // reCaptcha uses 'public_key'; Turnstile uses 'siteKey'
        return (string) $params->get($provider === 'recaptcha' ? 'public_key' : 'siteKey', '');
    }

    private function getCaptchaSecretKey(string $provider): string
    {
        if ($provider === 'none') {
            return '';
        }
        $plugin = PluginHelper::getPlugin('captcha', $provider);
        if (!$plugin) {
            return '';
        }
        $params = new Registry($plugin->params);
        // reCaptcha uses 'private_key'; Turnstile uses 'secret'
        return (string) $params->get($provider === 'recaptcha' ? 'private_key' : 'secret', '');
    }

    private function verifyCaptcha(string $provider, string $secretKey, string $token): bool
    {
        if ($secretKey === '') {
            return false;
        }
        try {
            $http     = HttpFactory::getHttp();
            $url      = $provider === 'turnstile'
                ? 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
                : 'https://www.google.com/recaptcha/api/siteverify';
            $response = $http->post($url, ['secret' => $secretKey, 'response' => $token]);
            if ($response->code !== 200) {
                return false;
            }
            $result = json_decode((string) $response->body, true);
            return is_array($result) && ($result['success'] ?? false) === true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function sendViaJoomlaContactFallback(string $name, string $contactId, string $email, string $message, string $sourceUrl): bool
    {
        try {
            $app = $this->getApplication();
            $mailer = Factory::getMailer();
            $mailFrom = (string) $app->get('mailfrom');
            $fromName = (string) $app->get('fromname', 'Website Contact');

            if ($mailFrom === '') {
                return false;
            }

            $body = "New inquiry from website widget\n\n"
                . "Name: " . $name . "\n"
                . "Contact ID: " . $contactId . "\n"
                . ($email !== '' ? "Email: " . $email . "\n" : '')
                . "Message:\n" . $message . "\n\n"
                . "Source: " . $sourceUrl;

            $mailer->setSender([$mailFrom, $fromName]);
            $mailer->addRecipient($mailFrom);
            $mailer->setSubject('Widget Inquiry Fallback');
            $mailer->setBody($body);

            return (bool) $mailer->Send();
        } catch (\Throwable) {
            return false;
        }
    }
}
