<?php
/**
 * Documentation page handler — WPEPP v3+ lightweight in-plugin documentation.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Docs
 */
class WPEPP_Docs {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_submenu' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register Documentation submenu page under WPEPP Security.
	 */
	public function add_submenu() {
		add_submenu_page(
			'wpepp-settings',
			__( 'Documentation', 'wp-edit-password-protected' ),
			__( 'Documentation', 'wp-edit-password-protected' ),
			'manage_options',
			'wpepp-docs',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Enqueue documentation assets on the docs screen only.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'wpepp-docs' ) ) {
			return;
		}

		wp_enqueue_style(
			'wpepp-docs',
			WPEPP_URL . '/assets/css/docs.css',
			[],
			WPEPP_VERSION
		);
	}

	/**
	 * Sidebar group headings mapping to section keys.
	 *
	 * @return array
	 */
	public function get_nav_groups() {
		return [
			[
				'label' => __( 'Start Here', 'wp-edit-password-protected' ),
				'items' => [ 'getting-started' ],
			],
			[
				'label' => __( 'Protect & Defend', 'wp-edit-password-protected' ),
				'items' => [ 'firewall', 'scanner', 'anti-spam', 'blocks', 'logins', 'site-access', 'hardening' ],
			],
			[
				'label' => __( 'Content & Design', 'wp-edit-password-protected' ),
				'items' => [ 'form-style', 'content-lock' ],
			],
			[
				'label' => __( 'System & Reference', 'wp-edit-password-protected' ),
				'items' => [ 'ai-crawler', 'activity', 'cpu-tools', 'settings', 'faq' ],
			],
		];
	}

	/**
	 * Get all v3+ documentation sections.
	 *
	 * @return array
	 */
	public function get_sections() {
		return [
			'getting-started' => $this->section_getting_started(),
			'firewall'        => $this->section_firewall(),
			'scanner'         => $this->section_scanner(),
			'anti-spam'       => $this->section_anti_spam(),
			'blocks'          => $this->section_blocks(),
			'logins'          => $this->section_logins(),
			'site-access'     => $this->section_site_access(),
			'hardening'       => $this->section_hardening(),
			'form-style'      => $this->section_form_style(),
			'content-lock'    => $this->section_content_lock(),
			'ai-crawler'      => $this->section_ai_crawler(),
			'activity'        => $this->section_activity(),
			'cpu-tools'       => $this->section_cpu_tools(),
			'settings'        => $this->section_settings(),
			'faq'             => $this->section_faq(),
		];
	}

	/**
	 * Section 1: Overview, Dashboard & Setup Wizard.
	 *
	 * @return array
	 */
	private function section_getting_started() {
		return [
			'icon'     => 'dashicons-flag',
			'title'    => __( 'Overview & Setup Wizard', 'wp-edit-password-protected' ),
			'tier'     => 'free',
			'location' => __( 'WPEPP Security → Dashboard', 'wp-edit-password-protected' ),
			'route'    => '#/',
			'intro'    => __( 'WPEPP v3+ combines an All-In-One security suite (WAF firewall, malware scanner, anti-spam shield, brute-force & 2FA login defense, server hardening, and activity telemetry) with the classic Password Form Customizer, Content Lock, and Login Page Designer. Every option is built to work safely out of the box without disrupting normal WordPress users.', 'wp-edit-password-protected' ),
			'callouts' => [
				[
					'type'  => 'tip',
					'title' => __( 'Recommended Day-1 Baseline', 'wp-edit-password-protected' ),
					'text'  => __( 'Start by running the 5-step Security Setup Wizard on the Dashboard. It turns on high-value, zero-friction protections (Login Limiter, Honeypots, Basic Firewall, Security Headers) while leaving disruptive options (Country Blocking, Site Password, Hide Login URL, HSTS, and AI Crawler Blocker) OFF until you deliberately choose them.', 'wp-edit-password-protected' ),
				],
			],
			'steps'    => [
				__( 'Go to WPEPP Security → Dashboard to view your live Security Score ring (0–100, Grade A–F) and 30-day event counters.', 'wp-edit-password-protected' ),
				__( 'Click Run Setup Wizard to apply safe baseline rules step-by-step without accidentally locking yourself out.', 'wp-edit-password-protected' ),
				__( 'If your site runs behind Cloudflare or a reverse proxy, visit Block Center → IP Lists immediately and add Trusted Proxies before turning on strict IP lockouts or Country Restriction.', 'wp-edit-password-protected' ),
				__( 'Open the Site Health Audit Report modal from the Dashboard to see exact failing checks and jump straight to each setting.', 'wp-edit-password-protected' ),
				__( 'Use the Dark/Light theme toggle in the top header bar anytime for comfortable admin reading.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Security Score & Checklist', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Always Active', 'wp-edit-password-protected' ),
					'how'     => __( 'Tracks weighted security controls across Firewall, Login Protection, Hardening, Scanner, and Anti-Spam. Local development hosts (localhost / .test / .local) are detected automatically so non-HTTPS local sites are not unfairly penalized.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not blindly enable advanced restrictions like HSTS Preload, Site Access Gate, or AI Crawler Blocker just to chase a higher score—only enable controls that match your real site requirements.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Security Setup Wizard (5 Steps)', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'On-Demand', 'wp-edit-password-protected' ),
					'how'     => __( 'Configures safe core defaults sequentially (login brute-force limiter, honeypots, spam shield, firewall basics, and recommended hardening) with a one-click Skip option per step.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not skip reviewing Block Center → IP Lists afterward if your site uses Cloudflare—proxy headers must be trusted for accurate attacker IP logging.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( '1-Click Site Health Audit Report', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'On-Demand', 'wp-edit-password-protected' ),
					'how'     => __( 'Generates a scannable summary inside the Dashboard showing passed checks, open risks, and direct links to fix each item.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Avoid running audit checks right before putting a site in Maintenance Mode—do audits in normal live/staging state for accurate header results.', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 2: Web Application Firewall (WAF).
	 *
	 * @return array
	 */
	private function section_firewall() {
		return [
			'icon'     => 'dashicons-shield',
			'title'    => __( 'Web Application Firewall (WAF)', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'WPEPP Security → Firewall', 'wp-edit-password-protected' ),
			'route'    => '#/firewall',
			'intro'    => __( 'Inspects incoming HTTP requests for SQL injection (SQLi), Cross-Site Scripting (XSS), path traversal, wp-config.php probing, and malicious bots before they can exploit WordPress or your plugins.', 'wp-edit-password-protected' ),
			'steps'    => [
				__( 'Enable Firewall (Basic Mode) to immediately filter suspicious requests inside WordPress initialization.', 'wp-edit-password-protected' ),
				__( 'On complex stores or custom headless apps, enable Learning Mode for 24–72 hours first to watch the Recent Matches log without blocking real visitors.', 'wp-edit-password-protected' ),
				__( 'Pro users can switch on Extended Protection (Early Boot) once host compatibility is confirmed for maximum pre-WordPress blocking speed.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Firewall Enabled (Basic Mode)', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Off / Wizard Opt-In', 'wp-edit-password-protected' ),
					'how'     => __( 'Enforces all built-in WAF inspection rules during WordPress runtime. Works reliably on every shared host, VPS, Apache, LiteSpeed, and Nginx server without server config changes.', 'wp-edit-password-protected' ),
					'dont'    => __( 'After enabling, quickly test complex page-builder saves (Elementor/Bricks) or custom code-snippet plugins—HTML/script payloads in POST fields can occasionally look like XSS if Learning Mode was skipped.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Learning Mode', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Evaluates all WAF rules and records matching requests inside Recent Matches without returning a 403 block. Ideal for auditing WooCommerce webhooks, REST integrations, or API tools.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do NOT leave Learning Mode turned on permanently in production! While Learning Mode is active, attacks are only logged—they are not blocked.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Extended Protection (Early-Boot Mode)', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'badge'   => 'pro',
					'default' => __( 'Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Configures PHP auto_prepend_file via .user.ini or .htaccess so attacks are rejected before WordPress core, database connections, or plugins load—saving CPU and memory under attack. Built-in auto-failsafe disables bootstrap if PHP fails.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do NOT enable without a fresh host backup on hosts that strictly lock .user.ini or disallow php_value in .htaccess. On pure Nginx + PHP-FPM without .user.ini support, configure auto_prepend_file at the pool level or stay on Basic Mode.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Live Rule Tester & Recent Matches', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'On-Demand', 'wp-edit-password-protected' ),
					'how'     => __( 'Simulate any URL path, HTTP method (GET/POST), User-Agent, and POST body in the Rule Tester to see which rule triggers and why. Review Recent Matches for matched rule ID, severity, and client IP.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not test live production URLs by sending real attack strings from your own admin IP if auto-blocking/lockouts are active unless your office IP is on the IP Allowlist.', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 3: Malware & Integrity Scanner.
	 *
	 * @return array
	 */
	private function section_scanner() {
		return [
			'icon'     => 'dashicons-search',
			'title'    => __( 'Malware & Integrity Scanner', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'WPEPP Security → Scanner', 'wp-edit-password-protected' ),
			'route'    => '#/scanner',
			'intro'    => __( 'Scans your WordPress installation in non-blocking slices across 4 stages: Suspicious Files & Malware Signatures, WordPress Core Checksums, Database Options Injection, and Admin/User Role Integrity.', 'wp-edit-password-protected' ),
			'callouts' => [
				[
					'type'  => 'danger',
					'title' => __( 'Verify Before Clicking "Delete File"', 'wp-edit-password-protected' ),
					'text'  => __( 'Heuristic rules can occasionally flag legitimate obfuscated third-party files, licensed themes, or cache helpers. Deleting a core theme or WooCommerce file directly can crash your site (WSOD). Always inspect the file path and snippet first, take a backup, and reinstall affected plugins from wordpress.org when confirmed.', 'wp-edit-password-protected' ),
				],
			],
			'steps'    => [
				__( 'Click Quick Scan for a fast baseline check on active code, core checksums, options, and users.', 'wp-edit-password-protected' ),
				__( 'Run Full Scan weekly (or immediately after any suspected breach) to inspect deeper directories and uploads.', 'wp-edit-password-protected' ),
				__( 'Review any flagged findings carefully—use Ignore for verified false positives, Resolve once cleaned, or Delete only on confirmed stray/backdoor files.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Quick Scan vs. Full Scan', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Manual Trigger', 'wp-edit-password-protected' ),
					'how'     => __( 'Quick Scan checks high-risk directories, core file checksums against official WordPress hashes, critical database options, and rogue admin users. Full Scan adds exhaustive traversal across plugin/theme/upload directories.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Avoid running Full Scan repeatedly on shared hosting with massive 50,000+ file media libraries during peak traffic—run Quick Scan daily and schedule Full Scan during off-peak hours.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Finding Actions (Resolve / Ignore / Delete)', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Per Finding', 'wp-edit-password-protected' ),
					'how'     => __( 'Read the built-in Fix Recommendation first. Click Ignore if you personally authored or verified the code; click Resolve after replacing an infected plugin from a clean source.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Never click Delete on wp-config.php, active theme functions.php, or core files—removing those takes the site offline.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Daily Automatic Scan', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Automatically runs background scan slices every 24 hours via WP-Cron and sends email alerts if critical malware or core tampering appears.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not rely on scheduled scans if DISABLE_WP_CRON is set to true without a real server-side crontab calling wp-cron.php.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Antivirus Definition Database & Signature Sync', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Built-In Rules Active', 'wp-edit-password-protected' ),
					'how'     => __( 'Displays total active malware signatures and lets you sync fresh signatures on demand. Built-in signatures stay active even if your host blocks outbound feed requests.', 'wp-edit-password-protected' ),
					'dont'    => __( 'You do not need to click Sync before every manual scan—local rules work immediately offline.', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 4: Anti-Spam & Universal CAPTCHA Engine.
	 *
	 * @return array
	 */
	private function section_anti_spam() {
		return [
			'icon'     => 'dashicons-filter',
			'title'    => __( 'Anti-Spam & Bot Shield', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'WPEPP Security → Anti-Spam', 'wp-edit-password-protected' ),
			'route'    => '#/anti-spam',
			'intro'    => __( 'Multi-layer bot defense covering WooCommerce registration, WordPress comments, login, and password-reset forms using zero-friction honeypots, timing checks, rate limiters, disposable email filtering, and CAPTCHA challenges.', 'wp-edit-password-protected' ),
			'steps'    => [
				__( 'Keep the Master Anti-Spam Shield toggle ON—all safe honeypot defaults run silently with zero UX friction for humans.', 'wp-edit-password-protected' ),
				__( 'Filter by category pills (WooCommerce Shield, Comment Spam, Login & Forms, CAPTCHA Engine) to fine-tune specific forms.', 'wp-edit-password-protected' ),
				__( 'In Pro, configure Cloudflare Turnstile or Google reCAPTCHA keys inside Universal CAPTCHA Engine and test with Test Mode ON before enforcing hard blocks.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'WooCommerce Registration Honeypot & Rate Limiter', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'On', 'wp-edit-password-protected' ),
					'how'     => __( 'Adds an invisible honeypot field to WooCommerce account/checkout signups and caps rapid signups from a single IP so fake customer floods are stopped automatically.', 'wp-edit-password-protected' ),
					'dont'    => __( 'If customers register from shared corporate/school Wi-Fi or behind unconfigured Cloudflare proxies, configure Block Center → Trusted Proxies first so legitimate users do not share one rate-limit bucket.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Block Disposable & Temp Emails on WooCommerce', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'On (Pro)', 'wp-edit-password-protected' ),
					'how'     => __( 'Rejects customer signups from 200+ temporary/burner inbox providers (Mailinator, Guerrilla Mail, YOPmail, etc.) to stop coupon abuse and fake accounts.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Temporarily disable if your QA team tests WooCommerce checkout with Mailinator/temp email addresses, or if privacy-focused buyers intentionally use burner aliases.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Comment Form Honeypot & <3s Time-Gate', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'On', 'wp-edit-password-protected' ),
					'how'     => __( 'Pairs an invisible trap field with a sub-3-second submission timer—human commenters take longer than 3 seconds to read and type, whereas automated spam scripts submit in milliseconds.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Turn off Time-Gate if a heavily customized AJAX comment plugin submits without printing the hidden timestamp field.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Login & Lost Password Honeypots', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'On', 'wp-edit-password-protected' ),
					'how'     => __( 'Traps automated credential-stuffing and password-reset flooding bots with invisible inputs on wp-login.php.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Safe for almost every site; only check compatibility if an aggressive browser extension auto-fills hidden non-standard fields.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Universal CAPTCHA Engine (Turnstile / reCAPTCHA v2 & v3)', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Choose Cloudflare Turnstile (recommended, frictionless), Google reCAPTCHA v2 (checkbox), or v3 (invisible 0.1–0.9 score threshold, default 0.5). Once keys are saved, enable CAPTCHA independently on Login, Registration, WooCommerce, Comments, or Lost Password.', 'wp-edit-password-protected' ),
					'dont'    => __( 'NEVER set reCAPTCHA v3 threshold too high (0.8–0.9) right away—privacy browsers and VPN users score lower and get blocked. Always enable CAPTCHA Test Mode (Do not block submissions) while verifying your keys!', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 5: Block Center & IP Management.
	 *
	 * @return array
	 */
	private function section_blocks() {
		return [
			'icon'     => 'dashicons-dismiss',
			'title'    => __( 'Block Center & IP Lists', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'WPEPP Security → Block Center', 'wp-edit-password-protected' ),
			'route'    => '#/blocks',
			'intro'    => __( 'Central control hub for inspecting automatic lockouts/404-flood bans, issuing manual IP/CIDR blocks, configuring Cloudflare/reverse proxy trust headers, and maintaining permanent IP Blocklists & Allowlists.', 'wp-edit-password-protected' ),
			'callouts' => [
				[
					'type'  => 'warn',
					'title' => __( 'Using Cloudflare, Load Balancers, or Nginx Proxy? Set Trusted Proxies First!', 'wp-edit-password-protected' ),
					'text'  => __( 'To prevent IP header spoofing, WPEPP only trusts X-Forwarded-For or CF-Connecting-IP when the direct peer (REMOTE_ADDR) is listed in Trusted Reverse Proxies. On Cloudflare sites, click "1-Click Add Cloudflare IP Ranges" inside IP Lists so attacks and Country Restrictions resolve the visitor\'s true IP instead of locking everyone behind one Cloudflare edge IP.', 'wp-edit-password-protected' ),
				],
			],
			'steps'    => [
				__( 'Visit Block Center → Blocks to review active temporary or permanent bans (shows type: Manual, Auto 404 flood, Rate limit, Login lockout, Country).', 'wp-edit-password-protected' ),
				__( 'To ban an attacker immediately, enter a single IP or CIDR range (e.g. 203.0.113.0/24), pick duration (1 hour, 24 hours, 7 days, Permanent), add an optional reason, and click Add Block.', 'wp-edit-password-protected' ),
				__( 'Open IP Lists to inspect your live REMOTE_ADDR vs. Resolved Client IP, configure Cloudflare/Proxy ranges, and maintain Pro Blocklists/Allowlists.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Manual IP / CIDR Blocks & Purge Expired', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'On-Demand', 'wp-edit-password-protected' ),
					'how'     => __( 'Block single IPs or subnet ranges temporarily (1h/24h/7d) or permanently, track hit counts per rule, and click Purge expired blocks anytime to tidy old bans.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Never enter massive ranges like /8 or /16 without verifying WHOIS ownership, and always check Your IP in IP Lists so you do not ban your own network.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Reverse Proxy & 1-Click Cloudflare IP Ranges', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Direct REMOTE_ADDR Only', 'wp-edit-password-protected' ),
					'how'     => __( 'Automatically detects Cloudflare traffic and populates official Cloudflare CIDRs into Trusted Reverse Proxies in one click (click Save Changes afterward to persist).', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do NOT add arbitrary public IPs or 0.0.0.0/0 to Trusted Proxies—doing so lets any attacker forge fake X-Forwarded-For headers and bypass IP blocks.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Permanent IP Blocklist', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Empty', 'wp-edit-password-protected' ),
					'how'     => __( 'Bulk list of IPs/CIDRs (one per line, # comments supported) returning immediate 403 Forbidden errors.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Avoid dumping thousands of unmaintained third-party IP feeds here—use WAF + Country Restriction for broad filtering.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'IP Allowlist', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Empty', 'wp-edit-password-protected' ),
					'how'     => __( 'Safelist static office, home, or VPN IPs/CIDRs so trusted team members and monitoring uptime bots bypass login lockouts and rate limits.', 'wp-edit-password-protected' ),
					'dont'    => __( 'NEVER add public coffee-shop Wi-Fi, shared cellular gateways, or Cloudflare/CDN proxy IPs into the Allowlist—everyone sharing that IP would bypass security!', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 6: Logins, Registration, Passwords, 2FA, Passkeys, Custom URL & Auto Login.
	 *
	 * @return array
	 */
	private function section_logins() {
		return [
			'icon'     => 'dashicons-lock',
			'title'    => __( 'Logins, 2FA, Passkeys & Auth', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'WPEPP Security → Logins', 'wp-edit-password-protected' ),
			'route'    => '#/logins/protection',
			'intro'    => __( 'Full authentication suite organized into 7 sub-tabs: Login Protection, Registration, Passwords, Two-Factor Auth, Passkeys, Custom Login URL, and Auto Login Magic Links.', 'wp-edit-password-protected' ),
			'callouts' => [
				[
					'type'  => 'danger',
					'title' => __( 'Custom Login URL & Hide wp-login.php Lockout Warning', 'wp-edit-password-protected' ),
					'text'  => __( 'Never enable "Hide wp-login.php" until you have already entered, saved, and bookmarked a Custom Login Slug AND excluded that slug from page caching plugins! If locked out, define WPEPP_SAFE_MODE = true in wp-config.php.', 'wp-edit-password-protected' ),
				],
			],
			'steps'    => [
				__( 'Under Login Protection, verify Login Limiter (default 5 attempts / 15 min) and Auto-Check "Remember Me" match your user policy.', 'wp-edit-password-protected' ),
				__( 'Enable Password Policies for new registrations and resets without breaking existing users.', 'wp-edit-password-protected' ),
				__( 'If masking the login endpoint, set and test Custom Login Slug first, then optionally toggle Hide wp-login.php.', 'wp-edit-password-protected' ),
				__( 'Pro users can enforce Role-Based 2FA (TOTP), register Passkeys over HTTPS, and issue temporary time-limited Auto Login links.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Login Limiter (Max Attempts & Lockout Duration)', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'On (5 tries / 15 min)', 'wp-edit-password-protected' ),
					'how'     => __( 'Temporarily locks out an IP address after repeated wrong passwords (configurable 1–20 attempts, 1–120 minutes). Automatically logs events and triggers lockout alerts.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not set Max Attempts to 1–2—normal users mistype passwords and lock themselves out. On Cloudflare, configure Trusted Proxies first.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Auto-Check "Remember Me"', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'On', 'wp-edit-password-protected' ),
					'how'     => __( 'Pre-selects WordPress\'s built-in "Remember Me" checkbox on wp-login.php so users keep their standard 14-day WordPress auth cookie across browser closes and computer restarts instead of logging in daily.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Turn this OFF on shared/public kiosk computers or sensitive client environments where closing the browser should strictly end the login session.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'WordPress Hardening (XML-RPC, WP Version, REST User Enumeration)', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'All 3 On', 'wp-edit-password-protected' ),
					'how'     => __( 'Blocks xmlrpc.php amplification attacks, strips the WP version generator tag from HTML source, and restricts /wp/v2/users for non-admin requests to stop username harvesting.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Disable "Disable XML-RPC" only if you actively rely on Jetpack or legacy mobile/desktop publishing apps. Disable REST User Restriction only if a custom frontend script requires anonymous access to author endpoints.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'After Login Redirect', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Default (wp-admin)', 'wp-edit-password-protected' ),
					'how'     => __( 'Send users to a custom dashboard or portal URL immediately after successful login.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not point to an access-restricted or 404 page—test the destination URL first, and leave blank on standard admin/WooCommerce sites using role-specific redirects.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Registration Protection (Rate Limit, Disposable Blocker, Domain Filter, Admin Approval)', 'wp-edit-password-protected' ),
					'tier'    => 'freemium',
					'default' => __( 'Honeypot On / Others Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Limits rapid signups per IP (Free, 1–20 signups / 5–1440 min). Pro adds Disposable Email Blocker, Whitelist/Blacklist Email Domain rules (one domain per line without @), and Require Admin Approval (approve under Users → All Users).', 'wp-edit-password-protected' ),
					'dont'    => __( 'Never select Email Domain "Whitelist" unless you strictly want ONLY those domains (it blocks Gmail/Outlook too!). Avoid Require Admin Approval on instant self-serve eCommerce shops.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Password Strength & Breach Policy', 'wp-edit-password-protected' ),
					'tier'    => 'freemium',
					'default' => __( 'Off (Breach Check Pro)', 'wp-edit-password-protected' ),
					'how'     => __( 'Enforces min length (6–64, default 12), uppercase+lowercase+number mix, optional special symbol, common weak-list rejection, and Pro k-anonymity check via api.pwnedpasswords.com (only 5 hash chars sent; fails open on outage).', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not enforce 20+ char symbol rules on casual WooCommerce customer accounts unless required—excessive friction increases cart/signup abandonment.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Two-Factor Authentication (TOTP App)', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Require 6-digit codes from Google Authenticator, Authy, or Microsoft Authenticator for selected roles, optionally remember trusted devices (Off or 7–90 days), and save one-time Recovery Codes immediately during QR setup.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do NOT select Subscriber/Customer roles unless your frontend users expect app-based 2FA setup in their profile, and ensure your server clock stays NTP-synced.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Passkeys (Fingerprint / Face ID / Hardware Key)', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Available on Login', 'wp-edit-password-protected' ),
					'how'     => __( 'Users register WebAuthn device passkeys ("MacBook Touch ID", Windows Hello, YubiKey) and click "Sign in with a passkey" on wp-login.php with no password required.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do NOT attempt passkey registration on plain HTTP staging URLs—browsers enforce HTTPS (or localhost only) for WebAuthn.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Custom Login URL & Hide wp-login.php', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Masks wp-login.php behind a clean secret slug (lowercase letters, digits, hyphens—e.g. team-portal) and optionally returns 404 for direct wp-login.php and logged-out wp-admin requests.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do NOT use generic slugs like "login" or "admin" if bots scan them, and NEVER enable Hide wp-login.php while Custom Login Slug is blank or page caching caches the custom login page.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Auto Login Magic Links', 'wp-edit-password-protected' ),
					'tier'    => 'freemium',
					'default' => __( 'On-Demand (3 Free)', 'wp-edit-password-protected' ),
					'how'     => __( 'Creates single-click login URLs for any user with optional custom redirect (up to 3 active links Free). Pro removes the cap and adds Expiration (1h–30d), Max Uses (1–100), and Role Restriction.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Treat magic links as full passwords! NEVER leave "Never expires" + unlimited Administrator links sitting in emails or chat logs—always set strict expiration + max uses, and delete immediately after support completes.', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 7: Site Access Gate, Site Password & Country Restriction.
	 *
	 * @return array
	 */
	private function section_site_access() {
		return [
			'icon'     => 'dashicons-admin-site',
			'title'    => __( 'Site Access & Geo Gate', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'WPEPP Security → Site Access', 'wp-edit-password-protected' ),
			'route'    => '#/site-access/admin-only',
			'intro'    => __( 'Whole-site visibility controls across three sub-tabs: Site Access Gate (login/role restriction), Site Password Protection (shared password screen), and Country Restriction (geo-blocking by visitor IP).', 'wp-edit-password-protected' ),
			'callouts' => [
				[
					'type'  => 'warn',
					'title' => __( 'Impact on SEO & Public Visitors', 'wp-edit-password-protected' ),
					'text'  => __( 'Enabling Site Access Gate or Site Password hides your ENTIRE frontend from anonymous users and search engine crawlers (Googlebot). Only use these on client staging sites, private portals, or pre-launch builds—never on active SEO blogs or public eCommerce stores.', 'wp-edit-password-protected' ),
				],
			],
			'steps'    => [
				__( 'Pick Site Access Gate to require WordPress accounts (All Logged-in Users or Administrators Only).', 'wp-edit-password-protected' ),
				__( 'Or choose Site Password when you want clients to unlock a staging site with a shared password without creating user accounts.', 'wp-edit-password-protected' ),
				__( 'Or configure Country Restriction to hide the site from specific countries (or restrict solely to target regions).', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Site Access Gate (Scope & Logged-Out Action)', 'wp-edit-password-protected' ),
					'tier'    => 'freemium',
					'default' => __( 'Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Choose between "All Logged-in Users (Members Only)" or "Administrators Only (manage_options)". Logged-out guests redirect to login (Free) or see a blurred Glassdoor Login Popup overlay (Pro) with customizable header & message.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not enable on live public stores or WooCommerce APIs/webhooks that expect unauthenticated frontend endpoints unless tested.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Site Password Protection', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Off (Cookie 7 days)', 'wp-edit-password-protected' ),
					'how'     => __( 'Requires a global password before showing any frontend page (must enter a password to activate). Customize message, Cookie Duration (1–365 days), and Logged-In User Bypass (Administrators always bypass).', 'wp-edit-password-protected' ),
					'dont'    => __( 'Avoid using aggressive full-page server/CDN caching (Cloudflare APO, Varnish) without excluding WPEPP unlock cookies—cached HTML may serve either the form to unlocked users or content to locked visitors.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Country Restriction (Block List vs. Allow List)', 'wp-edit-password-protected' ),
					'tier'    => 'freemium',
					'default' => __( 'Off (2 Countries Free / Unlimited Pro)', 'wp-edit-password-protected' ),
					'how'     => __( 'Resolves country via Cloudflare CF-IPCountry header when present, otherwise queries ipapi.co (sending visitor IP externally; administrators never blocked; fails open on lookup outage). Use Block List to hide from spam-heavy regions or Allow List for geo-locked businesses.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Be VERY careful with "Allow List" mode on SEO sites—major search crawlers operate mostly from US/EU datacenters and get blocked if their country is omitted. Remember to configure Trusted Proxies for Cloudflare first.', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 8: Hardening, File Locks, Headers & Diagnostics.
	 *
	 * @return array
	 */
	private function section_hardening() {
		return [
			'icon'     => 'dashicons-hammer',
			'title'    => __( 'Hardening & Response Headers', 'wp-edit-password-protected' ),
			'tier'     => 'free',
			'location' => __( 'WPEPP Security → Hardening', 'wp-edit-password-protected' ),
			'route'    => '#/hardening',
			'intro'    => __( 'Applies server-level filesystem protections, audits permissions on critical files like wp-config.php and .htaccess, manages browser security HTTP headers, and runs live configuration diagnostics.', 'wp-edit-password-protected' ),
			'callouts' => [
				[
					'type'  => 'danger',
					'title' => __( 'Handle HSTS & Preload With Extreme Care', 'wp-edit-password-protected' ),
					'text'  => __( 'Enabling HSTS tells visitors\' browsers to refuse HTTP forever for the max-age window. Enabling "preload" marks the domain for hardcoded browser lists (hstspreload.org) which takes months to reverse. NEVER turn on HSTS on HTTP sites, local dev, or domains with non-HTTPS subdomains.', 'wp-edit-password-protected' ),
				],
			],
			'steps'    => [
				__( 'Enable Block PHP in Uploads, Disable Directory Listing, and Disable File Editor under Server Tools.', 'wp-edit-password-protected' ),
				__( 'Check the Critical Files table to verify wp-config.php and .htaccess show Protected status.', 'wp-edit-password-protected' ),
				__( 'Keep default X-Frame-Options (SAMEORIGIN), Referrer-Policy, and nosniff active, then optionally test CSP Report-Only presets.', 'wp-edit-password-protected' ),
				__( 'Click Run diagnostics at the bottom to verify real live HTTP response headers returned by your server.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Server Hardening Tools (Uploads PHP Block, Dir Listing, File Editor)', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Toggleable', 'wp-edit-password-protected' ),
					'how'     => __( 'Writes Apache .htaccess rules blocking PHP inside wp-content/uploads and folder indexing (displays exact Nginx snippet when running Nginx), and sets DISALLOW_FILE_EDIT in wp-config.php so attackers cannot edit theme/plugin PHP inside wp-admin.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Only disable DISALLOW_FILE_EDIT temporarily if you intentionally rely on WordPress\'s built-in Appearance → Theme File Editor (use SFTP/Git instead for production safety).', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'X-Frame-Options & X-Content-Type-Options: nosniff', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'SAMEORIGIN & nosniff On', 'wp-edit-password-protected' ),
					'how'     => __( 'SAMEORIGIN stops external clickjacking while keeping Gutenberg/Customizer/Preview iframes working cleanly. nosniff prevents MIME-type confusion attacks.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Avoid switching X-Frame-Options to DENY if external apps, SaaS portals, or subdomains legitimately iframe your site pages.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Referrer-Policy & Permissions-Policy', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'strict-origin-when-cross-origin', 'wp-edit-password-protected' ),
					'how'     => __( 'Controls URL referrer leakage across origins and lets you restrict browser hardware APIs (e.g. camera=(), microphone=(), geolocation=()).', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not set Referrer-Policy to no-referrer if affiliate partners require origin referrers, and do not lock camera/microphone if your site embeds video calls or voice recorders.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Strict-Transport-Security (HSTS, includeSubDomains, preload)', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Forces HTTPS-only connections in modern browsers once your entire production domain has a valid TLS certificate.', 'wp-edit-password-protected' ),
					'dont'    => __( 'NEVER turn on over plain HTTP (WPEPP hides it on non-HTTPS). Never check includeSubDomains if staging/mail subdomains lack HTTPS, and never check preload unless committing permanently to hstspreload.org.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Content-Security-Policy (Report-Only) + Starter Templates', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Empty', 'wp-edit-password-protected' ),
					'how'     => __( 'Use one-click starter templates (Default site, Blog/ads, WooCommerce) in safe Report-Only mode—violations log in browser DevTools without breaking any scripts or checkout flows.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not paste random strict CSP policies blindly; always monitor DevTools console violations under WooCommerce/analytics plugins first.', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 9: Form Style & Templates Gallery.
	 *
	 * @return array
	 */
	private function section_form_style() {
		return [
			'icon'     => 'dashicons-admin-customizer',
			'title'    => __( 'Form Style & Templates', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'WPEPP Security → Form Style / Templates', 'wp-edit-password-protected' ),
			'route'    => '#/form-style/login',
			'intro'    => __( 'Visual live-preview design studio for WordPress\'s Login Form (Free), Register Form (Pro), Lost Password Form (Pro), and Password-Protected Post Form (Free + Pro layouts), paired with one-click preset Templates.', 'wp-edit-password-protected' ),
			'steps'    => [
				__( 'Start under Templates (Starter, Corporate, Creative, Elegant, Gradient Wave, Minimal, Modern Dark, Nature, Sunset, Tech Blue) to apply a cohesive preset in one click.', 'wp-edit-password-protected' ),
				__( 'Open Form Style → Login Form (or Password Protected Form) and adjust sections in the left accordion while watching the real iframe Live Preview update.', 'wp-edit-password-protected' ),
				__( 'Click Save Changes in the floating Save Bar; Pro users can also Export/Import design settings as JSON.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Page Background (Color / Gradient / Image / Video + Overlay)', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Solid Color (#f0f0f1)', 'wp-edit-password-protected' ),
					'how'     => __( 'Set brand solid colors, CSS gradients, Media Library images (position/size controls), or YouTube/Vimeo/MP4 background videos with optional tinted dark overlay and opacity.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Avoid heavy 10+ MB uncompressed videos/images on login pages (slows loading), and always add a dark/light background overlay when placing headings directly over busy photography.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Logo, Heading, Container, Inputs, Button & Links Panels', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'WordPress Defaults', 'wp-edit-password-protected' ),
					'how'     => __( 'Swap the WordPress logo for a custom image or typography logo (with optional badge background/padding/radius), customize form width/borders/inputs/buttons, and toggle Lost Password or Back to Site links.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not hide the "Lost your password?" link unless your users exclusively sign in via SSO, OAuth, or Passkeys—password-reset lockout support tickets will spike.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Register & Lost Password Form Styling', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'WordPress Defaults', 'wp-edit-password-protected' ),
					'how'     => __( 'Customize registration (?action=register) and lost-password (?action=lostpassword) screens with independent templates and visual branding matching your Login Form.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Ensure readable input text-to-background contrast on dark templates (like Elegant or Modern Dark) so error messages remain clear.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Password Protected Form Layouts & Social Icons', 'wp-edit-password-protected' ),
					'tier'    => 'freemium',
					'default' => __( 'Horizontal Layout', 'wp-edit-password-protected' ),
					'how'     => __( 'Customize WordPress password-protected post/page gates using Horizontal or Inline layouts (Free) or Vertical Card / Full Width (Pro), custom Top/Bottom text, error text & position, and 8 social icon links (top/bottom position, square/round styling, size & gap).', 'wp-edit-password-protected' ),
					'dont'    => __( 'Check mobile viewport width in Live Preview if adding long custom error messages or oversized logos on Horizontal/Inline layouts.', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 10: Content Lock, Conditional Display & Member Template.
	 *
	 * @return array
	 */
	private function section_content_lock() {
		return [
			'icon'     => 'dashicons-visibility',
			'title'    => __( 'Content Lock, Visibility & Member Template', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'Post/Page Editor & WPEPP Security → Content', 'wp-edit-password-protected' ),
			'route'    => '#/content/member-template',
			'intro'    => __( 'Granular post, page, and custom-post-type access rules controlled right inside the Gutenberg/Classic Editor, plus a dedicated Member-Only page template and central management tables.', 'wp-edit-password-protected' ),
			'callouts' => [
				[
					'type'  => 'tip',
					'title' => __( 'Where Controls Live (Editor vs. Plugin Dashboard)', 'wp-edit-password-protected' ),
					'text'  => __( 'To lock a post or assign conditional visibility rules, open that Post/Page in the WordPress Editor (Content Lock / Conditional Display sidebar panels or Classic metaboxes). Use WPEPP Security → Content to monitor locked posts, toggle rules centrally, and style the global Member Template.', 'wp-edit-password-protected' ),
				],
			],
			'steps'    => [
				__( 'Edit any Post/Page to enable Content Lock (Pro) or Conditional Display rules directly inside the editor.', 'wp-edit-password-protected' ),
				__( 'Or under Page Attributes → Template, pick "Member Only (Login Required)" to show a customized gate screen.', 'wp-edit-password-protected' ),
				__( 'Configure global Member Template copy/mode under WPEPP Security → Content → Member Template.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Per-Post Content Lock (Metabox + Central Table)', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Off per Post', 'wp-edit-password-protected' ),
					'how'     => __( 'Lock posts/pages/CPTs for logged-out guests or selected roles. Choose action: Show login link, Inline login form, Blur Popup login, or Redirect URL. Supports optional Auto-Unlock Expiry Date/Time and "Show excerpt on blog page" SEO teasers.', 'wp-edit-password-protected' ),
					'dont'    => __( 'NEVER lock Privacy Policy, Terms, Checkout, My Account, or Custom Login pages—and never set a locked page\'s Redirect URL back to itself (creates infinite redirect loops!).', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Conditional Display (12 Trigger Conditions)', 'wp-edit-password-protected' ),
					'tier'    => 'freemium',
					'default' => __( 'Off per Post', 'wp-edit-password-protected' ),
					'how'     => __( 'Show or hide post content by: Login Status (Logged In/Out), User Role, Device Type, Day of Week, Time of Day, Date Range, Recurring Schedule, Post Type, Browser Type, URL Parameter, or Referrer Source. Optionally hide Post Title, Featured Image, Comments, or display a custom fallback Notice. Central filterable dashboard is Pro.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Avoid relying on time/device/URL-param conditions if aggressive full-page disk/CDN caching serves identical static HTML to all visitors—configure cache exclusions or logged-in cookie bypass first.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Member-Only Page Template', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Manual Template Assign', 'wp-edit-password-protected' ),
					'how'     => __( 'Assign template "Member Only (Login Required)" on any Page. Customize Title, H1–H4 tag, body text/alignment, and Mode: Login (inline form or redirect button, Free), Info Only (notice without login box, Free), or Popup Glassdoor (Pro).', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not assign this template to your static Front Page or WooCommerce Shop page unless you intend to gate your main public entry.', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 11: AI Crawler Blocker.
	 *
	 * @return array
	 */
	private function section_ai_crawler() {
		return [
			'icon'     => 'dashicons-robot',
			'title'    => __( 'AI Crawler Blocker', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'WPEPP Security → AI Crawler Blocker', 'wp-edit-password-protected' ),
			'route'    => '#/ai-crawler',
			'intro'    => __( 'Controls whether AI training scrapers and AI search/answer engines (OpenAI GPTBot, ChatGPT-User, PerplexityBot, Anthropic ClaudeBot, Google-Extended, CCBot, Bytespider, etc.) may index your pages via robots.txt or active HTTP 403 User-Agent enforcement.', 'wp-edit-password-protected' ),
			'callouts' => [
				[
					'type'  => 'danger',
					'title' => __( 'Why This Feature Is OFF by Default (AI Search & Traffic Tradeoff)', 'wp-edit-password-protected' ),
					'text'  => __( 'Enabling AI Crawler Blocker stops bots from training on your site, BUT blocking search/citation crawlers (like OAI-SearchBot, ChatGPT-User, PerplexityBot, Google-Extended) also stops ChatGPT, Perplexity, and AI Overviews from recommending or linking to your articles and products! Leave OFF if you want AI discovery traffic.', 'wp-edit-password-protected' ),
				],
			],
			'steps'    => [
				__( 'Decide first whether you want AI visibility/recommendations (leave OFF) or strict anti-scraping protection (turn ON).', 'wp-edit-password-protected' ),
				__( 'Enable AI Crawler Blocker to automatically append Disallow: / directives for selected bots to WordPress\'s virtual robots.txt.', 'wp-edit-password-protected' ),
				__( 'Review the 21 known AI bots checklist—for balanced protection, uncheck AI search/citation bots (OAI-SearchBot, ChatGPT-User, PerplexityBot) while keeping dataset-only scrapers checked.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Enable AI Crawler Blocker (robots.txt Rules)', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Off (Intentional)', 'wp-edit-password-protected' ),
					'how'     => __( 'Injects clean User-agent Disallow: / sections into your live /robots.txt (includes a live preview panel inside the plugin). Polite crawlers obey immediately.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do NOT enable blindly on affiliate blogs, SaaS marketing pages, local businesses, or WooCommerce shops that want ChatGPT/Perplexity buyers to find and cite their brand.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Block via User-Agent Header (403 Response)', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Actively checks HTTP User-Agent on every frontend request and terminates matching bots with HTTP 403 Forbidden—stopping rogue scrapers that ignore robots.txt.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Avoid turning on unless aggressive AI scrapers are spiking your CPU/bandwidth—some legitimate social preview fetchers share generic tokens if misconfigured.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Select AI Bots (21 Built-In) & Custom User-Agent Strings', 'wp-edit-password-protected' ),
					'tier'    => 'freemium',
					'default' => __( 'All 21 Pre-Selected When Enabled', 'wp-edit-password-protected' ),
					'how'     => __( 'Individually check/uncheck 21 major AI bots or use Select All/Deselect All. Pro adds Custom User-Agent Strings (one case-insensitive substring per line when UA 403 blocking is active).', 'wp-edit-password-protected' ),
					'dont'    => __( 'NEVER enter short or common browser substrings (like "Mozilla", "Chrome", "Safari", or "Bot") into Custom User-Agents—substring matching would block real human visitors and Googlebot!', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 12: Activity Stream & Security Email Alerts.
	 *
	 * @return array
	 */
	private function section_activity() {
		return [
			'icon'     => 'dashicons-bell',
			'title'    => __( 'Activity Feed & Email Alerts', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'WPEPP Security → Activity', 'wp-edit-password-protected' ),
			'route'    => '#/activity',
			'intro'    => __( 'Unified security event feed with 7-day telemetry counters, event-type filters, live SMTP deliverability detection, one-click test alerts, and automated email notifications.', 'wp-edit-password-protected' ),
			'steps'    => [
				__( 'Inspect the Email Delivery Service banner at the top to see whether SMTP is active or if WordPress is falling back to unreliable PHP mail().', 'wp-edit-password-protected' ),
				__( 'Click Send Test Security Alert after entering an Alert Email Address to confirm messages actually reach your inbox (and not spam).', 'wp-edit-password-protected' ),
				__( 'Toggle only the alert categories you actively want to read and click Save Alert Settings.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Security Event Stream & 7-Day Filter', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'Active', 'wp-edit-password-protected' ),
					'how'     => __( 'Filter chronological security events (Logins, Failed logins, Lockouts, Firewall blocks/logs, Scan runs, Passkeys, Alerts, Settings changes) with IP, severity badge, and timestamps.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Combine with Database & Logs retention rules under Settings so busy sites do not hold unbounded months of old info logs.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Enable Alerts, Lockouts/Auto-Blocks & Scan Criticals', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'On (Throttled)', 'wp-edit-password-protected' ),
					'how'     => __( 'Sends emails to your custom address (or WP admin email) for critical scan findings and lockouts. Lockout emails are automatically rate-limited to at most 1 email per IP per 30 minutes to prevent inbox flooding.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not ignore a "PHP Mail Fallback" warning—many hosts silently drop PHP mail(); install a lightweight SMTP plugin for reliable delivery.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Admin Logins, Firewall Burst Digest & Weekly Report', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Get notified immediately when an Administrator logs in, receive digests during heavy firewall attack bursts, or receive automated weekly security summaries.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Avoid turning on Administrator Login emails on multi-admin agency portals where 5+ staff log in hourly—use Login Log instead to avoid alert fatigue.', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 13: CPU Monitor, Login Log & WP-CLI.
	 *
	 * @return array
	 */
	private function section_cpu_tools() {
		return [
			'icon'     => 'dashicons-performance',
			'title'    => __( 'CPU Monitor, Login Log & WP-CLI', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'WPEPP Security → Logs & Tools', 'wp-edit-password-protected' ),
			'route'    => '#/tools/cpu-monitor/overview',
			'intro'    => __( 'Real-time server diagnostics & performance suite (Overview, Slow Queries, Cron Jobs, Error Log, Plugin Performance, Options Bloat), dedicated Login Activity Log, and full WP-CLI terminal automation.', 'wp-edit-password-protected' ),
			'callouts' => [
				[
					'type'  => 'danger',
					'title' => __( 'Always Turn OFF "SAVEQUERIES" After Debugging!', 'wp-edit-password-protected' ),
					'text'  => __( 'Enabling Query Logging (SAVEQUERIES) inside CPU Monitor → Slow Queries records every SQL query + stack trace in RAM during every request. Leaving it turned on in live production significantly increases PHP memory usage and slows down your store.', 'wp-edit-password-protected' ),
				],
			],
			'steps'    => [
				__( 'Use CPU Monitor → Overview to check CPU %, PHP Memory limit/peak, load average, and health status.', 'wp-edit-password-protected' ),
				__( 'Inspect Options Bloat and run Clean Expired Transients whenever wp_options autoload size grows sluggish.', 'wp-edit-password-protected' ),
				__( 'During slow-site debugging, temporarily switch SAVEQUERIES or WP_DEBUG_LOG on, inspect findings, then switch both back OFF.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Overview & Slow Queries (SAVEQUERIES Toggle)', 'wp-edit-password-protected' ),
					'tier'    => 'freemium',
					'default' => __( 'SAVEQUERIES Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Shows live CPU/RAM metrics and slow SQL queries (Free shows last 10 queries; Pro reveals full PHP call stacks to pinpoint which plugin triggered slow SQL). One-click SAVEQUERIES wp-config toggle included.', 'wp-edit-password-protected' ),
					'dont'    => __( 'NEVER forget to toggle SAVEQUERIES back OFF after troubleshooting! Also requires writable wp-config.php.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'WP-Cron Events & Options Bloat (Clean Expired Transients)', 'wp-edit-password-protected' ),
					'tier'    => 'freemium',
					'default' => __( 'Read-Only Free / Actions Pro', 'wp-edit-password-protected' ),
					'how'     => __( 'Lists scheduled WP-Cron hooks (Pro lets you Run Now or Delete orphaned hooks) and displays wp_options table size & autoload size with a free 1-click Clean Expired Transients button.', 'wp-edit-password-protected' ),
					'dont'    => __( 'NEVER delete built-in WordPress cron hooks (wp_version_check, wp_privacy_delete_old_export_files, WooCommerce subscription/webhook jobs) unless you know a hook belongs to an uninstalled plugin.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Error Log & Plugin Performance Footprint', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Debug Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Toggle WP_DEBUG / WP_DEBUG_LOG and inspect parsed PHP fatal/warning entries directly inside admin, or audit active plugins and deactivate misbehaving plugins in one click.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Turn WP_DEBUG_LOG back off once debugging finishes so debug.log files do not fill server disk space. Do not deactivate essential active theme/eCommerce dependencies without checking.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Login Activity Log', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Enabled on Pro', 'wp-edit-password-protected' ),
					'how'     => __( 'Dedicated paginated history of every login success, failed password attempt, and blocked attempt with username, IP, and timestamp (with Enable toggle & Clear Log action).', 'wp-edit-password-protected' ),
					'dont'    => __( 'Keep retention at 30 days under Settings → Database & Logs on heavy sites so log rows never accumulate unnecessarily.', 'wp-edit-password-protected' ),
				],
			],
			'cli'      => "wp wpepp score\nwp wpepp scan start --full\nwp wpepp scan status\nwp wpepp block add 203.0.113.9 --reason=\"spam bot\" --duration=24h\nwp wpepp block list\nwp wpepp safe-mode enable",
		];
	}

	/**
	 * Section 14: Settings, Database Maintenance, Backup & Custom CSS.
	 *
	 * @return array
	 */
	private function section_settings() {
		return [
			'icon'     => 'dashicons-admin-settings',
			'title'    => __( 'Settings, DB Maintenance & Backups', 'wp-edit-password-protected' ),
			'tier'     => 'freemium',
			'location' => __( 'WPEPP Security → Settings', 'wp-edit-password-protected' ),
			'route'    => '#/settings/general',
			'intro'    => __( 'Global plugin configuration, password cookie expiration, uninstall cleanup policy, live database footprint diagnostics with automated pruning, JSON backup/restore, and sitewide Custom CSS.', 'wp-edit-password-protected' ),
			'steps'    => [
				__( 'Set Password Cookie Expiration (1–365 days, default 10) for password-protected posts under Settings → General.', 'wp-edit-password-protected' ),
				__( 'Check Settings → Database & Logs monthly (or enable Pro Automatic Daily Pruning) to keep log tables lean.', 'wp-edit-password-protected' ),
				__( 'Download a JSON Backup from Settings → Backup & Restore before making major firewall/server changes or migrating sites.', 'wp-edit-password-protected' ),
			],
			'options'  => [
				[
					'name'    => __( 'Password Cookie Expiration & Delete Data on Uninstall', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( '10 Days / Delete Off', 'wp-edit-password-protected' ),
					'how'     => __( 'Controls how many days unlocked post passwords stay valid in a visitor\'s browser cookie (1–365). Turn ON Delete Data on Uninstall only when permanently removing WPEPP and wanting zero leftover tables or options.', 'wp-edit-password-protected' ),
					'dont'    => __( 'NEVER enable Delete Data on Uninstall if you are only deactivating the plugin temporarily to troubleshoot—it wipes all saved settings, blocks, and form designs when deleted.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Database & Log Maintenance (Auto Pruning & Max Rows Cap)', 'wp-edit-password-protected' ),
					'tier'    => 'freemium',
					'default' => __( '30d/7d / 5,000 Rows Cap', 'wp-edit-password-protected' ),
					'how'     => __( 'Shows exact row counts and KB/MB footprint across Events, Logins, Slow Queries, and Blocks tables, with a Free "Clean Old Logs Now" button. Pro enables Automatic Daily Pruning, custom retention windows (7–90d events/logins, 3–30d queries), and Flood Protection cap (1,000–10,000 rows).', 'wp-edit-password-protected' ),
					'dont'    => __( 'Avoid setting 90 days + 10,000 rows on small shared MySQL plans with high bot traffic—30 days & 5,000 rows keeps dashboards snappy.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Backup & Restore (JSON Export / Import)', 'wp-edit-password-protected' ),
					'tier'    => 'free',
					'default' => __( 'On-Demand', 'wp-edit-password-protected' ),
					'how'     => __( 'Exports all plugin configuration sections cleanly to a timestamped .json file (schema wpepp/1) and imports into staging or client sites in one click.', 'wp-edit-password-protected' ),
					'dont'    => __( 'Note that API secrets (reCAPTCHA & Turnstile Secret Keys) are intentionally omitted from JSON exports for security—always re-enter your Secret Keys manually after importing on a new site.', 'wp-edit-password-protected' ),
				],
				[
					'name'    => __( 'Global Custom CSS', 'wp-edit-password-protected' ),
					'tier'    => 'pro',
					'default' => __( 'Empty', 'wp-edit-password-protected' ),
					'how'     => __( 'Inject custom CSS rules applying across all plugin front-end outputs (password forms, login screen, member-only template).', 'wp-edit-password-protected' ),
					'dont'    => __( 'Do not paste general theme header/footer layout CSS here—scope selectors to plugin containers so theme updates never conflict.', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Section 15: FAQ & Emergency Lockout Recovery.
	 *
	 * @return array
	 */
	private function section_faq() {
		return [
			'icon'     => 'dashicons-editor-help',
			'title'    => __( 'FAQ & Emergency Recovery', 'wp-edit-password-protected' ),
			'tier'     => 'free',
			'location' => __( 'Troubleshooting & Safe Mode', 'wp-edit-password-protected' ),
			'route'    => '#/',
			'intro'    => __( 'Quick answers to common configuration issues plus step-by-step Emergency Safe Mode instructions if a firewall rule, Custom Login URL, or IP block ever locks you out of wp-admin.', 'wp-edit-password-protected' ),
			'callouts' => [
				[
					'type'  => 'tip',
					'title' => __( '3 Ways to Trigger Emergency Safe Mode (Instant Bypass)', 'wp-edit-password-protected' ),
					'text'  => __( 'Safe Mode temporarily suspends WPEPP firewall checks, login lockouts, custom login hiding, and IP blocks without deactivating your settings. Use any of the three methods below, fix the setting inside wp-admin, and remove the constant/file immediately afterward.', 'wp-edit-password-protected' ),
				],
			],
			'cli'      => "// Method 1: Add to wp-config.php (remove once logged in!)\ndefine( 'WPEPP_SAFE_MODE', true );\n\n// Method 2: Upload an empty file via FTP/File Manager:\nwp-content/wpepp-safe-mode.txt\n\n// Method 3: SSH / WP-CLI:\nwp wpepp safe-mode enable\nwp wpepp safe-mode disable",
			'items'    => [
				[
					'q' => __( 'I forgot my Custom Login URL or got locked out by Hide wp-login.php / IP Block. How do I get back in?', 'wp-edit-password-protected' ),
					'a' => __( 'Trigger Emergency Safe Mode using any method above: add define( \'WPEPP_SAFE_MODE\', true ); in wp-config.php, create an empty wp-content/wpepp-safe-mode.txt file via FTP, or run "wp wpepp safe-mode enable" via WP-CLI. Visit wp-login.php normally, fix your slug or IP Allowlist, then remove Safe Mode immediately so protection resumes.', 'wp-edit-password-protected' ),
				],
				[
					'q' => __( 'Why does WordPress keep asking me to log in frequently after browser restart?', 'wp-edit-password-protected' ),
					'a' => __( 'Go to WPEPP Security → Logins → Login Protection and verify "Auto-Check Remember Me" is ON (it is enabled by default so users keep WordPress\'s normal 14-day cookie). If it is already ON and you still get logged out, verify another security plugin, browser privacy mode, or HTTP/HTTPS cookie mismatch is not clearing cookies on exit.', 'wp-edit-password-protected' ),
				],
				[
					'q' => __( 'Why did Login Limiter or Country Restriction lock out normal visitors when using Cloudflare?', 'wp-edit-password-protected' ),
					'a' => __( 'Without Trusted Reverse Proxies configured, WordPress sees Cloudflare\'s proxy IP instead of each visitor\'s real IP—causing all visitors to share one rate-limit counter. Open Block Center → IP Lists and click "1-Click Add Cloudflare IP Ranges", then click Save Changes.', 'wp-edit-password-protected' ),
				],
				[
					'q' => __( 'Why is AI Crawler Blocker disabled by default?', 'wp-edit-password-protected' ),
					'a' => __( 'Blocking AI search crawlers (ChatGPT-User, OAI-SearchBot, PerplexityBot) removes your site and products from AI citations and recommendations. WPEPP keeps it OFF by default to protect your AI organic traffic while giving site owners one-click control if strict anti-scraping is desired.', 'wp-edit-password-protected' ),
				],
				[
					'q' => __( 'My Login Form or Site Password page looks unstyled or shows stale locked state after unlocking.', 'wp-edit-password-protected' ),
					'a' => __( 'Full-page cache plugins or Cloudflare cache rules may serve old HTML. Clear your cache after styling changes, and ensure your caching plugin excludes wp-login.php, your Custom Login Slug, and requests carrying WPEPP site-password cookies.', 'wp-edit-password-protected' ),
				],
				[
					'q' => __( 'If I upgrade to Pro or temporarily deactivate the plugin, will I lose my settings?', 'wp-edit-password-protected' ),
					'a' => __( 'No. Deactivating or updating preserves all saved settings, custom designs, and content-lock meta values in WordPress. Data is removed ONLY if you explicitly check Settings → General → Delete Data on Uninstall and then delete the plugin.', 'wp-edit-password-protected' ),
				],
			],
		];
	}

	/**
	 * Render the documentation admin page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'wp-edit-password-protected' ) );
		}

		$sections = $this->get_sections();
		$groups   = $this->get_nav_groups();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only navigation parameter.
		$raw_tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'getting-started';
		$active   = isset( $sections[ $raw_tab ] ) ? $raw_tab : 'getting-started';
		$current  = $sections[ $active ];
		?>
		<div class="wrap wpepp-docs-wrap">
			<div class="wpepp-docs-header">
				<div class="wpepp-docs-header-content">
					<h1>
						<span class="dashicons dashicons-book-alt"></span>
						<?php
						printf(
							/* translators: %s: Plugin version */
							esc_html__( 'WPEPP Security Documentation (v%s)', 'wp-edit-password-protected' ),
							esc_html( WPEPP_VERSION )
						);
						?>
					</h1>
					<p class="wpepp-docs-subtitle">
						<?php esc_html_e( 'Complete option-by-option reference: how each security, hardening, and design setting works — plus when NOT to use it.', 'wp-edit-password-protected' ); ?>
					</p>
				</div>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpepp-settings' ) ); ?>" class="button button-primary wpepp-docs-back-btn">
					<span class="dashicons dashicons-arrow-left-alt"></span>
					<?php esc_html_e( 'Back to Dashboard', 'wp-edit-password-protected' ); ?>
				</a>
			</div>

			<div class="wpepp-docs-container">
				<?php $this->render_sidebar( $groups, $sections, $active ); ?>

				<div class="wpepp-docs-content">
					<?php
					$this->render_section( $current );
					$this->render_pager( $sections, $active );
					$this->render_quick_links();
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render grouped left-hand documentation navigation.
	 *
	 * @param array  $groups   Sidebar groups.
	 * @param array  $sections All sections.
	 * @param string $active   Active section slug.
	 */
	private function render_sidebar( $groups, $sections, $active ) {
		?>
		<nav class="wpepp-docs-sidebar" aria-label="<?php esc_attr_e( 'Documentation sections', 'wp-edit-password-protected' ); ?>">
			<?php foreach ( $groups as $group ) : ?>
				<div class="wpepp-docs-nav-group"><?php echo esc_html( $group['label'] ); ?></div>
				<?php
				foreach ( $group['items'] as $slug ) :
					if ( ! isset( $sections[ $slug ] ) ) {
						continue;
					}
					$sec      = $sections[ $slug ];
					$is_curr  = ( $active === $slug );
					$nav_href = admin_url( 'admin.php?page=wpepp-docs&tab=' . $slug );
					?>
					<a href="<?php echo esc_url( $nav_href ); ?>" class="wpepp-docs-nav-item<?php echo $is_curr ? ' active' : ''; ?>">
						<span class="dashicons <?php echo esc_attr( $sec['icon'] ); ?>"></span>
						<span class="wpepp-docs-nav-label"><?php echo esc_html( $sec['title'] ); ?></span>
						<?php if ( 'pro' === ( $sec['tier'] ?? '' ) ) : ?>
							<span class="wpepp-docs-pro-tag"><?php esc_html_e( 'Pro', 'wp-edit-password-protected' ); ?></span>
						<?php endif; ?>
					</a>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</nav>
		<?php
	}

	/**
	 * Render the currently active documentation section.
	 *
	 * @param array $current Active section data.
	 */
	private function render_section( $current ) {
		?>
		<div class="wpepp-docs-section">
			<div class="wpepp-docs-section-top">
				<div>
					<h2>
						<span class="dashicons <?php echo esc_attr( $current['icon'] ); ?>"></span>
						<?php echo esc_html( $current['title'] ); ?>
					</h2>
					<div class="wpepp-docs-meta-row">
						<?php if ( ! empty( $current['location'] ) ) : ?>
							<span class="wpepp-docs-path-pill">
								<span class="dashicons dashicons-admin-links"></span>
								<?php echo esc_html( $current['location'] ); ?>
							</span>
						<?php endif; ?>
						<?php echo wp_kses_post( $this->render_tier_badge( $current['tier'] ?? 'free' ) ); ?>
					</div>
				</div>

				<?php if ( ! empty( $current['route'] ) ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpepp-settings' . $current['route'] ) ); ?>" class="wpepp-docs-open-btn">
						<span><?php esc_html_e( 'Open Setting', 'wp-edit-password-protected' ); ?></span>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $current['intro'] ) ) : ?>
				<p class="wpepp-docs-intro"><?php echo esc_html( $current['intro'] ); ?></p>
			<?php endif; ?>

			<?php
			if ( ! empty( $current['callouts'] ) ) {
				foreach ( $current['callouts'] as $callout ) {
					$this->render_callout( $callout );
				}
			}

			if ( ! empty( $current['steps'] ) ) {
				$this->render_steps( $current['steps'] );
			}

			if ( ! empty( $current['options'] ) ) {
				$this->render_options( $current['options'] );
			}

			if ( ! empty( $current['cli'] ) ) {
				$this->render_cli_block( $current['cli'] );
			}

			if ( ! empty( $current['items'] ) ) {
				$this->render_faq( $current['items'] );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Build an HTML badge for Free / Free+Pro / Pro availability.
	 *
	 * @param string $tier Edition identifier ('free', 'freemium', 'pro').
	 * @return string
	 */
	private function render_tier_badge( $tier ) {
		switch ( $tier ) {
			case 'pro':
				return '<span class="wpepp-docs-badge wpepp-docs-badge--pro">' . esc_html__( 'Pro', 'wp-edit-password-protected' ) . '</span>';
			case 'freemium':
				return '<span class="wpepp-docs-badge wpepp-docs-badge--freemium">' . esc_html__( 'Free + Pro', 'wp-edit-password-protected' ) . '</span>';
			case 'free':
			default:
				return '<span class="wpepp-docs-badge wpepp-docs-badge--free">' . esc_html__( 'Free', 'wp-edit-password-protected' ) . '</span>';
		}
	}

	/**
	 * Render a tip, warning, or danger callout box.
	 *
	 * @param array $callout Callout config (`type`, `title`, `text`).
	 */
	private function render_callout( $callout ) {
		$type = $callout['type'] ?? 'tip';
		$map  = [
			'tip'    => [ 'class' => 'is-tip', 'icon' => 'dashicons-lightbulb' ],
			'warn'   => [ 'class' => 'is-warn', 'icon' => 'dashicons-warning' ],
			'danger' => [ 'class' => 'is-danger', 'icon' => 'dashicons-shield-alt' ],
		];
		$cfg = $map[ $type ] ?? $map['tip'];
		?>
		<div class="wpepp-docs-callout <?php echo esc_attr( $cfg['class'] ); ?>">
			<span class="dashicons <?php echo esc_attr( $cfg['icon'] ); ?>"></span>
			<div>
				<strong><?php echo esc_html( $callout['title'] ); ?></strong>
				<span><?php echo esc_html( $callout['text'] ); ?></span>
			</div>
		</div>
		<?php
	}

	/**
	 * Render quick workflow steps.
	 *
	 * @param array $steps List of step descriptions.
	 */
	private function render_steps( $steps ) {
		?>
		<div class="wpepp-docs-subhead">
			<span class="dashicons dashicons-editor-ol"></span>
			<span><?php esc_html_e( 'Quick Setup Workflow', 'wp-edit-password-protected' ); ?></span>
		</div>
		<ol class="wpepp-docs-steps">
			<?php foreach ( $steps as $step ) : ?>
				<li><?php echo wp_kses_post( $step ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}

	/**
	 * Render option-by-option cards showing How to Use vs When NOT to Use / Caution.
	 *
	 * @param array $options List of option entries.
	 */
	private function render_options( $options ) {
		?>
		<div class="wpepp-docs-subhead">
			<span class="dashicons dashicons-list-view"></span>
			<span><?php esc_html_e( 'Options Guide (How to Use vs. Caution)', 'wp-edit-password-protected' ); ?></span>
		</div>
		<div class="wpepp-docs-options-list">
			<?php foreach ( $options as $opt ) : ?>
				<div class="wpepp-docs-option-card">
					<div class="wpepp-docs-option-head">
						<h3><?php echo esc_html( $opt['name'] ); ?></h3>
						<div class="wpepp-docs-option-badges">
							<?php if ( ! empty( $opt['default'] ) ) : ?>
								<span class="wpepp-docs-badge wpepp-docs-badge--default">
									<?php
									printf(
										/* translators: %s: Default option value */
										esc_html__( 'Default: %s', 'wp-edit-password-protected' ),
										esc_html( $opt['default'] )
									);
									?>
								</span>
							<?php endif; ?>
							<?php echo wp_kses_post( $this->render_tier_badge( $opt['tier'] ?? 'free' ) ); ?>
						</div>
					</div>
					<div class="wpepp-docs-option-grid">
						<div class="wpepp-docs-option-box wpepp-docs-option-box--how">
							<div class="wpepp-docs-option-box-title">
								<span class="dashicons dashicons-yes-alt"></span>
								<span><?php esc_html_e( 'How to Use', 'wp-edit-password-protected' ); ?></span>
							</div>
							<p><?php echo wp_kses_post( $opt['how'] ); ?></p>
						</div>
						<div class="wpepp-docs-option-box wpepp-docs-option-box--dont">
							<div class="wpepp-docs-option-box-title">
								<span class="dashicons dashicons-warning"></span>
								<span><?php esc_html_e( 'When NOT to Use / Caution', 'wp-edit-password-protected' ); ?></span>
							</div>
							<p><?php echo wp_kses_post( $opt['dont'] ); ?></p>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render a terminal / code snippet block.
	 *
	 * @param string $snippet Plaintext commands or PHP snippet.
	 */
	private function render_cli_block( $snippet ) {
		?>
		<div class="wpepp-docs-subhead">
			<span class="dashicons dashicons-editor-code"></span>
			<span><?php esc_html_e( 'Commands & Snippets', 'wp-edit-password-protected' ); ?></span>
		</div>
		<pre class="wpepp-docs-cli-block"><?php echo esc_html( $snippet ); ?></pre>
		<?php
	}

	/**
	 * Render interactive FAQ accordion items.
	 *
	 * @param array $items FAQ items (`q` and `a`).
	 */
	private function render_faq( $items ) {
		?>
		<div class="wpepp-docs-subhead">
			<span class="dashicons dashicons-editor-help"></span>
			<span><?php esc_html_e( 'Frequently Asked Questions', 'wp-edit-password-protected' ); ?></span>
		</div>
		<div class="wpepp-docs-faq">
			<?php foreach ( $items as $item ) : ?>
				<details class="wpepp-docs-faq-item">
					<summary><?php echo esc_html( $item['q'] ); ?></summary>
					<p><?php echo wp_kses_post( $item['a'] ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render Previous / Next section navigation at bottom of active guide.
	 *
	 * @param array  $sections All sections.
	 * @param string $active   Current section key.
	 */
	private function render_pager( $sections, $active ) {
		$keys  = array_keys( $sections );
		$index = array_search( $active, $keys, true );
		if ( false === $index ) {
			return;
		}
		$prev_key = $index > 0 ? $keys[ $index - 1 ] : null;
		$next_key = isset( $keys[ $index + 1 ] ) ? $keys[ $index + 1 ] : null;
		?>
		<div class="wpepp-docs-pager">
			<?php if ( $prev_key ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpepp-docs&tab=' . $prev_key ) ); ?>" class="wpepp-docs-pager-link">
					<span class="dashicons dashicons-arrow-left-alt2"></span>
					<span><?php echo esc_html( $sections[ $prev_key ]['title'] ); ?></span>
				</a>
			<?php else : ?>
				<span></span>
			<?php endif; ?>

			<?php if ( $next_key ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpepp-docs&tab=' . $next_key ) ); ?>" class="wpepp-docs-pager-link">
					<span><?php echo esc_html( $sections[ $next_key ]['title'] ); ?></span>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</a>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render bottom Quick Links support card.
	 */
	private function render_quick_links() {
		?>
		<div class="wpepp-docs-card wpepp-docs-quick-links">
			<h3>
				<span class="dashicons dashicons-admin-links"></span>
				<?php esc_html_e( 'Quick Links & Resources', 'wp-edit-password-protected' ); ?>
			</h3>
			<div class="wpepp-docs-links-grid">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpepp-settings' ) ); ?>" class="wpepp-docs-link-box">
					<span class="dashicons dashicons-dashboard"></span>
					<strong><?php esc_html_e( 'Security Dashboard', 'wp-edit-password-protected' ); ?></strong>
					<span><?php esc_html_e( 'View score ring & run Setup Wizard', 'wp-edit-password-protected' ); ?></span>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpepp-docs&tab=faq' ) ); ?>" class="wpepp-docs-link-box">
					<span class="dashicons dashicons-shield-alt"></span>
					<strong><?php esc_html_e( 'Safe Mode Recovery', 'wp-edit-password-protected' ); ?></strong>
					<span><?php esc_html_e( 'Bypass lockouts in 60 seconds', 'wp-edit-password-protected' ); ?></span>
				</a>
				<a href="https://wordpress.org/support/plugin/wp-edit-password-protected/" target="_blank" rel="noopener noreferrer" class="wpepp-docs-link-box">
					<span class="dashicons dashicons-sos"></span>
					<strong><?php esc_html_e( 'Support Forum', 'wp-edit-password-protected' ); ?></strong>
					<span><?php esc_html_e( 'Ask questions on WordPress.org', 'wp-edit-password-protected' ); ?></span>
				</a>
				<?php if ( ! wpepp_has_pro_check() ) : ?>
					<a href="https://wpthemespace.com/product/wpepp-essential-security-password-protect-login-page-customizer/#pricing" target="_blank" rel="noopener noreferrer" class="wpepp-docs-link-box wpepp-docs-link-pro">
						<span class="dashicons dashicons-star-filled"></span>
						<strong><?php esc_html_e( 'Upgrade to Pro', 'wp-edit-password-protected' ); ?></strong>
						<span><?php esc_html_e( 'Unlock Extended WAF, 2FA, Passkeys & Content Lock', 'wp-edit-password-protected' ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}
