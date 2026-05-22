# Bale ChatBox Widget - Installation & Release Guide

## ✅ Phase 1: LOCAL SETUP & TESTING

### Prerequisites
- Joomla 4.x or 5.x
- SSH access to your kurdnic.com server
- Git installed locally
- PHP CLI access

### 1. Create Plugin Directory Structure

From your local workspace root, create:

```
bale_chat_plugin/
├── bale_chat.xml                 (✓ Created)
├── language/
│   ├── en-GB/
│   │   ├── plg_system_bale_chat.ini     (✓ Created)
│   │   └── plg_system_bale_chat.sys.ini (✓ Created)
│   └── fa-IR/
│       ├── plg_system_bale_chat.ini     (✓ Created)
│       └── plg_system_bale_chat.sys.ini (✓ Created)
├── src/
│   └── Extension/
│       └── BaleChat.php          (Copy from plugin/bale_chat/)
├── media/
│   └── js/
│       └── bale_chat.js          (Copy from plugin/bale_chat/)
├── services/
│   └── provider.php              (Copy from plugin/bale_chat/)
├── README.md
├── LICENSE.txt
└── CHANGELOG.md
```

### 2. Copy from GitHub to Local

```bash
cd /path/to/yoursite_com@kurdnic

# Copy plugin source files
mkdir -p bale_chat_plugin/src/Extension
mkdir -p bale_chat_plugin/media/js
mkdir -p bale_chat_plugin/services

# Copy existing files from your repo
cp plugin/bale_chat/src/Extension/BaleChat.php bale_chat_plugin/src/Extension/
cp plugin/bale_chat/media/js/bale_chat.js bale_chat_plugin/media/js/
cp plugin/bale_chat/services/provider.php bale_chat_plugin/services/
```

### 3. Create ZIP Installation Package Locally

```bash
# Create installable ZIP for Joomla
cd bale_chat_plugin
zip -r plg_system_bale_chat_1.0.0.zip \
  bale_chat.xml \
  language/ \
  src/ \
  media/ \
  services/ \
  README.md \
  LICENSE.txt \
  CHANGELOG.md

# This ZIP is now ready to install via Joomla Admin Panel
```

### 4. Test on Your Joomla Installation

**Option A: Upload via Joomla Admin Panel (Easiest)**
1. Login to `https://yoursite.com/administrator/`
2. Go to **System** → **Manage** → **Extensions** → **Upload** → **Upload & Install from URL** (or select file)
3. Upload `plg_system_bale_chat_1.0.0.zip`
4. Joomla automatically extracts to `/plugins/system/bale_chat/`

**Option B: Direct SSH Upload**
```bash
ssh root@kurdnic.com
cd /var/www/yoursite.com/public_html

# Upload ZIP and extract
unzip plg_system_bale_chat_1.0.0.zip -d plugins/system/bale_chat/

# Set permissions
chmod -R 755 plugins/system/bale_chat/
chown -R www-data:www-data plugins/system/bale_chat/
```

### 5. Install & Configure Plugin

1. Go to **Extensions** → **Manage** → **Plugins**
2. Find and click "**Bale ChatBox Widget**"
3. Click **Enable**
4. Configure settings:
   - **Primary Service**: Select "Bale" or "Telegram"
   - **Bale Bot Username**: `your_bale_username`
   - **Telegram Bot Username**: `your_telegram_username`
   - **Button Color**: `#0088cc` (or custom)
   - **Welcome Message**: `سلام! چطور می‌توانم کمک کنم؟`
   - **Widget Position**: `bottom-right` or `bottom-left`
   - **Fallback Timeout**: `4000` (milliseconds)
5. Click **Save & Close**

### 6. Verify Installation

Check frontend:
```bash
# Clear Joomla cache
ssh root@kurdnic.com
cd /var/www/yoursite.com/public_html
rm -rf administrator/cache/*
rm -rf cache/*
```

Visit `https://yoursite.com/` and look for floating chat button at bottom-right corner.

---

## 🚀 Phase 2: GITHUB SETUP FOR PUBLIC RELEASE

### 1. Create GitHub Release Structure

In your GitHub repo root, create:

```bash
.
├── plugin/bale_chat/          (existing plugin source)
├── releases/
│   └── v1.0.0/
│       └── plg_system_bale_chat_1.0.0.zip
├── .github/
│   └── workflows/
│       ├── release.yml        (Auto-create releases)
│       ├── test.yml           (Run tests)
│       └── lint.yml           (Code quality)
├── docs/
│   ├── INSTALLATION.md
│   ├── CONFIGURATION.md
│   └── TROUBLESHOOTING.md
├── CHANGELOG.md
├── LICENSE.txt
└── README.md
```

### 2. Create GitHub Actions Workflow (Auto-Release)

**File: `.github/workflows/release.yml`**

```yaml
name: Create Release Package

on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Create Package
      run: |
        mkdir -p package/bale_chat_plugin
        cp plugin/bale_chat/src package/bale_chat_plugin/ -r
        cp plugin/bale_chat/media package/bale_chat_plugin/ -r
        cp plugin/bale_chat/services package/bale_chat_plugin/ -r
        cp bale_chat.xml package/bale_chat_plugin/
        cp language package/bale_chat_plugin/ -r
        cp README.md package/bale_chat_plugin/
        cp LICENSE.txt package/bale_chat_plugin/
        
        cd package/bale_chat_plugin
        zip -r ../plg_system_bale_chat_${GITHUB_REF#refs/tags/}.zip .
    
    - name: Create GitHub Release
      uses: softprops/action-gh-release@v1
      with:
        files: package/plg_system_bale_chat_*.zip
        draft: false
        prerelease: false
      env:
        GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

### 3. Create Version Tags & Trigger Release

```bash
# From your local repo
git tag -a v1.0.0 -m "Initial release - Bale ChatBox Widget"
git push origin v1.0.0

# GitHub Actions automatically:
# 1. Creates ZIP package
# 2. Makes GitHub Release
# 3. Uploads ZIP as release asset
```

---

## 📦 Phase 3: JOOMLA EXTENSIONS DIRECTORY SUBMISSION

### 1. Prepare Required Files

Create/check:
- ✅ `bale_chat.xml` (manifest)
- ✅ Language files (en-GB, fa-IR)
- ✅ README.md (installation instructions)
- ✅ LICENSE.txt (GPL 2.0)
- ✅ CHANGELOG.md (version history)

### 2. Go to Joomla Extensions Directory

Visit: https://extensions.joomla.org/

1. **Create Account** (free)
2. **Add New Extension**
3. Fill form:
   - **Name**: Bale ChatBox Widget
   - **Category**: Chat / Support
   - **License**: GPL 2.0
   - **Description**: Floating Bale Messenger + Telegram widget
   - **Download URL**: `https://github.com/vafagh/baleChatBox/releases/download/v1.0.0/plg_system_bale_chat_1.0.0.zip`
4. Upload logo/banner
5. Submit for review (~3-5 business days)

---

## 📋 Checklist Before Release

- [ ] Test on Joomla 4.x
- [ ] Test on Joomla 5.x  
- [ ] Verify AJAX form submission works
- [ ] Test Bale fallback timeout
- [ ] Confirm Telegram fallback triggers
- [ ] Check RTL (Persian) text display
- [ ] Validate manifest XML syntax
- [ ] Language files complete (en-GB, fa-IR)
- [ ] README with screenshots
- [ ] CHANGELOG updated
- [ ] GitHub release created
- [ ] Joomla Extension Directory submitted

---

## 🔗 Update Server Setup (Optional)

To enable in-app updates, create `updates.xml`:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<updates>
  <update>
    <name>Bale ChatBox Widget</name>
    <description>Bale Messenger + Telegram chat widget</description>
    <element>bale_chat</element>
    <type>plugin</type>
    <folder>system</folder>
    <version>1.0.0</version>
    <infourl title="Bale ChatBox">https://github.com/vafagh/baleChatBox</infourl>
    <downloads>
      <downloadurl type="full" format="zip">
        https://github.com/vafagh/baleChatBox/releases/download/v1.0.0/plg_system_bale_chat_1.0.0.zip
      </downloadurl>
    </downloads>
    <targetplatform name="joomla" version="4,5" />
    <php_minimum>8.0</php_minimum>
  </update>
</updates>
```

Place at repo root: `updates.xml`

---

## 📚 Files to Create/Update

**README.md** (Comprehensive user guide)
**CHANGELOG.md** (Version history)
**docs/INSTALLATION.md** (Step-by-step)
**docs/CONFIGURATION.md** (All settings)
**docs/TROUBLESHOOTING.md** (FAQ + solutions)

---

## 🆘 Troubleshooting Installation

### Plugin not showing in Extension List?
```bash
# Check manifest syntax
php -r "simplexml_load_file('bale_chat.xml') ? print 'OK' : print 'Invalid XML';"

# Check permissions
ls -la /var/www/yoursite.com/public_html/plugins/system/bale_chat/
```

### AJAX not working?
```bash
# Check PHP error logs
tail -f /var/log/apache2/error.log | grep bale_chat

# Test AJAX endpoint
curl https://yoursite.com/index.php?option=com_ajax&plugin=bale_chat&group=system&format=json
```

### Widget not showing on frontend?
1. Verify plugin is enabled
2. Clear Joomla cache
3. Check browser console for JavaScript errors
4. Verify Bale API is reachable from your server

---

## 📈 Next Steps

1. **This week**: Test plugin locally → Create ZIP → Upload to Joomla
2. **Next week**: Setup GitHub Actions → Create releases
3. **Week 3**: Submit to Joomla Extensions Directory → Marketing
