/**
 * Login Protection settings — brute force, honeypot, XML-RPC, etc.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	ToggleControl,
	TextControl,
	SelectControl,
	RangeControl,
	Notice,
	Spinner,
} from '@wordpress/components';
import Card from '../../components/Card';
import ToggleCard from '../../components/ToggleCard';
import { useMemo, useCallback } from '@wordpress/element';
import ProBadge from '../../components/ProBadge';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';

const LoginProtection = () => {
	const { settings, isLoading, isSaving, isPro } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'security' ),
			isLoading: store.isLoading(),
			isSaving: store.isSaving(),
			isPro: store.isPro(),
		};
	} );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );

	const s = useMemo( () => ( { ...SECURITY_DEFAULTS, ...settings } ), [ settings ] );

	const update = useCallback( ( key, value ) => {
		updateSetting( 'security', key, value );
	}, [ updateSetting ] );

	const handleSave = useCallback( () => {
		saveSettings( 'security', s );
	}, [ saveSettings, s ] );

	const handleReset = useCallback( () => {
		resetSettings( 'security' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	const siteUrl = ( window.wpeppData?.adminUrl || '' ).replace( /\/wp-admin\/$/, '' );

	if ( isLoading ) {
		return <Spinner />;
	}

	return (
		<div className="wpepp-login-protection">
			<h3>{ __( 'Login Protection', 'wp-edit-password-protected' ) }</h3>

			<Card title={ __( 'Login Limiter', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={ __( 'Enable Login Limiter', 'wp-edit-password-protected' ) }
						description={ __( 'Users will be locked out after too many failed login attempts. The lockout is based on IP address.', 'wp-edit-password-protected' ) }
						checked={ s.login_limit_enabled !== false }
						onChange={ ( v ) => update( 'login_limit_enabled', v ) }
					/>
				</div>
				{ s.login_limit_enabled !== false && (
					<div className="wpepp-form-grid" style={ { marginTop: 16 } }>
						<RangeControl
							label={ __( 'Max Attempts', 'wp-edit-password-protected' ) }
							value={ s.max_attempts || 5 }
							onChange={ ( v ) => update( 'max_attempts', v ) }
							min={ 1 }
							max={ 20 }
						/>
						<RangeControl
							label={ __( 'Lockout Duration (minutes)', 'wp-edit-password-protected' ) }
							value={ s.lockout_duration || 15 }
							onChange={ ( v ) => update( 'lockout_duration', v ) }
							min={ 1 }
							max={ 120 }
						/>
					</div>
				) }
			</Card>

			<Card title={ __( 'Honeypot', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={ __( 'Enable Honeypot Field', 'wp-edit-password-protected' ) }
						description={ __( 'An invisible field is added to the login form. Bots that auto-fill it will be blocked. The field is hidden from real users — this is by design.', 'wp-edit-password-protected' ) }
						checked={ s.honeypot_enabled !== false }
						onChange={ ( v ) => update( 'honeypot_enabled', v ) }
					/>
				</div>
			</Card>

			<Card title={ __( 'WordPress Hardening', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={ __( 'Disable XML-RPC', 'wp-edit-password-protected' ) }
						description={ __( 'XML-RPC is disabled. This blocks remote publishing via third-party apps and prevents XML-RPC brute force attacks.', 'wp-edit-password-protected' ) }
						checked={ s.disable_xmlrpc !== false }
						onChange={ ( v ) => update( 'disable_xmlrpc', v ) }
					/>
					<ToggleCard
						title={ __( 'Hide WordPress Version', 'wp-edit-password-protected' ) }
						description={ __( 'The WordPress version number is removed from the page source, making it harder for attackers to identify known vulnerabilities.', 'wp-edit-password-protected' ) }
						checked={ s.hide_wp_version !== false }
						onChange={ ( v ) => update( 'hide_wp_version', v ) }
					/>
					<ToggleCard
						title={ __( 'Disable REST API User Enumeration', 'wp-edit-password-protected' ) }
						description={ __( 'The /wp/v2/users REST endpoint is blocked for non-admin users, preventing attackers from discovering usernames.', 'wp-edit-password-protected' ) }
						checked={ s.disable_rest_users !== false }
						onChange={ ( v ) => update( 'disable_rest_users', v ) }
					/>
				</div>
			</Card>

			<Card title={ __( 'CAPTCHA', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={
							<span>
								{ __( 'Enable CAPTCHA on login', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						checked={ isPro && s.captcha_enabled }
						onChange={ ( v ) => update( 'captcha_enabled', v ) }
						disabled={ ! isPro }
					/>
				</div>

				{ isPro && s.captcha_enabled && (
					<div className="wpepp-form-grid" style={ { marginTop: 16 } }>
						<div className="wpepp-form-grid__full">
							<SelectControl
								label={ __( 'Provider', 'wp-edit-password-protected' ) }
								value={ s.captcha_provider || '' }
								onChange={ ( v ) => update( 'captcha_provider', v ) }
								options={ [
									{ value: '', label: '—' },
									{ value: 'recaptcha_v2', label: 'reCAPTCHA v2 (checkbox)' },
									{ value: 'recaptcha_v3', label: 'reCAPTCHA v3 (invisible)' },
									{ value: 'turnstile', label: 'Cloudflare Turnstile' },
								] }
							/>
						</div>

						{ '' === ( s.captcha_provider || '' ) && (
							<div className="wpepp-form-grid__full">
								<Notice status="info" isDismissible={ false }>
									{ __( 'Choose a provider to continue.', 'wp-edit-password-protected' ) }
								</Notice>
							</div>
						) }

						{ ( s.captcha_provider === 'recaptcha_v2' || s.captcha_provider === 'recaptcha_v3' ) && (
							<>
								<TextControl
									label={ __( 'Google Site Key', 'wp-edit-password-protected' ) }
									value={ s.recaptcha_site_key || '' }
									onChange={ ( v ) => update( 'recaptcha_site_key', v ) }
								/>
								<TextControl
									label={ __( 'Google Secret Key', 'wp-edit-password-protected' ) }
									value={ s.recaptcha_secret_key || '' }
									onChange={ ( v ) => update( 'recaptcha_secret_key', v ) }
									type="password"
								/>
							</>
						) }

						{ s.captcha_provider === 'recaptcha_v3' && (
							<div className="wpepp-form-grid__full">
								<RangeControl
									label={ __( 'Score threshold', 'wp-edit-password-protected' ) }
									help={ sprintf( __( 'Requests scoring below %s are blocked. Higher = stricter.', 'wp-edit-password-protected' ), ( s.captcha_threshold ?? 0.5 ).toFixed( 1 ) ) }
									value={ Number( s.captcha_threshold ?? 0.5 ) }
									onChange={ ( v ) => update( 'captcha_threshold', v ) }
									min={ 0.1 }
									max={ 0.9 }
									step={ 0.1 }
									marks
								/>
							</div>
						) }

						{ s.captcha_provider === 'turnstile' && (
							<>
								<TextControl
									label={ __( 'Turnstile Site Key', 'wp-edit-password-protected' ) }
									value={ s.turnstile_site_key || '' }
									onChange={ ( v ) => update( 'turnstile_site_key', v ) }
								/>
								<TextControl
									label={ __( 'Turnstile Secret Key', 'wp-edit-password-protected' ) }
									value={ s.turnstile_secret_key || '' }
									onChange={ ( v ) => update( 'turnstile_secret_key', v ) }
									type="password"
								/>
							</>
						) }

						<div className="wpepp-form-grid__full">
							<div className="wpepp-toggle-grid">
								<ToggleCard
									title={ __( 'Test mode (skip verification)', 'wp-edit-password-protected' ) }
									description={ __( 'Use while configuring keys — no real checks are performed.', 'wp-edit-password-protected' ) }
									checked={ !! s.recaptcha_test_mode }
									onChange={ ( v ) => update( 'recaptcha_test_mode', v ) }
								/>
								<ToggleCard
									title={ __( 'Also show on the registration form', 'wp-edit-password-protected' ) }
									checked={ !! ( s.reg_captcha_enabled ?? s.reg_recaptcha_enabled ) }
									onChange={ ( v ) => update( 'reg_captcha_enabled', v ) }
								/>
							</div>
						</div>
					</div>
				) }
			</Card>

			<Card title={ __( 'Custom Login URL', 'wp-edit-password-protected' ) }>
				<div className="wpepp-form-grid">
					<div className="wpepp-form-grid__full">
						<TextControl
							label={
								<span>
									{ __( 'Custom Login Slug', 'wp-edit-password-protected' ) }
									{ ! isPro && <ProBadge /> }
								</span>
							}
							help={ siteUrl ? `${ siteUrl }/${ s.custom_login_url || 'my-login' }` : __( 'Replace the default wp-login.php URL with a custom slug.', 'wp-edit-password-protected' ) }
							value={ s.custom_login_url || '' }
							onChange={ ( v ) => update( 'custom_login_url', v.replace( /[^a-z0-9-]/gi, '' ).toLowerCase() ) }
							placeholder="my-login"
							disabled={ ! isPro }
						/>
					</div>
				</div>
				{ isPro && s.custom_login_url && (
					<Notice status="success" isDismissible={ false } style={ { marginTop: 16 } }>
						{ __( 'Your custom login URL is active. Use the slug above instead of wp-login.php.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
			</Card>

			<Card title={ __( 'Hide Login Page', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={ __( 'Hide wp-login.php', 'wp-edit-password-protected' ) }
						checked={ !! s.hide_login_page }
						onChange={ ( v ) => update( 'hide_login_page', v ) }
					/>
				</div>
				{ s.hide_login_page && (
					<div className="wpepp-form-grid" style={ { marginTop: 16 } }>
						<div className="wpepp-form-grid__full">
							<Notice status="warning" isDismissible={ false }>
								{ __( 'Direct access to wp-login.php and wp-admin will return a 404 page for non-logged-in users. Make sure you have a Custom Login URL set above before enabling this, or you may lock yourself out.', 'wp-edit-password-protected' ) }
							</Notice>
						</div>
						{ ! s.custom_login_url && (
							<div className="wpepp-form-grid__full">
								<Notice status="error" isDismissible={ false }>
									{ __( 'No Custom Login URL is set! Set a slug in the Custom Login URL section above first, otherwise you will not be able to access the login page.', 'wp-edit-password-protected' ) }
								</Notice>
							</div>
						) }
					</div>
				) }
			</Card>

			<Card title={ __( 'After Login Redirect', 'wp-edit-password-protected' ) }>
				<div className="wpepp-form-grid">
					<div className="wpepp-form-grid__full">
						<TextControl
							label={ __( 'Redirect URL', 'wp-edit-password-protected' ) }
							help={ __( 'Redirect users to this URL after a successful login. Leave empty for default WordPress behavior (wp-admin).', 'wp-edit-password-protected' ) }
							value={ s.after_login_redirect || '' }
							onChange={ ( v ) => update( 'after_login_redirect', v ) }
							placeholder="https://example.com/dashboard"
						/>
					</div>
				</div>
				{ s.after_login_redirect && (
					<Notice status="info" isDismissible={ false } style={ { marginTop: 16 } }>
						{ __( 'Users will be redirected to this URL after login, unless a specific redirect_to parameter is already in the login URL.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
			</Card>

		</div>
	);
};

export default LoginProtection;
