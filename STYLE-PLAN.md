# WPEPP Admin — Style Plan ("Midnight Sentinel")

Mode: **Operate** (security admin dashboard — scanability, state legibility, familiar affordances).
Direction: **brief-pinned** by supplied mockups — no concept roll. Old indigo/navy world is retired; this is a replacement, not a polish.
Hard constraint: **zero feature removal/hiding** — every control, page, and option stays; only the visual world changes.

## 1. World

Near-black neutral ground, one emerald signal color, hairline structure, precise data typography.
The security console reads like a night-ops instrument panel: dark by default, calm, high-contrast where it matters, emerald reserved for *active/healthy/primary-action*, red-pink for *danger*, amber for *warning*, blue for *informational*.

- **Color strategy:** Restrained — neutral ramp + emerald accent + 3 semantic hues.
- **Dark is the default** (user-pinned; night-ops scene: admins monitoring threats, long sessions, low-glare). Light is a first-class persisted alternative, not an afterthought.
- **Typography:** system UI stack (Operate register) + `tabular-nums`; all *data values* (stats, IPs, times, scores) in a monospace stack — numbers are the content of this product.
- **Geometry:** 12px cards, 8px controls, 1px hairlines, generous 24–28px content padding. No glows, no gradients on chrome (PRO badge keeps its violet gradient as the single deliberate exception — it marks *paid*, not *active*).

## 2. Tokens (CSS custom properties, `[data-theme]` scoped)

| Token | Dark (default) | Light |
|---|---|---|
| bg-canvas | `#0B0D12` | `#F5F7FA` |
| bg-sidebar | `#0E1117` | `#FFFFFF` |
| bg-surface | `#12151D` | `#EEF2F6` |
| bg-card | `#141824` | `#FFFFFF` |
| bg-card-hover | `#191E2B` | `#F8FAFC` |
| bg-input | `#0E1117` | `#FFFFFF` |
| border | `rgba(255,255,255,.07)` | `rgba(13,20,32,.10)` |
| border-strong | `rgba(255,255,255,.14)` | `rgba(13,20,32,.18)` |
| accent | `#2EE6A8` | `#0FA97E` |
| accent-contrast (text on accent) | `#06231A` | `#FFFFFF` |
| accent-rgb | `46,230,168` | `15,169,126` |
| text-primary | `#F2F5F9` | `#101828` |
| text-secondary | `rgba(226,232,244,.62)` | `#55627A` |
| text-muted | `rgba(226,232,244,.38)` | `#8A94A6` |
| danger | `#FB4B6B` | `#E11D48` |
| warning | `#F5A524` | `#B45309` |
| info | `#5B9DFF` | `#2563EB` |
| ring-track | `rgba(255,255,255,.10)` | `rgba(13,20,32,.10)` |
| shadow-card | `0 2px 10px rgba(0,0,0,.35)` | `0 1px 3px rgba(13,20,32,.08)` |

Implementation: SCSS variables are **re-pointed** at these custom properties (`$bg-card: var(--wpepp-card);`) so the existing 2,600-line stylesheet themes itself; hardcoded indigo `rgba(99,102,241,*)` / `#6366f1` occurrences are converted to `rgba(var(--wpepp-accent-rgb), x)`.

## 3. Component grammar (mockup-faithful)

- **Primary button:** solid emerald, **dark text** (`accent-contrast`), 8px radius, no colored glow. Hover: +brightness. (Upgrade, Run Setup Wizard, Add Block, Start Scan, Save.)
- **Ghost/secondary:** transparent, hairline border, primary text. Hover: accent border + accent text.
- **Header:** canvas bg, bottom hairline, 16px/650 title. Right cluster: theme **segmented toggle** (moon/sun in hairline pill, active side = accent-tinted tile) → Docs ghost pill → Upgrade emerald pill `»`.
- **Sidebar:** logo = emerald rounded tile + shield + wordmark; uppercase 10px/700 letterspaced group labels; items 13.5px with 20px icons; active = accent-tint bg + 2px inset accent bar + white text; **NEW** badge = emerald pill, dark text, 9px uppercase (Scanner); PRO badge unchanged (violet).
- **Inner tabs:** transparent bar on canvas, bottom hairline across content; active tab = accent text + 2px accent underline; no filled background.
- **Toggles (WP components):** checked track = accent, white thumb; unchecked = neutral track. Focus rings accent.
- **Tables:** transparent th (uppercase 10.5px muted, letterspaced), hairline row dividers, hover = card-hover tint; status **pills** (success mint / danger red / warning amber / info blue) with dot; action links accent ("Details", "Remove", "Fix →").
- **Stat cards:** 40px tinted icon chip (semantic hue at 12% bg), value 22px **mono** tabular, label 12px secondary.
- **Score ring:** grade-colored arc (F → danger pink `#FB4B6B`), theme-aware track, value 30px mono, grade letter below, "SECURITY SCORE" micro-caption.
- **Panels/inputs:** card bg, hairline borders; open panel title = accent 10% tint; inputs = input bg + hairline, focus accent ring.
- **Save bar / notices / snackbars:** surface bg + hairline; notice accents per semantic hue.
- **ProLock overlay:** canvas-tinted blur + emerald upgrade CTA.

## 4. Theme switching + persistence

- `data-theme` attribute on `.wpepp-admin-wrap`; dark = base tokens, light = `[data-theme="light"]` overrides.
- Initial theme server-rendered via `window.wpeppData.adminTheme` (read from **wp option** `wpepp_admin_theme`, default `dark`) and applied before first paint of the SPA.
- Header toggle: instant attr swap + `POST /wpepp/v1/theme { theme }` (new route, `manage_options`) → `update_option( 'wpepp_admin_theme', … )`. No settings-section coupling; survives reload; per-site (options table as requested).
- WP chrome behind the SPA (`body.toplevel_page_wpepp-settings`) follows via `:has()` on the wrap's theme attribute.

## 5. Scope guard

Restyled: shell (sidebar/header/tabs), every page class listed in admin.scss sections 904–2230 + Security Suite block, WP component overrides (buttons/inputs/toggles/selects/panels/tables/notices), Form Style editor + Live Preview frame, CPU Monitor, wizard, scanner, template gallery.
Untouched: all JSX logic, routes, settings keys, REST contracts, features, copy. JSX edits limited to: Header (theme toggle), Sidebar (NEW badge support), ScoreRing (theme-aware track + F hue), App (data-theme attr), index.js (initial theme), plus new tiny `/theme` REST route + `adminTheme` localization.
