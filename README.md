# WPEPP – All In One WordPress Security, Firewall, Malware Scanner & Login Protection

> The complete, high-performance All-In-One WordPress cybersecurity suite. Built with a Zero-Impact architecture, Early-Boot Web Application Firewall (WAF), Deep Malware Scanner, Anti-Spam Bot Shield, Two-Factor Authentication (2FA), Biometric Passkeys (WebAuthn), Server Hardening, Block Center with IPv6 CIDR, Multi-Core CPU Monitor, Content Lock, and a Real-Time Login Page Customizer — all in a lightning-fast React dashboard.

[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue?logo=wordpress)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2-green)](https://www.gnu.org/licenses/gpl-2.0.html)
[![Version](https://img.shields.io/badge/Version-3.0.0-orange)](https://wpthemespace.com)
[![Tests](https://img.shields.io/badge/Tests-100%25%20Passing-brightgreen)](https://wpthemespace.com)
[![Zero Legacy](https://img.shields.io/badge/React%2018-Zero%20jQuery-blueviolet)](https://wpthemespace.com)

**[🌐 Official Website](https://wpthemespace.com)** &nbsp;|&nbsp; **[💰 View Pro Pricing](https://wpthemespace.com/product/wpepp-essential-security-password-protect-login-page-customizer/#pricing)** &nbsp;|&nbsp; **[📖 Documentation](https://wpthemespace.com/wpepp/)** &nbsp;|&nbsp; **[🎥 Watch Video Tutorial](https://www.youtube.com/watch?v=QiMpOBgbhSY)**

---

## ⚡ Zero-Impact Philosophy: Why WPEPP Stands Out

Traditional security plugins often slow down WordPress with unindexed log tables, heavy front-end scripts on every page visit, and aggressive CPU-draining background tasks. 

**WPEPP** was engineered from scratch to provide uncompromising, enterprise-grade protection with **zero negative impact on page speed or server resources**:

- **Blazing Fast Frontend:** Anonymous visits trigger zero database writes. Rate limit counters and lockout states operate on high-speed transient caches.
- **Early-Boot Extended Firewall:** Drops malicious payloads *before WordPress or your database loads*, saving server RAM and keeping your site responsive under heavy DDoS or bot probes.
- **Auto-Failsafe Architecture:** Any bootstrap error or environment conflict safely detaches the early-boot filter automatically, preventing white-screens or locked sites.
- **Modern React Dashboard:** An intuitive, single-page application built on WordPress React components with zero legacy jQuery.
- **100% Privacy & Data Control:** All visitor logs, security events, and IP blocklists stay inside your WordPress database. No tracking, no external telemetry.

---

## 🔄 Stop Installing 10 Separate Plugins

WPEPP replaces an entire stack of disjointed plugins with one unified, cohesive solution:

| What you were using | → | WPEPP replaces it with |
|---|:---:|---|
| Heavy WAF / Firewall plugin | → | ✅ **30+ Core Rules & Early-Boot Drop-in Firewall** |
| Malware scanner plugin | → | ✅ **WordPress.org Checksums & 19 Heuristic Signatures** |
| WooCommerce & comment spam plugins | → | ✅ **Invisible Honeypots, Time-Gate & Disposable Email Blocker** |
| Captcha & Bot Shield plugin | → | ✅ **Cloudflare Turnstile & Google reCAPTCHA v2 / v3** |
| Limit Login Attempts plugin | → | ✅ **Brute Force Limiter, Lockouts & Self-Service Unlock Email** |
| Two-Factor Auth (2FA) plugin | → | ✅ **Free TOTP 2FA (Google Authenticator, Authy, etc.)** |
| Biometric Passkey plugin | → | ✅ **WebAuthn Passkeys (Apple Touch ID, Face ID, Windows Hello) [Pro]** |
| Custom Login URL / Hide wp-login | → | ✅ **Secret Custom Login URL & Masked 404s** |
| Content restriction / Private site | → | ✅ **Site-Wide Password, Admin-Only Mode & Content Lock** |
| AI Scraper / Crawler Blocker | → | ✅ **1-Click AI Crawler Blocker & robots.txt Engine** |
| Server CPU & Query Monitor | → | ✅ **Accurate Multi-Core CPU Monitor, Slow Queries & Cron Manager** |
| Login page customizer | → | ✅ **Real-Time Live Preview Customizer & Pre-designed Templates** |

---

## 🛡️ Core Feature Suite

### 1. 🛡️ Web Application Firewall (WAF)
Protect your WordPress website in real time from malicious requests and exploit probes.
- **30+ Curated Core Rules:** Blocks SQL Injection (SQLi), Cross-Site Scripting (XSS), Remote Code Execution (RCE), Local File Inclusion (LFI), Path Traversal, PHP Object Injection, and known hacker scanner tools.
- **Early-Boot Extended Mode:** Automatically generates a lightweight drop-in filter that intercepts requests before WordPress loads. Includes an auto-failsafe mechanism.
- **Learning Mode:** Monitor incoming traffic without blocking to safely review rule matches and prevent false positives before going live.
- **Cloudflare & Reverse Proxy Integration:** Automatically identifies verified Cloudflare IP ranges and reverse proxy headers (`CF-Connecting-IP`, `X-Forwarded-For`).

### 2. 🔍 Deep Malware & Vulnerability Scanner
Detect infections, unauthorized backdoors, and suspicious code before damage is done.
- **Official WordPress.org Checksum Engine:** Compares core files, themes, and plugins directly with official WordPress.org repository hashes to detect altered files.
- **Rogue & Unknown File Detection:** Instantly flags unexpected or rogue files inside critical folders like `/wp-admin/` and `/wp-includes/`.
- **19 High-Precision Heuristic Signatures:** Scans for dangerous PHP webshells (c99, r57, WSO), obfuscated code (`base64_decode`, `eval`, `gzinflate`), and hidden backdoors.
- **Resumable Chunked Background Scans:** Operates in small, manageable batches to prevent timeouts on shared hosting and strict server execution limits.
- **Remote Threat Signature Sync:** Automatically syncs the latest threat definitions daily from a secure live signature repository.

### 3. 🤖 Anti-Spam & Bot Shield
Eliminate automated spambots and fake customer accounts without annoying legitimate visitors.
- **WooCommerce Bot & Fake Account Shield:**
  - Invisible registration honeypot trap field.
  - Disposable & temporary email blocker (blocks 100+ throwaway email providers like Mailinator, TempMail, Yopmail).
  - Registration rate limiting to prevent botnets from flooding customer accounts.
  - One-click CAPTCHA enforcement on WooCommerce login, registration, and checkout.
- **WordPress Comment Spam Guard:**
  - Invisible honeypot trap stops automated spambots cold.
  - Submission Time-Gate (Speed Check) blocks automated bots submitting forms in under 3 seconds.
- **Integrated CAPTCHA Engine:** Seamlessly supports **Cloudflare Turnstile** (frictionless human verification) and **Google reCAPTCHA v2 / v3**.

### 4. 🚫 Block Center & Advanced IP Defense
Take absolute control over who accesses your server.
- **IPv4 & IPv6 CIDR Support:** Block individual IPs or entire subnets using standard CIDR notation (e.g., `192.168.1.0/24`, `2001:db8::/32`).
- **404-Flood Auto-Defense:** Automatically detects and blocks bots aggressively crawling for vulnerable paths (e.g., `wp-config.php.bak`, `phpmyadmin`, `.env`).
- **Intelligent Rate Limiting:** Enforces per-minute request thresholds per IP, keeping aggressive crawlers and scrapers from exhausting server bandwidth.
- **Permanent & Temporary Bans:** Set auto-expiring bans or permanent blacklists with customizable admin reasons.

### 5. 🔐 Login Security, 2FA & Password Defense
Lock down your WordPress administration doors against brute-force attacks and credential stuffing.
- **Login Attempt Limiter & Lockout:** Limits failed password attempts with customizable retry limits and lockout durations.
- **Invisible Login Honeypot:** Traps automated login scripts while remaining completely invisible to genuine human users.
- **Masked Login Errors:** Replaces generic WordPress error messages with unified feedback, preventing attackers from discovering valid usernames.
- **Self-Service Unlock Email:** Site administrators can safely email themselves a one-time unlock link to regain instant access.
- **Username Discovery Shielding:** Blocks REST API author enumeration, `?author=N` URL queries, oEmbed author exposure, and author sitemaps.
- **Custom Login URL:** Conceal your `/wp-login.php` behind a secret custom slug with optional 404 or home redirect.
- **Two-Factor Authentication (2FA / TOTP):** Secure login with standard authenticator apps (Google Authenticator, Microsoft Authenticator, Authy, 1Password) with local QR code generation, recovery codes, and trusted-device cookies.
- **Passkeys (WebAuthn / FIDO2) [Pro]:** Experience biometric passwordless authentication using Apple Touch ID, Face ID, or Windows Hello.
- **Password Strength & Breached Password Check [Pro]:** Enforces strong password requirements and audits passwords against billions of breached credentials via Have I Been Pwned (using privacy-preserving k-anonymity).
- **Auto Login & Magic Links:** Generate secure, encrypted single-use login links for fast client access.

### 6. 🛑 Site Access Gate & Content Protection
Control access to your entire website or specific private sections.
- **Site-Wide Password Protection:** Put the entire site behind a master password gate with customizable cookie expiration.
- **Admin-Only Mode:** Temporarily restrict access exclusively to logged-in administrators while performing maintenance.
- **Country-Based Access Restrictions:** Whitelist or blacklist access by country code using GeoIP lookups.
- **Complete REST API Enforcement:** Site-wide security gates are fully enforced on all public WordPress REST API endpoints, completely sanitizing post content, excerpts, and comments (fixes CVE-2025-8945).
- **Content Lock:** Protect individual posts and pages with custom passwords, user role restrictions, custom redirect URLs, and excerpt previews.
- **Conditional Content Display:** Show or hide content based on 12 conditions: user roles, logged-in status, device types, dates, or day/time schedules.

### 7. ⚡ WordPress Hardening & Server Rules
Harden your server configuration with 1-click controls:
- **Block PHP Execution in Uploads:** Prevents executed PHP webshells inside `/wp-content/uploads/` via `.htaccess`.
- **Disable Directory Listing:** Appends `Options -Indexes` to prevent visitors from viewing folder file trees.
- **Disable File Editor:** Injects `DISALLOW_FILE_EDIT` into `wp-config.php` to prevent attackers from editing plugin/theme files from the admin.
- **Security Headers Management:** Enforces modern browser headers including `X-Frame-Options` (anti-clickjacking), `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Strict-Transport-Security` (HSTS), and `Permissions-Policy`.
- **Critical Files Permission Inspector:** Real-time diagnostics of `wp-config.php`, `.htaccess`, and root folder permissions.
- **AI Crawler & Scraper Blocker:** Blocks unauthorized AI training scrapers (OpenAI GPTBot, Anthropic ClaudeBot, CCBot, Google-Extended, ByteDance, etc.) via dynamic robots.txt rules.

### 8. 📊 CPU Monitor, Performance & System Diagnostics
Never wonder what is happening on your server.
- **Accurate CPU & Resource Monitor:** High-precision CPU utilization meter with multi-core container/shared host support (`/sys/devices/system/cpu/online`, `procfs`, `nproc`) and realistic rolling averages that match your cPanel dashboard.
- **PHP Memory & Health Status:** Real-time visibility into current vs. peak PHP memory consumption and WP memory limits.
- **Slow Database Query Monitor:** Captures queries exceeding execution thresholds along with execution time and full call stack.
- **Background Cron Job Health:** Pinpoints overdue or stalled WP-Cron schedules with 1-click execution.
- **Options & Transient Bloat Cleaner:** Clean expired transients and autoloaded bloat in seconds.
- **Activity Audit Log:** Comprehensive event stream capturing logins, lockouts, blocks, malware scans, and settings updates with filtering and pagination.
- **Email Delivery Alerts:** Proactively detects active SMTP plugins and dispatches security alerts for critical findings, lockouts, and weekly digests.

### 9. 🎨 Login Page & Form Customizer with Live Preview
Transform your login, registration, and password-protected screens into beautiful, branded experiences.
- **Live Interactive Preview:** Customize layouts, logos, background images, video backgrounds, color palettes, and typography in real time.
- **Pre-Designed Templates:** Choose from sleek modern login templates or craft your own design.
- **Custom CSS Editor:** Global CSS editor with syntax verification and automated frontend inline injection.

---

## 🚀 Free vs. Pro Feature Comparison

| Feature | Free Version | Pro Version |
|---|:---:|:---:|
| Web Application Firewall (WAF) | ✅ 30+ Core Rules | ✅ Extended Early-Boot WAF |
| Deep Malware Scanner | ✅ Checksums & Rogue Files | ✅ Full Heuristics & Auto-Remediation |
| Threat Signature Daily Sync | ✅ Included | ✅ Included |
| Login Attempt Limiter & Lockout | ✅ Included | ✅ Included |
| Two-Factor Authentication (2FA / TOTP) | ✅ Free for All Roles | ✅ Remember-Device & Enforced Roles |
| Passkeys (WebAuthn / Touch ID / Face ID) | — | ✅ Full Passkey Support |
| Breached Password Check (HIBP) | — | ✅ Automated k-Anonymity Check |
| Password Strength Policy Enforcer | — | ✅ Role-based Complexity Enforcer |
| Anti-Spam (WooCommerce & Comments) | ✅ Honeypot & Time-Gate | ✅ Disposable Email & Rate Limits |
| CAPTCHA (Turnstile & reCAPTCHA) | ✅ Included | ✅ Included |
| Block Center (IP / CIDR Blacklist & Whitelist) | ✅ Included | ✅ 404 Auto-Defense & Flood Guard |
| Site Access Gate (Password & Admin Mode) | ✅ Included | ✅ GeoIP Country Restrictions |
| Server Hardening Tools & Security Headers | ✅ Included | ✅ Included |
| AI Crawler Blocker | ✅ Included | ✅ Included |
| CPU Monitor & Server Diagnostics | ✅ Core Diagnostics | ✅ Slow Query Traces & Cron Manager |
| Activity Audit Log & Email Alerts | ✅ Standard Logging | ✅ Weekly Digest & Bursts Alerts |
| Login & Password Page Customizer | ✅ Live Customizer | ✅ Pro Templates & Video Backgrounds |

---

## 🏗️ Installation & Quick Setup

### From WordPress Dashboard
1. Go to **Plugins → Add New**.
2. Search for **WPEPP** or **WPEPP Security**.
3. Click **Install Now**, then **Activate**.
4. Navigate to **WPEPP Security** in your admin sidebar.
5. Launch the **Setup Wizard** to configure recommended security settings in just five clicks!

### Manual via FTP or Git
1. Download or clone this repository into `/wp-content/plugins/wp-edit-password-protected/`.
2. Activate the plugin through the **Plugins** screen in WordPress.

---

## 🔒 Third-Party Services & Privacy Disclosures

To provide specific security features, WPEPP can optionally connect to the following external APIs:

1. **WordPress.org Checksum API** (`api.wordpress.org`)
   - **Purpose:** Verifies official WordPress core, plugin, and theme files against authentic hashes.
   - **Data Transmitted:** WordPress version, plugin slugs, and versions.
2. **Have I Been Pwned API** (`api.pwnedpasswords.com`)
   - **Purpose:** Checks passwords against billions of leaked credentials using the mathematical **k-Anonymity model**.
   - **Data Transmitted:** Only a 5-character prefix of the SHA-1 hash. Plaintext passwords and remaining hash fragments are NEVER transmitted or saved.
3. **IP Geolocation Service** (`ipapi.co`)
   - **Purpose:** Resolves visitor country codes for geo-blocking.
   - **Data Transmitted:** Visitor IP address.
4. **Threat Intelligence Feed** (GitHub Raw / WPEPP Security)
   - **Purpose:** Daily sync for updated malware heuristic signatures, webshell patterns, and exploit rules.
   - **Data Transmitted:** Standard HTTP GET request. No visitor or site data is transmitted.

---

## ❓ FAQ

<details>
<summary><strong>Will WPEPP slow down my website?</strong></summary>

No! WPEPP was designed specifically to address the performance overhead common in legacy security plugins. Anonymous visitor page views do not write to the database. Rate limit counters and login lockouts utilize fast transient caches. If you enable the Extended Firewall mode, malicious requests are blocked before WordPress even loads database connections or plugins.
</details>

<details>
<summary><strong>What makes WPEPP different from Wordfence or All In One WP Security?</strong></summary>

WPEPP combines enterprise-grade security (WAF, malware scanner, 2FA, passkeys, bot defense) with essential front-end utility (content locking, custom login page designer, site access gating) inside a single, feather-light React 18 dashboard. It does not clutter your database with perpetual tables or slow down your server with background pollers.
</details>

<details>
<summary><strong>Why does the CPU Monitor match my hosting cPanel better than other tools?</strong></summary>

Many plugins calculate CPU usage by dividing the entire shared server's load average by 1 core, creating alarming false readings of 90–100% Critical. WPEPP accurately reads `/sys/devices/system/cpu/online`, container limits, and procfs snapshots over rolling intervals, giving you a true, realistic reading that aligns with your hosting control panel.
</details>

<details>
<summary><strong>I locked myself out — how do I get back in?</strong></summary>

1. If enabled, click the **"Email me an unlock link"** button on the WordPress login screen to receive an instant bypass link at your administrator email.
2. If you have WP-CLI access, run `wp wpepp block remove <your-ip>`.
3. You can also temporarily rename the plugin directory via FTP/cPanel File Manager to regain access.
</details>

---

## 📝 Changelog

### 3.0.0
- **New Name:** WPEPP – All In One WordPress Security, Firewall, Malware Scanner & Login Protection.
- **Enterprise Web Application Firewall (WAF):** Added 30+ core inspection rules (SQLi, XSS, RCE, LFI, Traversal, Scanner agents) with Early-Boot Drop-in and Learning Mode.
- **Deep Malware Scanner:** Added official WordPress.org core & plugin checksum verification, rogue file detection in `/wp-admin/` and `/wp-includes/`, and 19 heuristic PHP webshell signatures.
- **Anti-Spam & Bot Shield:** WooCommerce registration & checkout bot protection, comment submission time-gate, invisible honeypots, disposable email blocker, and Cloudflare Turnstile / Google reCAPTCHA v2 & v3 integration.
- **Block Center:** IPv4 & IPv6 CIDR subnet blocking, 404-flood auto-defense, per-minute request rate limiting, and GeoIP country blocking.
- **Free Two-Factor Authentication:** TOTP 2FA unlocked for all user roles with local QR code generation and recovery backup codes.
- **Biometric Passkeys (WebAuthn / FIDO2) [Pro]:** Fingerprint and facial recognition login (Touch ID, Face ID, Windows Hello).
- **Breached Password Audit [Pro]:** Automated Have I Been Pwned check via k-anonymity model.
- **Accurate CPU Monitor:** Completely redesigned multi-core calculation engine reading `/sys/devices/system/cpu/online`, procfs, and container limits for realistic readings matching cPanel.
- **Security Hardening:** Block PHP in uploads, disable directory listing, disable plugin/theme file editor, security headers management, and critical file permissions inspector.
- **CVE-2025-8945 Full Fix:** Comprehensive REST API sanitization for password-protected and admin-only modes.
- **Massive Update Admin Notice:** Clean 28-day dismissible update banner for site administrators.

### 2.0.1
- Added CPU Monitor — real-time CPU usage, memory stats, load average, and system health overview.
- Added Slow Query Logger with configurable threshold and SAVEQUERIES toggle.
- Added Cron Jobs Manager — view, run, and delete cron events with overdue detection.
- Added Plugin Performance Monitor — view resource impact and deactivate plugins.
- Added Options Bloat Analyzer — inspect autoloaded options and clean expired transients.
- Added Error Log Viewer *(Pro)* — parse PHP/WordPress error log entries.
- Added WP Config Manager — toggle SAVEQUERIES, WP_DEBUG, WP_DEBUG_LOG from dashboard.

### 2.0.0
- Complete rebuild with React-powered admin dashboard.
- Added real-time live preview for all form and login page styling.
- Added login limiter, honeypot, registration honeypot, rate limiter.
- Added custom login URL — hide `wp-login.php`.
- Added AI crawler blocker (GPTBot, CCBot, Google-Extended, etc.).
- Added content lock — per-post, role-based, auto-expiry.
- Added conditional content display with 12 condition types.
- Added site access control — admin-only, login-required, site-wide password.
- Added member-only page template.
- Added templates gallery with 10+ designs.

---

## 📄 License

Licensed under the [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

---

*Built with ❤️ by [WPThemeSpace](https://wpthemespace.com)*
