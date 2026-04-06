# WP Edit Password Protected - v2.0 Plugin Plan

## Plugin Overview

**Name:** WP Edit Password Protected
**Slug:** wp-edit-password-protected
**Version:** 2.0.0
**Tech Stack:** React + @wordpress/scripts + WordPress REST API
**Admin UI:** React SPA with tabbed live preview
**Min Requirements:** WordPress 6.0+, PHP 7.4+

---

## Current Plugin State (v1.3.7) — Audit Summary

### Existing Features (to keep/upgrade)
| Feature | Current Implementation | v2 Plan |
|---------|----------------------|---------|
| Password Protected Form | `wpEditPasswordOutput` class — hooks `the_password_form`, 4 form styles (one/two/three/four), top/bottom text, social icons, error text | Keep all styles, add live editor per style |
| Member Only Page Template | `PageTemplater` class injects "Member only template" into page template dropdown. Shows login form or content based on auth state | Expand into Content Lock system |
| Customizer Settings (Kirki) | 2 panels: `wppass_protected_panel` (25 fields) + `wppass_adminpage_panel` (20+ fields) via bundled Kirki (5.1MB, 332 files) | Replace with React admin panel |
| Conditional Display Meta Box | `WPEPP_Conditional_Meta` — sidebar meta box on posts/pages with show/hide conditions. **Currently only `user_logged_in` and `user_logged_out` enabled** (rest commented out: user_role, device, day/time, date_range, recurring, post_type, browser, URL param, referrer) | Enable ALL conditions + add to admin dashboard list view |
| REST API Content Protection | Hides content/excerpt/title/featured_image in REST responses for conditional posts | Keep and extend |
| Login Fail Redirect | Redirects failed front-end logins back to referring page with `?login=failed` | Keep |

### Existing Option Keys (must preserve for migration)
**Password Form (wppasspro_* prefix):** wppasspro_form_style, wppasspro_show_top_text, wppasspro_top_text_align, wppasspro_top_header, wppasspro_top_content, wppasspro_show_bottom_text, wppasspro_bottom_text_align, wppasspro_bottom_header, wppasspro_bottom_content, wppasspro_form_label, wppasspro_form_btn_text, wppasspro_form_errortext, wppasspro_error_text_position, wppasspro_show_social, wppasspro_icons_vposition, wppasspro_icons_alignment, wppasspro_icons_style, wppasspro_link_facebook, wppasspro_link_twitter, wppasspro_link_youtube, wppasspro_link_instagram, wppasspro_link_linkedin, wppasspro_link_pinterest, wppasspro_link_tumblr, wppasspro_link_custom

**Admin Page (wpe_adpage_* prefix):** wpe_adpage_class, wpe_adpage_mode, wpe_adpage_style, wpe_adpage_text_align, wpe_adpage_infotitle, wpe_adpage_titletag, wpe_adpage_text, wpe_adpage_shortcode, wpe_adpage_login_mode, wpe_adpage_login_url, wpe_adpage_btntext, wpe_adpage_btnclass, wpe_adpage_form_head, wpe_adpage_user_placeholder, wpe_adpage_password_placeholder, wpe_adpage_form_remember, wpe_adpage_remember_text, wpe_adpage_wrongpassword, wpe_adpage_errorlogin, wpe_adpage_formbtn_text, wpe_adpage_width, wpe_adpage_header_show, wpe_adpage_comment, wppasspro_page_fimg

**Legacy arrays:** pp_basic_settings, pp_admin_page

**Post Meta keys:** _wpepp_conditional_display_enable, _wpepp_conditional_display_condition, _wpepp_conditional_action, _wpepp_conditional_control_title, _wpepp_conditional_control_featured_image, _wpepp_conditional_user_role, _wpepp_conditional_device_type, _wpepp_conditional_day_of_week, _wpepp_conditional_time_start, _wpepp_conditional_time_end, _wpepp_conditional_date_start, _wpepp_conditional_date_end, _wpepp_conditional_recurring_*, _wpepp_conditional_browser_type, _wpepp_conditional_url_parameter_*, _wpepp_conditional_referrer_source

---

## Core Concept

A React-based admin panel using **tabbed live preview** — each form style gets its own tab with an embedded live preview. Users can:

- **Style Login Page** (`wp-login.php`) — live preview per tab
- **Style Register Page** (`wp-login.php?action=register`) — live preview per tab
- **Style Password Protected Page** (post/page password form) — live preview per form style tab
- **Style Lost Password Page** (`wp-login.php?action=lostpassword`) — live preview per tab
- **Lock Posts/Pages for Logged-Out Users** — per-post toggle via meta box + admin dashboard list
- **Conditional Content Display** — expanded from current (all 12 conditions enabled + admin dashboard management)

---

## Pro / Free Tier System

### How Pro Lock Works

The plugin uses a single WordPress option `wpepp_has_pro` to control feature access:

```php
/**
 * Check if Pro features are unlocked.
 * Returns true only when the option exists AND is exactly 'yes'.
 *
 * @return bool
 */
function wpepp_has_pro_check() {
    return 'yes' === get_option( 'wpepp_has_pro', 'no' );
}
```

### Feature Tier Matrix

| Feature | Free | Pro | Notes |
|---------|:----:|:---:|-------|
| **Password Form Styling** | ✅ Style 1 + Style 2 | ✅ All 4 Styles | Style 3 & 4 locked with Pro badge |
| **Login Page Styling** | ✅ Basic (background, logo, form colors) | ✅ Full (all controls + custom CSS) | Advanced controls locked in Free |
| **Register Page Styling** | 🔒 | ✅ | Entire tab locked with Pro overlay |
| **Lost Password Page Styling** | 🔒 | ✅ | Entire tab locked with Pro overlay |
| **Content Lock** | 🔒 | ✅ | Entire section locked — "Content Lock" inner tab shows Pro upsell |
| **Conditional Display — Basic** | ✅ `user_logged_in` / `user_logged_out` | ✅ | 2 basic conditions always available |
| **Conditional Display — Advanced** | 🔒 | ✅ All 12 conditions | Condition dropdown shows locked conditions with Pro badge |
| **Conditional Display — Dashboard** | 🔒 | ✅ | Dashboard management page is Pro only |
| **Member-Only Template** | ✅ Basic | ✅ Full customization | Basic toggle works, advanced settings locked |
| **Templates Gallery** | ✅ 3 free templates | ✅ All 10+ templates | Locked templates show Pro badge + blurred preview |
| **Template Import/Export** | 🔒 | ✅ | Import/Export buttons disabled with Pro tooltip |
| **Security — Login Limiter** | ✅ | ✅ | Always available |
| **Security — Honeypot** | ✅ | ✅ | Always available |
| **Security — Disable XML-RPC** | ✅ | ✅ | Always available |
| **Security — Hide WP Version** | ✅ | ✅ | Always available |
| **Security — Disable REST User Enum** | ✅ | ✅ | Always available |
| **Security — reCAPTCHA** | 🔒 | ✅ | Locked with Pro badge |
| **Security — Custom Login URL** | 🔒 | ✅ | Entire inner tab locked |
| **Security — Login Log** | 🔒 | ✅ | Entire inner tab locked |
| **Custom CSS Editor** | 🔒 | ✅ | Entire inner tab locked |
| **Google Fonts** | 🔒 | ✅ | Font selector shows "Pro" badge, defaults to system fonts |
| **Social Icons (Password Form)** | ✅ 3 icons (Facebook, Twitter, YouTube) | ✅ All 7 icons | Extra icons (Instagram, LinkedIn, Pinterest, Tumblr) locked |
| **Responsive Preview Toggle** | ✅ Desktop only | ✅ Desktop + Tablet + Mobile | Tablet/Mobile buttons locked |

### Pro Lock Implementation

#### PHP — Server-Side Enforcement

Pro checks MUST be enforced on the server. The React UI lock is only visual — a savvy user could bypass it. The server is the final authority.

```php
/**
 * Sanitize and enforce Pro restrictions on settings save.
 * Called in REST API callback BEFORE saving to database.
 *
 * @param array  $settings The submitted settings.
 * @param string $section  The settings section.
 * @return array Filtered settings (Pro fields stripped if not Pro).
 */
function wpepp_enforce_pro_settings( $settings, $section ) {
    if ( wpepp_has_pro_check() ) {
        return $settings; // Pro user — allow everything
    }

    // Free user — strip Pro-only fields
    switch ( $section ) {
        case 'password':
            // Only allow style_one and style_two
            $allowed_styles = [ 'style_one', 'style_two' ];
            if ( isset( $settings['active_style'] ) && ! in_array( $settings['active_style'], $allowed_styles, true ) ) {
                $settings['active_style'] = 'style_one';
            }
            // Remove Pro style settings entirely
            unset( $settings['styles']['style_three'], $settings['styles']['style_four'] );
            break;

        case 'login':
            // Remove advanced controls for Free users
            unset(
                $settings['form']['custom_css'],
                $settings['form']['font_family'] // Google Fonts = Pro
            );
            break;

        case 'register':
        case 'lostpassword':
            // Entire section is Pro-only — reject all settings
            return [];

        case 'security':
            // Strip Pro-only security settings
            unset(
                $settings['recaptcha_enabled'],
                $settings['recaptcha_site_key'],
                $settings['recaptcha_secret_key'],
                $settings['custom_login_url']
            );
            break;
    }

    return $settings;
}

/**
 * Block Pro-only REST endpoints for Free users.
 * Used as additional permission_callback layer.
 */
function wpepp_check_pro_permission() {
    if ( ! wpepp_has_pro_check() ) {
        return new WP_Error(
            'wpepp_pro_required',
            __( 'This feature requires WP Edit Password Protected Pro.', 'wp-edit-password-protected' ),
            [ 'status' => 403 ]
        );
    }
    return true;
}

/**
 * Content Lock — only works if Pro is active.
 * Hooked into the_content filter.
 */
function wpepp_maybe_lock_content( $content ) {
    if ( ! wpepp_has_pro_check() ) {
        return $content; // Free users — content lock is not applied
    }

    if ( ! is_singular() || is_user_logged_in() ) {
        return $content;
    }

    $locked = get_post_meta( get_the_ID(), '_wpepp_content_lock_enabled', true );
    if ( 'yes' !== $locked ) {
        return $content;
    }

    // Show locked message instead of content
    return wpepp_get_locked_message( get_the_ID() );
}

/**
 * Conditional Display — advanced conditions only work in Pro.
 * Free users get user_logged_in and user_logged_out only.
 */
function wpepp_get_available_conditions() {
    $free_conditions = [
        'user_logged_in'  => __( 'User is logged in', 'wp-edit-password-protected' ),
        'user_logged_out' => __( 'User is logged out', 'wp-edit-password-protected' ),
    ];

    if ( ! wpepp_has_pro_check() ) {
        return $free_conditions;
    }

    // Pro — all 12 conditions
    return array_merge( $free_conditions, [
        'user_role'        => __( 'User role', 'wp-edit-password-protected' ),
        'device_type'      => __( 'Device type', 'wp-edit-password-protected' ),
        'day_of_week'      => __( 'Day of week', 'wp-edit-password-protected' ),
        'time_range'       => __( 'Time range', 'wp-edit-password-protected' ),
        'date_range'       => __( 'Date range', 'wp-edit-password-protected' ),
        'recurring'        => __( 'Recurring schedule', 'wp-edit-password-protected' ),
        'post_type'        => __( 'Post type', 'wp-edit-password-protected' ),
        'browser_type'     => __( 'Browser type', 'wp-edit-password-protected' ),
        'url_parameter'    => __( 'URL parameter', 'wp-edit-password-protected' ),
        'referrer_source'  => __( 'Referrer source', 'wp-edit-password-protected' ),
    ] );
}
```

#### React — Admin UI Pro Lock Components

```jsx
// src/components/ProLock.jsx — Reusable Pro lock overlay
import { Icon, lock } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * ProLock — Wraps a component/section with a Pro lock overlay.
 * If user has Pro, children render normally.
 * If not Pro, shows a blurred overlay with upgrade CTA.
 *
 * @param {Object}  props
 * @param {boolean} props.isPro       Whether user has Pro.
 * @param {string}  props.featureName Human-readable feature name for the CTA.
 * @param {React.ReactNode} props.children Content to lock.
 */
const ProLock = ( { isPro, featureName, children } ) => {
    if ( isPro ) {
        return children;
    }

    return (
        <div className="wpepp-pro-lock-wrapper">
            <div className="wpepp-pro-lock-content" aria-hidden="true">
                { children }
            </div>
            <div className="wpepp-pro-lock-overlay">
                <Icon icon={ lock } size={ 32 } />
                <h3>{ __( 'Pro Feature', 'wp-edit-password-protected' ) }</h3>
                <p>
                    { /* translators: %s: feature name */ }
                    { sprintf(
                        __( '%s is available in the Pro version.', 'wp-edit-password-protected' ),
                        featureName
                    ) }
                </p>
                <a
                    href={ wpeppData.proUrl }
                    className="components-button is-primary"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    { __( 'Upgrade to Pro', 'wp-edit-password-protected' ) }
                </a>
            </div>
        </div>
    );
};
export default ProLock;
```

```jsx
// src/components/ProBadge.jsx — Small "Pro" badge for individual controls
import { __ } from '@wordpress/i18n';

/**
 * ProBadge — Shows a "PRO" badge next to a control label.
 * For use on individual locked fields within a partially-free section.
 */
const ProBadge = () => (
    <span className="wpepp-pro-badge" aria-label={ __( 'Pro feature', 'wp-edit-password-protected' ) }>
        { __( 'PRO', 'wp-edit-password-protected' ) }
    </span>
);
export default ProBadge;
```

```scss
// src/styles/admin.scss — Pro lock styles
.wpepp-pro-lock-wrapper {
    position: relative;
}

.wpepp-pro-lock-content {
    filter: blur(2px);
    opacity: 0.5;
    pointer-events: none;
    user-select: none;
}

.wpepp-pro-lock-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.85);
    border-radius: 8px;
    text-align: center;
    z-index: 10;

    h3 {
        margin: 12px 0 4px;
        font-size: 18px;
    }

    p {
        margin: 0 0 16px;
        color: #757575;
    }
}

.wpepp-pro-badge {
    display: inline-block;
    padding: 1px 6px;
    margin-left: 6px;
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    color: #fff;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 3px;
    vertical-align: middle;
    letter-spacing: 0.5px;
    line-height: 18px;
}
```

```jsx
// Usage examples in pages:

// 1. Entire tab locked (Register, Lost Password, Content Lock, Custom Login URL, Login Log, Custom CSS)
import ProLock from '../components/ProLock';
import { useSelect } from '@wordpress/data';

const RegisterForm = () => {
    const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

    return (
        <ProLock isPro={ isPro } featureName={ __( 'Register Page Styling', 'wp-edit-password-protected' ) }>
            {/* Register form editor renders here — blurred + locked for Free users */}
            <RegisterFormEditor />
        </ProLock>
    );
};

// 2. Individual control locked (Google Fonts, reCAPTCHA, extra social icons)
import ProBadge from '../components/ProBadge';

const FontFamilyControl = ( { isPro, value, onChange } ) => (
    <div className="wpepp-control-row">
        <label>
            { __( 'Font Family', 'wp-edit-password-protected' ) }
            { ! isPro && <ProBadge /> }
        </label>
        { isPro ? (
            <GoogleFontSelector value={ value } onChange={ onChange } />
        ) : (
            <div className="wpepp-control-disabled">
                <span>{ __( 'System Default', 'wp-edit-password-protected' ) }</span>
            </div>
        ) }
    </div>
);

// 3. Partially locked list (Template gallery — 3 free, rest Pro)
const TemplateCard = ( { template, isPro } ) => {
    const isLocked = ! template.isFree && ! isPro;

    return (
        <div className={ `wpepp-template-card ${ isLocked ? 'wpepp-template-locked' : '' }` }>
            <img src={ template.preview } alt={ template.name } loading="lazy" />
            { isLocked && (
                <div className="wpepp-template-pro-overlay">
                    <ProBadge />
                </div>
            ) }
            <button disabled={ isLocked }>
                { isLocked
                    ? __( 'Pro Required', 'wp-edit-password-protected' )
                    : __( 'Apply Template', 'wp-edit-password-protected' )
                }
            </button>
        </div>
    );
};

// 4. Locked password form styles (Style 3 & 4)
const PasswordFormTabs = ( { isPro } ) => (
    <TabPanel
        tabs={ [
            { name: 'style_one', title: __( 'Style 1', 'wp-edit-password-protected' ) },
            { name: 'style_two', title: __( 'Style 2', 'wp-edit-password-protected' ) },
            {
                name: 'style_three',
                title: (
                    <span>
                        { __( 'Style 3', 'wp-edit-password-protected' ) }
                        { ! isPro && <ProBadge /> }
                    </span>
                ),
                disabled: ! isPro,
            },
            {
                name: 'style_four',
                title: (
                    <span>
                        { __( 'Style 4', 'wp-edit-password-protected' ) }
                        { ! isPro && <ProBadge /> }
                    </span>
                ),
                disabled: ! isPro,
            },
        ] }
    >
        { ( tab ) => <PasswordStyleEditor style={ tab.name } /> }
    </TabPanel>
);

// 5. Conditional display — locked conditions in dropdown
const ConditionSelector = ( { isPro, value, onChange, conditions } ) => (
    <SelectControl
        label={ __( 'Condition', 'wp-edit-password-protected' ) }
        value={ value }
        onChange={ onChange }
        options={ conditions.map( ( condition ) => ( {
            label: condition.isPro && ! isPro
                ? `${ condition.label } (Pro)`
                : condition.label,
            value: condition.value,
            disabled: condition.isPro && ! isPro,
        } ) ) }
    />
);
```

### Data Flow — Pro Status

```
1. Option in DB:         get_option( 'wpepp_has_pro' ) === 'yes'
                              │
2. PHP passes to JS:     wp_localize_script( 'wpepp-admin', 'wpeppData', [
                              'isPro'  => wpepp_has_pro_check(),
                              'proUrl' => 'https://wpthemespace.com/product/...', // Upgrade URL
                          ] );
                              │
3. Store selector:       // src/store/selectors.js
                         isPro: () => Boolean( window.wpeppData?.isPro ),
                              │
4. React components:     const isPro = useSelect( ( s ) => s( 'wpepp/settings' ).isPro() );
                              │
5. UI renders:           isPro ? <FullEditor /> : <ProLock>...</ProLock>
                              │
6. Server enforces:      wpepp_enforce_pro_settings() strips Pro fields on save
                         wpepp_check_pro_permission() blocks Pro-only endpoints
```

### Admin Sidebar with Pro Badges

```
┌──────────────────┐
│  Dashboard       │
│  Form Style      │
│  Content     PRO │  ← ContentLock inner tab shows Pro overlay
│  Templates       │  ← 3 free, rest show Pro badge
│  Security        │  ← Some inner tabs show Pro overlay
│  Settings        │  ← Custom CSS inner tab shows Pro overlay
└──────────────────┘
```

The sidebar itself doesn't lock entire parent tabs — users can always navigate to see what's inside. The **inner tabs and controls** show the Pro lock. This lets users discover Pro features and see what they're missing, driving conversions.

### Important Rules

1. **Server is the authority.** UI lock is cosmetic. `wpepp_enforce_pro_settings()` + `wpepp_check_pro_permission()` are the real gatekeepers.
2. **Free features must be genuinely useful.** Password form Style 1 + 2, basic login styling, login limiter, honeypot = real value.
3. **Pro lock must not break anything.** If `wpepp_has_pro` is `'no'` or missing, all Free features work perfectly. No errors, no broken pages.
4. **Pro lock is NOT obtrusive.** No fullscreen popups, no nagging notices in wp-admin. Just subtle badges + overlay on locked sections.
5. **Pro features are visible but disabled.** Users see the Pro features (blurred), understand the value, and can upgrade when ready.
6. **Option is simple:** `'yes'` = Pro unlocked, anything else = Free. No license keys, no API calls, no external validation in the core plugin. The Pro activation mechanism (license, purchase verification) is handled externally.

---

## Architecture

```
wp-edit-password-protected/
├── wp-edit-password-protected.php        # Main plugin file (bootstrap)
├── uninstall.php                         # Clean uninstall
├── composer.json
├── package.json                          # @wordpress/scripts
│
├── includes/
│   ├── class-plugin.php                  # Main plugin class (singleton)
│   ├── class-admin.php                   # Admin menu, enqueue React app
│   ├── class-rest-api.php                # REST API endpoints (settings, templates, locks, conditions)
│   ├── class-frontend.php                # Frontend rendering (login/register/password pages)
│   ├── class-login-customizer.php        # Apply login page styles
│   ├── class-register-customizer.php     # Apply register page styles
│   ├── class-password-customizer.php     # Apply password protected page styles (4 form styles)
│   ├── class-content-lock.php            # NEW: Lock single posts/pages for logged-out users
│   ├── class-conditional-meta.php        # Expanded conditional display (all 12 conditions enabled)
│   ├── class-conditional-meta-helper.php # Condition evaluator
│   ├── class-security.php                # Security features (login limiter, captcha, etc.)
│   ├── class-custom-login-url.php        # Custom login URL (Pro)
│   ├── class-login-log.php               # Login activity log (Pro)
│   ├── class-pro.php                     # Pro lock helper: wpepp_has_pro_check(), tier checks, settings filter
│   ├── class-member-template.php         # Member-only page template (upgraded from PageTemplater)
│   ├── class-migration.php               # Migrate old Kirki/option settings to new JSON format
│   └── class-activator.php               # Activation/deactivation hooks
│
├── src/                                  # React source (compiled by wp-scripts)
│   ├── index.js                          # Entry point
│   ├── App.jsx                           # Main app with hash router
│   │
│   ├── icons/                            # Inline SVG icon components (zero HTTP requests)
│   │   ├── index.js                      # Central export barrel file
│   │   ├── DashboardIcon.jsx             # Sidebar: Dashboard tab
│   │   ├── FormStyleIcon.jsx             # Sidebar: Form Style tab
│   │   ├── ContentIcon.jsx               # Sidebar: Content tab
│   │   ├── TemplatesIcon.jsx             # Sidebar: Templates tab
│   │   ├── SecurityIcon.jsx              # Sidebar: Security tab
│   │   ├── SettingsIcon.jsx              # Sidebar: Settings tab
│   │   ├── DesktopIcon.jsx               # Preview: responsive toggle
│   │   ├── TabletIcon.jsx                # Preview: responsive toggle
│   │   ├── MobileIcon.jsx                # Preview: responsive toggle
│   │   ├── LockIcon.jsx                  # Content lock indicator
│   │   ├── EyeIcon.jsx                   # Conditional display: show
│   │   ├── EyeOffIcon.jsx                # Conditional display: hide
│   │   └── SocialIcons.jsx               # Facebook, Twitter, YouTube, etc. (password form)
│   │
│   ├── pages/                            # Top-level route pages (one per vertical tab)
│   │   ├── Dashboard.jsx                 # Vertical tab: Dashboard (no inner tabs)
│   │   ├── FormStyle.jsx                 # Vertical tab: Form Style — renders inner tabs (Register/LostPassword = Pro lock)
│   │   ├── FormStyle/
│   │   │   ├── LoginForm.jsx             # Inner tab: Login form live editor
│   │   │   ├── RegisterForm.jsx          # Inner tab: Register form live editor (PRO — ProLock wrapped)
│   │   │   ├── LostPasswordForm.jsx      # Inner tab: Lost password form live editor (PRO — ProLock wrapped)
│   │   │   └── PasswordForm.jsx          # Inner tab: Password form (Style 1-2 free, Style 3-4 PRO)
│   │   ├── Content.jsx                   # Vertical tab: Content — renders inner tabs
│   │   ├── Content/
│   │   │   ├── ContentLock.jsx           # Inner tab: Locked posts list + toggle (PRO — ProLock wrapped)
│   │   │   ├── ConditionalDisplay.jsx    # Inner tab: Basic free (2 conditions), dashboard PRO
│   │   │   └── MemberTemplate.jsx        # Inner tab: Basic free, advanced settings PRO
│   │   ├── Templates.jsx                 # Vertical tab: Templates (no inner tabs)
│   │   ├── Security.jsx                  # Vertical tab: Security — renders inner tabs
│   │   ├── Security/
│   │   │   ├── LoginProtection.jsx       # Inner tab: Brute-force free, reCAPTCHA PRO
│   │   │   ├── CustomLoginUrl.jsx        # Inner tab: Custom login URL (PRO — ProLock wrapped)
│   │   │   └── LoginLog.jsx              # Inner tab: Activity log viewer (PRO — ProLock wrapped)
│   │   ├── Settings.jsx                  # Vertical tab: Settings — renders inner tabs
│   │   └── Settings/
│   │       ├── General.jsx               # Inner tab: Enable/disable features
│   │       └── CustomCss.jsx             # Inner tab: Global custom CSS editor (PRO — ProLock wrapped)
│   │
│   ├── components/                       # Reusable components
│   │   ├── LivePreview.jsx               # Iframe-based live preview component
│   │   ├── PreviewTabs.jsx               # Tab bar for switching form style previews
│   │   ├── ResponsiveToggle.jsx          # Desktop/Tablet/Mobile preview toggle (SVG icons)
│   │   ├── StyleControls/
│   │   │   ├── ColorPicker.jsx           # Color picker (wp.components)
│   │   │   ├── Typography.jsx            # Font family, size, weight, line-height
│   │   │   ├── Spacing.jsx               # Margin/padding controls
│   │   │   ├── Border.jsx                # Border style, width, radius, color
│   │   │   ├── Shadow.jsx                # Box shadow controls
│   │   │   ├── Background.jsx            # Color, gradient, image, overlay
│   │   │   ├── Dimensions.jsx            # Width, height, max-width
│   │   │   └── RangeSlider.jsx           # Custom range slider
│   │   ├── MediaUploader.jsx             # WP media library integration
│   │   ├── GoogleFonts.jsx               # Google Fonts selector (lazy-loaded)
│   │   ├── CodeEditor.jsx                # Custom CSS editor (lazy-loaded via React.lazy)
│   │   ├── TemplateCard.jsx              # Template preview card
│   │   ├── PostsTable.jsx                # Reusable posts list table (for content lock, conditional)
│   │   ├── ProLock.jsx                   # Pro lock overlay (blurred content + upgrade CTA)
│   │   ├── ProBadge.jsx                  # Small "PRO" badge for individual controls
│   │   ├── Sidebar.jsx                   # App sidebar navigation (SVG icons inline)
│   │   ├── Header.jsx                    # Admin page header
│   │   └── Notices.jsx                   # Toast notifications
│   │
│   ├── store/                            # @wordpress/data store
│   │   ├── index.js                      # Store registration
│   │   ├── actions.js                    # Redux actions
│   │   ├── reducer.js                    # Redux reducer
│   │   ├── selectors.js                  # Selectors
│   │   ├── controls.js                   # Async controls (API calls)
│   │   └── resolvers.js                  # Resolvers for async data
│   │
│   ├── hooks/                            # Custom React hooks
│   │   ├── useSettings.js                # Read/write settings
│   │   ├── useLivePreview.js             # Manage iframe communication
│   │   ├── useDebounce.js                # Debounce input changes
│   │   ├── useTemplates.js               # Template management
│   │   ├── usePro.js                     # Hook: const isPro = usePro() — reads from store
│   │   └── usePostsQuery.js              # Query posts for lock/conditional lists
│   │
│   ├── utils/                            # Utilities
│   │   ├── api.js                        # REST API wrapper
│   │   ├── css-generator.js              # Generate CSS from settings object
│   │   ├── defaults.js                   # Default settings values
│   │   ├── pro-features.js               # Pro feature map: which features/controls are Pro
│   │   └── constants.js                  # Constants
│   │
│   └── styles/                           # Admin styles
│       ├── admin.scss                    # Main admin styles
│       ├── live-preview.scss             # Preview panel styles
│       └── components.scss               # Component styles
│
├── build/                                # Compiled output (wp-scripts build)
│   ├── index.js
│   ├── index.asset.php
│   └── style-index.css
│
├── assets/
│   ├── css/
│   │   ├── frontend-login.css            # Compiled login page CSS (generated from settings)
│   │   ├── frontend-password-form.css    # Password form base styles (~3KB)
│   │   └── frontend-content-lock.css     # Content lock message styles (~1KB)
│   ├── images/
│   │   └── templates/                    # Template preview screenshots (WebP, lazy-loaded)
│   └── templates/                        # Template JSON presets
│       ├── starter.json
│       ├── modern-dark.json
│       ├── gradient-wave.json
│       ├── corporate.json
│       ├── minimal.json
│       ├── nature.json
│       ├── tech-blue.json
│       ├── sunset.json
│       ├── elegant.json
│       └── creative.json
│
├── languages/
│   └── wp-edit-password-protected.pot
│
└── readme.txt
```

---

## Performance & Zero Frontend Impact Strategy

### Core Principle: The Plugin Must NOT Slow Down the Website

A visitor browsing normal pages should experience **zero added HTTP requests, zero added CSS, and zero added JS** from this plugin. Assets load ONLY when the plugin's features are actually triggered on that specific page.

### Frontend Asset Loading Matrix

| Page Context | CSS Loaded | JS Loaded | Condition |
|-------------|-----------|----------|----------|
| Normal page/post (no lock, no password) | **NOTHING** | **NOTHING** | Default — plugin is invisible |
| Password-protected post/page | `frontend-password-form.css` (~3KB) + inline CSS from settings | **NOTHING** | Only when `post_password_required()` returns true |
| Locked post (Content Lock active) | `frontend-content-lock.css` (~1KB) + inline CSS | **NOTHING** (unless login form) | Only when `_wpepp_content_lock_enabled === 'yes'` on current post AND user is logged out |
| Conditional display active | **NOTHING** (server-side) | Tiny JS (~1KB, only if client-side conditions like browser/referrer) | Only when post has `_wpepp_conditional_display_enable` and condition is client-side |
| `wp-login.php` (login page) | Inline CSS via `login_enqueue_scripts` | **NOTHING** | Only if login styling is configured |
| `wp-login.php?action=register` | Inline CSS via `login_enqueue_scripts` | **NOTHING** | Only if register styling is configured |
| `wp-login.php?action=lostpassword` | Inline CSS via `login_enqueue_scripts` | **NOTHING** | Only if lost password styling is configured |
| Member-only page template | `frontend-content-lock.css` (~1KB) | **NOTHING** | Only on pages using the member template |
| Admin — plugin page | `build/style-index.css` + `build/index.js` | Full React app | Only on admin page `toplevel_page_wpepp-settings` |
| Admin — other pages | **NOTHING** | **NOTHING** | Plugin never loads on non-plugin admin pages |
| Admin — post editor | Tiny meta box CSS (~2KB) | Meta box JS (~3KB) | Only on post/page edit screens |

### On-Demand Loading Rules

```php
/**
 * RULE 1: Frontend CSS — only on pages that need it.
 * NO global wp_enqueue_scripts loading.
 */
add_action( 'wp_enqueue_scripts', function() {
    // Password form styles — only on password-protected singular posts
    if ( is_singular() && post_password_required() ) {
        wp_enqueue_style( 'wpepp-password-form', ... );
        // + inline CSS from wpepp_password_settings
    }

    // Content lock styles — only on locked posts for logged-out users
    if ( is_singular() && ! is_user_logged_in() ) {
        $locked = get_post_meta( get_the_ID(), '_wpepp_content_lock_enabled', true );
        if ( 'yes' === $locked ) {
            wp_enqueue_style( 'wpepp-content-lock', ... );
        }
    }

    // Everything else: NOTHING is loaded.
} );

/**
 * RULE 2: Login page CSS — only on wp-login.php.
 * Uses login_enqueue_scripts hook (it only fires on login page).
 */
add_action( 'login_enqueue_scripts', function() {
    $settings = json_decode( get_option( 'wpepp_login_settings', '{}' ), true );
    if ( ! empty( $settings ) ) {
        wp_add_inline_style( 'login', wp_strip_all_tags( wpepp_generate_css( $settings ) ) );
    }
} );

/**
 * RULE 3: Admin React app — only on our own plugin page.
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( 'toplevel_page_wpepp-settings' !== $hook ) {
        return; // Exit immediately — load NOTHING on other admin pages
    }
    // ... enqueue React app ...
} );

/**
 * RULE 4: Meta box assets — only on post editor screens.
 */
add_action( 'admin_enqueue_scripts', function( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }
    wp_enqueue_style( 'wpepp-meta-box', ... );  // ~2KB
    wp_enqueue_script( 'wpepp-meta-box', ... ); // ~3KB
} );

/**
 * RULE 5: Conditional display JS — only when client-side conditions are used.
 */
add_action( 'wp_enqueue_scripts', function() {
    if ( ! is_singular() ) return;

    $condition = get_post_meta( get_the_ID(), '_wpepp_conditional_display_condition', true );
    $client_conditions = [ 'browser_type', 'referrer_source' ];

    if ( in_array( $condition, $client_conditions, true ) ) {
        wp_enqueue_script( 'wpepp-conditional', ... ); // ~1KB, no dependencies
    }
} );

/**
 * RULE 6: NEVER enqueue on:
 * - Archive pages (unless conditional display is active on listed posts)
 * - Search results
 * - 404 pages
 * - RSS feeds
 * - REST API requests (styles not needed)
 * - WP-CLI context
 * - AJAX requests
 * - Cron jobs
 */
```

### Google Fonts — On-Demand, No Render Blocking

```php
/**
 * Google Fonts are ONLY loaded when:
 * 1. Admin has configured a custom font in settings
 * 2. The current page actually uses that font (login page, password form, etc.)
 *
 * Implementation:
 * - Font URL built dynamically from saved settings
 * - Loaded with display=swap (no render blocking)
 * - Preconnect hint added only when font is used
 * - NO font loaded if admin uses system fonts (default)
 */
function wpepp_maybe_enqueue_google_font( $font_family ) {
    if ( empty( $font_family ) || 'default' === $font_family ) {
        return; // System font — no external request
    }

    // Add preconnect for Google Fonts
    add_filter( 'wp_resource_hints', function( $hints, $relation ) {
        if ( 'preconnect' === $relation ) {
            $hints[] = [
                'href'        => 'https://fonts.googleapis.com',
                'crossorigin' => 'anonymous',
            ];
            $hints[] = [
                'href'        => 'https://fonts.gstatic.com',
                'crossorigin' => 'anonymous',
            ];
        }
        return $hints;
    }, 10, 2 );

    // Enqueue with display=swap
    $font_url = add_query_arg( [
        'family'  => urlencode( $font_family . ':wght@400;600;700' ),
        'display' => 'swap',
    ], 'https://fonts.googleapis.com/css2' );

    wp_enqueue_style(
        'wpepp-google-font-' . sanitize_title( $font_family ),
        esc_url_raw( $font_url ),
        [],
        null // No version for external resource
    );
}
```

### Performance Checklist

| Check | Target | How |
|-------|--------|-----|
| Frontend HTTP requests (normal page) | **0** | Conditional `is_singular()` + `post_password_required()` checks before any enqueue |
| Frontend HTTP requests (password page) | **1 CSS** | Single small CSS file + inline styles |
| Login page HTTP requests | **0 extra** | Inline CSS via `wp_add_inline_style('login', ...)` — piggybacks on existing WP login CSS |
| Admin page (non-plugin) | **0** | `$hook` check exits before any enqueue |
| JS on frontend | **0** (except browser/referrer conditions) | Server-side rendering for all features, no client JS |
| Icon fonts (Font Awesome, etc.) | **0 loaded** | All icons are inline SVG — zero HTTP requests |
| Google Fonts | **0 unless custom font set** | Default = system fonts, Google Fonts loaded only on-demand with `display=swap` |
| Inline CSS size | **< 5KB** | Generated CSS is minimal, only includes changed properties |
| `autoload` on options | `yes` only for active settings | Large options like login log use `autoload=no` |
| Database queries per page | **0-1** | `get_option()` uses WP object cache; `get_post_meta()` auto-loaded in main query |

### What We Do NOT Do (Anti-Patterns)

```
✗ Load a global CSS file on every page "just in case"
✗ Load Font Awesome / icon font library for a few icons
✗ Load admin JS/CSS outside our plugin page
✗ Load Google Fonts globally when only login page uses it
✗ Use jQuery as dependency for frontend scripts (keep frontend zero-jQuery)
✗ Enqueue scripts/styles inside init or wp_loaded hooks
✗ Use inline <script> or <style> tags directly
✗ Load all settings from DB on every page load
✗ Register global wp_footer or wp_head hooks that run on every page
✗ Use wp_enqueue_scripts without checking is_singular() or the page context
✗ Load media uploader scripts globally (only on admin plugin page)
✗ Echo CSS/JS in template files — always use wp_add_inline_style/script
```

### Admin React Bundle — Code Splitting with React.lazy()

Heavy admin components that aren't needed immediately should be loaded on-demand using `React.lazy()` + `Suspense`. This reduces the initial admin page load time.

```jsx
// src/App.jsx — Route-based code splitting
import { lazy, Suspense } from '@wordpress/element';
import { Spinner } from '@wordpress/components';

// These are loaded ONLY when the user navigates to their route:
const TemplatesPage   = lazy( () => import( './pages/TemplatesPage' ) );
const ContentLockPage = lazy( () => import( './pages/ContentLockPage' ) );
const ConditionalPage = lazy( () => import( './pages/ConditionalDisplayPage' ) );
const SecurityPage    = lazy( () => import( './pages/SecurityPage' ) );
const SettingsPage    = lazy( () => import( './pages/SettingsPage' ) );

// DashboardPage and FormStylePage are eagerly loaded (most visited):
import DashboardPage from './pages/DashboardPage';
import FormStylePage from './pages/FormStylePage';

function App() {
    return (
        <HashRouter>
            <Layout>
                <Suspense fallback={ <Spinner /> }>
                    <Routes>
                        <Route path="/"             element={ <DashboardPage /> } />
                        <Route path="/form-style/*" element={ <FormStylePage /> } />
                        <Route path="/templates"    element={ <TemplatesPage /> } />
                        <Route path="/content-lock"  element={ <ContentLockPage /> } />
                        <Route path="/conditional/*" element={ <ConditionalPage /> } />
                        <Route path="/security/*"    element={ <SecurityPage /> } />
                        <Route path="/settings/*"    element={ <SettingsPage /> } />
                    </Routes>
                </Suspense>
            </Layout>
        </HashRouter>
    );
}
```

**Also lazy-load heavy components within pages:**
```jsx
// src/components/CodeEditor.jsx is heavy (uses CodeMirror-like syntax highlighting)
const CodeEditor  = lazy( () => import( './components/CodeEditor' ) );

// src/components/GoogleFonts.jsx fetches the Google Fonts API list
const GoogleFonts = lazy( () => import( './components/GoogleFonts' ) );
```

**Result:** Initial admin bundle loads only Dashboard + Form Style code. Other pages load on first navigation (~100-200ms on cached connection). Users won't notice the split.

---

## SVG Icon Strategy

### Why Inline SVG (Not Icon Fonts)

| Aspect | Icon Font (Font Awesome) | Inline SVG (Our Choice) |
|--------|------------------------|--------------------------|
| HTTP requests | 1-2 (CSS + WOFF2 file) | **0** (bundled in JS) |
| File size loaded | ~30-80KB (even subset) | **0KB extra** (SVG inlined in React components, tree-shaken) |
| Render blocking | Can cause FOIT/FOUT | **None** — renders instantly |
| Accessibility | Needs `aria-hidden` hack | Native `role="img"` + `aria-label` |
| Styling | Limited (color only) | Full CSS control (fill, stroke, size, animation) |
| Frontend impact | Loads on every page | **Zero** — icons only exist in admin React bundle |
| Caching | Separate cache entry | Part of main JS bundle (single cache) |
| WordPress.org review | External asset concern | **Compliant** — no external resources |

### Icon Sources (GPL-Compatible)

1. **`@wordpress/icons`** — already a dependency, provides ~200 WordPress-native SVG icons. Use these FIRST.
2. **Custom SVG** — for plugin-specific icons not in `@wordpress/icons`, create minimal hand-crafted SVGs.
3. **Heroicons** (MIT license) — if more variety needed. Import individually (tree-shakeable).

**NEVER use:** Font Awesome, Material Icons, Dashicons font file, or any icon from CDN.

### Admin Panel Icons (React — SVG Components)

```jsx
// src/icons/index.js — Central barrel export
// All icons are React components that render inline SVG.
// Tree-shaking ensures unused icons are NOT in the final bundle.

export { default as DashboardIcon } from './DashboardIcon';
export { default as FormStyleIcon } from './FormStyleIcon';
export { default as ContentIcon } from './ContentIcon';
export { default as SecurityIcon } from './SecurityIcon';
export { default as SettingsIcon } from './SettingsIcon';
// ...
```

```jsx
// src/icons/LockIcon.jsx — Example custom SVG icon component
const LockIcon = ( { size = 24, className = '' } ) => (
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        width={ size }
        height={ size }
        className={ `wpepp-icon ${ className }` }
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
        strokeLinejoin="round"
        role="img"
        aria-hidden="true"
        focusable="false"
    >
        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
    </svg>
);
export default LockIcon;
```

```jsx
// Prefer @wordpress/icons when available
import { Icon, lock, settings, page, shield } from '@wordpress/icons';

// Usage in Sidebar.jsx
<Icon icon={ lock } size={ 20 } />
```

### Frontend Social Icons (Password Form — PHP SVG)

The password form has social media icons (Facebook, Twitter, etc.). Currently these likely use icon fonts. In v2, use **inline SVG in PHP**:

```php
/**
 * Render social icon as inline SVG.
 * Zero HTTP requests. Zero font files. Zero frontend JS.
 *
 * @param string $network Social network name.
 * @param string $url     Profile URL.
 */
function wpepp_social_icon_svg( $network, $url ) {
    if ( empty( $url ) ) {
        return;
    }

    $icons = [
        'facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'twitter'   => '<path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/>',
        'youtube'   => '<path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19.1c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.35 29 29 0 0 0-.46-5.33z"/><polygon points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"/>',
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
        'linkedin'  => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
        'pinterest' => '<path d="M12 2C6.48 2 2 6.48 2 12c0 4.25 2.67 7.9 6.44 9.34-.09-.78-.17-1.99.04-2.85.19-.78 1.22-5.16 1.22-5.16s-.31-.62-.31-1.54c0-1.45.84-2.53 1.89-2.53.89 0 1.32.67 1.32 1.47 0 .9-.57 2.24-.87 3.48-.25 1.05.52 1.9 1.55 1.9 1.86 0 3.29-1.96 3.29-4.79 0-2.51-1.8-4.26-4.38-4.26-2.98 0-4.74 2.24-4.74 4.55 0 .9.35 1.87.78 2.39.09.1.1.19.07.3-.08.33-.26 1.04-.29 1.18-.05.19-.15.23-.35.14-1.31-.61-2.13-2.52-2.13-4.06 0-3.31 2.41-6.35 6.94-6.35 3.65 0 6.48 2.6 6.48 6.07 0 3.62-2.28 6.54-5.46 6.54-1.07 0-2.07-.55-2.41-1.21l-.66 2.5c-.24.91-.88 2.05-1.31 2.75A10 10 0 0 0 22 12c0-5.52-4.48-10-10-10z"/>',
        'tumblr'    => '<path d="M14.5 2H9.5v5H7v3h2.5v5.5a5 5 0 0 0 5 5h2.5v-3h-2.5a2 2 0 0 1-2-2V10h4V7h-4V2z"/>',
    ];

    if ( ! isset( $icons[ $network ] ) ) {
        return;
    }

    printf(
        '<a href="%s" target="_blank" rel="noopener noreferrer" class="wpepp-social-icon wpepp-social-%s" aria-label="%s">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true">%s</svg>
        </a>',
        esc_url( $url ),
        esc_attr( $network ),
        /* translators: %s: Social network name */
        esc_attr( sprintf( __( 'Visit our %s page', 'wp-edit-password-protected' ), ucfirst( $network ) ) ),
        $icons[ $network ] // Static SVG paths — no user input, safe to output
    );
}
```

### Admin Menu Icon (SVG Base64 — WordPress Standard)

```php
// For the admin sidebar menu item, WordPress accepts base64 SVG:
add_menu_page(
    __( 'Password Protected', 'wp-edit-password-protected' ),
    __( 'Password Protected', 'wp-edit-password-protected' ),
    'manage_options',
    'wpepp-settings',
    'wpepp_render_admin_page',
    // SVG icon encoded as data URI (WordPress standard approach for custom menu icons)
    'data:image/svg+xml;base64,' . base64_encode(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">' .
        '<rect x="3" y="11" width="18" height="11" rx="2" ry="2" fill="none" stroke="currentColor" stroke-width="2"/>' .
        '<path d="M7 11V7a5 5 0 0 1 10 0v4" fill="none" stroke="currentColor" stroke-width="2"/>' .
        '</svg>'
    ),
    30
);
```

### Frontend Icon Impact Summary

| Where | Icons Used | Method | HTTP Requests | File Size |
|-------|-----------|--------|---------------|-----------|
| Password form (social) | Facebook, Twitter, etc. | PHP inline SVG (`wpepp_social_icon_svg()`) | **0** | ~200 bytes per icon (inline in HTML) |
| Content lock message | Lock icon | PHP inline SVG | **0** | ~100 bytes |
| Member-only template | Lock/login icon | PHP inline SVG | **0** | ~100 bytes |
| Login page | No custom icons by default | — | **0** | 0 |
| Admin sidebar menu | Lock icon | Base64 data URI (WP standard) | **0** | ~300 bytes |
| Admin React panel | 15-20 icons | Inline SVG via React components + `@wordpress/icons` | **0** | Part of React bundle (tree-shaken) |
| **Total frontend icon cost** | | | **0 requests** | **< 1KB** inline |

---

## WordPress Coding Standards

### PHP Coding Standards (WPCS)

All PHP code MUST follow the [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).

**File Headers — every PHP file:**
```php
<?php
/**
 * Class description.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;
```

**Naming Conventions:**

| Type | Convention | Example |
|------|-----------|--------|
| Functions | `wpepp_` prefix, snake_case | `wpepp_get_settings()` |
| Classes | `WPEPP_` prefix, Title_Case | `WPEPP_Rest_Api` |
| Constants | `WPEPP_` prefix, UPPER_CASE | `WPEPP_VERSION` |
| Hooks (actions/filters) | `wpepp/` or `wpepp_` prefix | `wpepp/settings/saved` |
| Options | `wpepp_` prefix | `wpepp_login_settings` |
| Post Meta | `_wpepp_` prefix (underscore = hidden) | `_wpepp_content_lock_enabled` |
| REST namespace | `wpepp/v1` | `/wp-json/wpepp/v1/settings` |
| Transients | `wpepp_` prefix | `wpepp_preview_{token}` |
| Nonces | `wpepp_` prefix | `wpepp_save_settings` |
| CSS classes | `wpepp-` prefix | `.wpepp-admin-wrap` |
| JS globals | `wpeppData` | `window.wpeppData` |
| Text domain | `wp-edit-password-protected` | `__( 'Save', 'wp-edit-password-protected' )` |

**Required WordPress Functions (use these instead of PHP natives):**

| Instead Of | Use | Why |
|-----------|-----|-----|
| `json_encode()` | `wp_json_encode()` | Handles encoding issues, UTF-8 safe |
| `cURL` / `file_get_contents(URL)` | `wp_remote_get()` / `wp_remote_post()` | Respects WP proxy, SSL, timeout settings |
| `wp_redirect()` | `wp_safe_redirect()` + `exit` | Validates redirect URL against allowed hosts |
| `die()` / `exit()` | `wp_die()` | Proper WP error page, hooks for cleanup |
| `date()` | `wp_date()` or `gmdate()` | Timezone-aware, no PHP deprecation warnings |
| `strip_tags()` | `wp_strip_all_tags()` | More thorough, handles edge cases |
| `htmlspecialchars()` | `esc_html()` / `esc_attr()` | Context-aware escaping |
| `$_GET['x']` raw | `sanitize_text_field( wp_unslash( $_GET['x'] ) )` | Sanitize + unslash |
| `serialize()` | `maybe_serialize()` | WP-safe serialization |
| `unserialize()` | `maybe_unserialize()` | Prevents object injection attacks |
| `file_put_contents()` | WP_Filesystem API | Respects file permissions, FTP credentials |
| Raw SQL | `$wpdb->prepare()` | Prevents SQL injection |
| `header('Location:')` | `wp_safe_redirect()` | Validates URL, proper headers |

**Forbidden Patterns (WordPress.org reviewers automatically flag these):**

```php
// ❌ WILL BE REJECTED in plugin review
eval( ... );
create_function( ... );
extract( $array );                     // Use explicit variable assignment
$$variable_name;                       // Variable variables
base64_decode() on execution paths;    // Obfuscation flag
file_get_contents() for remote URLs;   // Use wp_remote_get()
file_put_contents();                   // Use WP_Filesystem API
serialize() / unserialize();           // Use maybe_serialize()
cURL functions directly;               // Use WP HTTP API
error_reporting( 0 );                  // Never suppress errors
ini_set( 'display_errors', ... );      // Let WP_DEBUG handle this
call_user_func() with user input;      // Code execution risk
preg_replace() with /e modifier;       // Code execution — use preg_replace_callback()
header() directly;                     // Use wp_safe_redirect() + wp_die()
echo '<script>...</script>';           // Use wp_add_inline_script()
wp_redirect() without exit;            // Always exit after redirect
$_SERVER['REQUEST_URI'] unescaped;     // Always esc_url() or sanitize
```

**PHP Code Style (WPCS enforced):**
- Spaces inside parentheses: `if ( $condition ) {`
- Yoda conditions: `if ( true === $value ) {`
- Braces on same line: `} elseif ( $x ) {`
- No shorthand PHP: always `<?php`, never `<?` or `<?=`
- Single-line array syntax for 3 or fewer items, multi-line for 4+
- Use `===` strict comparison by default, `==` only when intentional

### JavaScript Coding Standards

- Follow [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- Use `@wordpress/scripts` ESLint config (extends `@wordpress/eslint-plugin`)
- All user-facing strings wrapped in `__()` or `_x()` from `@wordpress/i18n`
- Use `wp.apiFetch` (from `@wordpress/api-fetch`) for REST calls — automatically handles nonce
- No `console.log()` in production code (use ESLint rule `no-console`)
- No inline event handlers — use React event system
- Use `wp.data` for state management — no direct `fetch()` calls to REST API
- Text domain `wp-edit-password-protected` for all `__()` calls

### CSS/SCSS Standards

- Follow [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- All custom classes prefixed with `wpepp-`
- No `!important` unless absolutely necessary (document why in comment)
- Use `wp-components` styles as base — don't override WP admin styles globally
- Admin styles scoped inside `.wpepp-admin-wrap` to prevent leaking into WP admin
- No external CDN resources — bundle everything or use WP-registered assets
- Use `wp_add_inline_style()` for dynamic CSS — never echo `<style>` directly

### Internationalization (i18n)

```php
// PHP — all user-facing strings
__( 'Save Settings', 'wp-edit-password-protected' )
esc_html__( 'Password', 'wp-edit-password-protected' )
esc_attr__( 'Submit', 'wp-edit-password-protected' )
_e( 'Settings saved.', 'wp-edit-password-protected' )
esc_html_e( 'Error occurred.', 'wp-edit-password-protected' )
sprintf( __( 'Locked %d posts.', 'wp-edit-password-protected' ), $count )
_n( '%d post', '%d posts', $count, 'wp-edit-password-protected' )
_x( 'Post', 'noun', 'wp-edit-password-protected' ) // disambiguation
```

```js
// JS — import from @wordpress/i18n
import { __, _n, sprintf } from '@wordpress/i18n';
__( 'Save', 'wp-edit-password-protected' );
sprintf( __( '%d items', 'wp-edit-password-protected' ), count );
```

- Text domain MUST match plugin slug: `wp-edit-password-protected`
- Generate POT file: `wp i18n make-pot . languages/wp-edit-password-protected.pot`
- For JS translations: `wp i18n make-json languages/` + `wp_set_script_translations()`

---

## Admin Panel - React App

### Navigation Structure — Two-Level Tab System

The admin panel uses **vertical parent tabs** (left sidebar) and **horizontal inner tabs** (top bar inside each parent). This keeps all related features grouped without deep nesting.

```
┌──────────────────┬──────────────────────────────────────────────┐
│                  │                                              │
│  VERTICAL TABS   │  HORIZONTAL INNER TABS (top)                 │
│  (left sidebar)  │                                              │
│                  │                                              │
│  ┌────────────┐  │                                              │
│  │ Dashboard  │  │  (no inner tabs — single overview page)      │
│  ├────────────┤  │                                              │
│  │ Form Style │  │  [Login] [Register] [Lost Password] [Password]│
│  ├────────────┤  │                                              │
│  │ Content    │  │  [Content Lock] [Conditional Display]        │
│  ├────────────┤  │  [Member Template]                           │
│  │ Templates  │  │  (no inner tabs — template gallery)          │
│  ├────────────┤  │                                              │
│  │ Security   │  │  [Login Protection] [Custom Login URL]       │
│  │            │  │  [Login Log]                                 │
│  ├────────────┤  │                                              │
│  │ Settings   │  │  [General] [Custom CSS]                      │
│  └────────────┘  │                                              │
│                  │                                              │
└──────────────────┴──────────────────────────────────────────────┘
```

### Parent Tab → Inner Tab Breakdown

| Vertical Tab (Parent) | Horizontal Inner Tabs | Description |
|----------------------|----------------------|-------------|
| **Dashboard** | — | Overview: stats, locked posts count, conditional posts count, quick actions |
| **Form Style** | Login Form \| Register Form 🔒 \| Lost Password Form 🔒 \| Password Form | Each inner tab = live preview editor. Register + Lost Password = **Pro**. Password Form: Style 1-2 free, Style 3-4 **Pro** |
| **Content** | Content Lock 🔒 \| Conditional Display \| Member Template | Content Lock = **Pro**. Conditional: 2 conditions free, 12 conditions **Pro**. Member: basic free, advanced **Pro** |
| **Templates** | — | 3 free templates, rest **Pro**. Import/Export = **Pro** |
| **Security** | Login Protection \| Custom Login URL 🔒 \| Login Log 🔒 | Limiter + Honeypot = free. reCAPTCHA + Custom URL + Login Log = **Pro** |
| **Settings** | General \| Custom CSS 🔒 | General = free. Custom CSS editor = **Pro** |

### Full Layout — Vertical + Horizontal + Live Preview

```
┌──────────────┬──────────────────────────────────────────────────────┐
│              │  [Login Form] [Register Form] [Lost Password] [Password Form]
│              │                                    ← Inner tabs (top)│
│              ├──────────────┬───────────────────────────────────────┤
│  Dashboard   │              │                                      │
│              │  Style       │      Live Preview (iframe)            │
│ ►Form Style  │  Controls    │                                      │
│              │              │  ┌────────────────────────────┐       │
│  Content     │  ▸ Background│  │                            │       │
│              │  ▸ Logo      │  │   wp-login.php preview     │       │
│  Templates   │  ▸ Form      │  │   (updates in real-time)   │       │
│              │  ▸ Fields    │  │                            │       │
│  Security    │  ▸ Button    │  └────────────────────────────┘       │
│              │  ▸ Links     │                                      │
│  Settings    │  ▸ Custom CSS│  ┌──────┐ ┌──────┐ ┌──────┐         │
│              │              │  │Desktop│ │Tablet│ │Mobile│         │
│              │              │                                      │
├──────────────┴──────────────┴───────────────────────────────────────┤
│  Footer: Plugin version | Docs | Support                           │
└────────────────────────────────────────────────────────────────────┘
```

**When "Password Form" inner tab is active**, it shows sub-tabs for the 4 form styles:

```
 Inner tab:  [Login Form] [Register Form] [Lost Password] [►Password Form]
 Sub-tabs:   [Style 1] [Style 2] [Style 3] [Style 4]
             ├──────────────┬───────────────────────────────────────┤
             │              │                                      │
             │  ▸ Top Text  │   Password form preview              │
             │  ▸ Form      │   (Style 1 layout active)            │
             │  ▸ Labels    │                                      │
             │  ▸ Button    │                                      │
             │  ▸ Social    │                                      │
             │  ▸ Bottom    │                                      │
             │  ▸ Error Msg │                                      │
             │              │                                      │
```

### How Live Preview Works:

1. Admin clicks a **vertical parent tab** (e.g., "Form Style")
2. **Inner horizontal tabs** appear (Login Form | Register Form | Lost Password | Password Form)
3. Clicking an inner tab loads the iframe: `?wpepp_preview=1&type=login` or `&type=password&style=two`
4. When user changes a setting, React generates CSS in real-time
5. CSS is injected into the iframe via `postMessage()` API
6. On save, settings are sent to REST API and stored in `wp_options`
7. Frontend generates CSS from saved settings on actual pages

```
React App                          iframe (preview page)
    │                                      │
    │  1. User clicks [Form Style] tab     │
    │  2. Inner tabs appear                │
    │  3. User clicks [Password Form]      │
    │  4. Sub-tabs: [Style 1] [Style 2]... │
    │  5. iframe loads ?type=password       │
    │     &style=two                       │
    │  6. User changes a color             │
    │  7. Generate CSS string              │
    │  8. window.postMessage({css})  ───►  │
    │                                      │  9. Apply CSS to <style> tag
    │                                      │  10. Instant visual update
    │                                      │
    │  11. User clicks "Save"              │
    │  12. POST /wp-json/wpepp/v1/settings │
    │  13. Stored in wp_options            │
```

### React Route Structure (Hash Router)

```
#/                              → Dashboard (default)
#/form-style                    → Form Style → defaults to Login Form inner tab
#/form-style/login              → Form Style → Login Form (live editor)
#/form-style/register           → Form Style → Register Form (live editor)
#/form-style/lost-password      → Form Style → Lost Password Form (live editor)
#/form-style/password           → Form Style → Password Form → defaults to Style 1
#/form-style/password/:style    → Form Style → Password Form → Style 1/2/3/4 sub-tab
#/content                       → Content → defaults to Content Lock inner tab
#/content/lock                  → Content → Content Lock
#/content/conditional           → Content → Conditional Display
#/content/member-template       → Content → Member Template
#/templates                     → Templates (single page, no inner tabs)
#/security                      → Security → defaults to Login Protection inner tab
#/security/protection           → Security → Login Protection
#/security/custom-url           → Security → Custom Login URL
#/security/log                  → Security → Login Log
#/settings                      → Settings → defaults to General inner tab
#/settings/general              → Settings → General
#/settings/custom-css           → Settings → Custom CSS
```

---

## Design Settings Structure

### Login Page Settings Object

```js
const loginSettings = {
  // Page
  page: {
    background_type: 'color',       // color | gradient | image
    background_color: '#f0f0f1',
    background_gradient: '',
    background_image: '',
    background_position: 'center center',
    background_size: 'cover',
    background_overlay: '',
    background_overlay_opacity: 0.5,
  },

  // Logo
  logo: {
    type: 'default',               // default | custom | text | hide
    image: '',
    width: 84,
    height: 84,
    url: '',
    title: '',
    text: '',
    text_color: '#444444',
    text_font_size: 24,
    text_font_family: '',
  },

  // Form Container
  form: {
    width: 320,
    background_color: '#ffffff',
    background_opacity: 1,
    border_radius: 8,
    border_width: 0,
    border_color: '#dddddd',
    border_style: 'solid',
    padding: { top: 26, right: 24, bottom: 26, left: 24 },
    shadow: 'none',                // none | small | medium | large | custom
    shadow_custom: '',
    position: 'center',           // center | left | right
  },

  // Form Heading
  heading: {
    show: false,
    text: 'Welcome Back',
    color: '#333333',
    font_size: 22,
    font_family: '',
    font_weight: '600',
    text_align: 'center',
    margin_bottom: 20,
  },

  // Input Fields
  fields: {
    label_color: '#72777c',
    label_font_size: 14,
    background_color: '#ffffff',
    text_color: '#333333',
    border_color: '#8c8f94',
    border_width: 1,
    border_radius: 4,
    focus_border_color: '#2271b1',
    height: 40,
    font_size: 14,
    placeholder_color: '#999999',
  },

  // Submit Button
  button: {
    text: 'Log In',
    background_color: '#2271b1',
    text_color: '#ffffff',
    font_size: 14,
    font_weight: '600',
    border_radius: 4,
    width: '100%',
    height: 40,
    hover_background: '#135e96',
    hover_text_color: '#ffffff',
    shadow: 'none',
  },

  // Links (Lost password, Back to site)
  links: {
    color: '#50575e',
    hover_color: '#135e96',
    font_size: 13,
    show_lost_password: true,
    show_back_to_site: true,
    custom_lost_password_text: '',
    custom_back_to_site_text: '',
  },

  // Remember Me
  remember_me: {
    show: true,
    label: 'Remember Me',
    color: '#50575e',
  },

  // Error Messages
  error: {
    background_color: '#d63638',
    text_color: '#ffffff',
    border_radius: 4,
  },

  // Footer
  footer: {
    show: true,
    text: '',
    color: '#999999',
    font_size: 12,
  },

  // Custom CSS
  custom_css: '',
};
```

### Password Protected Page Settings

```js
const passwordSettings = {
  // Same structure as old plugin settings (backward compatible)
  form_style: 'four',              // one | two | three | four
  show_top_text: 'on',
  top_text_align: 'center',
  top_header: 'This content is password protected',
  top_content: '',
  show_bottom_text: 'off',
  bottom_text_align: 'left',
  bottom_header: '',
  bottom_content: '',
  form_label: 'Password',
  form_btn_text: 'Submit',
  form_errortext: '',
  error_text_position: 'top',
  show_social: 'on',
  icons_vposition: 'top',
  icons_alignment: 'right',
  icons_style: 'square',
  link_facebook: '',
  link_twitter: '',
  link_youtube: '',
  link_instagram: '',
  link_linkedin: '',
  link_pinterest: '',
  link_tumblr: '',
  link_custom: '',

  // NEW v2 style options
  page_background: '',
  form_background: '#ffffff',
  form_border_radius: 8,
  form_shadow: 'medium',
  input_border_color: '#8c8f94',
  button_color: '#2271b1',
  button_text_color: '#ffffff',
  custom_css: '',
};
```

---

## NEW Feature: Content Lock (Lock Posts/Pages for Logged-Out Users)

Per-post/page toggle that hides the entire content from logged-out users and shows a customizable message with a login button/form instead.

### How It Works

1. **Post Editor:** A meta box "Content Lock" in the post/page sidebar with:
   - Toggle: "Lock this content for logged-out users"
   - Locked message (text field, default: "This content is for members only")
   - Action: Show login form / Show login link / Redirect to login page
   - Custom redirect URL (optional)

2. **Admin Dashboard (React):** A "Content Lock" page showing:
   - Table of all locked posts/pages with columns: Title, Post Type, Lock Status, Date
   - Quick toggle lock on/off from the table
   - Bulk lock/unlock actions
   - Filter by post type
   - Search

3. **Frontend Behavior:**
   - Logged-out user visits a locked post → sees locked message + login form/link
   - Logged-in user → sees full content normally
   - REST API responses also filtered (content hidden for unauthenticated requests)

### Post Meta

```
_wpepp_content_lock_enabled    → 'yes' / 'no'
_wpepp_content_lock_message    → string (custom locked message)
_wpepp_content_lock_action     → 'form' / 'link' / 'redirect'
_wpepp_content_lock_redirect   → string (URL for redirect action)
```

### Difference from Conditional Display

| Feature | Content Lock | Conditional Display |
|---------|-------------|-------------------|
| Purpose | Simple members-only lock | Complex multi-condition rules |
| Conditions | Logged-in only | 12+ conditions (role, device, time, etc.) |
| UI | Simple toggle | Advanced rule builder |
| Message | Customizable locked message | Content hidden (empty) |
| Login form | Shows login form/link option | No login form |

---

## Expanded Conditional Display (Admin Dashboard)

The existing conditional display meta box currently only enables 2 conditions (logged_in / logged_out). In v2, **all 12 conditions will be enabled** and managed from both the post editor AND the React admin dashboard.

### Conditions (all enabled in v2)

| Condition | Description | Implementation |
|-----------|-------------|----------------|
| `user_logged_in` | User is logged in | Server-side (existing) |
| `user_logged_out` | User is logged out | Server-side (existing) |
| `user_role` | User has specific role(s) | Server-side (existing code, was commented out) |
| `device_type` | Desktop / Tablet / Mobile | Server-side via wp_is_mobile() (existing code) |
| `day_of_week` | Specific days | Server-side (existing code) |
| `time_of_day` | Time range (09:00 - 17:00) | Server-side (existing code) |
| `date_range` | Date range (start - end) | Server-side (existing code) |
| `recurring_schedule` | Recurring days + time | Server-side (existing code) |
| `post_type` | Current post type | Server-side (existing code) |
| `browser_type` | Chrome / Firefox / Safari / etc. | Client-side JS (existing code) |
| `url_parameter` | URL has specific parameter | Server-side (existing code) |
| `referrer_source` | Visitor came from specific URL | Client-side JS (existing code) |

### Admin Dashboard Page (React)

```
┌─────────────────────────────────────────────────────────┐
│  Conditional Display                                     │
├─────────────────────────────────────────────────────────┤
│  Filter: [All] [Posts] [Pages]  Search: [________]      │
├─────────────────────────────────────────────────────────┤
│  ☑ │ Title            │ Type │ Condition      │ Action  │
│  ──┼──────────────────┼──────┼────────────────┼─────────│
│  ☐ │ Premium Guide    │ Post │ User logged in │ Show    │
│  ☐ │ Members Area     │ Page │ User role: Admin│ Show   │
│  ☐ │ Weekend Deal     │ Post │ Day: Sat, Sun  │ Show    │
│  ☐ │ Holiday Banner   │ Page │ Date range     │ Show    │
│  ☐ │ Mobile Promo     │ Post │ Device: Mobile │ Show    │
├─────────────────────────────────────────────────────────┤
│  Bulk Actions: [Disable Selected] [Apply]               │
└─────────────────────────────────────────────────────────┘
```

---

## REST API Endpoints

```
Namespace: wpepp/v1

# Design Settings
GET    /settings                    # Get all settings
POST   /settings                    # Save all settings (Pro fields stripped server-side for Free)
GET    /settings/{section}          # Get settings for a section (login, register, password, security)
POST   /settings/{section}          # Save settings for a section (register/lostpassword = Pro only)

# Pro Status
GET    /pro/status                  # Get Pro status { isPro: bool }

# Templates
GET    /templates                   # List available templates (response includes isFree flag per template)
POST   /templates/apply             # Apply a template (Pro templates require wpepp_has_pro_check)
POST   /templates/export            # Export current settings (PRO)
POST   /templates/import            # Import settings JSON (PRO)

# Content Lock (PRO)
GET    /content-lock                # List all locked posts (Pro required)
POST   /content-lock/{post_id}      # Toggle lock on a post (Pro required)
GET    /content-lock/{post_id}      # Get lock settings for a post (Pro required)
PUT    /content-lock/{post_id}      # Update lock settings (Pro required)
POST   /content-lock/bulk           # Bulk lock/unlock posts (Pro required)

# Conditional Display
GET    /conditional                 # List all posts with conditional display (dashboard = Pro)
GET    /conditional/{post_id}       # Get conditional settings for a post
PUT    /conditional/{post_id}       # Update conditional settings (Pro conditions stripped for Free)
POST   /conditional/{post_id}/toggle # Enable/disable conditional display

# Security
GET    /security/log                # Get login log entries (PRO)
DELETE /security/log                # Clear login log (PRO)

# Preview
POST   /preview/css                 # Generate preview CSS (for iframe)
GET    /preview/url                 # Get preview URL with token
```

### REST API Security Requirements

Every REST route MUST include all four security layers:

```php
register_rest_route( 'wpepp/v1', '/settings/(?P<section>[a-z_-]+)', [
    'methods'             => 'POST',
    'callback'            => [ $this, 'save_settings' ],
    'permission_callback' => [ $this, 'check_admin_permission' ],  // 1. CAPABILITY CHECK
    'args'                => [                                     // 2. SCHEMA VALIDATION
        'section' => [
            'type'              => 'string',
            'required'          => true,
            'sanitize_callback' => 'sanitize_text_field',          // 3. INPUT SANITIZATION
            'validate_callback' => function( $value ) {            // 4. WHITELIST VALIDATION
                return in_array(
                    $value,
                    [ 'login', 'register', 'password', 'lostpassword', 'security', 'general', 'member_template' ],
                    true
                );
            },
        ],
        'settings' => [
            'type'              => 'object',
            'required'          => true,
            'sanitize_callback' => [ $this, 'sanitize_settings_object' ],
        ],
    ],
] );

public function check_admin_permission() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error(
            'wpepp_rest_forbidden',
            __( 'You do not have permission to access this resource.', 'wp-edit-password-protected' ),
            [ 'status' => 403 ]
        );
    }
    return true;
}
```

**Permission Levels per Endpoint:**

| Endpoint | HTTP Method | Required Capability | Reason |
|----------|------------|--------------------|---------|
| `/settings/*` | GET/POST | `manage_options` | Admin-only plugin settings |
| `/templates/*` | GET/POST | `manage_options` | Design templates |
| `/content-lock/*` | GET/POST/PUT | `edit_posts` | Per-post lock control |
| `/content-lock/bulk` | POST | `edit_others_posts` | Bulk operations need higher cap |
| `/conditional/*` | GET/PUT | `edit_posts` | Per-post conditional rules |
| `/security/log` | GET | `manage_options` | Sensitive security data |
| `/security/log` | DELETE | `manage_options` | Destructive action |
| `/preview/url` | GET | `manage_options` | Admin preview only |

**Nonce Handling:**
- `@wordpress/api-fetch` automatically sends `X-WP-Nonce` header when registered as a script dependency
- WordPress core validates the nonce via `rest_cookie_check_errors()` — no manual nonce check needed in REST callbacks
- Preview iframe URL uses a one-time transient token instead of nonce (iframe may not share admin cookies)

**NEVER do this:**
```php
// ❌ WRONG — open endpoint, no permission check
'permission_callback' => '__return_true'   // for write endpoints

// ❌ WRONG — capability check in callback instead of permission_callback
'permission_callback' => '__return_true',
'callback' => function( $request ) {
    if ( ! current_user_can( 'manage_options' ) ) { ... }  // Too late — already processed
}
```

---

## WordPress Data Store (`@wordpress/data`)

```js
// Store name: 'wpepp/settings'

// Actions
saveSettings(section, settings)     // Save settings to API (Pro fields enforced server-side)
updateSetting(section, key, value)  // Update single setting (local)
resetSettings(section)              // Reset to defaults
applyTemplate(templateId)           // Apply a template (Pro check server-side)
importSettings(json)                // Import settings (Pro only)
fetchLoginLog()                     // Fetch login activity (Pro only)

// Selectors
getSettings(section)                // Get settings for section
getSetting(section, key)            // Get single setting value
isPro()                             // Pro status from wpeppData.isPro
isLoading()                         // Loading state
isSaving()                          // Saving state
hasChanges()                        // Unsaved changes
getTemplates()                      // Get all templates (includes isFree flag)
getLoginLog()                       // Get login log entries (Pro only)
```

---

## Security Features

### Free Security Features
| Feature | Settings Key | Default |
|---------|-------------|---------|
| Login attempt limiter | `security.login_limit_enabled` | true |
| Max attempts before lockout | `security.max_attempts` | 5 |
| Lockout duration (minutes) | `security.lockout_duration` | 15 |
| Disable XML-RPC | `security.disable_xmlrpc` | true |
| Hide WP version | `security.hide_wp_version` | true |
| Disable REST user enum | `security.disable_rest_users` | true |
| Honeypot field | `security.honeypot_enabled` | true |

### Pro Security Features (requires `wpepp_has_pro_check() === true`)
| Feature | Settings Key | Default |
|---------|-------------|---------|
| reCAPTCHA v2/v3 | `security.recaptcha_enabled` | false |
| reCAPTCHA site key | `security.recaptcha_site_key` | '' |
| reCAPTCHA secret key | `security.recaptcha_secret_key` | '' |
| Custom login URL | `security.custom_login_url` | '' |
| Login log (30 days) | `security.login_log_enabled` | true |

### Pro Version — Future Roadmap
- 2FA (Email + TOTP)
- Country-based blocking
- Email alerts on login
- Session management
- Temporary login links
- Device trust
- Extended log (export, filters)

---

## Security Implementation Guide

### Input Sanitization — EVERY Input, EVERY Time

**Rule: Never trust any data from `$_GET`, `$_POST`, `$_REQUEST`, `$_SERVER`, `$_COOKIE`, or REST request params.**

| Data Type | Sanitize Function | Plugin Usage |
|-----------|------------------|--------------|
| Plain text | `sanitize_text_field()` | Form labels, button text, error messages |
| Textarea / HTML | `wp_kses_post()` | Top/bottom text content, locked message |
| Integer | `absint()` | Max attempts, lockout duration, post IDs |
| Boolean | `rest_sanitize_boolean()` | Toggle switches (show_social, lock_enabled) |
| URL | `esc_url_raw()` for DB / `sanitize_url()` 5.9+ | Social links, login URL, redirect URL |
| Email | `sanitize_email()` | Alert email fields |
| Hex color | `sanitize_hex_color()` | All color picker values |
| CSS class name | `sanitize_html_class()` | Custom CSS classes |
| File name | `sanitize_file_name()` | Template import filenames |
| Slug | `sanitize_title()` | Custom login URL slug |
| Array | `array_map( 'sanitize_text_field', $arr )` | Role multi-select, day checkboxes |
| JSON input | Decode → validate schema → sanitize each field → re-encode | Settings import, template JSON |
| Option key | Whitelist check `in_array( $key, $allowed, true )` | Settings section parameter |
| HTML tag name | Whitelist `in_array( $tag, [ 'h1','h2','h3','h4','h5','h6','p','span' ], true )` | Title tag selector |
| Select value | Whitelist `in_array( $val, $options, true )` | Form style, alignment, mode |

**Pattern — Sanitize $_POST in meta box save:**
```php
public function save_meta_box( $post_id ) {
    // 1. Verify nonce
    if ( ! isset( $_POST['wpepp_meta_nonce'] )
        || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpepp_meta_nonce'] ) ), 'wpepp_save_meta' )
    ) {
        return;
    }

    // 2. Check capability
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // 3. Check autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // 4. Sanitize and save
    $enabled = isset( $_POST['_wpepp_content_lock_enabled'] ) ? 'yes' : 'no';
    update_post_meta( $post_id, '_wpepp_content_lock_enabled', $enabled );

    if ( isset( $_POST['_wpepp_content_lock_message'] ) ) {
        update_post_meta(
            $post_id,
            '_wpepp_content_lock_message',
            wp_kses_post( wp_unslash( $_POST['_wpepp_content_lock_message'] ) )
        );
    }

    if ( isset( $_POST['_wpepp_content_lock_action'] ) ) {
        $action  = sanitize_text_field( wp_unslash( $_POST['_wpepp_content_lock_action'] ) );
        $allowed = [ 'form', 'link', 'redirect' ];
        if ( in_array( $action, $allowed, true ) ) {
            update_post_meta( $post_id, '_wpepp_content_lock_action', $action );
        }
    }
}
```

### Output Escaping — EVERY Output, EVERY Time

**Rule: Escape as late as possible, as close to output as possible.**

| Context | Escape Function | When to Use |
|---------|----------------|-------------|
| HTML body text | `esc_html()` | Text inside HTML tags |
| HTML attribute | `esc_attr()` | Inside `class=""`, `value=""`, `title=""` |
| URL (href/src) | `esc_url()` | Inside `href=""`, `src=""`, `action=""` |
| JavaScript string | `esc_js()` | Inline JS (prefer `wp_add_inline_script()` instead) |
| Textarea content | `esc_textarea()` | Inside `<textarea>` tags |
| Rich HTML | `wp_kses_post()` | Trusted user content with allowed tags |
| Strict HTML | `wp_kses( $text, $allowed )` | Controlled subset of HTML |
| CSS in attribute | `esc_attr()` | Inline `style=""` attributes |
| SQL | `$wpdb->prepare()` | Never manually escape SQL |
| Translation+escape | `esc_html__()`, `esc_attr__()` | Translate and escape in one call |

**Examples — Frontend Password Form:**
```php
// ❌ WRONG — unescaped output
echo '<h2>' . $settings['top_header'] . '</h2>';
echo '<a href="' . $url . '">' . $text . '</a>';
echo '<div class="' . $class . '">';

// ✅ RIGHT — escaped output
echo '<h2>' . esc_html( $settings['top_header'] ) . '</h2>';
echo '<a href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
echo '<div class="' . esc_attr( $class ) . '">';

// ✅ RIGHT — printf with escaping
printf(
    '<button type="submit" class="%s">%s</button>',
    esc_attr( $settings['btn_class'] ),
    esc_html( $settings['form_btn_text'] )
);
```

**Dynamic CSS Output:**
```php
// ❌ WRONG — direct CSS output
echo '<style>' . $custom_css . '</style>';

// ✅ RIGHT — use wp_add_inline_style() which handles context
wp_add_inline_style( 'wpepp-frontend', wp_strip_all_tags( $custom_css ) );

// ✅ RIGHT — for generated CSS from settings
$safe_color = sanitize_hex_color( $settings['button_color'] );
$css = sprintf( '.wpepp-form .submit-btn { background-color: %s; }', $safe_color );
wp_add_inline_style( 'wpepp-frontend', $css );
```

### Nonce Verification — EVERY State Change

```php
// ── Meta box form ──
// In form output:
wp_nonce_field( 'wpepp_save_meta', 'wpepp_meta_nonce' );

// In save handler:
if ( ! isset( $_POST['wpepp_meta_nonce'] )
    || ! wp_verify_nonce(
        sanitize_text_field( wp_unslash( $_POST['wpepp_meta_nonce'] ) ),
        'wpepp_save_meta'
    )
) {
    return;
}

// ── REST API ── (automatic)
// @wordpress/api-fetch sends X-WP-Nonce header automatically.
// WordPress core verifies via rest_cookie_check_errors().
// No manual nonce check needed in REST callbacks.

// ── Admin AJAX (if any legacy) ──
check_ajax_referer( 'wpepp_ajax_action', 'nonce' );
```

### Capability Checks — EVERY Admin Action

```php
// Settings save (REST)
'permission_callback' => function() {
    return current_user_can( 'manage_options' );
},

// Post meta save
if ( ! current_user_can( 'edit_post', $post_id ) ) {
    return;
}

// Admin page render
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Unauthorized access.', 'wp-edit-password-protected' ) );
}

// Bulk operations on others' posts
if ( ! current_user_can( 'edit_others_posts' ) ) {
    return new WP_Error( 'wpepp_forbidden', 'Insufficient permissions.', [ 'status' => 403 ] );
}
```

### SQL Safety

```php
// ❌ WRONG — SQL injection
$wpdb->query( "DELETE FROM {$wpdb->prefix}wpepp_login_log WHERE id = {$_GET['id']}" );

// ✅ RIGHT — prepared statement
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}wpepp_login_log WHERE id = %d",
        absint( $id )
    )
);

// ✅ RIGHT — IN clause with prepare
$ids          = array_map( 'absint', $ids );
$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
$wpdb->query(
    $wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}wpepp_login_log WHERE id IN ($placeholders)",
        ...$ids
    )
);

// ✅ RIGHT — table names ALWAYS use $wpdb->prefix
$table = $wpdb->prefix . 'wpepp_login_log';

// ✅ PREFER — WordPress query functions over raw SQL
get_posts(), get_option(), get_post_meta(), WP_Query, WP_User_Query
```

### Custom CSS Sanitization

The Custom CSS field allows user-entered CSS which requires special security handling:

```php
function wpepp_sanitize_css( $css ) {
    if ( empty( $css ) ) {
        return '';
    }

    // Strip all HTML tags
    $css = wp_strip_all_tags( $css );

    // Block dangerous CSS patterns
    $css = preg_replace( '/expression\s*\(/i', '/* blocked */(', $css );
    $css = preg_replace( '/url\s*\(\s*["\']?\s*javascript:/i', 'url(/* blocked */', $css );
    $css = preg_replace( '/@import\s+url\s*\(/i', '/* @import blocked */(', $css );
    $css = preg_replace( '/behavior\s*:/i', '/* behavior blocked */:', $css );
    $css = preg_replace( '/-moz-binding\s*:/i', '/* binding blocked */:', $css );
    $css = preg_replace( '/-webkit-binding\s*:/i', '/* binding blocked */:', $css );

    return $css;
}
```

### Preview URL Token Security

The live preview iframe cannot rely on nonces (may not share admin cookies), so we use a one-time transient token:

```php
// Generate secure preview token
function wpepp_generate_preview_token() {
    $token = wp_generate_password( 32, false );
    set_transient(
        'wpepp_preview_' . $token,
        get_current_user_id(),
        5 * MINUTE_IN_SECONDS
    );
    return $token;
}

// Validate preview token (on iframe load)
function wpepp_validate_preview_token( $token ) {
    $token   = sanitize_text_field( $token );
    $user_id = get_transient( 'wpepp_preview_' . $token );

    if ( ! $user_id || ! user_can( $user_id, 'manage_options' ) ) {
        return false;
    }

    delete_transient( 'wpepp_preview_' . $token ); // One-time use
    return true;
}
```

### Login Security Implementation

```php
// Get client IP — only trust REMOTE_ADDR (proxy headers can be spoofed)
function wpepp_get_client_ip() {
    $ip = isset( $_SERVER['REMOTE_ADDR'] )
        ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
        : '0.0.0.0';

    return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
}

// Record login attempt
function wpepp_record_login_attempt( $username, $success ) {
    global $wpdb;

    $wpdb->insert(
        $wpdb->prefix . 'wpepp_login_log',
        [
            'user_login' => sanitize_user( $username ),
            'ip_address' => wpepp_get_client_ip(),
            'status'     => $success ? 'success' : 'failed',
            'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] )
                ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
                : '',
        ],
        [ '%s', '%s', '%s', '%s' ]
    );
}

// Check if IP is locked out
function wpepp_is_ip_locked( $ip ) {
    global $wpdb;

    $settings     = json_decode( get_option( 'wpepp_security_settings', '{}' ), true );
    $max_attempts = absint( $settings['max_attempts'] ?? 5 );
    $lockout_min  = absint( $settings['lockout_duration'] ?? 15 );

    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wpepp_login_log
             WHERE ip_address = %s AND status = 'failed'
             AND created_at > DATE_SUB( NOW(), INTERVAL %d MINUTE )",
            sanitize_text_field( $ip ),
            $lockout_min
        )
    );

    return (int) $count >= $max_attempts;
}
```

### Template Import Security

```php
function wpepp_validate_template_import( $json_string ) {
    // 1. Decode JSON
    $data = json_decode( $json_string, true );
    if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
        return new WP_Error( 'invalid_json', __( 'Invalid JSON format.', 'wp-edit-password-protected' ) );
    }

    // 2. Whitelist allowed top-level keys
    $allowed_sections = [ 'login', 'register', 'password', 'lostpassword' ];
    $data = array_intersect_key( $data, array_flip( $allowed_sections ) );

    // 3. Sanitize every value recursively
    $data = wpepp_sanitize_settings_recursive( $data );

    // 4. Validate against known schema (max sizes, types)
    // ... schema validation ...

    return $data;
}
```

### Uninstall Data Cleanup

```php
// uninstall.php — clean removal of ALL plugin data
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Options
$options = [
    'wpepp_login_settings', 'wpepp_register_settings', 'wpepp_password_settings',
    'wpepp_lostpassword_settings', 'wpepp_member_template', 'wpepp_security_settings',
    'wpepp_general_settings', 'wpepp_content_lock_defaults', 'wpepp_active_template',
    'wpepp_custom_login_url', 'wpepp_version', 'wpepp_has_pro',
];
foreach ( $options as $option ) {
    delete_option( $option );
}

// Post meta
delete_post_meta_by_key( '_wpepp_content_lock_enabled' );
delete_post_meta_by_key( '_wpepp_content_lock_message' );
delete_post_meta_by_key( '_wpepp_content_lock_action' );
delete_post_meta_by_key( '_wpepp_content_lock_redirect' );
// ... all _wpepp_conditional_* keys ...

// Custom table
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}wpepp_login_log" );

// Transients
$wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_wpepp_%'
        OR option_name LIKE '_transient_timeout_wpepp_%'"
);
```

---

## Database Storage

### Options Table (`wp_options`)

```
wpepp_login_settings        → JSON (login page design settings)
wpepp_register_settings     → JSON (register page design settings)
wpepp_password_settings     → JSON (password page design settings — includes per-style settings)
wpepp_lostpassword_settings → JSON (lost password page design settings)
wpepp_member_template       → JSON (member-only page template settings)
wpepp_security_settings     → JSON (security settings)
wpepp_general_settings      → JSON (general/global settings)
wpepp_content_lock_defaults → JSON (default lock message, action, redirect)
wpepp_active_template       → string (current template name)
wpepp_custom_login_url      → string (custom login slug)
wpepp_version               → string (plugin version for migrations)
wpepp_has_pro               → 'yes' / 'no' (Pro license active flag — set externally)

# Backward compatible - keep reading old keys on migration
wppasspro_*                 → (25 old Kirki password form options)
wpe_adpage_*                → (20+ old Kirki admin page options)
pp_basic_settings           → (legacy array option)
pp_admin_page               → (legacy array option)
```

### Post Meta Table (`wp_postmeta`)

```
# Content Lock (NEW)
_wpepp_content_lock_enabled     → 'yes' / 'no'
_wpepp_content_lock_message     → string
_wpepp_content_lock_action      → 'form' / 'link' / 'redirect'
_wpepp_content_lock_redirect    → string (URL)

# Conditional Display (existing — all preserved)
_wpepp_conditional_display_enable    → 'yes' / 'no'
_wpepp_conditional_display_condition → string (condition type)
_wpepp_conditional_action            → 'show' / 'hide'
_wpepp_conditional_control_title     → 'yes' / 'no'
_wpepp_conditional_control_featured_image → 'yes' / 'no'
_wpepp_conditional_user_role         → array
_wpepp_conditional_device_type       → string
_wpepp_conditional_day_of_week       → array
_wpepp_conditional_time_start        → string (HH:MM)
_wpepp_conditional_time_end          → string (HH:MM)
_wpepp_conditional_date_start        → string (YYYY-MM-DD)
_wpepp_conditional_date_end          → string (YYYY-MM-DD)
_wpepp_conditional_recurring_*       → various
_wpepp_conditional_browser_type      → array
_wpepp_conditional_url_parameter_*   → string
_wpepp_conditional_referrer_source   → string
```

### Custom Table: `{prefix}wpepp_login_log`

```sql
CREATE TABLE {prefix}wpepp_login_log (
  id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_login VARCHAR(255) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  status ENUM('success', 'failed', 'lockout') NOT NULL,
  user_agent TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ip (ip_address),
  INDEX idx_status (status),
  INDEX idx_created (created_at)
);
```

---

## Migration Strategy (v1.3.7 → v2.0)

### On plugin activation/update:

```php
function wpepp_migrate_settings() {
    $version = get_option('wpepp_version', '1.0');

    if (version_compare($version, '2.0', '<')) {

        // ── 1. Migrate Password Form settings (wppasspro_* → JSON) ──
        $password_settings = [
            'form_style'          => get_option('wppasspro_form_style', 'four'),
            'show_top_text'       => get_option('wppasspro_show_top_text', 'on'),
            'top_text_align'      => get_option('wppasspro_top_text_align', 'center'),
            'top_header'          => get_option('wppasspro_top_header', ''),
            'top_content'         => get_option('wppasspro_top_content', ''),
            'show_bottom_text'    => get_option('wppasspro_show_bottom_text', 'off'),
            'bottom_text_align'   => get_option('wppasspro_bottom_text_align', 'left'),
            'bottom_header'       => get_option('wppasspro_bottom_header', ''),
            'bottom_content'      => get_option('wppasspro_bottom_content', ''),
            'form_label'          => get_option('wppasspro_form_label', 'Password'),
            'form_btn_text'       => get_option('wppasspro_form_btn_text', 'Submit'),
            'form_errortext'      => get_option('wppasspro_form_errortext', ''),
            'error_text_position' => get_option('wppasspro_error_text_position', 'top'),
            'show_social'         => get_option('wppasspro_show_social', 'on'),
            'icons_vposition'     => get_option('wppasspro_icons_vposition', 'top'),
            'icons_alignment'     => get_option('wppasspro_icons_alignment', 'right'),
            'icons_style'         => get_option('wppasspro_icons_style', 'square'),
            'link_facebook'       => get_option('wppasspro_link_facebook', ''),
            'link_twitter'        => get_option('wppasspro_link_twitter', ''),
            'link_youtube'        => get_option('wppasspro_link_youtube', ''),
            'link_instagram'      => get_option('wppasspro_link_instagram', ''),
            'link_linkedin'       => get_option('wppasspro_link_linkedin', ''),
            'link_pinterest'      => get_option('wppasspro_link_pinterest', ''),
            'link_tumblr'         => get_option('wppasspro_link_tumblr', ''),
            'link_custom'         => get_option('wppasspro_link_custom', ''),
        ];
        update_option('wpepp_password_settings', wp_json_encode($password_settings));

        // ── 2. Migrate Admin Page / Member Template settings (wpe_adpage_* → JSON) ──
        $member_settings = [
            'page_fimg'              => get_option('wppasspro_page_fimg', 'hide'),
            'class'                  => get_option('wpe_adpage_class', ''),
            'mode'                   => get_option('wpe_adpage_mode', 'login'),
            'style'                  => get_option('wpe_adpage_style', 's1'),
            'text_align'             => get_option('wpe_adpage_text_align', 'center'),
            'infotitle'              => get_option('wpe_adpage_infotitle', ''),
            'titletag'               => get_option('wpe_adpage_titletag', 'h2'),
            'text'                   => get_option('wpe_adpage_text', ''),
            'shortcode'              => get_option('wpe_adpage_shortcode', ''),
            'login_mode'             => get_option('wpe_adpage_login_mode', 'form'),
            'login_url'              => get_option('wpe_adpage_login_url', ''),
            'btntext'                => get_option('wpe_adpage_btntext', 'Login'),
            'btnclass'               => get_option('wpe_adpage_btnclass', 'btn button'),
            'form_head'              => get_option('wpe_adpage_form_head', 'Login Form'),
            'user_placeholder'       => get_option('wpe_adpage_user_placeholder', 'username'),
            'password_placeholder'   => get_option('wpe_adpage_password_placeholder', 'Password'),
            'form_remember'          => get_option('wpe_adpage_form_remember', 'on'),
            'remember_text'          => get_option('wpe_adpage_remember_text', 'Remember Me'),
            'wrongpassword'          => get_option('wpe_adpage_wrongpassword', ''),
            'errorlogin'             => get_option('wpe_adpage_errorlogin', ''),
            'formbtn_text'           => get_option('wpe_adpage_formbtn_text', 'Login'),
            'width'                  => get_option('wpe_adpage_width', 'standard'),
            'header_show'            => get_option('wpe_adpage_header_show', 'on'),
            'comment'                => get_option('wpe_adpage_comment', ''),
        ];
        update_option('wpepp_member_template', wp_json_encode($member_settings));

        // ── 3. Also read legacy pp_basic_settings array as fallback ──
        $legacy = get_option('pp_basic_settings', []);
        if (!empty($legacy) && empty($password_settings['form_style'])) {
            // Apply legacy values if new options were never set
        }

        // Update version
        update_option('wpepp_version', '2.0');

        // Note: Do NOT delete old options yet
        // Delete after 2-3 versions when migration is verified
    }
}
```

### Conditional Display Migration
- No migration needed — all existing post meta keys (`_wpepp_conditional_*`) are preserved as-is
- The only change is uncommenting the additional conditions in the meta box UI
- Existing posts with `user_logged_in` / `user_logged_out` conditions continue to work

---

## WordPress Dependencies (wp-scripts)

### package.json

```json
{
  "name": "wp-edit-password-protected",
  "version": "2.0.0",
  "scripts": {
    "build": "wp-scripts build",
    "start": "wp-scripts start",
    "lint:js": "wp-scripts lint-js",
    "lint:css": "wp-scripts lint-style"
  },
  "devDependencies": {
    "@wordpress/scripts": "^31.6.0"
  },
  "dependencies": {
    "@wordpress/api-fetch": "^7.41.0",
    "@wordpress/components": "^32.3.0",
    "@wordpress/data": "^10.41.0",
    "@wordpress/element": "^6.41.0",
    "@wordpress/i18n": "^6.14.0",
    "@wordpress/icons": "^11.8.0",
    "@wordpress/notices": "^5.41.0",
    "react-router-dom": "^7.13.1"
  }
}
```

**Why NO icon font libraries in dependencies:**
- No `font-awesome`, `dashicons`, `material-icons`, or any icon font package
- All admin icons come from `@wordpress/icons` (already a WP dependency) or custom inline SVG components in `src/icons/`
- All frontend icons rendered as inline SVG via PHP (`wpepp_social_icon_svg()`)
- **Result: zero icon-related HTTP requests on both admin and frontend**
}
```

### WordPress Components Used

- `Panel`, `PanelBody`, `PanelRow` — Settings panels
- `ColorPicker`, `ColorPalette` — Color controls
- `RangeControl` — Sliders (font size, spacing, etc.)
- `SelectControl` — Dropdowns
- `ToggleControl` — On/off switches
- `TextControl`, `TextareaControl` — Text inputs
- `Button`, `ButtonGroup` — Actions
- `TabPanel` — Section tabs
- `MediaUpload`, `MediaUploadCheck` — Image uploads
- `Popover` — Floating panels
- `Spinner` — Loading states
- `Notice` — Alerts

---

## Enqueue Strategy

```php
/**
 * Enqueue admin React app — ONLY on our plugin page.
 * On all other admin pages: zero assets loaded.
 *
 * @param string $hook The current admin page hook suffix.
 */
function wpepp_enqueue_admin( $hook ) {
    // ── Strict page check — exit immediately for non-plugin pages ──
    if ( 'toplevel_page_wpepp-settings' !== $hook ) {
        return;
    }

    $asset_file = WPEPP_PATH . 'build/index.asset.php';

    // Verify build exists (prevents fatal on missing build)
    if ( ! file_exists( $asset_file ) ) {
        return;
    }

    $asset = include $asset_file;

    wp_enqueue_script(
        'wpepp-admin',
        plugins_url( 'build/index.js', WPEPP_FILE ),
        $asset['dependencies'],
        $asset['version'],
        true
    );

    wp_enqueue_style(
        'wpepp-admin',
        plugins_url( 'build/style-index.css', WPEPP_FILE ),
        [ 'wp-components' ],
        $asset['version']
    );

    // Pass data to JS — all values escaped for safe embedding
    wp_localize_script( 'wpepp-admin', 'wpeppData', [
        'restUrl'   => esc_url_raw( rest_url( 'wpepp/v1/' ) ),
        'nonce'     => wp_create_nonce( 'wp_rest' ),
        'adminUrl'  => esc_url( admin_url() ),
        'loginUrl'  => esc_url( wp_login_url() ),
        'pluginUrl' => esc_url( plugins_url( '', WPEPP_FILE ) ),
        'version'   => sanitize_text_field( WPEPP_VERSION ),
        'isPro'     => wpepp_has_pro_check(),
        'proUrl'    => esc_url( 'https://wpthemespace.com/product/wp-edit-password-protected-pro/' ),

    ] );

    // JS translations for i18n
    wp_set_script_translations( 'wpepp-admin', 'wp-edit-password-protected', WPEPP_PATH . 'languages' );
}
add_action( 'admin_enqueue_scripts', 'wpepp_enqueue_admin' );

/**
 * Enqueue meta box assets — ONLY on post/page editor screens.
 * Lightweight: ~2KB CSS + ~3KB JS for Content Lock and Conditional Display meta boxes.
 * Does NOT load the full React app.
 */
function wpepp_enqueue_meta_box_assets( $hook ) {
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
        return;
    }

    wp_enqueue_style(
        'wpepp-meta-box',
        plugins_url( 'assets/css/meta-box.css', WPEPP_FILE ),
        [],
        WPEPP_VERSION
    );

    wp_enqueue_script(
        'wpepp-meta-box',
        plugins_url( 'assets/js/meta-box.js', WPEPP_FILE ),
        [],       // No jQuery dependency — vanilla JS
        WPEPP_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'wpepp_enqueue_meta_box_assets' );

/**
 * Enqueue login page styles from saved settings.
 * Uses login_enqueue_scripts hook — fires ONLY on wp-login.php.
 * We piggyback on the existing 'login' stylesheet with inline CSS.
 * Result: zero extra HTTP requests.
 */
function wpepp_enqueue_login_styles() {
    $raw      = get_option( 'wpepp_login_settings', '{}' );
    $settings = json_decode( $raw, true );

    if ( empty( $settings ) || ! is_array( $settings ) ) {
        return;
    }

    // CSS generator returns sanitized CSS (hex colors validated, values escaped)
    $css = wpepp_generate_css( $settings );

    if ( ! empty( $css ) ) {
        wp_add_inline_style( 'login', wp_strip_all_tags( $css ) );
    }

    // Google Font — only if a custom font is configured
    $font = $settings['form']['font_family'] ?? '';
    wpepp_maybe_enqueue_google_font( $font );
}
add_action( 'login_enqueue_scripts', 'wpepp_enqueue_login_styles' );

/**
 * Enqueue frontend styles for password protected forms.
 * ONLY loads on singular posts/pages where post_password_required() is true.
 * On every other page: zero assets.
 */
function wpepp_enqueue_password_form_styles() {
    if ( ! is_singular() || ! post_password_required() ) {
        return;
    }

    wp_enqueue_style(
        'wpepp-password-form',
        plugins_url( 'assets/css/frontend-password-form.css', WPEPP_FILE ),
        [],
        WPEPP_VERSION
    );

    $raw      = get_option( 'wpepp_password_settings', '{}' );
    $settings = json_decode( $raw, true );

    if ( ! empty( $settings ) && is_array( $settings ) ) {
        $css = wpepp_generate_password_css( $settings );
        if ( ! empty( $css ) ) {
            wp_add_inline_style( 'wpepp-password-form', wp_strip_all_tags( $css ) );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'wpepp_enqueue_password_form_styles' );

/**
 * Enqueue frontend styles for content-locked posts.
 * ONLY loads on singular posts where content lock is enabled AND user is logged out.
 * This check happens ONCE per page — cached by WP.
 */
function wpepp_enqueue_content_lock_styles() {
    if ( ! is_singular() || is_user_logged_in() ) {
        return;
    }

    $post_id = get_the_ID();
    $locked  = get_post_meta( $post_id, '_wpepp_content_lock_enabled', true );

    if ( 'yes' !== $locked ) {
        return;
    }

    wp_enqueue_style(
        'wpepp-content-lock',
        plugins_url( 'assets/css/frontend-content-lock.css', WPEPP_FILE ),
        [],
        WPEPP_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'wpepp_enqueue_content_lock_styles' );

/**
 * Conditional display JS — ONLY when client-side conditions are used.
 * Server-side conditions (logged_in, role, time, date, etc.) need zero JS.
 * Only browser_type and referrer_source require a tiny client-side script.
 */
function wpepp_enqueue_conditional_script() {
    if ( ! is_singular() ) {
        return;
    }

    $enabled   = get_post_meta( get_the_ID(), '_wpepp_conditional_display_enable', true );
    $condition = get_post_meta( get_the_ID(), '_wpepp_conditional_display_condition', true );

    if ( 'yes' !== $enabled ) {
        return;
    }

    // Only these conditions need client-side JS
    $client_conditions = [ 'browser_type', 'referrer_source' ];

    if ( in_array( $condition, $client_conditions, true ) ) {
        wp_enqueue_script(
            'wpepp-conditional',
            plugins_url( 'assets/js/conditional-display.js', WPEPP_FILE ),
            [],       // Zero dependencies — no jQuery, no wp-* scripts
            WPEPP_VERSION,
            true      // Load in footer
        );

        wp_localize_script( 'wpepp-conditional', 'wpeppCondition', [
            'condition' => sanitize_text_field( $condition ),
            'action'    => sanitize_text_field(
                get_post_meta( get_the_ID(), '_wpepp_conditional_action', true )
            ),
            'data'      => wpepp_get_client_condition_data( get_the_ID() ),
        ] );
    }
}
add_action( 'wp_enqueue_scripts', 'wpepp_enqueue_conditional_script' );
```

---

## Development Phases

### Phase 1 — Foundation + Core Architecture
- [ ] Setup `package.json` with `@wordpress/scripts` (no icon font dependencies)
- [ ] Main plugin file refactor (class-based bootstrap, define constants)
- [ ] Admin page registration (React mount point under top-level menu, SVG data URI menu icon)
- [ ] React app shell (HashRouter, sidebar nav with `@wordpress/icons`, header, footer)
- [ ] Create `src/icons/` directory with custom SVG icon components (LockIcon, etc.)
- [ ] Create `includes/class-pro.php` — `wpepp_has_pro_check()` helper, `wpepp_enforce_pro_settings()` filter, `wpepp_check_pro_permission()` callback
- [ ] Create `src/components/ProLock.jsx` + `ProBadge.jsx` + Pro lock SCSS styles
- [ ] Create `src/hooks/usePro.js` + `src/utils/pro-features.js` (feature map)
- [ ] Pass `isPro` + `proUrl` via `wp_localize_script()` to React app
- [ ] REST API class with settings CRUD endpoints (Pro enforcement on save)
- [ ] WordPress data store (`@wordpress/data`) with `isPro()` selector
- [ ] Migration class (Kirki → JSON, all old options)
- [ ] Remove Kirki framework (delete `admin/kirki/`, remove require_once)
- [ ] Verify on-demand loading: admin assets only on plugin page (`$hook` check), zero on frontend

### Phase 2 — Password Protected Page Designer (Tabbed Preview)
- [ ] Tabbed preview system — Style 1, Style 2 (free) + Style 3, Style 4 (Pro locked with ProBadge)
- [ ] Live preview iframe component with `postMessage()` communication
- [ ] Preview endpoint: `?wpepp_preview=1&style=two&type=password`
- [ ] Style controls: top text, bottom text, form label, button text, social icons, error text
- [ ] Social icons: 3 free (Facebook, Twitter, YouTube), 4 Pro (Instagram, LinkedIn, Pinterest, Tumblr) with ProBadge
- [ ] Social icons as inline SVG via `wpepp_social_icon_svg()` (replaces Font Awesome / icon font)
- [ ] CSS generator from settings (both JS for preview and PHP for frontend)
- [ ] Frontend output using new JSON settings (backward compatible with old options)
- [ ] Frontend CSS: `frontend-password-form.css` (~3KB) ONLY on `is_singular() && post_password_required()`
- [ ] Save/reset functionality (Pro fields stripped by `wpepp_enforce_pro_settings()` for Free users)
- [ ] Responsive preview toggle — Desktop free, Tablet + Mobile = Pro with ProBadge

### Phase 3 — Login Page Designer (Tabbed Preview)
- [ ] Login page tabbed preview (Default / Modern / Minimal / Custom)
- [ ] Style controls: background, logo, form, fields, button, links, remember me, error, footer
- [ ] Advanced controls (custom CSS, Google Fonts) locked with ProBadge for Free users
- [ ] CSS injection into `wp-login.php` iframe via postMessage
- [ ] Frontend: `login_enqueue_scripts` hook to output saved CSS
- [ ] Register page designer — entire tab wrapped in ProLock (Pro only)
- [ ] Lost password page designer — entire tab wrapped in ProLock (Pro only)

### Phase 4 — Content Lock (PRO Feature)
- [ ] `class-content-lock.php` — meta box on posts/pages, content filter, REST protection
- [ ] All Content Lock code checks `wpepp_has_pro_check()` before executing
- [ ] Post meta: `_wpepp_content_lock_enabled`, `_wpepp_content_lock_message`, etc.
- [ ] Frontend: locked message + login form/link for logged-out users (inline SVG lock icon) — only if Pro
- [ ] Frontend CSS: `frontend-content-lock.css` (~1KB) ONLY on locked posts for logged-out users
- [ ] React: ContentLock.jsx wrapped in ProLock — shows upgrade CTA for Free users
- [ ] REST API: `/content-lock` endpoints use `wpepp_check_pro_permission()` as additional check
- [ ] Admin columns: "Lock" column in WP post list table (inline SVG icon, no font dependency) — only if Pro

### Phase 5 — Conditional Display (Expanded)
- [ ] Free: `user_logged_in` + `user_logged_out` conditions enabled in meta box dropdown
- [ ] Pro: All 12 conditions enabled — extra conditions show ProBadge + disabled in dropdown for Free
- [ ] Server-side: `wpepp_get_available_conditions()` returns only free conditions for Free users (uses `wpepp_has_pro_check()`)
- [ ] React: ConditionalDisplay.jsx — condition selector with locked Pro conditions
- [ ] React: Dashboard management page wrapped in ProLock (Pro only)
- [ ] REST API: `/conditional` GET dashboard = Pro permission, PUT strips Pro conditions for Free
- [ ] Verify all existing condition evaluators work (user_role, device, time, etc.)
- [ ] Member Template settings page in React (basic free, advanced settings wrapped in ProLock)

### Phase 6 — Templates
- [ ] 10 pre-built templates as JSON presets (login + password page styles)
- [ ] Template gallery UI with preview screenshots — 3 free, rest show Pro badge + blurred preview
- [ ] Apply: free templates always work, Pro templates check `wpepp_has_pro_check()` server-side
- [ ] Import/Export: buttons disabled with ProBadge for Free, functional for Pro
- [ ] Template preview in iframe before applying

### Phase 7 — Security Features
- [ ] Login attempt limiter (DB tracking) — Free
- [ ] Lockout system (IP-based) — Free
- [ ] Honeypot field — Free
- [ ] Disable XML-RPC toggle — Free
- [ ] Hide WP version toggle — Free
- [ ] Disable REST user enumeration — Free
- [ ] reCAPTCHA v2/v3 integration — Pro (UI wrapped in ProLock)
- [ ] Custom login URL — Pro (entire CustomLoginUrl.jsx wrapped in ProLock)
- [ ] Login activity log (custom table + React viewer) — Pro (entire LoginLog.jsx wrapped in ProLock)
- [ ] Security REST endpoints: Pro-only endpoints use `wpepp_check_pro_permission()`

### Phase 8 — Security Audit & WPCS Compliance
- [ ] Run `phpcs --standard=WordPress` on all PHP files — fix all errors
- [ ] Run `npx wp-scripts lint-js` on all JS/JSX — fix all errors
- [ ] Verify every `$_GET`, `$_POST`, `$_REQUEST`, `$_SERVER` access is sanitized + unslashed
- [ ] Verify every `echo`, `printf`, HTML output is escaped (`esc_html`, `esc_attr`, `esc_url`)
- [ ] Verify every form/AJAX/meta box has nonce check (`wp_verify_nonce`)
- [ ] Verify every admin action has capability check (`current_user_can`)
- [ ] Verify every REST route has `permission_callback` + `sanitize_callback` + `validate_callback`
- [ ] Verify every SQL query uses `$wpdb->prepare()`
- [ ] Verify no forbidden functions (`eval`, `extract`, `serialize`, `file_get_contents` for URLs)
- [ ] Verify `defined( 'ABSPATH' ) || exit;` in every PHP file
- [ ] Verify all strings use text domain `wp-edit-password-protected`
- [ ] Verify `uninstall.php` removes ALL plugin data cleanly
- [ ] Verify Custom CSS field is sanitized with `wpepp_sanitize_css()`
- [ ] Verify preview token is one-time use + time-limited transient
- [ ] Verify no data leaks in REST responses for non-admin users

### Phase 9 — Performance Audit & Verification
- [ ] Verify ZERO HTTP requests on normal frontend pages (no plugin CSS, JS, or font files)
- [ ] Verify password form page: only `frontend-password-form.css` loaded (~3KB)
- [ ] Verify content lock page: only `frontend-content-lock.css` loaded (~1KB)
- [ ] Verify login page: zero extra HTTP requests (inline CSS only via `wp_add_inline_style`)
- [ ] Verify admin non-plugin pages: zero plugin assets loaded
- [ ] Verify admin post editor: only meta box assets (~5KB total)
- [ ] Verify no icon font files loaded anywhere (Font Awesome, Dashicons font, etc.)
- [ ] Verify Google Fonts only loads when custom font is configured AND on affected page
- [ ] Verify conditional display JS only loads for client-side conditions (browser/referrer)
- [ ] Verify all SVG icons have `aria-hidden="true"` and proper `aria-label` on clickable parents
- [ ] Verify template preview images use WebP and lazy loading (`loading="lazy"`)
- [ ] Run Lighthouse audit on: normal page, password page, login page — performance score target: 95+
- [ ] Run Query Monitor — verify zero unnecessary DB queries from plugin on normal pages
- [ ] Verify `autoload` flag: `wpepp_version` and active settings = `yes`, login log = `no`

### Phase 10 — Polish & Release
- [ ] i18n audit (all strings translatable, POT generated, JS translations set up)
- [ ] Accessibility audit (ARIA labels, keyboard navigation, screen reader support, SVG icons)
- [ ] WordPress.org readme.txt (description, FAQ, changelog, screenshots)
- [ ] Screenshots for WordPress.org (admin panel, live preview, content lock, etc.)
- [ ] Testing on PHP 7.4, 8.0, 8.1, 8.2, 8.3, 8.4
- [ ] Testing on WordPress 6.0 - 6.8+
- [ ] Plugin Check (PCP) — run official WordPress Plugin Check plugin
- [ ] Test with `WP_DEBUG`, `WP_DEBUG_LOG`, `SCRIPT_DEBUG` enabled — zero notices/warnings
- [ ] Verify GPL-compatible license header in every file

---

## Key Technical Decisions

| Decision | Choice | Reason |
|----------|--------|--------|
| State management | `@wordpress/data` | Native WP, integrates with core stores |
| Routing | `react-router-dom` (hash) | `#/form-style/login`, `#/content/lock`, etc. |
| Styling admin | SCSS + wp-components | Matches WP admin look |
| Live preview | iframe + postMessage | Safe isolation, real page rendering |
| Settings storage | JSON in wp_options | Single query, easy backup/export |
| CSS generation | PHP (frontend) + JS (preview) | JS for real-time, PHP for production |
| Build tool | @wordpress/scripts | Standard WP tooling, handles deps |
| API | WP REST API | Standard, authenticated, validated |
| Form validation | Server-side PHP | Never trust client-side only |
| Input sanitization | WordPress sanitize_*() functions | Context-specific, reviewer-approved |
| Output escaping | WordPress esc_*() functions | Late escaping, context-aware |
| SQL queries | `$wpdb->prepare()` always | Prevents SQL injection |
| Nonce verification | `wp_verify_nonce()` on all state changes | CSRF protection |
| Capability checks | `current_user_can()` on all actions | Authorization enforcement |
| File access | `defined('ABSPATH') \|\| exit` every file | Prevents direct execution |
| HTTP requests | `wp_remote_get()` / `wp_remote_post()` | Uses WP HTTP API, respects proxy settings |
| Coding standard | WPCS (WordPress-Core + WordPress-Extra) | Required for WordPress.org approval |
| Preview auth | One-time transient token | Secure iframe auth without cookie dependency |
| Custom CSS | `wpepp_sanitize_css()` + `wp_strip_all_tags()` | Blocks expression(), javascript: URL, @import |
| Pro tier gating | `wpepp_has_pro_check()` option check + server-side enforcement | UI lock is cosmetic; server is authority. No external API calls. |
| Icons (admin) | Inline SVG via `@wordpress/icons` + custom `src/icons/` | Zero HTTP requests, tree-shaken, no icon font |
| Icons (frontend) | PHP inline SVG (`wpepp_social_icon_svg()`) | Zero HTTP requests, ~200 bytes each, no font file |
| Icon fonts | **BANNED** — no Font Awesome, no icon CDN | Eliminates 30-80KB+ unnecessary load |
| Frontend loading | On-demand: `is_singular()` + `post_password_required()` | Zero assets on normal pages |
| Google Fonts | On-demand with `display=swap` + preconnect | Only if custom font set, only on affected page |
| Admin loading | `$hook` strict check + separate meta box enqueue | Zero assets on non-plugin admin pages |
| Frontend JS | Zero (except client-side conditions: ~1KB) | Server-side rendering for all features |
| Meta box assets | Separate lightweight bundle (~5KB) | Not bundled with React app |
| Template images | WebP + `loading="lazy"` | Minimal bandwidth, no render blocking |

---

## Backward Compatibility

- Old `wppasspro_*` options (25 keys) migrated to `wpepp_password_settings` JSON on update
- Old `wpe_adpage_*` options (20+ keys) migrated to `wpepp_member_template` JSON on update
- Old `pp_basic_settings` and `pp_admin_page` array options read as fallback defaults
- All existing `_wpepp_conditional_*` post meta keys preserved as-is (no changes)
- Password protected page continues working with all 4 existing form styles
- Member-only page template continues functioning with upgraded settings
- Old option keys kept for 3 versions before cleanup
- Plugin version tracked in `wpepp_version` for migration control
- Conditional display posts with `user_logged_in` / `user_logged_out` work unchanged

---

## WordPress.org Plugin Review Checklist

Before submitting to WordPress.org, the plugin MUST pass all of these checks that plugin reviewers will verify:

### Sanitization & Escaping (Most Common Rejection Reason)
- [ ] Every `$_GET`, `$_POST`, `$_REQUEST` value sanitized with appropriate function
- [ ] Every `$_SERVER` value sanitized before use (especially `REQUEST_URI`, `HTTP_HOST`, `HTTP_USER_AGENT`)
- [ ] `wp_unslash()` called before sanitization on all superglobals
- [ ] Every `echo`, `print`, `printf` of dynamic data is escaped with `esc_html()`, `esc_attr()`, `esc_url()`, or `wp_kses_post()`
- [ ] No raw database values echoed without escaping
- [ ] Translation functions escaped: use `esc_html__()`, `esc_html_e()`, `esc_attr__()` instead of `__()` + separate escape
- [ ] All URLs in output escaped with `esc_url()`, in DB with `esc_url_raw()`

### Security
- [ ] Every form has nonce field (`wp_nonce_field()`) and verification (`wp_verify_nonce()`)
- [ ] Every admin action checks capability (`current_user_can()`)
- [ ] Every REST route has `permission_callback` (never `__return_true` for write endpoints)
- [ ] Every REST route has `sanitize_callback` and `validate_callback` in args
- [ ] Every SQL query with variables uses `$wpdb->prepare()`
- [ ] No direct file operations — use WP_Filesystem API
- [ ] No `eval()`, `extract()`, `create_function()`, variable variables (`$$var`)
- [ ] No `file_get_contents()` for remote URLs — use `wp_remote_get()`
- [ ] No `serialize()` / `unserialize()` — use `maybe_serialize()` / `maybe_unserialize()`
- [ ] Preview iframe uses secure one-time token, not predictable URL
- [ ] Login attempt data (IP, username) properly sanitized before DB insert

### Direct File Access Prevention
- [ ] Every PHP file starts with `defined( 'ABSPATH' ) || exit;`
- [ ] No PHP files that can be executed directly via URL

### Prefix Everything
- [ ] All functions prefixed: `wpepp_*`
- [ ] All classes prefixed: `WPEPP_*`
- [ ] All constants prefixed: `WPEPP_*`
- [ ] All options prefixed: `wpepp_*`
- [ ] All post meta prefixed: `_wpepp_*`
- [ ] All transients prefixed: `wpepp_*`
- [ ] All custom hooks prefixed: `wpepp_*` or `wpepp/*`
- [ ] All CSS classes prefixed: `wpepp-*`
- [ ] All REST routes under `wpepp/v1` namespace
- [ ] No generic names that could conflict with other plugins

### Proper Use of WordPress APIs
- [ ] Scripts/styles enqueued via `wp_enqueue_script()` / `wp_enqueue_style()` (no direct `<script>` / `<link>` tags)
- [ ] AJAX uses WordPress AJAX API (`admin-ajax.php` or REST API)
- [ ] Redirects use `wp_safe_redirect()` + `exit` (not `header('Location:')`)
- [ ] Plugin uses `wp_die()` (not `die()` or `exit()` alone)
- [ ] Plugin uses `wp_json_encode()` (not `json_encode()`)
- [ ] Plugin uses `gmdate()` or `wp_date()` (not `date()`)
- [ ] Plugin uses `wp_remote_*()` functions for HTTP requests
- [ ] Custom tables use `$wpdb->prefix` (not hardcoded prefix)
- [ ] `dbDelta()` for table creation (in activation hook)

### Assets & Performance
- [ ] No external CDN resources (Google Fonts, Bootstrap CDN, etc.)
- [ ] Admin scripts/styles only loaded on plugin's own admin page (check `$hook`)
- [ ] Frontend scripts/styles only loaded where needed (not globally)
- [ ] No inline `<script>` or `<style>` — use `wp_add_inline_script()` / `wp_add_inline_style()`
- [ ] Images/assets bundled with plugin (not loaded from external servers)

### Internationalization
- [ ] All user-facing strings wrapped in `__()`, `_e()`, `esc_html__()`, etc.
- [ ] Text domain matches plugin slug exactly: `wp-edit-password-protected`
- [ ] Text domain is a string literal (not a variable) in all translation calls
- [ ] POT file generated and included in `languages/` directory
- [ ] JS translations set up with `wp_set_script_translations()`
- [ ] No HTML inside translation strings — use `sprintf()` with placeholders

### Clean Data Management
- [ ] `uninstall.php` exists and removes ALL plugin data (options, post meta, tables, transients)
- [ ] `uninstall.php` starts with `defined( 'WP_UNINSTALL_PLUGIN' ) || exit;`
- [ ] No leftover data after uninstall
- [ ] Database tables created in activation hook with `dbDelta()`
- [ ] Database tables dropped in `uninstall.php`

### Licensing & Legal
- [ ] Plugin is GPL v2+ compatible
- [ ] License header in main plugin file: `License: GPLv2 or later`
- [ ] All bundled libraries are GPL-compatible
- [ ] No obfuscated code (base64, encoded strings)
- [ ] No phone-home / tracking without explicit opt-in consent
- [ ] No upsell nags in WP dashboard outside plugin's own pages

### Code Quality
- [ ] No PHP errors, warnings, or notices with `WP_DEBUG` enabled
- [ ] No JavaScript console errors
- [ ] No deprecated WordPress function calls
- [ ] Plugin works with default themes (Twenty Twenty-*)
- [ ] Plugin deactivation doesn't break the site
- [ ] Failed activation handled gracefully (missing requirements)
