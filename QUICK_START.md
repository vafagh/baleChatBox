# Bale ChatBox Widget - Quick Start Guide

## 📦 What You Have

**Installation File:** `plg_system_bale_chat_1.0.0.zip`
- Ready to upload to Joomla
- Contains all plugin code, languages, and configuration

---

## 🚀 Step-by-Step Installation & Setup

### Step 1: Upload ZIP to Joomla Admin

1. Open Joomla Administrator: **https://yoursite.com/administrator/**
2. Login with your admin credentials
3. Go to: **Extensions** → **Manage** → **Extensions**

   ```
   Top Menu:
   [Extensions] ← Click here
   ```

4. Click **Upload** tab

   ```
   Tabs visible:
   [Browse] [Upload] [Manage] [Update]
              ↑
            Click here
   ```

5. Click **"Select file"** or drag-drop `plg_system_bale_chat_1.0.0.zip`
6. Click **"Upload & Install"** button
7. You'll see: **"Extension successfully installed"** message ✅

---

### Step 2: Enable the Plugin

1. Go to: **Extensions** → **Plugins**

   ```
   Left Sidebar:
   Extensions
   ├── Manage
   ├── Plugins ← Click here
   ├── Templates
   └── Languages
   ```

2. Find: **"Bale ChatBox Widget"** in the list (or search for it)

   ```
   List view shows:
   
   [ ] Plugin Name               | Type      | Access   | Status
   [ ] Bale ChatBox Widget       | System    | Public   | [Status Toggle]
                                                         ↑
                                                    Click to enable
   ```

3. Click the **status icon** (red X or green checkmark) to **enable it**
   - Should turn **green** when enabled

---

### Step 3: Configure Bot Settings

1. Click on **"Bale ChatBox Widget"** plugin name (link)
2. You'll see the configuration form:

   ```
   CONFIGURATION FORM:
   
   ═══════════════════════════════════════
   Bale ChatBox Settings
   ═══════════════════════════════════════
   
   Basic Settings:
   
   ┌─ Primary Chat Service ────────────────┐
   │ (Select) Bale / Telegram              │
   └───────────────────────────────────────┘
   
   ┌─ Bale Bot Username ───────────────────┐
   │ [_______________________]             │  ← Enter your Bale bot username
   │ e.g., my_support_bot                  │
   └───────────────────────────────────────┘
   
   ┌─ Telegram Bot Username ───────────────┐
   │ [_______________________]             │  ← Enter your Telegram bot username
   │ e.g., my_support_bot                  │
   └───────────────────────────────────────┘
   
   ┌─ Button Color ────────────────────────┐
   │ [#0088cc] [Color Picker]              │  ← Pick color or hex code
   └───────────────────────────────────────┘
   
   ┌─ Welcome Message ─────────────────────┐
   │ [سلام! چطور می‌توانم کمک کنم؟    ] │
   │                                       │  ← Your greeting message
   │                                       │
   └───────────────────────────────────────┘
   
   ┌─ Widget Position ─────────────────────┐
   │ (Select) Bottom Right / Bottom Left    │
   └───────────────────────────────────────┘
   
   ┌─ Fallback Timeout (ms) ───────────────┐
   │ [4000]                                │  ← Time before Telegram fallback
   │ (1000-10000 milliseconds)             │
   └───────────────────────────────────────┘
   ```

3. Fill in your bot usernames:

   **For Bale Bot Username:**
   1. Visit https://bale.ai
   2. Create or login to your business account
   3. Go to dashboard → Create new bot
   4. Open bot settings
   5. Copy the username (without the @)
   6. Paste into **"Bale Bot Username"** field

   **For Telegram Bot Username:**
   1. Open Telegram app or go to telegram.org
   2. Search for **@BotFather**
   3. Send the command: `/start`
   4. Send the command: `/newbot`
   5. Follow the steps to create your bot
   6. BotFather will send you the bot username (without the @)
   7. Paste into **"Telegram Bot Username"** field

4. Click **"Save & Close"** button (top right)

---

## 💬 Where the Widget Appears

### On Your Website Frontend

Once enabled, visitors will see:

```
Example: https://yoursite.com/

┌─────────────────────────────────────────────────┐
│                                                 │
│  Your Website Content                           │
│                                                 │
│                                                 │
│                                                 │
│                                                 │
│                                ┌────────────┐  │
│                                │    💬      │  ← Chat Button
│                                │  Floating  │     (Bottom Right)
│                                │   Button   │
│                                └────────────┘  │
└─────────────────────────────────────────────────┘
```

### When Visitor Clicks the Chat Button

```
┌─────────────────────────────────────────────────┐
│                                                 │
│  Your Website Content                           │
│                                                 │
│                    ┌──────────────────┐         │
│                    │  💬 Support      │ [×]    │ ← Opens Chat Panel
│                    ├──────────────────┤         │
│                    │ Robot: السلام!   │         │
│                    │                  │         │
│                    │ [Messenger] [Form]│        │ ← Two Tabs
│                    ├──────────────────┤         │
│                    │ [Chat via Bale]  │         │ ← Direct Links
│                    │ [Chat via TG]    │         │
│                    └──────────────────┘         │
│                                                 │
└─────────────────────────────────────────────────┘
```

### Widget Tabs Explanation

**Messenger Tab:**
- Shows direct links to Bale and Telegram
- Clicking opens chat in new window
- No server involved

**Form Tab:**
- Users enter: Name, Email (optional), Message
- Submission goes to server
- Server forwards to Telegram Bot API
- User gets confirmation

---

## 🎨 Customization Options in Plugin Settings

| Setting | What It Controls | Example |
|---------|-----------------|---------|
| **Primary Service** | Which chat opens by default | Bale (with Telegram fallback) |
| **Bale Bot Username** | Where Bale messages go | my_support |
| **Telegram Bot Username** | Where Telegram messages go | my_support_bot |
| **Button Color** | Floating button + panel color | #FF6B35 (orange) |
| **Welcome Message** | First message visitor sees | سلام! چطور کمک کنم؟ |
| **Widget Position** | Button location on screen | Bottom Right / Bottom Left |
| **Fallback Timeout** | Time before showing Telegram | 4000 ms (4 seconds) |

---

## 🔄 How It Works (Behind the Scenes)

```
┌─────────────────┐
│   Visitor       │
│   Sees Widget   │
└────────┬────────┘
         │ Clicks Button
         ↓
┌─────────────────────────────┐
│  Check: Is Bale available?  │ (4 second timeout)
└──────────┬──────────────────┘
           │
      ┌────┴────┐
      │          │
  YES ↓          ↓ NO/TIMEOUT
  ┌────┐      ┌─────────┐
  │Bale│      │ Telegram│
  └────┘      └─────────┘
```

**Scenario 1: Bale Online**
- Show Bale chat option
- User clicks → Opens ble.ir/my_support_bot

**Scenario 2: Bale Offline (or Timed Out)**
- Automatically show Telegram option
- User clicks → Opens t.me/my_support_bot

**Scenario 3: User Submits Form**
- Name, Email, Message go to SERVER
- Server → Telegram Bot API
- Telegram Bot receives notification
- User gets confirmation message

---

## ✅ What to Test After Installation

1. **Widget Appears on Frontend**
   - Visit: https://yoursite.com/
   - Look for floating chat button (bottom right)
   - ✅ If visible: SUCCESS

2. **Can Open Chat Panel**
   - Click the chat button
   - Panel slides up with welcome message
   - ✅ If panel opens: SUCCESS

3. **Can Click Messenger Links**
   - Click "Chat in Bale" or "Chat in Telegram"
   - Should open in new window
   - ✅ If links work: SUCCESS

4. **Can Submit Form**
   - Click "Form" tab
   - Fill name, email, message
   - Click "Send Message"
   - Should see success message
   - ✅ If message sent: SUCCESS

---

## 🆘 Troubleshooting

### Widget Doesn't Appear on Website

1. **Check plugin is enabled:**
   - Extensions → Plugins
   - Find "Bale ChatBox Widget"
   - Status should be GREEN ✓

2. **Clear Joomla Cache:**
   - System → Clear Cache
   - Click "Clear All"

3. **Check browser console for errors:**
   - Press F12 in browser
   - Go to "Console" tab
   - Reload page
   - Look for red error messages

### Form Submission Doesn't Work

1. **Check bot credentials are filled in:**
   - Extensions → Plugins → Bale ChatBox Widget
   - Verify Bale + Telegram usernames are entered
   - Save & Close

2. **Check CSRF token is enabled:**
   - This is automatic in Joomla
   - Widget includes security token

3. **Check Telegram fallback:**
   - If Bale unavailable, can still use Telegram form

---

## 📚 Additional Resources

- **Full Guide:** `INSTALLATION_GUIDE.md` (in project root)
- **Plugin Code:** `bale_chat/` folder
- **Installation File:** `plg_system_bale_chat_1.0.0.zip`

---

**Your widget is ready!** 🎉
Just upload the ZIP, enable it, configure your bot usernames, and it's live on your website.
