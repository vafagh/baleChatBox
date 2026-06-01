# Bale ChatBox Widget - Joomla Plugin

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![Joomla](https://img.shields.io/badge/joomla-4%2B-orange)
![PHP](https://img.shields.io/badge/php-8.0%2B-purple)
![License](https://img.shields.io/badge/license-GPL%202.0-green)

A modern, production-ready Joomla system plugin that injects a floating chat widget for visitor contact. Supports **Bale Messenger**, **Telegram**, and **WhatsApp**. Includes AJAX-powered contact form with CSRF protection and optional CAPTCHA.

## Features

✅ **Multi-Service Contact Form**
- Visitor selects: Bale / Telegram / WhatsApp
- Collects name, contact ID, email, phone, message, optional file attachment
- Requires at least 2 contact methods (reduces spam)

✅ **Bot Notification**
- Sends visitor message to admin via Bale or Telegram bot
- Automatic fallback: tries Bale first, falls back to Telegram

✅ **Security**
- CSRF token validation on every submission
- Optional CAPTCHA: Cloudflare Turnstile or Google reCAPTCHA v2
- Server-side input validation and sanitization

✅ **UX**
- Success card replaces form after submission (no stale fields visible)
- "ارسال پیام دیگری" button to reset and send again
- Confirmation message shows each contact method on its own line

✅ **Multi-language Support**
- English (en-GB)
- Persian/Farsi (fa-IR)
- Kurdish-Sorani (ckb-IR)

✅ **Admin Test Panel**
- Test bot connectivity and form submission from plugin edit page

## Installation

1. Download ZIP from [GitHub Releases](https://github.com/vafagh/baleChatBox/releases)
2. Joomla Admin → **System** → **Manage** → **Extensions** → **Upload**
3. Select the ZIP file
4. Go to **Extensions** → **Plugins**
5. Find "Bale ChatBox Widget" → Click to configure and enable

## Configuration

| Setting | Description |
|---------|-------------|
| **Bale Bot Token** | Your Bale bot API token |
| **Bale Chat ID** | Admin chat/group ID to receive messages |
| **Telegram Bot Token** | Your Telegram bot API token |
| **Telegram Chat ID** | Admin chat/group ID to receive messages |
| **Button Color** | Widget button color (hex code) |
| **Welcome Message** | Message shown when widget opens |
| **Widget Position** | bottom-left or bottom-right |
| **Captcha Provider** | none / turnstile / recaptcha |

## License

GNU General Public License v2.0 - See LICENSE.txt

## Author

Vafa - https://github.com/vafagh
