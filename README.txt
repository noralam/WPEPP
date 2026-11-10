=== WPEPP – All In One WordPress Security, Firewall, Malware Scanner & Login Protection ===
Contributors: wpthemespace
Donate link: https://wpthemespace.com
Tags: security, firewall, malware scanner, login security, all in one security
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WPEPP is the complete All-In-One WordPress security suite: early-boot firewall, deep malware scanner, 2FA, passkeys, bot shield & login customizer.

== Description ==

**WPEPP** is a modern, high-performance **All-In-One WordPress Security Suite**. Built from the ground up for speed, reliability, and enterprise-grade defense, WPEPP protects your website against brute-force attacks, malware, webshells, SQL injection, cross-site scripting (XSS), zero-day exploits, comment spam, fake WooCommerce accounts, and malicious bots — **all without slowing your website down**.

### Zero-Impact Architecture: Why WPEPP Stands Out
Traditional security plugins often burden your database with unindexed log rows, load heavy scripts on every public page, and consume server CPU. 

WPEPP operates on a **Zero-Impact Philosophy**:
* **Blazing Fast Performance:** Anonymous page visits incur zero database writes. Rate limiting and counters utilize high-speed transient caches.
* **Early-Boot Extended Firewall:** Drops malicious payloads *before WordPress or your database loads*, saving server RAM and keeping your site responsive under heavy bot attacks.
* **Modern React Dashboard:** An intuitive, single-page admin interface built with WordPress components and zero jQuery legacy baggage.
* **100% Privacy & Data Control:** Your visitor logs, passwords, and security data stay inside your WordPress database. No external tracking or telemetry.

---

### 🛡️ 1. Web Application Firewall (WAF)
Defend your WordPress website in real time from malicious requests and exploit probes.
* **30+ Curated Core Rules:** Blocks SQL Injection (SQLi), Cross-Site Scripting (XSS), Remote Code Execution (RCE), Local File Inclusion (LFI), Path Traversal, PHP Object Injection, and known hacker scanner tools.
* **Early-Boot Extended Mode:** Automatically generates a lightweight drop-in filter that intercepts requests before WordPress loads. Includes an auto-failsafe mechanism (any unexpected bootstrap error automatically disables itself rather than breaking your site).
* **Learning Mode:** Monitor incoming traffic without blocking to safely review rule matches and prevent false positives before going live.
* **Cloudflare & Reverse Proxy Integration:** Automatically identifies verified Cloudflare IP ranges and reverse proxy headers (`CF-Connecting-IP`, `X-Forwarded-For`) so legitimate visitors are never misidentified.

### 🔍 2. Deep Malware & Vulnerability Scanner
Detect infections, unauthorized backdoors, and suspicious code before damage is done.
* **Official WordPress.org Core & Plugin Checksums:** Compares core files, themes, and plugins directly with official WordPress.org repository hashes to detect altered files.
* **Rogue & Unknown File Detection:** Instantly flags unexpected or rogue files inside critical folders like `/wp-admin/` and `/wp-includes/`.
* **19 High-Precision Heuristic Signatures:** Scans for dangerous PHP webshells (c99, r57, WSO), obfuscated code (`base64_decode`, `eval`, `gzinflate`), hidden backdoors, and eval execution.
* **Resumable Chunked Scans:** Background scanning engine operates in small, manageable batches to prevent timeouts on shared hosting and strict server execution limits.
* **Remote Threat Signature Sync:** Automatically syncs the latest threat definitions daily from a secure live signature repository.
* **Vulnerability & Outdated Plugin Audit:** Alerts administrators to unmaintained, abandoned, or vulnerable extensions.

### 🤖 3. Anti-Spam & Bot Shield
Eliminate automated spambots and fake customer accounts without annoying legitimate visitors.
* **WooCommerce Bot & Fake Account Shield:**
  - Invisible registration honeypot trap field.
  - Disposable & temporary email blocker (blocks 100+ throwaway email providers like Mailinator, TempMail, Yopmail).
  - Registration rate limiting to prevent botnets from flooding customer accounts.
  - One-click CAPTCHA enforcement on WooCommerce login, registration, and checkout.
* **WordPress Comment Spam Guard:**
  - Invisible honeypot trap stops automated spambots cold.
  - Submission Time-Gate (Speed Check) blocks automated bots submitting forms in under 3 seconds.
* **Integrated CAPTCHA Engine:** Seamlessly supports **Cloudflare Turnstile** (frictionless human verification) and **Google reCAPTCHA v2 / v3**.

### 🚫 4. Block Center & Advanced IP Defense
Take absolute control over who accesses your server.
* **IPv4 & IPv6 CIDR Support:** Block individual IPs or entire subnets using standard CIDR notation (e.g., `192.168.1.0/24`, `2001:db8::/32`).
* **404-Flood Auto-Blocking:** Automatically detects and blocks bots aggressively crawling for vulnerable paths (e.g., `wp-config.php.bak`, `phpmyadmin`, `.env`).
* **Intelligent Rate Limiting:** Enforces per-minute request thresholds per IP, keeping aggressive crawlers and scraper scrapers from exhausting server bandwidth.
* **Permanent & Temporary Blocks:** Set auto-expiring bans or permanent blacklists with customizable admin reasons.

### 🔐 5. Login Security, 2FA & Password Defense
Lock down your WordPress administration doors against brute-force attacks and credential stuffing.
* **Login Attempt Limiter & Lockout:** Limits failed password attempts with customizable retry limits and lockout durations.
* **Invisible Login Honeypot:** Traps automated login scripts while remaining completely invisible to genuine human users.
* **Masked Login Errors:** Replaces generic WordPress error messages with unified feedback, preventing hackers from discovering valid usernames.
* **Self-Service Unlock Email:** Legitimate site administrators can safely email themselves a one-time unlock link to regain instant access.
* **Username Discovery Shielding:** Blocks REST API author enumeration, `?author=N` URL queries, oEmbed author exposure, and author sitemaps.
* **Custom Login URL:** Conceal your `/wp-login.php` behind a secret custom slug with optional 404 or home redirect.
* **Two-Factor Authentication (2FA / TOTP):** Secure login with standard authenticator apps (Google Authenticator, Microsoft Authenticator, Authy, 1Password) with local QR code generation, recovery codes, and trusted-device cookies.
* **Passkeys (WebAuthn / FIDO2) [Pro]:** Experience biometric passwordless authentication using Apple Touch ID, Face ID, or Windows Hello.
* **Password Strength & Breached Password Check [Pro]:** Enforces strong password requirements and audits passwords against billions of breached credentials via Have I Been Pwned (using privacy-preserving k-anonymity).
* **Auto Login & Magic Links:** Generate secure, encrypted single-use login links for fast client access.

### 🛑 6. Site Access Gate & Content Protection
Control access to your entire website or specific private sections.
* **Site-Wide Password Protection:** Put the entire site behind a master password gate with customizable cookie expiration.
* **Admin-Only Mode:** Temporarily restrict access exclusively to logged-in administrators while performing maintenance.
* **Country-Based Access Restrictions:** Whitelist or blacklist access by country code using GeoIP lookups.
* **Complete REST API Enforcement:** Site-wide security gates are fully enforced on all public WordPress REST API endpoints, completely sanitizing post content, excerpts, and comments (fixes CVE-2025-8945).
* **Content Lock:** Protect individual posts and pages with custom passwords, user role restrictions, custom redirect URLs, and excerpt previews.
* **Conditional Content Display:** Show or hide content based on user roles, device types, dates, or day/time schedules.

### ⚡ 7. WordPress Hardening & Server Rules
Harden your server configuration with 1-click controls:
* **Block PHP Execution in Uploads:** Prevents executed PHP webshells inside `/wp-content/uploads/` via `.htaccess`.
* **Disable Directory Listing:** Appends `Options -Indexes` to prevent visitors from viewing folder file trees.
* **Disable File Editor:** Injects `DISALLOW_FILE_EDIT` into `wp-config.php` to prevent attackers from editing plugin/theme files from the admin.
* **Security Headers Management:** Enforce modern browser headers including `X-Frame-Options` (anti-clickjacking), `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Strict-Transport-Security` (HSTS), and `Permissions-Policy`.
* **Critical Files Permission Inspector:** Real-time diagnostics of `wp-config.php`, `.htaccess`, and root folder permissions.
* **AI Crawler & Scraper Blocker:** Blocks unauthorized AI training scrapers (OpenAI GPTBot, Anthropic ClaudeBot, CCBot, Google-Extended, ByteDance, etc.) via dynamic robots.txt rules.

### 📊 8. CPU Monitor, Performance & Activity Audit
Never wonder what is happening on your server.
* **Accurate CPU & Resource Monitor:** High-precision CPU utilization meter with multi-core container/shared host support (`/sys/devices/system/cpu/online`, `procfs`, `nproc`) and realistic rolling averages that match your cPanel dashboard.
* **PHP Memory & Health Status:** Real-time visibility into current vs. peak PHP memory consumption and WP memory limits.
* **Slow Database Query Monitor:** Captures queries exceeding execution thresholds along with execution time and full call stack.
* **Background Cron Job Health:** Pinpoints overdue or stalled WP-Cron schedules with 1-click execution.
* **Options & Transient Bloat Cleaner:** Clean expired transients and autoloaded bloat in seconds.
* **Activity Audit Log:** Comprehensive event stream capturing logins, lockouts, blocks, malware scans, and settings updates with filtering and pagination.
* **Email Delivery Status & Alerts:** Proactively detects active SMTP plugins and dispatches security alerts for critical findings, lockouts, and weekly digests.

### 🎨 9. Login Page & Form Customizer with Live Preview
Transform your login, registration, and password-protected screens into beautiful, branded experiences.
* **Live Interactive Preview:** Customize layouts, logos, background images, video backgrounds, color palettes, and typography in real time.
* **Pre-Designed Templates:** Choose from sleek modern login templates or craft your own design.
* **Custom CSS Editor:** Global CSS editor with syntax verification and automated frontend inline injection.

---

### 🚀 Free vs. Pro Feature Comparison

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

== Installation ==

1. Upload the `wp-edit-password-protected` folder to `/wp-content/plugins/`, or install directly via **Plugins → Add New** by searching for `WPEPP`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Navigate to **WPEPP Security** in your WordPress admin menu.
4. Launch the **Setup Wizard** to configure recommended security settings in just five clicks.

*(Optional)* If you have purchased WPEPP Pro, install the Pro add-on and enter your license key under **WPEPP Security → License**.

---

== Frequently Asked Questions ==

= Will this plugin slow my website down? =
No! WPEPP Security was engineered specifically to solve the performance issues found in legacy security plugins. Anonymous visitor page views do not write to the database. Rate limiting counters and login lockouts utilize fast transient caches. If you enable the Extended Firewall mode, malicious requests are blocked before WordPress even loads database connections or plugins, saving substantial server CPU and RAM.

= What makes WPEPP different from Wordfence or All In One WP Security? =
WPEPP combines enterprise-grade security (WAF, malware scanner, 2FA, passkeys, bot defense) with essential front-end utility (content locking, custom login page designer, site access gating) inside a single, feather-light React 18 dashboard. It does not clutter your database with perpetual tables or slow down your server with background pollers.

= Why does the CPU Monitor match my hosting cPanel better than other tools? =
Many plugins calculate CPU usage by dividing the entire shared server's load average by 1 core, creating alarming false readings of 90–100% Critical. WPEPP accurately reads `/sys/devices/system/cpu/online`, container limits, and procfs snapshots over rolling intervals, giving you a true, realistic reading that aligns with your hosting control panel.

= I locked myself out — how do I get back in? =
1. If enabled, click the **"Email me an unlock link"** button on the WordPress login screen to receive an instant, secure bypass link at your administrator email.
2. If you have WP-CLI access, run `wp wpepp block remove <your-ip>`.
3. You can also temporarily rename the plugin directory via FTP/cPanel File Manager to regain access.

= How does the Breached Password check protect my privacy? =
We use the mathematical **k-Anonymity** model implemented by Troy Hunt's *Have I Been Pwned* API. Only the first 5 characters of a SHA-1 hashed password are sent over HTTPS. Your plaintext password and the remainder of the hash are never transmitted over the internet or saved to disk.

= Is the Extended Firewall Mode safe? =
Yes. The auto-generated bootstrap script runs inside a failsafe try/catch wrapper. If any PHP syntax error, server misconfiguration, or environment conflict occurs, it safely detaches itself and allows your site to load normally rather than triggering a critical error or white screen.

= Does this plugin track my visitors? =
No. We respect privacy. All activity logs, IP blocklists, and security events remain strictly inside your own WordPress database. We do not sell, track, or share any visitor telemetry with third parties.

---

== Third-Party Services ==

This plugin can connect to external services to provide specific security features:

1. **WordPress.org Checksum API** (`api.wordpress.org`)
   * **Service:** Verifies core WordPress files and official repository plugins against trusted hashes.
   * **Data Transmitted:** WordPress version, plugin slugs, and versions.
   * **Usage:** Triggered during malware scans and checksum verification checks.
   * **Privacy Policy:** https://wordpress.org/about/privacy/

2. **Have I Been Pwned Passwords API** (`api.pwnedpasswords.com`)
   * **Service:** Checks whether submitted passwords have appeared in known data breaches.
   * **Data Transmitted:** Only a 5-character prefix of the password's SHA-1 hash (k-Anonymity). Plaintext passwords are NEVER sent.
   * **Usage:** Only when breached password checks are enabled on registration, reset, or profile update.
   * **Privacy Policy:** https://haveibeenpwned.com/Privacy

3. **IP Geolocation Service** (`ipapi.co`)
   * **Service:** Resolves visitor country codes.
   * **Data Transmitted:** Visitor IP address.
   * **Usage:** Only when Country-Based Access Restriction or Country Blocking is enabled.
   * **Privacy Policy:** https://ipapi.co/privacy/

4. **Threat Intelligence Feed** (GitHub Raw / WPEPP Security)
   * **Service:** Fetches updated malware heuristic signatures, webshell signatures, and exploit patterns.
   * **Data Transmitted:** No site or visitor information is transmitted; standard HTTP GET request for public definition files.
   * **Usage:** Daily background sync or manual click on "Check for Signature Updates".
   * **Privacy Policy:** https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement

---

== Screenshots ==

1. **Security Dashboard** — Real-time security score, fast setup wizard, and high-level protection metrics.
2. **Web Application Firewall (WAF)** — 30+ core exploit rules, learning mode, and early-boot extended firewall.
3. **Malware & Integrity Scanner** — Core/plugin checksums, heuristic webshell detection, and background runner.
4. **Anti-Spam & Bot Shield** — WooCommerce fake account blocker, comment honeypot, and time-gate.
5. **Login Protection & 2FA** — Brute-force rate limiting, lockout manager, TOTP two-factor auth, and Passkeys.
6. **Block Center** — IPv4/IPv6 CIDR subnet blocks, 404 auto-blocking, and rate-limiting buckets.
7. **Site Access Gate** — Site-wide password protection, maintenance mode, and GeoIP country rules.
8. **WordPress Hardening** — Uploads PHP execution killer, directory protection, and security headers.
9. **CPU & System Monitor** — Accurate server load diagnostics, slow database queries, and WP-Cron health.
10. **Customizer with Live Preview** — Brandable login screens and custom password form layouts.

---

== Changelog ==

= 3.0.0 =
* **Major Milestone Release** — Complete All-In-One WordPress Security Suite.
* **New:** Web Application Firewall (WAF) with 30+ exploit rules, learning mode, and early-boot extended filter.
* **New:** Deep Malware & Vulnerability Scanner with core/plugin checksums, 19 heuristic signatures, and chunked background runner.
* **New:** Remote malware threat signature sync engine with 1-click update.
* **New:** Anti-Spam & Bot Shield with WooCommerce fake account blocker and comment time-gate speed check.
* **New:** Universal CAPTCHA Engine supporting Cloudflare Turnstile and Google reCAPTCHA v2/v3.
* **New:** Passkeys (WebAuthn / FIDO2) biometric sign-in support (Pro).
* **New:** Password policy manager and Have I Been Pwned (HIBP) k-anonymity breached password verification (Pro).
* **New:** Block Center with IPv4 and IPv6 CIDR subnet support, 404-flood auto-defense, and sliding-window rate limits.
* **New:** Hardening suite (Security response headers, uploads PHP execution block, directory listing lock, `DISALLOW_FILE_EDIT`).
* **New:** Security Score dashboard, 5-click Setup Wizard, and automated presets.
* **New:** CPU & Server Monitor with accurate multi-core detection, slow database query logger, and WP-Cron audit.
* **New:** WP-CLI integration commands (`wp wpepp score|scan|block`).
* **New:** Settings backup and restore via secure JSON export/import.
* **Improved:** Accurate CPU usage calculation resolving 90–95% false critical spikes on shared/cPanel hosts.
* **Improved:** Real-time email delivery diagnostics with automatic SMTP detection.
* **Security:** Enforced site-wide access modes (Site Password, Admin Only, Country Restriction) over the WordPress REST API across all public post types (fixes CVE-2025-8945 residual; reported by Artus KG via WPScan/Automattic).

= 2.0.7 =
* Security: Hardened REST API site-wide access enforcement — eliminated query-string bypasses, removed unverified preview parameter exemptions, added pre-dispatch error guards, and extended metadata and comment scrubbing across all endpoints.

= 2.0.6 =
* Security: Enforced site-wide access modes (Site Password, Admin Only, Country Restriction) over the WordPress REST API across all public post types.

= 2.0.5 =
* CPU monitor improvements, AI crawler blocker, registration protection suite.

= 1.x–2.x =
* Password protection, member-only template, conditional display, live-preview customizer.

---

== Upgrade Notice ==

= 3.0.0 =
Major upgrade to the full All-In-One Security suite! Includes Firewall, Malware Scanner, Anti-Spam, 2FA, Passkeys, and Server Hardening. After upgrading, open WPEPP Security and run the 5-click Setup Wizard to apply recommended defenses.
