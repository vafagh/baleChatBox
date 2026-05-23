<?php

declare(strict_types=1);

namespace Joomla\Plugin\System\BaleChat;

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

        $primaryService = (string) $this->params->get('primary_service', 'bale');
        $baleToken      = (string) $this->params->get('bale_bot_token', '');
        $baleChatId     = (string) $this->params->get('bale_chat_id', '');
        $tgToken        = (string) $this->params->get('telegram_bot_token', '');
        $tgChatId       = (string) $this->params->get('telegram_chat_id', '');

        // Check if at least one service is configured
        $baleConfigured = $baleToken !== '' && $baleChatId !== '';
        $tgConfigured   = $tgToken !== '' && $tgChatId !== '';

        if (!$baleConfigured && !$tgConfigured) {
            $this->ajaxReturn($event, false, 'سرویس پشتیبانی هنوز پیکربندی نشده است.');

            return;
        }

        $contactLabel = $contactType === 'bale' ? '🆔 بله ID' : '🆔 Telegram ID';
        $text      = "🌐 *پیام از وب‌سایت*\n"
               . "👤 *نام:* " . $name . "\n"
               . $contactLabel . ': ' . $contactId . "\n"
                   . "💬 *پیام:*\n" . $message . "\n"
                   . "🔗 " . Uri::current();

        $successCount = 0;
        $errors       = [];

        // Send to Bale first if it's configured
        if ($baleConfigured && ($primaryService === 'bale' || !$tgConfigured)) {
            try {
                $http     = HttpFactory::getHttp();
                $baleUrl  = sprintf('https://tapi.bale.ai/bot%s/sendMessage', $baleToken);
                $response = $http->post(
                    $baleUrl,
                    [
                        'chat_id'                  => $baleChatId,
                        'text'                     => $text,
                        'disable_web_page_preview' => 'true',
                    ],
                );

                $payload = json_decode((string) $response->body, true);

                if ($response->code === 200 && (!is_array($payload) || !array_key_exists('ok', $payload) || (bool) $payload['ok'] === true)) {
                    $successCount++;
                } else {
                    $desc = is_array($payload) && isset($payload['description']) ? (string) $payload['description'] : '';
                    $errors[] = 'Bale: ' . $response->code . ($desc !== '' ? (' - ' . $desc) : '');
                }
            } catch (\Throwable $e) {
                $errors[] = 'Bale: ' . $e->getMessage();
            }
        }

        // Send to Telegram if it's configured and either it's primary or Bale failed
        if ($tgConfigured && ($primaryService === 'telegram' || !$baleConfigured || count($errors) > 0)) {
            try {
                $http     = HttpFactory::getHttp();
                $tgUrl    = sprintf('https://api.telegram.org/bot%s/sendMessage', $tgToken);
                $response = $http->post(
                    $tgUrl,
                    [
                        'chat_id'                  => $tgChatId,
                        'text'                     => $text,
                        'disable_web_page_preview' => 'true',
                    ],
                );

                $payload = json_decode((string) $response->body, true);

                if ($response->code === 200 && (!is_array($payload) || !array_key_exists('ok', $payload) || (bool) $payload['ok'] === true)) {
                    $successCount++;
                } else {
                    $desc = is_array($payload) && isset($payload['description']) ? (string) $payload['description'] : '';
                    $errors[] = 'Telegram: ' . $response->code . ($desc !== '' ? (' - ' . $desc) : '');
                }
            } catch (\Throwable $e) {
                $errors[] = 'Telegram: ' . $e->getMessage();
            }
        }

        if ($successCount > 0) {
            $this->ajaxReturn($event, true, 'پیام شما با موفقیت ارسال شد.');
        } else {
            $detail = count($errors) > 0 ? (' (' . implode(' | ', $errors) . ')') : '';
            $this->ajaxReturn($event, false, 'ارسال پیام ناموفق بود. لطفاً دوباره تلاش کنید.' . $detail);
        }
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
}
