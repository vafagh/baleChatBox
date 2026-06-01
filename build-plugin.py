#!/usr/bin/env python3
"""
Bale ChatBox Plugin - ZIP Builder for Joomla
Creates ZIP with proper forward slashes for Linux/Joomla compatibility.
Also regenerates updates.xml in the baleChatBox git repo so Joomla's
built-in update engine picks up new versions automatically.
"""

import os
import re
import zipfile
import sys
from pathlib import Path

def get_version(plugin_dir):
    """Extract version from bale_chat.xml"""
    xml_path = os.path.join(plugin_dir, "bale_chat.xml")
    with open(xml_path, "r", encoding="utf-8") as f:
        content = f.read()
    m = re.search(r"<version>([\d.]+)</version>", content)
    if not m:
        print("ERROR: Could not read <version> from bale_chat.xml")
        sys.exit(1)
    return m.group(1)


def update_updates_xml(version, repo_root):
    """Rewrite updates.xml in the baleChatBox repo with the current version."""
    updates_path = os.path.join(repo_root, "updates.xml")
    content = f"""<?xml version="1.0" encoding="utf-8"?>
<!--
  Joomla Extension Update Manifest
  =================================
  This file is fetched by the Joomla update engine (Extensions > Manage > Update).
  It is regenerated automatically by build-plugin.py every time a new build is
  made, so the <version> and <downloadurl> always reflect the latest release.

  Admins who installed this plugin will see an update badge in their Joomla
  admin panel and can upgrade with one click — no manual ZIP download needed.

  DO NOT edit version or downloadurl manually; run build-plugin.py instead.
-->
<updates>
  <update>
    <name>Bale ChatBox Widget</name>
    <description>Floating chat widget for Joomla — keeps visitors connected during Iran internet disruptions. Supports Bale Messenger, Telegram fallback, WhatsApp contact, CAPTCHA, and CSRF protection.</description>
    <element>bale_chat</element>
    <type>plugin</type>
    <folder>system</folder>
    <client>site</client>
    <version>{version}</version>
    <infourl title="Release Notes">https://github.com/vafagh/baleChatBox/releases/latest</infourl>
    <downloads>
      <downloadurl type="full" format="zip">
        https://github.com/vafagh/baleChatBox/releases/download/latest/plg_system_bale_chat_{version}.zip
      </downloadurl>
    </downloads>
    <targetplatform name="joomla" version="4.*"/>
    <targetplatform name="joomla" version="5.*"/>
    <targetplatform name="joomla" version="6.*"/>
    <php_minimum>8.0</php_minimum>
  </update>
</updates>
"""
    with open(updates_path, "w", encoding="utf-8", newline="\n") as f:
        f.write(content)
    print(f"  Updated: updates.xml → version {version}")


def create_plugin_zip(source_dir, output_zip):
    """Create ZIP file with forward slashes in paths"""
    
    with zipfile.ZipFile(output_zip, 'w', zipfile.ZIP_DEFLATED) as zipf:
        for root, dirs, files in os.walk(source_dir):
            for file in files:
                file_path = os.path.join(root, file)
                # Calculate archive name with forward slashes
                arcname = os.path.relpath(file_path, source_dir)
                arcname = arcname.replace(os.sep, '/')  # Convert backslashes to forward slashes
                zipf.write(file_path, arcname)
                print(f"  Added: {arcname}")

def main():
    plugin_dir = "bale_chat"
    # Repo root is one level up from this script's directory (baleChatBox/)
    script_dir = os.path.dirname(os.path.abspath(__file__))
    repo_root = os.path.join(script_dir, "..", "baleChatBox")

    # Check if plugin directory exists
    if not os.path.isdir(plugin_dir):
        print(f"ERROR: '{plugin_dir}' directory not found!")
        sys.exit(1)

    version = get_version(plugin_dir)
    zip_name = f"plg_system_bale_chat_{version}.zip"

    print("=== Bale ChatBox Plugin - ZIP Creator for Joomla ===\n")
    print("Checking required files...")
    
    # Check required files
    required_files = [
        "bale_chat.xml",
        "README.md",
        "LICENSE.txt"
    ]
    
    required_dirs = [
        "language/en-GB",
        "language/fa-IR",
        "language/ckb-IR",
        "src",
        "media",
        "services"
    ]
    
    missing = []
    
    for file in required_files:
        path = os.path.join(plugin_dir, file)
        if os.path.isfile(path):
            print(f"  OK: {plugin_dir}/{file}")
        else:
            print(f"  MISSING: {plugin_dir}/{file}")
            missing.append(file)
    
    for dir_name in required_dirs:
        path = os.path.join(plugin_dir, dir_name)
        if os.path.isdir(path):
            print(f"  OK: {plugin_dir}/{dir_name}/")
        else:
            print(f"  MISSING: {plugin_dir}/{dir_name}/")
            missing.append(dir_name)
    
    if missing:
        print("\nERROR: Missing files or directories")
        sys.exit(1)
    
    # Remove old ZIP if exists
    if os.path.exists(zip_name):
        os.remove(zip_name)
        print(f"Removed old {zip_name}")
    
    print("\nCreating ZIP package with forward slashes...")
    
    # Create ZIP
    create_plugin_zip(plugin_dir, zip_name)

    # Regenerate updates.xml in baleChatBox repo
    print("\nUpdating update server manifest...")
    if os.path.isdir(repo_root):
        update_updates_xml(version, repo_root)
    else:
        print(f"  WARNING: baleChatBox repo not found at {repo_root} — skipping updates.xml")
        print(f"  Manually update updates.xml with version {version} before pushing.")

    # Get file size
    size_mb = os.path.getsize(zip_name) / (1024 * 1024)
    print(f"\nSUCCESS: ZIP created: {zip_name}")
    print(f"File size: {size_mb:.2f} MB")
    print("\nNEXT STEPS:")
    print("1. Copy changed plugin files to baleChatBox/plugin/bale_chat/")
    print("2. cd ../baleChatBox && git add -A && git commit -m 'feat: ...' && git push origin master")
    print("   → GitHub Actions publishes the release asset automatically")
    print("   → Joomla sites pick up the new version on next update check")
    print(f"\nDirect install: upload {zip_name} via Extensions > Manage > Install > Upload")
    print("\nReady!")

if __name__ == "__main__":
    main()
