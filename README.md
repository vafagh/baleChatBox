# Bale ChatBox Widget

**📖 Documentation Languages:**
- **English** (Current)
- **[فارسی](plugin/bale_chat/language/fa-IR/README.md)** (Persian/Farsi)
- **[کوردی](plugin/bale_chat/language/ckb-IR/README.md)** (Kurdish-Sorani)

A professional Joomla 4+ system plugin that adds a floating chat widget for visitor contact. Supports **Bale Messenger**, **Telegram**, and **WhatsApp** with AJAX form, CSRF protection, and optional CAPTCHA.

![Joomla](https://img.shields.io/badge/Joomla-4%2B-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![License](https://img.shields.io/badge/License-GPL%202.0-green)
![Languages](https://img.shields.io/badge/Languages-3-lightblue)

## Features

- **Floating Chat Button** — Fixed position widget visible on all pages
- **Multi-Service Form** — Visitor selects Bale / Telegram / WhatsApp as preferred contact
- **Bot Notification** — Sends visitor message to admin via Bale bot (Telegram fallback)
- **CAPTCHA Support** — Cloudflare Turnstile or Google reCAPTCHA v2 (configurable)
- **CSRF Protection** — Token validated on every submission
- **File Attachment** — Optional image/PDF/document upload
- **Success UX** — Form replaced by success card after sending; "send another" resets it
- **Admin Test Panel** — Test bot and form directly from plugin settings
- **Multi-Language** — English, Persian/Farsi, Kurdish-Sorani with full RTL support

## Quick Installation

1. Download `plg_system_bale_chat_1.0.0.zip`
2. Joomla Admin → **Extensions → Manage → Extensions → Upload**
3. Upload the ZIP file
4. **Extensions → Plugins** → Find "Bale ChatBox Widget" → Enable and configure

## Configuration

| Setting | Description | Example |
|---------|-------------|---------|
| **Bale Bot Token** | Your Bale bot API token | `1234567890:ABCdef...` |
| **Bale Chat ID** | Admin chat ID to receive messages | `-100123456789` |
| **Telegram Bot Token** | Telegram bot token (fallback) | `987654:XYZabc...` |
| **Telegram Chat ID** | Admin chat ID for Telegram | `-100987654321` |
| **Button Color** | Hex color for chat button | `#0088cc` |
| **Welcome Message** | Greeting when widget opens | `سلام! چطور کمک کنم؟` |
| **Widget Position** | bottom-left or bottom-right | `bottom-right` |
| **Captcha Provider** | none / turnstile / recaptcha | `turnstile` |

## Widget Flow

```
Visitor opens widget
        ↓
Fills name + contact ID (Bale/Telegram/WhatsApp) + at least one of email/phone
        ↓
Completes CAPTCHA (if configured)
        ↓
Submits form → AJAX POST with CSRF token
        ↓
Server sends message to admin via Bale bot (or Telegram fallback)
        ↓
Success card shown — form hidden until "ارسال پیام دیگری" clicked
```

## File Structure

```
plugin/bale_chat/
├── bale_chat.xml              Joomla manifest
├── README.md                  Plugin documentation
├── CHANGELOG.md               Version history
├── LICENSE.txt                GPL 2.0 license
├── src/
│   └── BaleChat.php           Main plugin logic (events, AJAX, bot API)
├── media/
│   └── js/bale_chat.js        Frontend widget (self-contained CSS + JS)
├── services/
│   ├── provider.php           DI container registration
│   └── installer.php          Install/uninstall hooks
└── language/
    ├── en-GB/
    ├── fa-IR/
    └── ckb-IR/
```

## Security

- CSRF token validated on every form submission
- Input sanitized server-side (HTML stripped, email/phone validated)
- CAPTCHA: Cloudflare Turnstile or Google reCAPTCHA v2
- Minimum 2 contact methods required (reduces spam)
- HTTPS-only API calls to Bale/Telegram

## Building & Deploying

```powershell
# Build zip
python build-plugin.py

# Deploy
scp plg_system_bale_chat_1.0.0.zip root@server:/tmp/
ssh root@server "cd /var/www/site/public_html && unzip -o /tmp/plg_system_bale_chat_1.0.0.zip -d /tmp/d && rsync -a /tmp/d/ plugins/system/bale_chat/ && chown -R www-data:www-data plugins/system/bale_chat && rm -rf /tmp/d"
```

## Translations

| Language | Code | Status |
|----------|------|--------|
| English | en-GB | Complete |
| Persian/Farsi | fa-IR | Complete |
| Kurdish (Sorani) | ckb-IR | Complete |

## License

GNU General Public License v2.0 — See [LICENSE.txt](plugin/bale_chat/LICENSE.txt)

## Links

- **GitHub**: https://github.com/vafagh/baleChatBox
- **Bale Messenger**: https://bale.ai
- **Telegram**: https://telegram.org
- **Joomla**: https://www.joomla.org
