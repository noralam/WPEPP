# WPEPP Security — All-in-One Security Plugin Roadmap

**Goal:** Transform WPEPP into a modern, accurate, error-free all-in-one WordPress security plugin. Benchmark: Wordfence 9.x (analyzed in depth). Positioning: lighter, faster, modern-UI alternative — not a Wordfence clone.

**Version target:** v3.0 (major). Current: v2.0.5.

---

## Part 1 — Current State Audit (v2.0.5)

### What the plugin ALREADY has (keep + upgrade)

| Area | Feature | Status | Quality |
|------|---------|--------|---------|
| Content protection | Post password form customizer (4 styles) | ✅ Solid | Good |
| Content protection | Site-wide password | ✅ Works | ⚠️ Plaintext compare |
| Content protection | Content Lock per post/page (Pro) | ✅ Works | ⚠️ Heavy archive query |
| Content protection | Conditional display (12 conditions) | ✅ Works | OK |
| Content protection | Member-only template | ✅ Works | OK |
| Login UX | Login/Register/Lost-password customizer w/ live preview | ✅ Solid | Good |
| Login security | Login attempt limiter (IP-based, DB table) | ✅ Free | ⚠️ Proxy IP issue |
| Login security | Honeypot (login + register) | ✅ Free | OK |
| Login security | reCAPTCHA v2 login+register (Pro) | ✅ Works | ⚠️ Register fails open |
| Login security | 2FA TOTP + recovery codes (Pro) | ✅ Strong impl | ⚠️ QR via external service |
| Login security | Custom login URL slug (Pro) | ✅ Works | ⚠️ POST bypass |
| Login security | Hide login page for logged-out (Free) | ✅ Works | Same bypass |
| Login security | Auto-login links w/ rate limit | ✅ Works | OK |
| Access control | Admin-only mode | ✅ Works | OK |
| Access control | Country block/allow (≤2 free) | ✅ Works | ❌ Spoofable header, fails open, blocking HTTP call in render path |
| Access control | IP allow/block lists w/ CIDR (Pro) | ✅ IPv4 only | Needs IPv6 |
| Registration | Rate limit, disposable email block, domain lists, admin approval | ✅ Mostly Pro | OK |
| Hardening | Disable XML-RPC | ✅ Free | OK |
| Hardening | Hide WP version | ✅ Free | OK |
| Hardening | Disable REST user enumeration | ✅ Free | ⚠️ Breaks /users/me |
| Anti-AI | AI crawler blocker (robots.txt + UA block, 21 bots) | ✅ Free | ⚠️ UA-only, per-hit DB write |
| Logging | Login log table `{prefix}wpepp_login_log` | ✅ Written free | ⚠️ Never pruned, stale cache, 'lockout' never inserted |
| Performance | CPU Monitor suite (slow queries, cron, options bloat, wp-config toggles) | ✅ Unique differentiator | OK |
| Admin UI | React SPA, tabbed live preview, REST `wpepp/v1` | ✅ Modern | Good foundation |

### What is MISSING vs Wordfence (the real gap)

1. **Firewall (WAF)** — request filtering before WP fully loads. Biggest gap.
2. **Scanner** — core/plugin/theme integrity check, malware heuristics, unknown-file detection.
3. **Vulnerability detection** — outdated/vulnerable plugin & theme alerts (wp.org API).
4. **File change monitoring** — real-time modified/added/deleted file tracking.
5. **Activity/event log** — beyond login: content changes, user changes, settings changes, blocked attacks.
6. **Live traffic** — lightweight request log with bot/human classification.
7. **Advanced blocking** — 404-flood auto-block, rate throttling (views/404s), pattern blocks, hostname/UA/referrer rules.
8. **Country blocking done right** — local GeoLite2-style lookup or WAF-level, never in render path, never spoofable.
9. **Passkeys/WebAuthn** — modern passwordless 2FA.
10. **Breached-password protection** — block known-compromised passwords on login/change.
11. **Strong password policy enforcement** — by role.
12. **Username shielding** — block `?author=N`, oEmbed author, REST/sitemap user discovery, mask login errors.
13. **Application passwords control** — disable per role/site.
14. **Security headers** — CSP-lite, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, etc.
15. **Alerts** — email on admin login, lockouts, scan findings, file changes; scheduled activity report.
16. **Security dashboard with score** — graded checklist driving users through setup (Wordfence's biggest conversion tool).
17. **Self-service unlock email** — locked-out admin recovery without support ticket.
18. **Scheduled scans** — daily/weekly profiles (quick/full).
19. **Import/export settings**.
20. **Diagnostics page** — env, connectivity, cron, filesystem checks.

### Bugs found in current code (MUST fix in Phase 0)

Severity-ranked, from line-level audit:

| # | Severity | Issue | Location |
|---|----------|-------|----------|
| B1 | CRITICAL | Country gate trusts `HTTP_CF_IPCOUNTRY` unconditionally — attacker spoofs header to bypass geo-block | `class-geo.php:49-55` |
| B2 | CRITICAL | Behind reverse proxy with default empty trusted-proxy list, all visitors share proxy IP → one attacker locks out entire site (limiter keyed only on REMOTE_ADDR-derived IP) | `class-security.php:371-394` |
| B3 | HIGH | TOTP secret sent to third-party `api.qrserver.com` in URL — secret disclosure | `class-totp.php:153-155` |
| B4 | HIGH | Site-wide password stored and compared as plaintext, non-timing-safe `===` | `class-site-access.php:459`, `class-ajax-password.php:103` |
| B5 | HIGH | `GET /settings` returns `recaptcha_secret_key` + plaintext site password into browser JS data | `class-rest-api.php:357-369` |
| B6 | HIGH | Hide-login/custom-login leaves POST to wp-login.php open → endpoint still brute-forceable; "hidden" claim void | `class-security.php:589-605, 699-707` |
| B7 | HIGH | reCAPTCHA registration verify fails OPEN on network error (login side correctly fails closed) | `class-security.php:1046` |
| B8 | MEDIUM | `%i` SQL placeholder requires WP 6.2 but header declares `Requires at least: 6.0` → broken queries/fatals on 6.0–6.1 | `class-security.php:263`, `class-rest-api.php:952+`, `class-cpu-query-monitor.php:54` |
| B9 | MEDIUM | Uninstall deletes wrong meta keys (`_wpepp_content_lock`, `_wpepp_conditional_display`) and misses options (`wpepp_site_access_settings`, `wpepp_auto_login_links`, counters) → orphaned data | `uninstall.php:20-46` |
| B10 | MEDIUM | `wp_cache_set()` with no TTL on dashboard stats/login-log/slow-queries → frozen forever under persistent object cache | `class-rest-api.php:942-995`, `class-cpu-query-monitor.php:48-59` |
| B11 | MEDIUM | `'lockout'` status exists in schema but never written | schema vs `class-security.php` |
| B12 | MEDIUM | AI-bot block counter does `update_option` write per blocked hit → DB write amplification under bot flood | `class-security.php:1743-1744` |
| B13 | LOW | Hide login exempts ALL logged-in users, not just admins | `class-security.php:674-676` |
| B14 | LOW | Disabling REST user routes also kills `/wp/v2/users/me` for subscribers | `class-security.php:446-458` |
| B15 | LOW | Archive exclusion runs `posts_per_page => -1` query + per-post meta lookups every archive view (Pro) | `class-plugin.php:1112-1147` |
| B16 | LOW | Dead files ship in package: `includes/wp_edit_pass_customize.php`, `admin/pagetemplater.php` (never loaded, contain `session_start()`) | includes/, admin/ |
| B17 | LOW | Notice CSS/JS loads on EVERY wp-admin page, no hook guard | `class-notice.php:349-369` |
| B18 | LOW | No pruning job for login log table → unbounded growth | missing cron |

---

## Part 2 — Architecture Principles (learned from Wordfence)

Adopt these; skip what doesn't fit a lean plugin:

1. **Fail-open, never white-screen.** Every subsystem wrapped in try/catch → `error_log` → continue request. Protection degrades, availability never dies. Wordfence ships a dummy-WAF fallback; we adopt the same philosophy everywhere.
2. **Early bootstrap for firewall.** Basic mode = load at `plugins_loaded`. Extended mode (opt-in, admin confirms) = drop-in loader file written to `mu-plugins/` + `.user.ini`/`.htaccess` `auto_prepend` pointing at it. Storage must work WITHOUT WordPress loaded → flat PHP files in `wp-content/wpepp-logs/` (blocks, ips, config) like Wordfence's `wflogs/`.
3. **Custom tables + aggressive housekeeping.** Key-value config table optional; events/blocks/hits tables with hourly/daily prune crons and hard row caps (Wordfence trims wfLogins >20k, wfStatus ring buffer 1000).
4. **Chunked, resumable scans.** Scan engine = state machine serialized between HTTP cycles (loopback request with signature + rotating key), positional resume markers. Survives shared-hosting process recycling.
5. **Caching everything expensive.** Geo lookups cached per-IP/day; bot verification cached; negative caches for blocklist API misses; bloom-filter/sorted-prefix tricks for large list membership tests.
6. **Piggyback sync, no polling loops.** Attack-data/rule updates ride normal page loads or one AJAX endpoint — no background polling daemons.
7. **Zero frontend cost discipline** (already good in WPEPP — keep the asset matrix, extend it to new modules; live traffic OFF by default).
8. **Server is authority.** All Pro gating enforced server-side (already correct in WPEPP — keep).

---

## Part 3 — Target Architecture

```
wp-edit-password-protected/
├── wp-edit-password-protected.php          # bootstrap (unchanged entry)
├── uninstall.php                           # FIXED: complete cleanup
├── includes/
│   ├── class-plugin.php                    # module registry
│   ├── class-activator.php                 # + new tables, defaults
│   ├── class-rest-api.php                  # + new endpoints
│   │
│   ├── core/
│   │   ├── class-ip.php                    # SINGLE source of truth for IPs:
│   │   │                                   #   trusted-proxy CIDR setting, header chain
│   │   │                                   #   (CF/CF-Connecting-IP/XFF only from trusted),
│   │   │                                   #   IPv4+IPv6 normalize, inet helpers
│   │   ├── class-settings.php              # typed settings layer w/ schema,
│   │   │                                   #   autoload flags, defaults, export/import
│   │   ├── class-logger.php                # event bus → DB tables + email hooks
│   │   ├── class-cron.php                  # scheduler registry + min-interval guards
│   │   └── class-failsafe.php              # try/catch wrappers, dummy-mode fallbacks
│   │
│   ├── modules/
│   │   ├── firewall/
│   │   │   ├── class-firewall.php          # orchestrator: basic (plugins_loaded)
│   │   │   │                               #   + extended (mu-plugin drop-in)
│   │   │   ├── class-rules.php             # rule engine: matchers (ip, ua, referrer,
│   │   │   │                               #   path, query, method, body-pattern)
│   │   │   ├── class-request-store.php     # flat-file store for early-boot mode
│   │   │   ├── class-country-block.php     # local geo db lookup (cached), fail-open
│   │   │   ├── class-rate-limit.php        # token-bucket: views/404s × human/crawler
│   │   │   ├── class-block-center.php      # manual/temporary/CIDR/IPv6 blocks CRUD
│   │   │   └── rules/                      # bundled JSON rule packs (versioned)
│   │   ├── scanner/
│   │   │   ├── class-scanner.php           # chunked state machine, loopback trigger
│   │   │   │                               #   signed key, resume markers
│   │   │   ├── class-integrity.php         # core checksums via api.wordpress.org;
│   │   │   │                               #   plugin/theme checksums from wp.org SVN API;
│   │   │   │                               #   unknown-file classification
│   │   │   ├── class-signatures.php        # malware heuristic regex pack (bundled +
│   │   │   │                               #   updatable pack from own CDN, version-stamped)
│   │   │   ├── class-file-watcher.php      # realtime change detection (mtime/size index)
│   │   │   ├── class-vuln-db.php           # vulnerable software detection via wp.org
│   │   │   │                               #   plugin-api + closed-plugin detection
│   │   │   ├── class-option-scan.php       # suspicious options (siteurl hijack, user roles)
│   │   │   └── class-user-scan.php         # ghost admins, weak-password audit
│   │   ├── login-security/
│   │   │   ├── class-limiter.php           # existing limiter, rebuilt on Ip service:
│   │   │   │                               #   IP + username dimensions, forgot-pw budget,
│   │   │   │                               #   invalid-username instant lock, unlock email
│   │   │   ├── class-totp.php              # existing (fix QR → local JS renderer)
│   │   │   ├── class-passkeys.php          # WebAuthn registration/login (new)
│   │   │   ├── class-password-policy.php   # strength meter enforcement by role,
│   │   │   │                               #   breached-password check (k-anonymity
│   │   │   │                               #   range API — SHA1 prefix only, privacy-safe)
│   │   │   ├── class-recaptcha.php         # unified v2/v3 wrapper, fail-closed both sides
│   │   │   └── class-username-shield.php   # ?author=N, oEmbed, sitemap users,
│   │   │                                   #   masked login errors, app-password control
│   │   ├── monitor/
│   │   │   ├── class-activity-log.php      # structured events table: logins, content
│   │   │   │                               #   CUD, user CUD, option changes, blocks
│   │   │   ├── class-live-traffic.php      # OFF by default; sampled logging,
│   │   │   │                               #   human/bot classify, geo cache
│   │   │   └── class-alerts.php            # alert matrix + digest + weekly report
│   │   └── hardening/
│   │       ├── class-headers.php           # security response headers (configurable)
│   │       ├── class-file-perms.php        # disable code exec in uploads (.htaccess/user.ini)
│   │       └── class-misc.php              # directory listing off, feed controls, emogb
│   │
│   ├── dashboard/
│   │   ├── class-score.php                 # weighted checklist → score + issues feed
│   │   ├── class-diagnostics.php           # env/connectivity/cron/fs tests
│   │   └── class-io.php                    # settings import/export (signed JSON)
│   │
│   └── (existing classes stay: customizers, content lock, conditional, site access,
│        cpu monitor, pro, migration — refactored onto core/ services)
├── src/                                    # React SPA — add pages:
│   │                                       # Firewall, Scanner, Blocks, ActivityLog,
│   │                                       # LiveTraffic, Alerts, Tools, Score-driven Setup wizard
│   └── ...
└── assets/geo/                             # optional bundled country ranges (compact,
                                            #   CC-licensed subset) OR lazy-download w/ cache
```

### New DB tables

```sql
{prefix}wpepp_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_type VARCHAR(64) NOT NULL,          -- login_success, login_failed, block, scan_issue...
  severity TINYINT NOT NULL DEFAULT 1,      -- 1 info, 2 warn, 3 critical
  actor_user BIGINT NULL,
  ip VARBINARY(16) NULL,                    -- binary IP, IPv4+IPv6 unified
  summary VARCHAR(255) NOT NULL,
  context LONGTEXT NULL,                    -- JSON payload
  created_at DATETIME NOT NULL,
  INDEX idx_type_time (event_type, created_at),
  INDEX idx_ip_time (ip, created_at)
);

{prefix}wpepp_blocks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ip_start VARBINARY(16) NOT NULL,
  ip_end VARBINARY(16) NOT NULL,            -- range form covers single IP + CIDR + IPv6
  type ENUM('manual','auto_404','rate','lockout','country') NOT NULL,
  reason VARCHAR(255) DEFAULT '',
  hit_count BIGINT DEFAULT 0,
  expires DATETIME NULL,                    -- NULL = permanent
  created_by BIGINT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_range (ip_start, ip_end),
  INDEX idx_expires (expires)
);

{prefix}wpepp_scan_issues (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kind VARCHAR(32) NOT NULL,               -- core_modified, malware, unknown_file, vuln_software, suspicious_option, ghost_admin, weak_password
  severity TINYINT NOT NULL,
  object_path TEXT NULL,
  ignore_hash CHAR(32) NULL,               -- dedupe + "ignore" support
  detail LONGTEXT NULL,                    -- JSON: diff, signature id, versions
  status ENUM('new','ignored','fixed') NOT NULL DEFAULT 'new',
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_issue (kind, ignore_hash)
);

{prefix}wpepp_file_index (
  path VARCHAR(191) NOT NULL,
  hash CHAR(32) NOT NULL,                  -- md5 for cheap compare; sha256 on demand
  size INT UNSIGNED NOT NULL,
  mtime INT UNSIGNED NOT NULL,
  kind ENUM('core','plugin','theme','unknown') NOT NULL,
  PRIMARY KEY (path(191))
);
```

Rate-limit counters: transient/object-cache first, fallback batched rows in options — avoid per-hit INSERT (lesson from B12).

---

## Part 4 — Phased Roadmap

### Phase 0 — Stabilize & harden existing code (BLOCKING, ~1–2 weeks)

Fix every audit finding BEFORE adding features. A security plugin with known holes cannot ship.

1. **B1/B2:** Build `core/class-ip.php`. Trusted-proxy CIDR setting (default: trust nothing except loopback). Only read CF/CDN headers when `REMOTE_ADDR` ∈ trusted proxies. Refactor limiter, geo, logs onto it. Add self-test diagnostic ("detected IP correctness probe" — copy of Wordfence's howGetIPs check).
2. **B3:** Render QR code client-side (bundle tiny QR JS lib, GPL/MIT) — secret never leaves browser/server HTML.
3. **B4:** Store site password as `wp_hash_password()`; verify with `wp_check_password()`; timing-safe compares everywhere (`hash_equals`).
4. **B5:** Redact secrets from GET `/settings` responses (write-only fields; return `"set": true` flag instead of value).
5. **B6:** On hide/custom login, require a valid access token/slug for POST too (cookie-less challenge cookie set on GET of the custom slug; POSTs to bare `wp-login.php` get 403 when hidden).
6. **B7:** Registration reCAPTCHA fails CLOSED on verification-network error (mirror login side); add "test mode" toggle.
7. **B8:** Replace all `%i` placeholders with explicit identifier whitelisting (`in_array` against known table names) — restores WP 6.0/6.1 compat.
8. **B9+B16:** Rewrite `uninstall.php` driven by a central registry (`WPEPP_Settings::all_option_keys()`, meta keys array, table names). Delete dead files.
9. **B10:** Every `wp_cache_set` gets TTL (60–300s) + explicit invalidation on write.
10. **B11:** Insert `lockout` rows when limiter fires.
11. **B12:** Batch bot-block counter (increment transient, flush to option every 50 hits / 5 min).
12. **B13/B14:** Hide-login exemption limited to administrators; keep `/users/me` alive while killing `/users` list route.
13. **B15:** Replace archive exclusion query with single `$wpdb` JOIN over `postmeta` EXISTS subquery, cached 5 min.
14. **B17:** Guard notice assets behind own-page hook check.
15. **B18:** Daily cron prunes login log (keep 30 days default, configurable).
16. Add PHPUnit test scaffold (WP test suite) covering: IP resolution matrix, limiter windows, CIDR math, TOTP vectors, uninstall completeness.
17. Run PHPCS (WordPress standard) + PHPStan level ≥5 clean; wire into CI.

**Exit criteria:** zero open criticals/highs, PHPCS+PHPStan+PHPUnit green, tested on WP 6.0 & latest, PHP 7.4–8.3.

### Phase 1 — Security Dashboard & Setup Wizard (~1 week)

The single highest-conversion feature in Wordfence. Do it before heavy engines.

1. `dashboard/class-score.php`: weighted checklist across all modules (each item: label, weight, pass/fail callable, fix-deep-link). Score 0–100, letter grade.
2. Checklist items wired to existing features day one: XML-RPC off, version hidden, REST enum off, limiter on, honeypot on, 2FA enrolled for admins, custom login active, strong pw policy, headers on, backups detectable (UpdraftPlus/etc. presence hint), TLS check (is_admin_url_https), file-editor disabled check (DISALLOW_FILE_EDIT), plugins updated count.
3. Setup wizard React flow: 5 steps (protect logins → hide login → hardening → scanning schedule → alerts email), each step applies safe defaults with one click.
4. Dashboard widgets: score ring, recent events (from new logger), blocked attempts today, scan status, update-status strip.

### Phase 2 — Blocking Center + Rate Limiting + Real Country Block (~1.5 weeks)

Foundation for the firewall: storage + IP service already exist after Phase 0.

1. `wpepp_blocks` table CRUD + React Blocks page (add/remove, import/export CIDR lists, temporary blocks w/ expiry).
2. Token-bucket rate limiter: separate budgets for logged-in/crawler/all × views/404s; actions: throttle (429) or temp-block. Counters in object cache w/ DB fallback batching.
3. 404-flood auto-detection: N unique 404 paths in window → auto temp block + alert.
4. Country blocking rebuilt: bundled compact country-range dataset OR ip-api batch lookup cached 24h per IP in `{prefix}wpepp_geo_cache`; decision made in early hook (init prio 0) with fail-open on lookup error; never trust client geo headers unless trusted proxy confirmed (Phase 0 IP service).
5. Self-service unlock: blocked admin can request one-time unlock link by email (nonce'd, rate-limited) — removes top support burden.
6. Branded 403/block template (simple, translatable, no branding lock-in).

### Phase 3 — Firewall (~2–3 weeks, flagship feature)

1. Rule engine: declarative JSON rules (matchers: IP/CIDR, ASN later, UA, referrer, method, path regex, query param patterns, body patterns for common SQLi/XSS payloads). Bundled starter pack (~100 curated rules) + versioned pack updates fetched weekly from your CDN (checksum-verified; signature verification stretch goal).
2. Basic mode: run rules at `plugins_loaded` (still pre-content, catches most).
3. Extended mode (opt-in): write `wpepp-loader.php` mu-plugin that `require`s a copied bootstrap from stable path + writes `auto_prepend_file` into `.user.ini` (CGI/FPM) and/or `.htaccess` (mod_php). Flat-file stores so decisions need no DB/WP. Careful uninstall removes ini directives + mu-plugin. Host detection guards (skip on managed hosts where ini edits fail).
4. Learning mode: matched rules logged not blocked for first N days; auto-suggest scoped exclusions when legit requests match (response != 404/403 heuristic — adapted from Wordfence).
5. Integration: limiter lockouts + blocks center + country blocks evaluated inside same pipeline; single block event stream to logger.
6. Fail-safe: any exception → disable extended mode automatically, fall back to basic, raise issue + email alert.

### Phase 4 — Scanner + Vulnerability Detection (~2–3 weeks)

1. Integrity: fetch core checksums from `api.wordpress.org/core/checksums/`, plugin checksums from `api.wordpress.org/plugins/checksums` (available for wp.org-hosted plugins), theme checksums likewise. Classify unknown files. Store results in `wpepp_file_index`.
2. Malware heuristics: bundled regex pack (~500 high-precision signatures: eval/base64 chains, gzdecode exec, iframe injections, spam links, backdoor patterns) with common-string pre-index to cut regex passes; cap file size scanned (default 1MB, configurable); exclude caches/uploads optionally.
3. Chunked runner: jobs queue (integrity → signatures → vulns → options → users), loopback-triggered slices honoring `max_execution_time` headroom, resumable positions, watchdog cron restarts stalled scans.
4. Vuln detection: compare installed core/plugin/theme versions against wp.org API (`plugins_api_information` closed/status + minimum supported WP) → "abandoned plugin" warnings; premium tier later: faster vuln feed (Patchstack/wpscan-style licensed data or own curated feed).
5. Suspicious-options scan: siteurl/home overrides, `users_can_register` flips, dangerous `active_plugins` entries, injected user-role definitions.
6. User scans: ghost admins (created outside registration history), weak/common password audit (opt-in, k-anonymity range check).
7. Findings UI: severity-tagged issues list, diff viewer for modified core files (port of Diff SequenceMatcher idea — implement minimal side-by-side), actions: repair (rewrite pristine from wp.org for core/plugins/themes!), delete, ignore.
8. Scheduling: quick (daily, integrity+vulns only) / full (weekly, +signatures+options+users) profiles; email report.

### Phase 5 — Login Security Advanced (~1.5 weeks)

1. Passkeys/WebAuthn (Pro): registration + authentication flows, RP bound to site host, credential count cap, recovery-code fallback mandatory. Consider extracting as standalone companion plugin later (Wordfence pattern).
2. Password policy: per-role minimums (length, char classes), block list of top-10k common passwords bundled, breached-password k-anonymity check on set/reset (SHA1-prefix range query — never send full password).
3. Username shield: kill `?author=N` redirects, oEmbed author data, wp-sitemap users tab, REST `/users` (already), uniform login error messages ("invalid username or password"), optional banned-username list, disable application passwords toggle.
4. 2FA upgrade: enforce-by-role with grace period + nag emails; remember-device for N days (hashed token cookie); XML-RPC policy: disable entirely / require 2FA / apps-only.
5. Unified CAPTCHA service interface (reCAPTCHA v2/v3 now, hCaptcha/Turnstile adapters — Turnstile strongly recommended as modern default).

### Phase 6 — Monitoring, Alerts, Reports (~1.5 weeks)

1. `wpepp_events` + Logger service: replace direct login-log writes; migrate old rows; retention cron (30/90 days by type).
2. Alert matrix (email): admin login, failed-admin-login burst, lockout issued, new scan criticals, file-change detected, plugin deactivated (watchdog), firewall extended-mode failure. Per-alert enable + threshold + quiet-hours.
3. Weekly activity report: logins count, blocks by type top-IPs, scan summary, score delta.
4. Live Traffic (Pro, OFF by default): sampled hits table (cap 5k rows), bot/human classify (UA parse + optional JS beacon), geo from cache, filters UI. Documented performance contract: <1ms overhead when off, sampling when on.
5. Notification center in SPA (bell): categories, priorities, read-state (adopt Wordfence model).

### Phase 7 — Hardening Suite + Tools (~1 week)

1. Security headers manager: X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, HSTS (with careful warning + preload opt-out), CSP report-only starter templates per site type.
2. Uploads code-execution killer (drop .htaccess/nginx.conf snippet generator), directory-listing off, `DISALLOW_FILE_EDIT` writer (with consent), feed controls, remove RSD/WLW manifest.
3. Diagnostics page: PHP/WP/env table, connectivity tests (wp.org, your update CDN), cron health, filesystem writability, DNS/resolve, detected-IP probe result.
4. Settings import/export (schema-versioned JSON, capability-gated, sanitized recursively).
5. WP-CLI commands: `wpepp scan start|status`, `wpepp block add|list|remove`, `wpepp score`.

### Phase 8 — Polish & Release (~1 week)

1. Multisite: network-aware tables, per-site settings with network defaults overlay (decide scope: start single-site, multisite-compatible storage).
2. Full i18n pass + POT regen; JS translations via `wp_set_script_translations`.
3. readme.txt rewrite: security-first positioning, feature comparison table, FAQ (lockout recovery, performance guarantees, GDPR stance — document exactly what IPs are stored and retention).
4. Performance validation suite: Query Monitor baseline (target: ≤2 extra queries per anonymous page with all modules ON, 0 when idle), load-test limiter/firewall hot paths.
5. Security review of the plugin itself (wp-plugin-audit sweep), PHPCS/PHPStan green, changelog, tag v3.0.0.
6. Optional: submit scanner signature pack + rule pack updater infrastructure to your CDN.

---

## Part 5 — Free/Pro Split Architecture (APPROVED v3.0)

Two separate codebases:
- **Free** `wp-edit-password-protected/` — all free features + integration APIs
- **Pro** `wpepp-pro/` — license flow (VTPBD, unchanged) + pro-only modules in `inc/modules/`

### The 3-layer contract

```
LAYER 1 — GATE (free owns)
  wpepp_has_pro_check() === true only when ALL of:
    - option wpepp_has_pro === 'yes'          (license flag)
    - pro plugin ACTIVE                        (is_plugin_active)
    - version handshake passes                 (WPEPP_PRO_VERSION >= WPEPP_REQUIRED_PRO_VERSION)
  Legacy fallback: pre-3.0 pro builds (no version constant) still honoured.

LAYER 2 — MODULE REGISTRATION (pro owns its code entirely)
  Free fires   do_action( 'wpepp/pro/init' )   at plugins_loaded prio 99,
               ONLY when Layer 1 passes.
  Pro hooks    add_action( 'wpepp/pro/init', [ WPepp_Pro, 'load_modules' ] )
               → WPEPP_Pro_Module_Loader requires every inc/modules/*.php.
  Module contract: own settings reads; never duplicate license checks;
                   underscore-prefixed files disabled.

LAYER 3 — DATA FILTERS WITH PRIORITIES (only where free needs a fallback)
  'wpepp/features/tiers'   feature→tier map (free prio 10, pro may adjust)
  'wpepp/security/score_items'  dashboard checklist items
  'wpepp/firewall/rules'   starter rules (free) + pro rule packs
```

Supporting rules:
- Pro shows admin nag when free inactive (`WPepp_Pro::is_free_compatible()`).
- Free shows handshake warning when active pro < required version.
- React SPA stays in free; pro registers extra tabs via `window.WPEPP_PRO_PAGES` registry from its own bundle.
- REST: pro adds routes under same `wpepp/v1` namespace with own permission callbacks.
- Shared DB tables remain free-owned; pro tables only when unavoidable.
- Uninstall: each plugin cleans its own data.

### Final tier matrix (APPROVED)

**FREE:** login limiter, honeypot, lockout+unlock email, XML-RPC off, hide version, REST user shield, username shielding (?author=N / oEmbed / sitemap users / masked errors / banned names), **2FA TOTP + recovery codes + role enforcement (moved Pro→Free)**, integrity scan (core/plugin/theme checksums), vuln detection (weekly), malware basic pack, daily file watcher, firewall basic mode + starter rules, basic rate limiting + 404 auto-block, country block ×2, security headers, uploads hardening, security score dashboard + wizard, alerts (lockout + criticals), activity log 7d, single-IP lists, diagnostics, WP-CLI, CPU monitor, existing content-protection free tiers.

**PRO:** firewall extended early-boot mode + learning mode + rule-pack updates, malware full pack real-time, daily vuln feed, near-real-time file watcher, passkeys/WebAuthn + remember-device, breached-password check + full password policy, country block unlimited + login-only, advanced rate-limit policies, live traffic, activity log 90d + export, full alert matrix + weekly report, CIDR/import IP lists, custom login URL, reCAPTCHA/Turnstile, auto-login advanced options, registration protection suite (disposable/domain-list/approval), content lock suite, template gallery, import/export, multisite.

Retiering = one line in `wpepp_feature_tier()` map + optional pro module.

---

## Part 6 — Non-Negotiable Engineering Rules

1. Every input sanitized per type map (existing PLUGIN-PLAN.md §security — keep enforcing).
2. Every output escaped late; dynamic CSS via `wp_add_inline_style`.
3. REST: permission_callback always; section/key allowlists; secrets write-only.
4. No `update_option` on hot paths; batch counters; autoload flags audited.
5. All new tables pruned by cron with hard caps.
6. Fail-open wrappers around every external call (geo, checksums API, breach API, rule CDN) — timeouts ≤3s, cached negatives.
7. IP handling ONLY via `WPEPP_Core_Ip` — grep CI check forbids raw `$_SERVER['REMOTE_ADDR']` outside it.
8. Target envs: WP 6.0+ (no `%i`), PHP 7.4+ syntax guarded (no readonly/enums), tested 8.1/8.2/8.3.
9. Each phase lands with: unit tests, PHPCS clean, manual QA script, changelog entry.

---

## Immediate Next Step

Start Phase 0 today: build `includes/core/class-ip.php` + refactor limiter/geo onto it (kills B1+B2, the two criticals), then work down bug list B3→B18. Everything else builds on this foundation.


## Part 9 — Production Packaging Checklist (PHPCS / Plugin Check)

Deferred per dev-mode decision. Fix ALL of these before generating the release zip.

### 1. EscapeOutput (class-frontend.php 525-526, class-password-customizer.php 338-368)
Logo width/height/font_size/color/text printed into printf format strings.
Fix: wrap numeric values with absint/floatval at assignment, colors pass through
sanitize_color already -> wrap each printf arg in esc_attr() to satisfy the sniff
(values are pre-sanitized, so zero behavior change).

### 2. Filesystem API (WordPress.WP.AlternativeFunctions)
- class-cpu-wp-config.php:110 is_writable, 183/200 chmod, 185 rename, 188 unlink, 241 is_writable
- hardening/class-file-hardening.php:191 rename
- uninstall.php:193 unlink, 195 rmdir
Fix: add includes/core/class-fs.php wrapper that boots WP_Filesystem
(request_filesystem_credentials direct) and exposes is_writable/chmod/move/delete/
rmdir. Swap call sites 1:1. For uninstall.php init FS with WP_Filesystem() first.

### 3. PreparedSQL (core/class-blocks.php, core/class-logger.php)
Interpolated {\} + sprintf-built SQL. Tables are internal prefix-derived
names — accepted pattern is targeted ignores:
  phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
Also: replace call_user_func_array( [,'prepare'], ... ) with
\->prepare( \, ...\ ) spread (fixes NotPrepared on the array form),
and blocks.php:313 restructure to plain prepare() (kills ReplacementsWrongNumber).

### 4. parse_url / NonceVerification (core/class-fw-engine.php)
Engine must stay WordPress-free (runs from early-boot bootstrap before WP loads).
Add inline phpcs:ignore with justification comment:
  // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- early-boot context, wp_parse_url unavailable
  // phpcs:ignore WordPress.Security.NonceVerification.* -- engine input capture, no nonce context exists pre-WP
option-scan.php:28-29 uses real WP context -> change to wp_parse_url().

### 5. Test scaffolding (excluded from zip anyway)
tests/bootstrap.php strip_tags shim -> preg_replace based.
tests/unit/PasskeysB64Test.php eval() loader -> plain require_once (bootstrap
shims load first); normalize file EOL to CRLF.
Keep tests/ and all _*.php/_*.js dev files OUT of the production zip.

### Zip contents (production)
wp-edit-password-protected.php, readme.txt, uninstall.php, includes/, assets/,
build/, languages/, LICENSE.txt -- exclude: tests/, _*.php, _*.js, .github/,
.vscode/, .claude/, node_modules/, vendor/ (keep if appsero needs), src/, docs,
*.md dev notes, playwright dirs, phpunit.xml.dist, composer.lock optional.
