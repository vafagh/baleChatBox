# Changelog

All notable changes to Bale ChatBox Widget will be documented in this file.
Versioning: PATCH (bug fix), MINOR (new feature), MAJOR (breaking change or new Joomla major support).

## [1.1.0] - 2026-06-01

### Changed
- Plugin description updated: emphasizes Iran internet disruption use-case and Bale accessibility
- License changed to Custom Non-Commercial — free for personal/non-commercial use, all rights reserved

### Added
- WhatsApp as visitor contact preference in form (admin notified via Bale/Telegram only — no WhatsApp bot)
- Optional file attachment (image/PDF/document)
- Cloudflare Turnstile and Google reCAPTCHA v2 CAPTCHA support
- Success card replaces form after submission (clean UX)
- "ارسال پیام دیگری" reset button on success card
- Admin test panel (bot + form simulation) from plugin settings page
- Confirmation message formatted with one contact method per line
- GitHub Actions workflow: auto-builds ZIP and publishes Release on version tag

### Fixed
- Removed `display:flex` from `.bc-bubble` — `<br>` line breaks now render correctly
- AJAX URL fixed for Joomla root installs (`Uri::root(true)` returns empty, not `/`)
- Cloudflare Turnstile param key corrected (`siteKey` vs `site_key`)
- WhatsApp handler URL suppressed when contact ID has no digits

## [1.0.0] - 2024-04-01

### Added
- Initial public release
- Bale Messenger integration with Telegram fallback
- AJAX-powered contact form with CSRF token protection
- Multi-language support: English, Persian/Farsi, Kurdish-Sorani
- RTL (Right-to-Left) layout support
- Customizable widget position and button color
- Server-side input validation and sanitization
- Joomla 4.x and 5.x support, PHP 8.0+
