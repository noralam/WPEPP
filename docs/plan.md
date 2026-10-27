# WPEPP — CPU Monitor & Optimizer Tab Plan

## Overview

Add a new **"CPU Monitor"** vertical sidebar tab to the existing WPEPP plugin (React admin app). It helps site owners **see why their CPU is high** and **fix it easily** — without needing to be a developer. The feature shows real-time CPU load, slow database queries, error logs, cron jobs, and more inside inner sub-tabs.

Some sub-tabs are **Free**, others are **Pro-locked**. All code (free + pro) lives inside this main plugin — the separate `wpepp-pro/wpepp-pro.php` add-on is only a license-key activator that sets the `wpepp_has_pro` option. Pro features are gated in PHP via `wpepp_has_pro_check()` and in React via the `<ProLock>` / `<ProBadge>` components. No CPU Monitor code is written in the pro add-on.

---

## The Problem

WordPress site owners often see their hosting panel showing **100% CPU** but have no idea why. Common causes include:

| # | Cause | How Often |
|---|-------|-----------|
| 1 | Slow / unoptimized database queries | Very Common |
| 2 | Too many or badly written cron jobs | Very Common |
| 3 | PHP errors & warnings looping | Common |
| 4 | Heavyweight or conflicting plugins | Common |
| 5 | Too many simultaneous visitors | Common |
| 6 | Large image uploads without compression | Common |
| 7 | No caching (every page rebuilt from scratch) | Very Common |
| 8 | External HTTP requests timing out | Less Common |
| 9 | Transient bloat in `wp_options` table | Common |
| 10 | Autoloaded options overload | Common |

---

## Goals

- ✅ Show **live CPU % usage** (server load)
- ✅ Show **top reasons** for high CPU with severity labels
- ✅ Let site owner **fix each issue** with one click or clear guidance
- ✅ No technical knowledge required to understand the dashboard
- ✅ Lightweight — the plugin itself must not cause CPU load
- ✅ Integrate seamlessly with existing WPEPP sidebar navigation & Pro system

---

## Integration with Existing Plugin

### Current Sidebar Tabs (vertical)
```
Dashboard
Site Access
Content           (Pro)
Security
AI Crawler Blocker
Form Style
Templates
Settings
```

### Updated Sidebar Tabs (vertical) — add "CPU Monitor"
```
Dashboard
Site Access
Content           (Pro)
Security
AI Crawler Blocker
Form Style
Templates
CPU Monitor       ← NEW (icon: desktop / trendingUp)
Settings
```

### CPU Monitor Inner Tabs (horizontal, inside the page)
```
Overview  |  Slow Queries  |  Cron Jobs  |  Error Log  |  Plugins  |  Options Bloat
─────────────────────────────────────────────────────────────────────────────────────
 FREE        FREE (Pro+)      FREE          PRO           PRO         FREE (Pro+)
```

---

## Free vs Pro Feature Split

| Inner Tab | Free | Pro |
|-----------|------|-----|
| **Overview** (CPU/Memory/Health Score) | ✅ Full | ✅ Full |
| **Slow Queries** — view list | ✅ Last 10 queries | ✅ Unlimited + export |
| **Slow Queries** — fix guidance | ❌ | ✅ |
| **Cron Jobs** — view list | ✅ Read-only list | ✅ Full list |
| **Cron Jobs** — delete / run now | ❌ | ✅ |
| **Error Log** — view & parse | ❌ | ✅ Full |
| **Plugin Performance** — load times | ❌ | ✅ Full |
| **Options Bloat** — view stats | ✅ Summary only | ✅ Full breakdown |
| **Options Bloat** — delete transients | ❌ | ✅ One-click clean |
| Auto-refresh interval | 30s fixed | ✅ Configurable (5–60s) |
| Email alerts on critical CPU | ❌ | ✅ |
| Export report (CSV) | ❌ | ✅ |

---

## File Structure (new files only)

All new files integrate into the **existing** plugin structure. No new plugin bootstrap needed.

### PHP — Backend (inside `includes/`)

```
includes/
├── class-cpu-monitor.php            # Boot CPU Monitor module, register hooks
├── class-cpu-query-monitor.php      # Track slow DB queries (SAVEQUERIES)
├── class-cpu-cron-monitor.php       # List all cron jobs + overdue detection
├── class-cpu-error-log.php          # Parse PHP error log
├── class-cpu-plugin-monitor.php     # Plugin load time + HTTP requests
├── class-cpu-options-monitor.php    # Autoloaded options + transient bloat
└── class-cpu-system-info.php        # Server CPU, RAM, PHP info
```

### React — Frontend (inside `src/pages/`)

```
src/pages/
├── CpuMonitor.jsx                   # Main page with inner tab navigation
└── CpuMonitor/
    ├── Overview.jsx                 # CPU/Memory dashboard (FREE)
    ├── SlowQueries.jsx              # Slow query list (FREE limited / PRO full)
    ├── CronJobs.jsx                 # Cron job list (FREE read-only / PRO actions)
    ├── ErrorLog.jsx                 # Error log parser (PRO)
    ├── PluginPerformance.jsx        # Plugin load times (PRO)
    └── OptionsBloat.jsx             # Options/transient stats (FREE limited / PRO full)
```

### REST API — New Endpoints (added to `class-rest-api.php`)

All under namespace `wpepp/v1`:

| Endpoint | Method | Description | Tier |
|----------|--------|-------------|------|
| `/cpu/stats` | GET | CPU load, memory, health score | Free |
| `/cpu/slow-queries` | GET | Recent slow queries | Free (limited) / Pro |
| `/cpu/cron-jobs` | GET | All cron events | Free |
| `/cpu/cron-jobs/run` | POST | Run a specific cron job now | Pro |
| `/cpu/cron-jobs/delete` | DELETE | Delete a specific cron job | Pro |
| `/cpu/error-log` | GET | Parsed error log entries | Pro |
| `/cpu/plugin-stats` | GET | Plugin load times | Pro |
| `/cpu/options-bloat` | GET | Options table analysis | Free (limited) / Pro |
| `/cpu/transients/clean` | POST | Delete all expired transients | Pro |
| `/cpu/settings` | GET/POST | CPU Monitor settings | Free |

All endpoints protected with `permission_callback => check_admin_permission` (same pattern as existing REST API).

Pro-only endpoints additionally check `wpepp_has_pro_check()` and return a `403` with an upgrade message if Pro is not active. This keeps all route registrations in the main plugin — the pro add-on never registers its own endpoints.

---

## Features Breakdown

### 1. 🖥️ Overview (Inner Tab — FREE)

**What it shows:**
- Current server load average (1 min / 5 min / 15 min)
- CPU usage % estimate
- PHP memory limit vs actual usage
- WordPress memory limit
- Number of active DB connections
- Overall health score (Green / Yellow / Red)
- Top reasons summary cards linking to other inner tabs

**How it works:**
- Uses `sys_getloadavg()` for load average
- Uses `memory_get_usage()` and `memory_get_peak_usage()`
- Auto-refreshes every 30s (free) or configurable (pro) via REST polling

---

### 2. 🗄️ Slow Queries (Inner Tab — FREE limited / PRO full)

**FREE:**
- View last 10 slow queries (> 0.5s threshold)
- Query execution time shown

**PRO adds:**
- Unlimited query history
- Which plugin/function called each query
- Fix guidance ("No index", "Too many queries from plugin X")
- Export to CSV

**How it works:**
- Hooks into `SAVEQUERIES` when enabled
- Stores in a custom DB table `{$wpdb->prefix}wpepp_slow_queries`
- Threshold configurable in CPU Monitor settings

---

### 3. ⏰ Cron Jobs (Inner Tab — FREE read-only / PRO actions)

**FREE:**
- List all registered WP cron events
- Schedule, last run, next run, overdue status

**PRO adds:**
- One-click delete a cron job
- One-click run overdue job now
- Warning if WP-Cron uses page visits (suggest real cron)

**How it works:**
- Uses `_get_cron_array()` to list all cron events
- Compares `next_run` with `current_time()` to detect overdue jobs

---

### 4. 🚨 Error Log (Inner Tab — PRO only)

**What it shows:**
- Last N lines of PHP error log (configurable, default 200)
- Error type (Fatal, Warning, Notice, Deprecated)
- File and line number
- Timestamp
- How many times each error repeated
- Color-coded by severity

**How it works:**
- Reads `WP_DEBUG_LOG` path or `wp-content/debug.log`
- Groups repeated errors to avoid noise

---

### 5. 🔌 Plugin Performance (Inner Tab — PRO only)

**What it shows:**
- Load time added by each active plugin
- Number of DB queries per plugin
- External HTTP requests made by each plugin
- One-click deactivate from dashboard

**How it works:**
- Hooks into plugin loading with `microtime()` tracking
- Monitors `pre_http_request` and `http_api_debug` for external calls

---

### 6. 🗃️ Options Bloat (Inner Tab — FREE summary / PRO full)

**FREE:**
- Total `wp_options` table size
- Autoloaded options total size
- Expired transients count

**PRO adds:**
- Top 20 largest autoloaded options with plugin attribution
- One-click delete all expired transients
- Warning if autoloaded data > 1MB

**How it works:**
- Queries `wp_options` for `autoload = 'yes'` entries
- Identifies expired transients (`_transient_timeout_*`)

---

## Database Tables

### `{$wpdb->prefix}wpepp_slow_queries`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK AUTO_INCREMENT | Row ID |
| query_sql | LONGTEXT | The SQL query |
| exec_time | FLOAT | Execution time in seconds |
| call_stack | TEXT | PHP backtrace |
| recorded_at | DATETIME | When captured |

Created via `dbDelta()` on activation (add to `class-activator.php`).
Auto-pruned daily via WP cron (keeps last 7 days).

---

## UI Layout

### Sidebar (existing, add one entry)

```
┌──────────────────────┐
│  🔒 WPEPP            │
├──────────────────────┤
│  📊 Dashboard        │
│  🌐 Site Access      │
│  🔒 Content    [PRO] │
│  🛡️ Security        │
│  🤖 AI Crawler       │
│  🎨 Form Style       │
│  📄 Templates        │
│  🖥️ CPU Monitor ← NEW│
│  ⚙️ Settings         │
└──────────────────────┘
```

### CPU Monitor Page (inner horizontal tabs)

```
┌─────────────────────────────────────────────────────────────┐
│  CPU Monitor                                                │
├─────────────────────────────────────────────────────────────┤
│  [Overview] [Slow Queries] [Cron Jobs] [Error Log🔒] [Plugins🔒] [Options Bloat] │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  CPU Load:  ████████░░  78%   🔴 HIGH                       │
│  Memory:    ████░░░░░░  42%   🟢 OK                         │
│  Health Score:  ⚠️  NEEDS ATTENTION                         │
│                                                             │
│  TOP REASONS YOUR CPU IS HIGH                               │
│                                                             │
│  🔴 Slow Queries        12 queries > 0.5s    [View →]       │
│  🔴 Overdue Cron Jobs   3 jobs stuck         [Fix →]        │
│  🟡 PHP Errors          47 warnings today    [View →]       │
│  🟡 Options Bloat       2.3MB autoloaded     [Clean →]      │
│  🟢 Plugins             All OK                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

🔒 = ProBadge shown for non-pro users, wrapped in `<ProLock>` component.

---

## Severity Color System

| Color | Label | Meaning |
|-------|-------|---------|
| 🔴 Red | CRITICAL | Immediate action needed |
| 🟡 Yellow | WARNING | Should be fixed soon |
| 🟢 Green | OK | No action needed |
| ⚪ Gray | INFO | Informational only |

---

## CPU Monitor Settings (added to existing Settings page OR inline)

| Setting | Default | Tier | Description |
|---------|---------|------|-------------|
| Enable CPU Monitor | ON | Free | Master toggle |
| Slow query threshold | 0.5s | Free | Queries slower than this are logged |
| Enable query logging | ON | Free | Toggle SAVEQUERIES constant |
| Max error log lines | 200 | Pro | How many lines to read from error log |
| Auto-refresh interval | 30s | Pro | Dashboard live refresh rate (5–60s) |
| Error log path | auto-detect | Pro | Custom path if non-standard |
| Email alerts | OFF | Pro | Send email when CPU > threshold |

---

## Pro Gating Architecture

All CPU Monitor code ships inside the main plugin. The pro add-on (`wpepp-pro/wpepp-pro.php`) is a lightweight license activator — it only sets `update_option( 'wpepp_has_pro', 'yes' )` when a valid license is verified. It does **not** contain any CPU Monitor logic.

**PHP (backend):**
- Use `wpepp_has_pro_check()` before returning pro-only data or executing pro-only actions.
- Free-limited endpoints return a truncated dataset (e.g. last 10 queries) when pro is inactive.
- Pro-only endpoints return `WP_Error( 'wpepp_pro_required', '...', [ 'status' => 403 ] )`.

**React (frontend):**
- Pro-only inner tabs are wrapped in `<ProLock isPro={isPro}>` (shows upgrade overlay when locked).
- Pro-only action buttons (delete cron, clean transients) are conditionally rendered or disabled via `isPro`.
- Free-limited tabs show a "Upgrade to Pro for full data" notice when `!isPro`.
- The `isPro` flag comes from the `wpepp/settings` data store (fetched from REST on load).

---

## Performance & Safety

- CPU Monitor module only loads classes when admin page is visited (lazy boot)
- Query logging disabled on front-end by default (admin-only)
- REST endpoints use existing `check_admin_permission` callback
- Nonce verification via WP REST API cookie auth (same as existing endpoints)
- Auto-prune: slow query log cleaned daily via `wp_schedule_event`
- Plugin itself uses **< 5ms** overhead on normal page loads

---

## React Implementation Notes

### Sidebar Entry (update `src/components/Sidebar.jsx`)
```jsx
// Add to navItems array:
{ to: '/cpu-monitor', icon: trendingUp, label: __( 'CPU Monitor', 'wp-edit-password-protected' ) },
```

### Route (update `src/App.jsx`)
```jsx
const CpuMonitor = lazy( () => import( './pages/CpuMonitor' ) );
// Inside <Routes>:
<Route path="/cpu-monitor/*" element={ <CpuMonitor /> } />
```

### Page Pattern (follow `Security.jsx` pattern)
```jsx
// src/pages/CpuMonitor.jsx
// - Uses <NavLink> inner tabs with `wpepp-inner-tabs` class
// - Each inner tab is lazy-loaded from src/pages/CpuMonitor/
// - Pro-only tabs wrapped in <ProLock>
// - Pro-limited tabs use isPro to conditionally show full data
```

### REST Store (update `src/store/`)
- Add `cpu_monitor` section to settings store or create a dedicated store slice
- REST calls to `wpepp/v1/cpu/*` endpoints

---

## Development Phases

### Phase 1 — Core (MVP)
- [ ] Create `class-cpu-monitor.php` (boot module)
- [ ] Create `class-cpu-system-info.php` (CPU, memory stats)
- [ ] Register REST endpoints for `/cpu/stats`
- [ ] Add sidebar entry + route in React
- [ ] Build `CpuMonitor.jsx` page shell with inner tabs
- [ ] Build `Overview.jsx` inner tab (FREE)

### Phase 2 — Monitors
- [ ] `class-cpu-query-monitor.php` + DB table + REST endpoint
- [ ] Build `SlowQueries.jsx` (FREE limited + Pro full)
- [ ] `class-cpu-cron-monitor.php` + REST endpoints
- [ ] Build `CronJobs.jsx` (FREE read-only + Pro actions)
- [ ] `class-cpu-options-monitor.php` + REST endpoints
- [ ] Build `OptionsBloat.jsx` (FREE summary + Pro full)

### Phase 3 — Pro Features
- [ ] `class-cpu-error-log.php` + REST endpoint
- [ ] Build `ErrorLog.jsx` (PRO only)
- [ ] `class-cpu-plugin-monitor.php` + REST endpoint
- [ ] Build `PluginPerformance.jsx` (PRO only)
- [ ] Settings integration (threshold, refresh, alerts)

### Phase 4 — Polish
- [ ] Auto-prune cron for slow query table
- [ ] Email alerts on critical CPU (Pro)
- [ ] CSV export for queries & plugin stats (Pro)
- [ ] Translations (add strings to .pot)

---

## Tech Stack (matches existing plugin)

| Layer | Technology |
|-------|-----------|
| Backend | PHP 7.4+ / WordPress REST API |
| Database | MySQL via `$wpdb` + `dbDelta()` |
| Frontend | React (via `@wordpress/element`) |
| State | `@wordpress/data` store |
| Routing | `react-router-dom` (HashRouter) |
| UI Components | `@wordpress/components` |
| Pro Gating | `wpepp_has_pro_check()` (PHP) + `<ProLock>` / `<ProBadge>` (React) — all in main plugin, pro add-on is license-only |
| Security | WP REST cookie auth + `manage_options` capability |

---

## Minimum Requirements (same as WPEPP)

- WordPress 6.0+
- PHP 7.4+
- MySQL 5.6+ / MariaDB 10.1+
- Administrator role to view dashboard

---

*Plan Version: 2.0 | Integrated with WPEPP plugin structure*
