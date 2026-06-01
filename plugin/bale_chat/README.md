# Bale ChatBox Widget - Joomla Plugin

![Version](https://img.shields.io/badge/version-1.1.0-green)
![Joomla](https://img.shields.io/badge/joomla-4%2B-orange)
![PHP](https://img.shields.io/badge/php-8.0%2B-purple)
![License](https://img.shields.io/badge/license-Non--Commercial-red)

Floating chat widget for Joomla — keeps visitors connected to site admins
even during Iran internet disruptions. Bale Messenger is accessible both inside
and outside Iran. Visitors submit their contact info; admin is notified via Bale
or Telegram bot.

## Features

- **Multi-Service Contact Form** — Visitor picks Bale / Telegram / WhatsApp as preferred reply channel
- **Bot Notification** — Admin notified via Bale bot (Telegram as fallback)
- **WhatsApp**: visitor contact *preference* only — no WhatsApp Bot API; admin follows up manually
- **CAPTCHA** — Cloudflare Turnstile or Google reCAPTCHA v2 (optional)
- **CSRF Protection** — Token validated on every submission
- **Success UX** — Form replaced by a success card after sending
- **Admin Test Panel** — Test bot and form from plugin settings
- **Multi-language** — English, Persian/Farsi, Kurdish-Sorani (full RTL)

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

## 🔒 Security & Privacy Disclosure

Because this plugin routes visitor data through Bale Messenger (a domestic Iranian platform
connected to the National Bank of Iran), administrators must be aware of the following.

### 1. Data Transmission & Monitoring
- **No End-to-End Encryption (E2EE):** Cybersecurity audits (including by the Open Technology Fund) confirm Bale does not use E2EE. Visitor messages and contact details sent via your bot are visible to Bale's backend servers.
- **URL Monitoring:** Links transmitted through the bot are redirected via Bale's own servers, enabling them to log and monitor accessed addresses.

### 2. Government Surveillance Risk
- **State-Supported Platform:** Bale operates under domestic Iranian regulations that permit monitoring of communications for "security and anti-fraud purposes."
- **Censorship Risk:** Messages containing specific keywords, political content, or references to restricted external platforms may be automatically filtered or flagged.

### 3. GDPR & Privacy Obligations
- **PII Handling:** Visitors submit personally identifiable information (Telegram handles, WhatsApp numbers, phone numbers) through this form.
- **Cross-Border Data Transfer:** Routing user data from a site hosted outside Iran into Iranian messaging infrastructure may conflict with GDPR or other international privacy laws without explicit visitor consent.
- **Built-in Notice:** This plugin shows a pop-up privacy notice to visitors who select Bale as their contact channel before submitting the form.

### 4. Financial System Overlap
Bale also operates as a banking application tightly integrated with Iranian financial infrastructure. All Bale accounts are linked to an Iranian national ID and SIM card.

### 💡 Admin Best Practices
- **Use a dedicated bot account** — never your personal Bale credentials.
- **Do not collect sensitive data** (passwords, financial info) — treat the channel as unencrypted text.
- **Review Joomla logs** — ensure AJAX submissions are not permanently stored if only used as a real-time router.

## License

Custom Non-Commercial License — See LICENSE.txt

Free for personal and non-commercial use with attribution.
Commercial use requires written permission from the author.

Copyright (C) 2024-2026 Vafa Ghoreyshi. All rights reserved.

## Author

Vafa Ghoreyshi — https://github.com/vafagh
