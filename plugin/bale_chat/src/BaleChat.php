<?php

declare(strict_types=1);

namespace Joomla\Plugin\System\BaleChat;

use Joomla\CMS\Factory;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

defined('_JEXEC') || die;

/**
 * Bale Chat system plugin – injects a floating support-chat widget that uses
 * Bale Messenger as primary channel and Telegram as automatic fallback.
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
    // Widget injection
    // -------------------------------------------------------------------------

    /**
     * Injects the widget configuration and script into every front-end HTML page.
     */
    public function injectWidget(Event $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('site')) {
            return;
        }

        $document = $app->getDocument();

        if ($document->getType() !== 'html') {
            return;
        }

        $params = $this->params;

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
                'ajaxUrl'         => 'index.php?option=com_ajax&plugin=bale_chat&group=system&format=json',
                'token'           => Session::getFormToken(),
            ],
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE,
        );

        $wa = $document->getWebAssetManager();

        // Add configuration inline
        $wa->addInlineScript(
            'window.BaleChatConfig = ' . $config . ';'
        );

        // Load widget JavaScript inline to ensure it executes
        $scriptPath = dirname(__DIR__) . '/media/js/bale_chat.js';
        if (is_file($scriptPath)) {
            $widgetScript = file_get_contents($scriptPath);
            $wa->addInlineScript($widgetScript);
        }
    }

    // -------------------------------------------------------------------------
    // AJAX handler (web contact form → Bale and/or Telegram Bot APIs)
    // -------------------------------------------------------------------------

    /**
     * Receives the contact-form POST from the widget and forwards it to the
     * configured Bale and/or Telegram bots. Bot tokens are never exposed to the browser.
     */
    public function handleAjax(Event $event): void
    {
        $app   = $this->getApplication();
        $input = $app->getInput();

        // CSRF check – Joomla sends the session-token hash as the field name
        $formToken = Session::getFormToken();

        if ($input->post->get($formToken, 0, 'int') !== 1) {
            $this->ajaxReturn($event, false, 'Security check failed.');

            return;
        }

        // Collect and sanitise input
        $name    = mb_substr(
            trim(strip_tags($input->post->get('name', '', 'string'))),
            0,
            100,
        );
        $contactType = strtolower(trim((string) $input->post->get('contact_type', 'telegram', 'string')));
        $contactId   = mb_substr(
            trim(strip_tags((string) $input->post->get('contact_id', '', 'string'))),
            0,
            120,
        );
        $message  = mb_substr(
            trim(strip_tags($input->post->get('message', '', 'string'))),
            0,
            2000,
        );

        if ($name === '' || $message === '' || $contactId === '') {
            $this->ajaxReturn($event, false, 'نام، شناسه تماس و پیام الزامی است.');

            return;
        }

        $baleToken      = (string) $this->params->get('bale_bot_token', '');
        $baleChatId     = (string) $this->params->get('bale_chat_id', '');
        $tgToken        = (string) $this->params->get('telegram_bot_token', '');
        $tgChatId       = (string) $this->params->get('telegram_chat_id', '');

        // Check if at least one service is configured
        $baleConfigured = $baleToken !== '' && $baleChatId !== '';
        $tgConfigured   = $tgToken !== '' && $tgChatId !== '';

        $contactLabel = $contactType === 'bale' ? '🆔 بله ID' : '🆔 Telegram ID';
        if ($contactType === 'joomla') {
            $contactLabel = '🆔 اطلاعات تماس';
        }

        $contactLink = '';
        $normalizedId = ltrim($contactId, '@');
        if ($contactType === 'bale' && $normalizedId !== '') {
            $contactLink = ' (https://ble.ir/' . $normalizedId . ')';
        }

        if ($contactType === 'telegram' && $normalizedId !== '') {
            $contactLink = ' (https://t.me/' . $normalizedId . ')';
        }

        $text      = "🌐 *پیام از وب‌سایت*\n"
               . "👤 *نام:* " . $name . "\n"
                   . $contactLabel . ': ' . $contactId . $contactLink . "\n"
                   . "💬 *پیام:*\n" . $message . "\n"
                   . "🔗 " . Uri::current();

        // Resolve provider order strictly: Bale (if configured+online) -> Telegram (if configured+online) -> Joomla contact fallback.
        $providerQueue = [];

        if ($baleConfigured && $this->isBaleOnline($baleToken)) {
            $providerQueue[] = ['type' => 'bale', 'token' => $baleToken, 'chatId' => $baleChatId];
        }

        if ($tgConfigured && $this->isTelegramOnline($tgToken)) {
            $providerQueue[] = ['type' => 'telegram', 'token' => $tgToken, 'chatId' => $tgChatId];
        }

        $errors = [];

        foreach ($providerQueue as $provider) {
            $result = $this->sendToProvider($provider['type'], $provider['token'], $provider['chatId'], $text);

            if ($result['ok'] === true) {
                $this->ajaxReturn($event, true, 'ارسال پیام انجام شد.');

                return;
            }

            $errors[] = $result['error'];
        }

        // Final fallback: Joomla contact-style server notification.
        if ($this->sendViaJoomlaContactFallback($name, $contactId, $message, Uri::current())) {
            $this->ajaxReturn($event, true, 'درخواست شما ثبت شد. همکاران ما به‌زودی از طریق شناسه تماس اعلام‌شده با شما ارتباط می‌گیرند.');

            return;
        }

        $detail = count($errors) > 0 ? (' (' . implode(' | ', $errors) . ')') : '';
        $this->ajaxReturn($event, false, 'ارسال پیام ناموفق بود. لطفاً دوباره تلاش کنید.' . $detail);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function ajaxReturn(Event $event, bool $success, string $message): void
    {
        $event->setArgument('result', [['success' => $success, 'message' => $message]]);
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

    private function isBaleOnline(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        try {
            $http = HttpFactory::getHttp();
            $url = sprintf('https://tapi.bale.ai/bot%s/getMe', $token);
            $response = $http->get($url);
            $payload = json_decode((string) $response->body, true);

            return $response->code === 200
                && is_array($payload)
                && (!array_key_exists('ok', $payload) || (bool) ($payload['ok'] ?? false));
        } catch (\Throwable) {
            return false;
        }
    }

    private function isTelegramOnline(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        try {
            $http = HttpFactory::getHttp();
            $url = sprintf('https://api.telegram.org/bot%s/getMe', $token);
            $response = $http->get($url);
            $payload = json_decode((string) $response->body, true);

            return $response->code === 200
                && is_array($payload)
                && (!array_key_exists('ok', $payload) || (bool) ($payload['ok'] ?? false));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array{ok: bool, error: string}
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

            $payload = json_decode((string) $response->body, true);
            $ok = $response->code === 200 && (!is_array($payload) || !array_key_exists('ok', $payload) || (bool) $payload['ok'] === true);

            if ($ok) {
                return ['ok' => true, 'error' => ''];
            }

            $desc = is_array($payload) && isset($payload['description']) ? (string) $payload['description'] : '';

            return [
                'ok' => false,
                'error' => ucfirst($provider) . ': ' . $response->code . ($desc !== '' ? (' - ' . $desc) : ''),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'error' => ucfirst($provider) . ': ' . $e->getMessage(),
            ];
        }
    }

    private function sendViaJoomlaContactFallback(string $name, string $contactId, string $message, string $sourceUrl): bool
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
                . "Shared contact: " . $contactId . "\n"
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
