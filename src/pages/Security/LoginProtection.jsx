/**
 * Login Protection settings — brute force, honeypot, XML-RPC, etc.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
	RangeControl,
	Notice,
	Spinner,
} from '@wordpress/components';
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

			<PanelBody title={ __( 'Login Limiter', 'wp-edit-password-protected' ) } initialOpen>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Enable Login Limiter', 'wp-edit-password-protected' ) }
						checked={ s.login_limit_enabled !== false }
						onChange={ ( v ) => update( 'login_limit_enabled', v ) }
					/>
				</PanelRow>
				{ s.login_limit_enabled !== false && (
					<>
						<Notice status="info" isDismissible={ false }>
							{ __( 'Users will be locked out after too many failed login attempts. The lockout is based on IP address.', 'wp-edit-password-protected' ) }
						</Notice>
						<PanelRow>
							<RangeControl
								label={ __( 'Max Attempts', 'wp-edit-password-protected' ) }
								value={ s.max_attempts || 5 }
								onChange={ ( v ) => update( 'max_attempts', v ) }
								min={ 1 }
								max={ 20 }
							/>
						</PanelRow>
						<PanelRow>
							<RangeControl
								label={ __( 'Lockout Duration (minutes)', 'wp-edit-password-protected' ) }
								value={ s.lockout_duration || 15 }
								onChange={ ( v ) => update( 'lockout_duration', v ) }
								min={ 1 }
								max={ 120 }
							/>
						</PanelRow>
					</>
				) }
			</PanelBody>

			<PanelBody title={ __( 'Honeypot', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Enable Honeypot Field', 'wp-edit-password-protected' ) }
						checked={ s.honeypot_enabled !== false }
						onChange={ ( v ) => update( 'honeypot_enabled', v ) }
					/>
				</PanelRow>
				{ s.honeypot_enabled !== false && (
					<Notice status="info" isDismissible={ false }>
						{ __( 'An invisible field is added to the login form. Bots that auto-fill it will be blocked. The field is hidden from real users — this is by design.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
			</PanelBody>

			<PanelBody title={ __( 'WordPress Hardening', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Disable XML-RPC', 'wp-edit-password-protected' ) }
						checked={ s.disable_xmlrpc !== false }
						onChange={ ( v ) => update( 'disable_xmlrpc', v ) }
					/>
				</PanelRow>
				{ s.disable_xmlrpc !== false && (
					<Notice status="info" isDismissible={ false }>
						{ __( 'XML-RPC is disabled. This blocks remote publishing via third-party apps and prevents XML-RPC brute force attacks.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Hide WordPress Version', 'wp-edit-password-protected' ) }
						checked={ s.hide_wp_version !== false }
						onChange={ ( v ) => update( 'hide_wp_version', v ) }
					/>
				</PanelRow>
				{ s.hide_wp_version !== false && (
					<Notice status="info" isDismissible={ false }>
						{ __( 'The WordPress version number is removed from the page source, making it harder for attackers to identify known vulnerabilities.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Disable REST API User Enumeration', 'wp-edit-password-protected' ) }
						checked={ s.disable_rest_users !== false }
						onChange={ ( v ) => update( 'disable_rest_users', v ) }
					/>
				</PanelRow>
				{ s.disable_rest_users !== false && (
					<Notice status="info" isDismissible={ false }>
						{ __( 'The /wp/v2/users REST endpoint is blocked for non-admin users, preventing attackers from discovering usernames.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
			</PanelBody>

			<PanelBody title={ __( 'reCAPTCHA', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom
						label={
							<span>
								{ __( 'Enable reCAPTCHA', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						checked={ isPro && s.recaptcha_enabled }
						onChange={ ( v ) => update( 'recaptcha_enabled', v ) }
						disabled={ ! isPro }
					/>
				</PanelRow>
				{ isPro && s.recaptcha_enabled && (
					<>
						<Notice status="info" isDismissible={ false }>
							{ __( 'Google reCAPTCHA v2 checkbox will appear on the login form. You need a Site Key and Secret Key from', 'wp-edit-password-protected' ) }
							{ ' ' }
							<a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener noreferrer">
								{ __( 'Google reCAPTCHA Admin', 'wp-edit-password-protected' ) }
							</a>.
						</Notice>
						{ ( ! s.recaptcha_site_key || ! s.recaptcha_secret_key ) && (
							<Notice status="warning" isDismissible={ false }>
								{ __( 'reCAPTCHA will not appear until both Site Key and Secret Key are provided.', 'wp-edit-password-protected' ) }
							</Notice>
						) }
						<PanelRow>
							<TextControl
								label={ __( 'Site Key', 'wp-edit-password-protected' ) }
								value={ s.recaptcha_site_key || '' }
								onChange={ ( v ) => update( 'recaptcha_site_key', v ) }
							/>
						</PanelRow>
						<PanelRow>
							<TextControl
								label={ __( 'Secret Key', 'wp-edit-password-protected' ) }
								value={ s.recaptcha_secret_key || '' }
								onChange={ ( v ) => update( 'recaptcha_secret_key', v ) }
								type="password"
							/>
						</PanelRow>
					</>
				) }
			</PanelBody>

			<PanelBody title={ __( 'Custom Login URL', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
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
				</PanelRow>
				{ isPro && s.custom_login_url && (
					<Notice status="success" isDismissible={ false }>
						{ __( 'Your custom login URL is active. Use the slug above instead of wp-login.php.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
			</PanelBody>

			<PanelBody title={ __( 'Hide Login Page', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Hide wp-login.php', 'wp-edit-password-protected' ) }
						checked={ !! s.hide_login_page }
						onChange={ ( v ) => update( 'hide_login_page', v ) }
					/>
				</PanelRow>
				{ s.hide_login_page && (
					<>
						<Notice status="warning" isDismissible={ false }>
							{ __( 'Direct access to wp-login.php and wp-admin will return a 404 page for non-logged-in users. Make sure you have a Custom Login URL set above before enabling this, or you may lock yourself out.', 'wp-edit-password-protected' ) }
						</Notice>
						{ ! s.custom_login_url && (
							<Notice status="error" isDismissible={ false }>
								{ __( 'No Custom Login URL is set! Set a slug in the Custom Login URL section above first, otherwise you will not be able to access the login page.', 'wp-edit-password-protected' ) }
							</Notice>
						) }
					</>
				) }
			</PanelBody>

			<PanelBody title={ __( 'After Login Redirect', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
					<TextControl
						label={ __( 'Redirect URL', 'wp-edit-password-protected' ) }
						help={ __( 'Redirect users to this URL after a successful login. Leave empty for default WordPress behavior (wp-admin).', 'wp-edit-password-protected' ) }
						value={ s.after_login_redirect || '' }
						onChange={ ( v ) => update( 'after_login_redirect', v ) }
						placeholder="https://example.com/dashboard"
					/>
				</PanelRow>
				{ s.after_login_redirect && (
					<Notice status="info" isDismissible={ false }>
						{ __( 'Users will be redirected to this URL after login, unless a specific redirect_to parameter is already in the login URL.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
			</PanelBody>

		</div>
	);
};

export default LoginProtection;
