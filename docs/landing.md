# Landing Page Prompt: WPEPP – Essential Security, Password Protect & Login Page Customizer

## Overview
Build a single-page HTML/CSS/JS landing page for **WPEPP** (WP Edit Password Protected) — all-in-one WordPress security, content protection, and login page customizer plugin. Design must be **benefit-driven**, **modern**, **animated**, and **conversion-optimized**.

**Brand:** WPEPP Security  
**Tagline:** *Lock Down, Style Up, Sleep Easy*  
**Target User:** WordPress site owners (non-developers & developers alike)  
**Tone:** Confident, secure, professional, warm

---

## Design System

### Colors
- **Primary:** #1a1a2e (deep navy)  
- **Secondary:** #16213e (dark blue)  
- **Accent:** #e94560 (vibrant red/coral — urgency, security)  
- **Accent-2:** #0f3460 (medium blue)  
- **Success/CTA:** #00b894 (emerald green — safe, go)  
- **Gradient 1:** linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)  
- **Gradient 2:** linear-gradient(135deg, #e94560 0%, #c0392b 100%)  
- **Gradient 3:** linear-gradient(135deg, #00b894 0%, #00cec9 100%)  
- **Text Light:** #f8f9fa  
- **Text Dark:** #2d3436  
- **Text Muted:** #b2bec3  
- **Bg Light:** #f0f2f5  
- **Card Bg:** #ffffff  
- **Border:** #e0e3e8  

### Typography
- **Headings:** `'Inter', system-ui, -apple-system, sans-serif` (700, 800, 900 weights)  
- **Body:** `'Inter', sans-serif` (400, 500, 600 weights)  
- **Monospace (badges/stats):** `'JetBrains Mono', monospace`  

### Icons
- Use **Feather Icons** (feathericons.com) — clean, consistent stroke icons  
- Use **Font Awesome 6** (free) for brand icons (WP, GitHub, etc.)  

### Components & Effects
- **Glassmorphism cards:** `backdrop-filter: blur(20px); background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);`
- **Floating particles/glow orbs** in hero section (CSS-only or canvas-free animated gradients)
- **Smooth scroll** anchor navigation
- **Intersection Observer** reveal animations (fade-up, slide-in, scale-in on scroll)
- **Counter animation** on stats numbers
- **Tab switching** for feature categories
- **Pricing toggle** animate slider between yearly/monthly/lifetime
- **Magnetic hover** effect on CTA buttons
- **Typewriter** effect on hero subheading
- **Shine/shimmer** effect on primary CTA
- **Live preview mockup** with subtle floating animation

---

## Page Sections

### 1. Navigation Bar (Sticky)
- **Logo:** WPEPP shield icon + "WPEPP Security" text — on scroll, shrink logo height 60px → 40px, add blur background  
- **Nav Links (smooth scroll):** Features → Security → Content → Pricing → FAQ  
- **CTA Button:** "Get WPEPP Pro" — gradient accent, pulse animation on load  
- **Mobile:** hamburger menu with slide-in overlay, backdrop blur  
- **Scroll behavior:** change bg from transparent to glassmorphism on scroll > 80px  

### 2. Hero Section (Above the Fold)
- **Layout:** Full viewport height, split left/right (60/40)  
- **Left Content:**
  - **Badge:** "🚀 v2.0 — All-New React Dashboard" — small pill, glassmorphism  
  - **Headline (H1):** *"The Only WordPress Security Plugin You'll Ever Need"* — font-size responsive (clamp 2.5rem → 4rem), weight 800  
  - **Subheadline (typewriter):** *"Login limiter. AI crawler blocker. 2FA. Login customizer. CPU monitor. All in one."*  
  - **Benefit line:** *"Replace 5 plugins with 1. Zero bloat. Zero external scripts. Just peace of mind."* — text muted  
  - **CTA buttons:**  
    - "Explore Features →" (primary, accent gradient, shine animation)  
    - "See Pricing" (outline, white border, arrow hover animate)  
  - **Trust signals row:** "⭐ 4.9/5 — Trusted by 10,000+ sites" + WP.org badge + "No credit card required"  
- **Right Content:**
  - **Hero mockup:** MacBook/iPhone frame showing the React admin dashboard (CSS-drawn or screenshot placeholder)  
  - **Floating element 1:** "🔒 10,000+ Attacks Blocked" badge — float animation  
  - **Floating element 2:** "⚡ 0 External Scripts" badge — float animation (delayed)  
  - **Background:** Animated gradient mesh + subtle floating geometric shapes (circles, triangles) with blur  

### 3. Social Proof / Stats Bar
- **Layout:** Full-width dark section, 4 counter stats side by side  
- **Stats (animated count up on scroll):**
  - "10,000+" — Active Installations  
  - "4.9 ⭐" — Average Rating  
  - "50M+" — Login Attempts Blocked  
  - "150+" — 5-Star Reviews  
- **Design:** Each stat in a glassmorphism card, counter animation using `requestAnimationFrame`  

### 4. Feature Categories (Tabbed)
- **Layout:** Centered heading + subtitle + tab navigation + content panels  
- **Heading:** *"Everything You Need to Secure & Style Your WordPress Site"*  
- **Subtitle:** *"Five categories. One dashboard. Zero compromises."*  
- **Tabs (custom radio/checkbox styled as pills):**  
  1. 🔐 Login Security  
  2. 🛡️ Site Protection  
  3. 🔒 Content Locking  
  4. 🎨 Login Customizer  
  5. 📊 CPU Monitor  
- **Content per tab — card grid (3 cols, responsive):**
  - Each feature card has: icon (Feather), feature name, short benefit description, "Free" or "Pro" badge  
  - Cards have hover lift effect (translateY(-8px) + shadow increase)  
  - Pro cards subtly bordered with accent color  

  **Tab 1 — Login Security cards:** Login Limiter, Login Honeypot, Registration Honeypot, Registration Rate Limiter, reCAPTCHA (Pro), 2FA TOTP (Pro), Login Activity Log (Pro), IP Allow/Block List (Pro)  
  **Tab 2 — Site Protection cards:** Hide Login Page (Pro), Custom Login URL (Pro), AI Crawler Blocker, Country Restriction (Pro), XML-RPC Disable, Hide WP Version, REST API Protection, Auto-Login Links  
  **Tab 3 — Content Locking cards:** Site-Wide Password, Admin-Only Mode, Per-Post Content Lock (Pro), Role-Based Locking (Pro), Conditional Display (12 conditions), Member-Only Template, Auto-Expiry Unlock (Pro)  
  **Tab 4 — Login Customizer cards:** Live Preview, Background (color/gradient/image/video), Logo Upload, Form Styling, Button Styling, Error Messages, Register Page Style (Pro), Lost Password Style (Pro), 10+ Templates  
  **Tab 5 — CPU Monitor cards:** CPU Usage Stats, Slow Query Logger, Cron Manager, Plugin Performance, Options Bloat Analyzer, Error Log Viewer (Pro), WP Config Manager, Transient Cleanup  

- **Animation:** Tab content fades + slides on switch, cards stagger in with Intersection Observer  

### 5. "Why WPEPP?" — Comparison / USP Section
- **Layout:** Alternating rows (image left / text right, then flipped)  
- **Row 1 — Image Left:**  
  - *"Replace 5 Plugins with 1"* — illustration of 5 plugin icons merging into 1 WPEPP icon  
  - Text: "Stop installing separate plugins for login limiter, security, password protect, login customizer, and CPU monitor. WPEPP does it all — with one unified dashboard, one settings panel, zero compatibility issues."  
- **Row 2 — Image Right:**  
  - *"Zero External Scripts. Zero Bloat."* — performance meter / lighthouse score mockup  
  - Text: "WPEPP loads absolutely zero external scripts on your front end. Your Google PageSpeed score stays perfect. CSS is generated inline. JS loads only where needed. Your visitors never know it's there — attackers do."  
- **Row 3 — Image Left:**  
  - *"Modern React Dashboard. Real-Time Preview."* — split screen showing dashboard + preview  
  - Text: "No confusing settings pages. Our React-powered admin gives you a beautiful, fast interface with live preview. Change a color — see it instantly. Apply a template — done. No save-and-reload loop."  

### 6. Interactive Live Demo Section (Optional Visual)
- **Layout:** Dark section, centered browser frame mockup  
- **Inside frame:** Animated GIF or static screenshot of the live preview customizer  
- **Overlay play button** if video demo available  
- **Caption:** *"See it in action. Change any style. Watch it update instantly."*  

### 7. Templates Gallery Section
- **Heading:** *"10+ Professionally Designed Templates. One Click Away."*  
- **Grid of template cards (4 cols, 2 rows):** Minimal, Modern Dark, Corporate, Nature, Gradient Wave, Elegant, Creative, Sunset, Tech Blue, Starter  
- **Each card:** Template name + small preview circle showing dominant colors + "Free" or "Pro" badge  
- **Hover:** Card lifts, shows a "Preview" overlay button  
- **CTA below:** "Browse All Templates →"  

### 8. Pricing Section
- **Layout:** Centered heading + toggle + 3 pricing cards (Free / Pro Yearly / Pro Lifetime)  
- **Heading:** *"Simple, Transparent Pricing"*  
- **Subtitle:** *"Start free. Upgrade when you need more power."*  
- **Toggle:** "Pay Monthly" / "Pay Yearly (Save 20%)" / "Lifetime" — custom pill toggle with 3 positions, animated slide indicator  
  - Yearly highlighted as "Best Value" with a small tag  
  - Lifetime highlighted as "Best Deal" with a small tag  
- **Cards:**

  **Card 1 — Free (center-left):**
  - Price: "$0" — forever  
  - Tag: "Get Started"  
  - Features: Login Limiter, Honeypots, AI Crawler Blocker, Site Password, Admin-Only Mode, Login Form Styling (basic), Password Form Styles 1-2, Member Template, CPU Monitor (basic), 2 Conditional Conditions, XML-RPC Disable, Hide WP Version, REST API Protection  
  - Button: "Install Free" (outline)  

  **Card 2 — Pro Yearly (center, larger, highlighted with glow border):**
  - Price: "$49" — /year (show crossed-out "$59" if showing monthly)  
  - Tag: "🔥 Most Popular"  
  - Features: Everything in Free + 2FA TOTP, reCAPTCHA, Custom Login URL, Login Activity Log, IP Allow/Block List, Content Lock, 12 Conditional Conditions, Register/Lost Password Styling, Password Styles 3-4, Country Restriction (unlimited), Auto-Login with expiry/limits, Error Log Viewer, All Templates, Template Export/Import, Priority Support  
  - Button: "Get Pro Yearly" (accent gradient, shine)  

  **Card 3 — Pro Lifetime (center-right):**
  - Price: "$149" — one-time  
  - Tag: "🏆 Best Value"  
  - Features: Same as Pro Yearly (full feature list)  
  - Highlight: *"One payment. Lifetime updates. Lifetime support."*  
  - Button: "Get Lifetime Access" (emerald gradient)  

- **Animation:** Cards stagger in, price changes animate on toggle switch, "Save X%" badge updates dynamically  
- **Guarantee:** Below cards — "30-Day Money-Back Guarantee 🛡️" with lock icon  

### 9. FAQ Section
- **Layout:** Centered, accordion items with smooth open/close animation  
- **Heading:** *"Frequently Asked Questions"*  
- **Items (click to expand, rotate chevron icon):**
  - "Is WPEPP compatible with caching plugins?" — Yes, works with WP Rocket, W3 Total Cache, LiteSpeed Cache, etc.  
  - "Does WPEPP slow down my site?" — Zero external scripts. Conditional asset loading. Lighthouse perfect.  
  - "Can I use WPEPP with WooCommerce?" — Yes. Compatible with WooCommerce, EDD, and all major plugins.  
  - "Will my settings survive an update?" — Yes. Built-in migration system preserves all settings across v1 → v2 updates.  
  - "Does WPEPP work on multisite?" — Yes. Each sub-site has independent settings. Network activation supported.  
  - "What happens when I downgrade from Pro to Free?" — Pro features become inactive. Your data is preserved. No lock-in.  
  - "Is my data safe with WPEPP?" — All data stored in your own WordPress database. No external calls (unless you enable telemetry).  
  - "How do I get support?" — Free users: WordPress.org forums. Pro users: Priority ticket support with < 24h response.  

### 10. Final CTA Section
- **Layout:** Full-width dark section with animated gradient background, centered  
- **Heading:** *"Ready to Lock Down Your WordPress Site?"*  
- **Subheading:** *"Join 10,000+ site owners who sleep easy with WPEPP."*  
- **CTA Button:** "Get Started Free" + "View Pricing" (two buttons side by side)  
- **Below:** Trust badges row — "🔒 256-bit SSL", "💳 Secure Payment", "🚚 Instant Download", "🛡️ 30-Day Guarantee"  

### 11. Footer
- **Layout:** Dark background, 4 columns  
- **Column 1:** Logo + short description + social links (X/Twitter, YouTube, WordPress.org)  
- **Column 2:** Product (Features, Pricing, Changelog, Documentation)  
- **Column 3:** Support (FAQ, Contact, WordPress.org Forum, GitHub)  
- **Column 4:** Legal (Privacy Policy, Terms, License GPLv2)  
- **Bottom bar:** "© 2026 WPEPP Security. Made with ❤️ for WordPress."  

---

## Animations & Interactivity (JS)

### CSS Animations
1. **Hero gradient mesh:** `@keyframes gradientShift` — slowly animate background-position on hero gradient  
2. **Floating badges:** `@keyframes float` — translateY oscillate, each badge has different animation-delay  
3. **Shimmer CTA:** `@keyframes shimmer` — pseudo-element sliding gradient across primary buttons  
4. **Pulse glow on pricing highlight card:** `@keyframes glowPulse` — box-shadow oscillates  
5. **Loading skeleton:** Pulse animation for placeholder content on initial render  

### JavaScript Interactions
1. **Intersection Observer** — 3 reveal classes: `.fade-up` (translateY(40px) → 0, opacity 0 → 1), `.fade-left`/`.fade-right` (translateX), `.scale-in` (scale 0.9 → 1) — stagger children with `data-delay` attributes  
2. **Counter animation** — stats numbers count from 0 to target value using `requestAnimationFrame`, ~2s duration  
3. **Typewriter effect** — hero subheading characters appear one by one, then blink cursor  
4. **Tab switcher** — click tab → remove active from all → add to clicked → hide all panels → show matching panel (with opacity/transform transition)  
5. **Pricing toggle** — click monthly/yearly/lifetime → animate price change (fade out → change text → fade in), update "Save" badges, highlight active card  
6. **FAQ accordion** — click question → toggle answer max-height with `scrollHeight` animation, rotate chevron  
7. **Mobile nav** — hamburger toggle → overlay menu slides in from right with backdrop blur  
8. **Nav scroll effect** — on scroll > 80px, reduce logo size, add glassmorphism background to nav, add box-shadow  
9. **Smooth scroll** — all anchor links `easeInOutCubic` using `scrollIntoView({ behavior: 'smooth' })`  
10. **Magnetic buttons** — on mousemove over CTAs, button slightly translates toward cursor (GSAP-free, pure JS with `requestAnimationFrame`)  

---

## Technical Requirements

- **Single HTML file** — all CSS in `<style>`, all JS in `<script>` at bottom  
- **Responsive** — mobile-first, breakpoints: 480px, 768px, 1024px, 1280px  
- **Performance** — no external libraries (no jQuery, no GSAP, no Bootstrap). Pure vanilla JS.  
- **Fonts:** Google Fonts `Inter` + `JetBrains Mono` (preconnect + display swap)  
- **Icons:** Feather Icons (SVG sprite or inline) + Font Awesome 6 (free)  
- **SEO meta tags** — viewport, description, og:title, og:description, og:image  
- **Accessibility** — proper heading hierarchy, aria-labels on interactive elements, focus styles, skip-to-content link  
- **Dark mode** — not needed (dark theme by default)  
- **Cross-browser** — Chrome, Firefox, Safari, Edge (last 2 versions)  

---

## File Structure (Output)

```
landing-page/
├── index.html          # Single page (all CSS/JS inline)
├── assets/
│   ├── img/
│   │   ├── hero-mockup.png       # Admin dashboard screenshot
│   │   ├── logo.svg              # WPEPP shield logo
│   │   ├── template-minimal.png  # Template thumbnails
│   │   ├── template-dark.png
│   │   ├── template-corporate.png
│   │   ├── ...
│   │   └── og-image.png          # Open Graph share image
│   └── fonts/                    # (optional, Google Fonts preferred)
```

---

## Copywriting Guidelines

- Every headline must answer "What's in it for me?"  
- Features are listed as **benefits first**, then mechanics  
- Use **action verbs**: Lock, Block, Style, Monitor, Protect, Control  
- Numbers and data: "10,000+ sites", "50M+ attacks blocked", "0 external scripts"  
- Social proof woven throughout (not just one section)  
- Pain point → Solution → Benefit structure for each feature paragraph  
- No jargon unless explained in parenthetical  
- Mobile users: shorter headlines, swipeable feature cards  

---

## Conversion Optimization Notes

- **Primary CTA** appears 5+ times: hero (2), after each feature tab, pricing, bottom CTA  
- **Scarcity:** "Save 20% with Yearly" / "Limited Lifetime pricing"  
- **Risk reversal:** 30-day money-back guarantee on all Pro plans  
- **Social proof:** Rating, install count, review count consistently visible  
- **Comparison:** "Replace 5 plugins with 1" triggers cost/overhead avoidance  
- **Free tier:** lowest friction entry point, Pro features clearly gated  
- **Sticky nav CTA** follows user through entire page  

---

> **Next step for AI:** Generate the complete `index.html` file implementing every section above. Use only vanilla CSS and vanilla JS. Include all copy, all animations, all interactivity. The page must be ~1000-1500 lines, production-ready, and pixel-perfect on desktop + mobile.
