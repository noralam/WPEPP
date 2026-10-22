# WPEPP Admin — DESIGN.md ("Midnight Sentinel")

Recorded from the shipped build (admin.scss + Header/Sidebar/ScoreRing/App). Source of truth over intention.

## Identity
Night-ops security console. Near-black neutral ground, one emerald signal color, hairline structure, monospaced data numerals. Dark is the default scene; light is a persisted alternative. Chrome stays neutral — data carries color.

## Color system (CSS custom properties on `.wpepp-admin-wrap`)
| Role | Dark | Light |
|---|---|---|
| Canvas `--wpepp-canvas` | `#0B0D12` | `#F5F7FA` |
| Sidebar `--wpepp-sidebar` | `#0E1117` | `#FFFFFF` |
| Surface `--wpepp-surface` | `#12151D` | `#EDF1F6` |
| Card `--wpepp-card` | `#141824` | `#FFFFFF` |
| Border `--wpepp-border` | `white @ 7%` | `ink @ 10%` |
| Accent `--wpepp-accent` | `#2EE6A8` | `#0FA97E` |
| Accent text `--wpepp-accent-contrast` | `#06231A` | `#FFFFFF` |
| Text `--wpepp-text / -2 / -3` | `#F2F5F9` / 62% / 38% | `#101828` / `#55627A` / `#8A94A6` |
| Danger / Warning / Info | `#FB4B6B` / `#F5A524` / `#5B9DFF` | `#E11D48` / `#B45309` / `#2563EB` |

Semantic rule: emerald = active/healthy/primary action; danger pink = threats/blocked; amber = warnings; blue = informational. PRO badge keeps a violet gradient (`--wpepp-pro-gradient`) — it marks *paid*, never *active*.

## Typography
System UI stack, 14px base. Page titles 16px/650 (header) and 22px/700 (page h2). Micro-labels 10–11px/700, +0.08–0.12em tracking, uppercase (sidebar groups, table headers, captions). **All data numerals** (stat values, score ring, IPs, timestamps, counters) use `--wpepp-mono` with `tabular-nums`.

## Components
- **Primary button:** solid accent, accent-contrast text, 8px radius, no glow.
- **Ghost button:** hairline border, hover → accent border/text.
- **Theme toggle:** segmented moon/sun pill in header; active side accent-tinted.
- **Sidebar:** 240px; emerald logo tile; uppercase group labels; active item = accent-tint bg + 2px inset accent bar; NEW = accent pill w/ dark text; PRO = violet badge.
- **Inner tabs:** transparent on canvas, hairline underline; active = accent text + 2px accent underline.
- **Toggles:** checked = accent track, white thumb.
- **Tables:** transparent uppercase th, hairline rows, hover = card-hover; status pills (accent/danger/warning/info tints); action links accent.
- **Notices:** tinted bg + matching border, no side bars.
- **Score ring:** grade-colored arc (A `#10B981` → F `#FB4B6B`), `--wpepp-ring-track` groove, mono value.
- **Panels:** card bg, hairline; open title = accent 6–12% tint; inputs on `--wpepp-input`, focus accent ring.
- **Browser surfaces:** accent selection, accent focus-visible rings, thin themed scrollbars, `color-scheme` per theme.

## Motion
One grammar: 0.15–0.2s ease on color/background/border; save-bar slides up; score arc animates dash-offset 0.8s. No entrance choreography.

## Theme persistence
`data-theme` on `.wpepp-admin-wrap`; initial value from `window.wpeppData.adminTheme` (wp option `wpepp_admin_theme`, default `dark`); header toggle POSTs `/wpepp/v1/theme` (`manage_options`, enum dark|light). WP chrome behind the SPA follows via `:has()` selectors.

## Boundaries
Feature-neutral: no control, route, key, or copy changed for styling. JSX deltas: App (theme state + attr), Header (toggle + pill styles), Sidebar (logo tile + NEW badge), ScoreRing (track var + grade hues).
