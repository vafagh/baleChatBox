# Bale ChatBox Widget

**Documentation Language:** [English] | [Persian](plugin/bale_chat/language/fa-IR/README.md) | [Kurdish](plugin/bale_chat/language/ckb-IR/README.md)

---

## Why This Plugin Exists

Iran experiences recurring internet disruptions and filtering of many services.
**Bale Messenger** is an Iranian messaging platform accessible **both inside and outside Iran**,
making it ideal for maintaining contact between Iranian users and website admins
when other channels are blocked.

This plugin lets any **Joomla site hosted outside Iran** embed a floating chat widget
so Iranian visitors can still reach the site admin by providing their Bale, Telegram,
or WhatsApp contact details through a secure AJAX form.
The admin is then notified instantly via their **Bale or Telegram bot**.

### The Abroad Visitor Problem

Bale Messenger requires an **Iranian phone number** to register.
Visitors located **outside Iran** cannot create a Bale account.

This widget solves that: **no Bale account is needed to use it.**
The visitor simply fills in a form and submits their preferred contact details
(Bale ID, Telegram, or WhatsApp). No app installation or registration required on their side.

### One-Way Channel

The widget creates a **one-way notification channel**:

```
Visitor (abroad) ──submits form──▶ Admin bot notification (Iran)
```

The site admin inside Iran receives the visitor's contact details and message.
The admin is then **responsible for following up** — by calling, messaging, or
reaching out through whatever channel is available between Iran and the visitor's location.
The plugin itself does not provide a reply mechanism.

---

![Joomla](https://img.shields.io/badge/Joomla-4%2B-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![Version](https://img.shields.io/badge/version-1.1.0-green)
![License](https://img.shields.io/badge/License-Non--Commercial-red)
![Languages](https://img.shields.io/badge/Languages-3-lightblue)

## Features

- **Floating Chat Button** — Fixed-position widget on all pages
- **Visitor Contact Form** — Visitor picks Bale / Telegram / WhatsApp as their preferred reply channel
- **Bot Notification** — Admin receives message via **Bale bot** (Telegram as automatic fallback)
- **WhatsApp**: visitor contact *preference* only — no WhatsApp Bot API; admin follows up manually
- **CAPTCHA** — Cloudflare Turnstile or Google reCAPTCHA v2 (optional)
- **CSRF Protection** — Joomla token validated on every submission
- **File Attachment** — Optional image/PDF upload with form
- **Success UX** — Form replaced by success card after sending
- **Admin Test Panel** — Test bot and form from plugin settings
- **Multi-Language** — English, Persian/Farsi, Kurdish-Sorani with full RTL support

## Quick Installation

1. Download from [Releases](https://github.com/vafagh/baleChatBox/releases) (e.g. `plg_system_bale_chat_1.1.0.zip`)
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
├── LICENSE.txt                Non-commercial license
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

## Versioning

- `1.0.x` — bug fixes
- `1.x.0` — new features
- `x.0.0` — major changes or new Joomla major version support

GitHub Actions auto-builds a ZIP and publishes a Release on every version tag:

```bash
git tag -a v1.1.0 -m "Release 1.1.0"
git push origin v1.1.0
```

## Building & Deploying Locally

```powershell
# Build zip
python build-plugin.py

# Deploy
scp plg_system_bale_chat_1.1.0.zip root@server:/tmp/
ssh root@server "cd /var/www/site/public_html && unzip -o /tmp/plg_system_bale_chat_1.1.0.zip -d /tmp/d && rsync -a /tmp/d/ plugins/system/bale_chat/ && chown -R www-data:www-data plugins/system/bale_chat && rm -rf /tmp/d"
```

## Translations

| Language | Code | Status |
|----------|------|--------|
| English | en-GB | Complete |
| Persian/Farsi | fa-IR | Complete |
| Kurdish (Sorani) | ckb-IR | Complete |

## License

Custom Non-Commercial License — See [LICENSE.txt](plugin/bale_chat/LICENSE.txt)

Free for personal and non-commercial use with attribution.
**Commercial use requires written permission from the author.**

Copyright (C) 2024-2026 Vafa Ghoreyshi. All rights reserved.

## Links

- GitHub: https://github.com/vafagh/baleChatBox
- Bale Messenger: https://bale.ai
- Telegram: https://telegram.org
