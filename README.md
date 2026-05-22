# Bale ChatBox Widget

A professional Joomla 4+ system plugin that integrates **Bale Messenger** with **Telegram** as a floating chat widget for website visitors.

![Joomla](https://img.shields.io/badge/Joomla-4%2B-blue)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple)
![License](https://img.shields.io/badge/License-GPL%202.0-green)
![Languages](https://img.shields.io/badge/Languages-3-lightblue)

## 🎯 Features

- **Floating Chat Button** - Fixed position widget visible on all pages
- **Bale Messenger Integration** - Primary chat service with automatic availability detection
- **Telegram Fallback** - Automatically switches to Telegram if Bale is unavailable
- **Multi-Language Support** - English, Persian/Farsi, and Kurdish-Sorani
- **Admin Configuration** - Easy setup from Joomla admin panel
- **RTL Support** - Full right-to-left text direction support
- **Customizable Appearance** - Button color, position, and welcome message
- **Form Submission** - Direct contact form with Telegram bot forwarding
- **Security** - CSRF token validation and input sanitization

## 📦 Quick Installation

### Method 1: Joomla Admin Panel (Recommended)
1. Download `plg_system_bale_chat_1.0.0.zip`
2. Log in to Joomla Admin
3. Navigate: **Extensions → Manage → Extensions → Upload**
4. Upload the ZIP file
5. Navigate: **Extensions → Plugins**
6. Find "Bale ChatBox Widget" and click the status icon to **Enable**
7. Click the plugin name to configure

### Method 2: Manual Upload via SSH
```bash
cd /var/www/yoursite.com/public_html
unzip plg_system_bale_chat_1.0.0.zip -d plugins/system/
```

## ⚙️ Configuration

### Admin Settings (Extensions → Plugins → Bale ChatBox Widget)

| Setting | Description | Example |
|---------|-------------|---------|
| **Bale Bot Username** | Your Bale Messenger bot username | `support_bot` |
| **Telegram Bot Username** | Your Telegram bot username (fallback) | `mysite_support_bot` |
| **Button Color** | Hex color code for chat button | `#0088cc` |
| **Welcome Message** | Greeting shown when widget opens | `سلام! چطور کمک کنم؟` |
| **Widget Position** | bottom-left or bottom-right | `bottom-right` |
| **Fallback Timeout** | Milliseconds before switching to Telegram | `4000` |

### Get Bot Usernames

**Bale Messenger:**
1. Visit https://bale.ai
2. Create/Login to business account
3. Create new bot from dashboard
4. Copy your bot username

**Telegram:**
1. Open Telegram
2. Search for `@BotFather`
3. Send `/start` then `/newbot`
4. Follow the steps to get your bot username

## 🚀 Widget Behavior

```
User visits website
         ↓
Chat button appears (bottom-right)
         ↓
User clicks button
         ↓
Widget checks Bale availability (4000ms timeout)
         ↓
    ├─ Bale available? → Show Bale link
    └─ Bale down? → Show Telegram link + Form
```

## 📂 File Structure

```
plugin/bale_chat/
├── bale_chat.xml              Joomla manifest
├── README.md                  Plugin documentation
├── CHANGELOG.md               Version history
├── LICENSE.txt                GPL 2.0 license
│
├── src/
│   └── Extension/BaleChat.php Main plugin logic
│
├── media/
│   └── js/bale_chat.js        Frontend widget
│
├── services/
│   ├── provider.php           DI container
│   └── installer.php          Installation hooks
│
└── language/
    ├── en-GB/                 English
    ├── fa-IR/                 Persian
    └── ckb-IQ/                Kurdish
```

## 🔒 Security

- **CSRF Protection** - All form submissions validated
- **Input Sanitization** - Strips HTML, validates emails
- **Server-Side Validation** - Messages processed on backend
- **Safe API Calls** - HTTPS requests to Telegram API

## 📖 Documentation

- **[QUICK_START.md](QUICK_START.md)** - Step-by-step installation guide with visual diagrams
- **[INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)** - Detailed setup, GitHub integration, and Joomla Directory submission
- **[plugin/bale_chat/README.md](plugin/bale_chat/README.md)** - Technical plugin documentation

## 🛠️ Development

### Local Testing
1. Extract plugin to Joomla test installation
2. Enable plugin in Extensions → Plugins
3. Configure bot usernames in plugin settings
4. Visit website frontend and test widget

### Building Package
```bash
# Windows PowerShell
./build-plugin.ps1
```

Generates: `plg_system_bale_chat_1.0.0.zip`

## 🌍 Translations

Fully translated user interfaces and admin descriptions:

| Language | Code | Status |
|----------|------|--------|
| English | en-GB | ✅ Complete |
| Persian/Farsi | fa-IR | ✅ Complete |
| Kurdish (Sorani) | ckb-IQ | ✅ Complete |

## 📝 License

GNU General Public License v2.0 - See [LICENSE.txt](plugin/bale_chat/LICENSE.txt)

## 🔗 Links

- **GitHub**: https://github.com/vafagh/baleChatBox
- **Bale Messenger**: https://bale.ai
- **Telegram**: https://telegram.org
- **Joomla**: https://www.joomla.org

## ✨ Version History

**v1.0.0** (2024-04-01)
- Initial release
- Bale + Telegram integration
- Multi-language support (English, Persian, Kurdish)
- Complete admin configuration UI
- Security features (CSRF, input validation)

See [CHANGELOG.md](plugin/bale_chat/CHANGELOG.md) for full history.

## 👨‍💻 Support

For questions or issues, please visit the [GitHub repository](https://github.com/vafagh/baleChatBox).

---

**Made with ❤️ for the Joomla community**
