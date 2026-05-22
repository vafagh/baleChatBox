<?php

declare(strict_types=1);

namespace Joomla\Plugin\System\BaleChat\Extension;

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
                'ajaxUrl'         => Uri::root() . 'index.php?option=com_ajax&plugin=bale_chat&group=system&format=json',
                'token'           => Session::getFormToken(),
            ],
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE,
        );

        $wa = $document->getWebAssetManager();

        $wa->addInlineScript(
            'window.BaleChatConfig = ' . $config . ';',
            ['position' => 'before'],
            [],
            ['plg_system_balechat.widget'],
        );

        $wa->registerAndUseScript(
            'plg_system_balechat.widget',
            'plg_system_bale_chat/js/bale_chat.js',
            ['relative' => true, 'version' => 'auto'],
        );
    }

    // -------------------------------------------------------------------------
    // AJAX handler (web contact form → Telegram Bot API)
    // -------------------------------------------------------------------------

    /**
     * Receives the contact-form POST from the widget and forwards it to the
     * configured Telegram bot.  The bot token is never exposed to the browser.
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
        $rawEmail = $input->post->get('email', '', 'string');
        $email    = filter_var($rawEmail, FILTER_VALIDATE_EMAIL) ? $rawEmail : '';
        $message  = mb_substr(
            trim(strip_tags($input->post->get('message', '', 'string'))),
            0,
            2000,
        );

        if ($name === '' || $message === '') {
            $this->ajaxReturn($event, false, 'نام و پیام الزامی است.');

            return;
        }

        $botToken = (string) $this->params->get('telegram_bot_token', '');
        $chatId   = (string) $this->params->get('telegram_chat_id', '');

        if ($botToken === '' || $chatId === '') {
            $this->ajaxReturn($event, false, 'سرویس پشتیبانی هنوز پیکربندی نشده است.');

            return;
        }

        $emailLine = $email !== '' ? "\n📧 *ایمیل:* " . $email : '';
        $text      = "🌐 *پیام از وب‌سایت*\n"
                   . "👤 *نام:* " . $name . $emailLine . "\n"
                   . "💬 *پیام:*\n" . $message . "\n"
                   . "🔗 " . Uri::current();

        $apiUrl = sprintf('https://api.telegram.org/bot%s/sendMessage', $botToken);

        try {
            $http     = HttpFactory::getHttp();
            $response = $http->post(
                $apiUrl,
                [
                    'chat_id'                  => $chatId,
                    'text'                     => $text,
                    'parse_mode'               => 'Markdown',
                    'disable_web_page_preview' => 'true',
                ],
            );

            if ($response->code === 200) {
                $this->ajaxReturn($event, true, 'پیام شما با موفقیت ارسال شد.');
            } else {
                $this->ajaxReturn($event, false, 'ارسال پیام ناموفق بود. لطفاً دوباره تلاش کنید.');
            }
        } catch (\Throwable) {
            $this->ajaxReturn($event, false, 'خطای اتصال. لطفاً دوباره تلاش کنید.');
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
