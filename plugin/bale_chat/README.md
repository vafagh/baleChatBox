# Bale ChatBox Widget - Joomla Plugin

![Version](https://img.shields.io/badge/version-1.0.0-blue)
![Joomla](https://img.shields.io/badge/joomla-4%2B-orange)
![PHP](https://img.shields.io/badge/php-8.0%2B-purple)
![License](https://img.shields.io/badge/license-GPL%202.0-green)

A modern, production-ready Joomla system plugin that injects a floating chat widget supporting **Bale Messenger** with automatic **Telegram** fallback. Includes AJAX-powered contact form with CSRF protection.

## Features

✅ **Dual Service Support**
- Primary: Bale Messenger (tapi.bale.ai)
- Fallback: Telegram Bot API (configurable timeout)

✅ **Smart Availability Detection**
- Automatic fallback to Telegram if Bale unavailable
- Configurable timeout (1-10 seconds)

✅ **AJAX Contact Form**
- No page reload required
- CSRF token protection
- Server-side input validation & sanitization

✅ **Multi-language Support**
- English (en-GB)
- Persian/Farsi (fa-IR)
- Kurdish-Sorani (ckb-IR)

## Installation

1. Download ZIP from [GitHub Releases](https://github.com/vafagh/baleChatBox/releases)
2. Joomla Admin → **System** → **Manage** → **Extensions** → **Upload**
3. Select the ZIP file
4. Go to **Extensions** → **Plugins**
5. Find "Bale ChatBox Widget" → Click to configure

## Configuration

| Setting | Description |
|---------|-------------|
| **Bale Bot Username** | Your Bale support bot username |
| **Telegram Bot Username** | Your Telegram bot username |
| **Button Color** | Widget button color (hex code) |
| **Welcome Message** | Message when widget opens |
| **Widget Position** | bottom-left or bottom-right |
| **Fallback Timeout** | Milliseconds before Telegram fallback (1000-10000) |

## License

GNU General Public License v2.0 - See LICENSE.txt

## Author

Vafa - https://github.com/vafagh
