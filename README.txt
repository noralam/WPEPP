=== WPEPP – Essential Security, Password Protect & Login Page Customizer ===
Contributors: wpthemespace
Tags: security, firewall, password protect, login security, two factor
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 3.0.0
License: GPLv2 or later

All-in-one WordPress security: firewall, malware scanner, brute-force protection, passkeys, 2FA, password policy, blocks & monitoring.

== Description ==

WPEPP Security turns the classic password-protect plugin into a complete, modern security suite — without slowing your site down.

**Zero-impact philosophy:** on pages that need nothing from us, we load nothing. No global CSS, no icon fonts, no per-hit database writes.

= Protect =
* **Firewall** — 30+ curated rules against SQLi, XSS, RCE, LFI, webshells and known scanner tooling. Basic mode runs inside WordPress; opt-in Extended mode filters requests *before WordPress loads* via an auto-generated drop-in that fails safe (any error disables itself instead of breaking your site). Learning mode logs matches so you can tune before blocking.
* **Malware Scanner** — official checksums for core, plugins and themes; unknown-file detection inside wp-admin/wp-includes; 19 high-precision heuristic signatures; resumable chunked scans that survive shared hosting.
* **Block Center** — manual + automatic IP/CIDR blocks (IPv4 & IPv6), 404-flood auto-blocking, rate limiting with per-minute buckets and zero DB writes on hot paths.
* **Login Protection** — attempt limiter with lockouts, honeypots, self-service unlock email, username-discovery shielding (?author=N, oEmbed, sitemaps), masked login errors.
* **Two-Factor Auth** — TOTP with recovery codes, role enforcement, trusted-device cookies.
* **Passkeys (Pro)** — real WebAuthn: fingerprint/face/PIN sign-in on the login form.
* **Site Access** — site-wide password, admin-only mode, country restrictions.

= Monitor =
* **Activity** event stream (logins, lockouts, blocks, firewall matches, scan results, settings changes).
* **Email alerts** with anti-flood guards: lockouts, admin logins (Pro), firewall bursts (Pro), weekly activity report (Pro).

= Harden =
* Security response headers manager (X-Frame-Options, nosniff, Referrer-Policy, HSTS, Permissions-Policy, CSP report-only templates).
* One-click server tools: block PHP execution in uploads, disable directory listing, DISALLOW_FILE_EDIT writer.
* Diagnostics: environment, connectivity, cron health, filesystem checks.

Plus: content lock & conditional display, login/register/password-form customizer with live preview, CPU monitor, WP-CLI (`wp wpepp score|scan|block`), settings import/export.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install from Plugins → Add New.
2. Activate, then open **WPEPP Security** in the admin menu.
3. Run the Setup Wizard for safe defaults in five clicks.

For Pro features install **WPEPP Pro** alongside and activate your license under WPEPP Security → License.

== Frequently Asked Questions ==

= Will it slow my site down? =
No. On anonymous page views with all modules on, added cost is ~2 queries. Live traffic is off by default. Rate-limit counters use the object cache, not the database. The firewall's Extended mode makes its decision before WordPress even loads.

= I locked myself out — how do I get back in? =
Use the "Email me an unlock link" form on the login screen (sends to your admin address), or run `wp wpepp block remove <id>` / delete rows from the Block Center as another admin.

= What data do you store about visitors? =
IP addresses and user-agent strings for login attempts, blocks and events only — retention is configurable (30 days default for events) and everything is deleted on uninstall when you enable that option. No third-party tracking of your visitors.

= Does the breached-password check send my password anywhere? =
Never. Only a 5-character prefix of the SHA-1 hash is sent to api.pwnedpasswords.com; matching happens locally. If the service is unreachable the check fails open.

= Is Extended firewall mode safe? =
The generated bootstrap wraps everything in try/catch: if anything goes wrong it renames itself and your site keeps running unprotected rather than white-screening.

== Third-Party Services ==

This plugin may connect to external third-party services to provide security and integrity checks:

1. WordPress.org API (api.wordpress.org)
* Service: WordPress core and plugin checksum verification.
* Data Sent: WordPress core version, installed plugin slugs and versions, site locale.
* When Used: During scheduled or manual malware scans and system diagnostic connectivity checks.
* Privacy Policy: https://wordpress.org/about/privacy/

2. Have I Been Pwned Passwords API (api.pwnedpasswords.com)
* Service: Breached password verification using the mathematical k-Anonymity privacy model.
* Data Sent: Only the first 5 hexadecimal characters of the SHA-1 hash of the password. Your plaintext password and the remainder of the hash are NEVER transmitted over the network.
* When Used: When breached password checking is enabled and a password is submitted at registration, password reset, or profile update.
* Privacy Policy: https://haveibeenpwned.com/Privacy

3. IP Geolocation API (ipapi.co)
* Service: Geolocation data to determine visitor country for access rules.
* Data Sent: Visitor IP address.
* When Used: Only when country-based access restrictions or country blocking is enabled.
* Privacy Policy: https://ipapi.co/privacy/

4. Malware Signature Threat Feed (GitHub Raw / WPEPP Security)
* Service: Synchronizes updated malware signatures and webshell detection patterns for the scanner.
* Data Sent: No site or visitor data is sent. Only a standard HTTP GET request to fetch public signature definitions.
* When Used: Automatically once daily during background scans or on-demand when the administrator clicks "Check for Signature Updates".
* Privacy Policy: https://docs.github.com/en/site-policy/privacy-policies/github-general-privacy-statement


== Changelog ==

= 3.0.0 =
Major release — full security suite.

* New: Firewall with rule engine, learning mode and optional early-boot extended mode.
* New: Malware/integrity scanner (core, plugin, theme checksums + heuristics) with resumable chunked runner.
* New: Vulnerability detection for outdated or abandoned plugins/themes.
* New: Block Center with IPv4/IPv6 CIDR support, rate limiting, 404-flood auto-blocks.
* New: Passkeys/WebAuthn sign-in (Pro module).
* New: Password strength policy + breached-password k-anonymity check (Pro).
* New: Unified CAPTCHA layer — reCAPTCHA v2/v3 and Cloudflare Turnstile.
* New: Activity event stream, email alert matrix, weekly report (Pro).
* New: Hardening tools (headers, uploads PHP block, directory listing, DISALLOW_FILE_EDIT) + diagnostics.
* New: Security score dashboard, setup wizard, setup presets.
* New: WP-CLI commands (`wp wpepp score|scan|block`), settings import/export.
* Improved: Trusted-proxy-aware IP resolution across all modules; country blocking no longer spoofable.
* Improved: 2FA moved to free tier; remember-device cookies; local QR rendering (no external service).
* Security: Enforced site-wide access modes (Site Password, Admin Only, Country Restriction) over the WordPress REST API across all public post types, with pre-dispatch guards and full metadata/comment scrubbing (fixes CVE-2025-8945 residual; reported by Artus KG via WPScan/Automattic).
* Fixed: Login-page hiding no longer bypassable via POST; secrets never returned by REST API; site-password hashing; uninstall completeness; WP 6.0–6.1 compatibility (%i removal); cache TTLs; unpruned log tables.

= 2.0.7 =
* Security: Hardened REST API site-wide access enforcement — eliminated query-string bypasses, removed unverified preview parameter exemptions, added pre-dispatch error guards, and extended metadata and comment scrubbing across all endpoints (fixes CVE-2025-8945 residual; reported by Artus KG via WPScan/Automattic).

= 2.0.6 =
* Security: Enforced site-wide access modes (Site Password, Admin Only, Country Restriction) over the WordPress REST API across all public post types (fixes CVE-2025-8945 residual; reported by Artus KG via WPScan/Automattic).

= 2.0.5 =
CPU monitor improvements, AI crawler blocker, registration protection suite.

= 1.x–2.x =
Password protection, member-only template, conditional display, live-preview customizer.

== Upgrade Notice ==

= 3.0.0 =
Biggest update ever: firewall, scanner, passkeys and a full security dashboard. Run the new Setup Wizard after upgrading.
