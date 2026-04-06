/**
 * Registration Protection settings — honeypot, rate limiter, disposable email blocker, etc.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	TextControl,
	TextareaControl,
	RangeControl,
	SelectControl,
	Notice,
	Spinner,
} from '@wordpress/components';
import { useMemo, useCallback } from '@wordpress/element';
import ProBadge from '../../components/ProBadge';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';

const RegistrationProtection = () => {
	const { settings, isLoading, isPro } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'security' ),
			isLoading: store.isLoading(),
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

	if ( isLoading ) {
		return <Spinner />;
	}

	return (
		<div className="wpepp-registration-protection">
			<h3>{ __( 'Registration Protection', 'wp-edit-password-protected' ) }</h3>

			{ /* --- Registration Honeypot (Free) --- */ }
			<PanelBody title={ __( 'Registration Honeypot', 'wp-edit-password-protected' ) } initialOpen>
				<PanelRow>
					<ToggleControl
						label={ __( 'Enable Registration Honeypot', 'wp-edit-password-protected' ) }
						checked={ !! s.reg_honeypot_enabled }
						onChange={ ( v ) => update( 'reg_honeypot_enabled', v ) }
					/>
				</PanelRow>
				{ s.reg_honeypot_enabled && (
					<Notice status="info" isDismissible={ false }>
						{ __( 'An invisible field is added to the registration form. Bots that auto-fill it will be blocked. Real users never see this field.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
			</PanelBody>

			{ /* --- Registration Rate Limiter (Free) --- */ }
			<PanelBody title={ __( 'Registration Rate Limiter', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
					<ToggleControl
						label={ __( 'Enable Registration Rate Limiter', 'wp-edit-password-protected' ) }
						checked={ !! s.reg_rate_limit_enabled }
						onChange={ ( v ) => update( 'reg_rate_limit_enabled', v ) }
					/>
				</PanelRow>
				{ s.reg_rate_limit_enabled && (
					<>
						<Notice status="info" isDismissible={ false }>
							{ __( 'Limits the number of registrations from the same IP address within a time window.', 'wp-edit-password-protected' ) }
						</Notice>
						<PanelRow>
							<RangeControl
								label={ __( 'Max Registrations Per IP', 'wp-edit-password-protected' ) }
								value={ s.reg_rate_limit_max || 3 }
								onChange={ ( v ) => update( 'reg_rate_limit_max', v ) }
								min={ 1 }
								max={ 20 }
							/>
						</PanelRow>
						<PanelRow>
							<RangeControl
								label={ __( 'Time Window (minutes)', 'wp-edit-password-protected' ) }
								value={ s.reg_rate_limit_window || 60 }
								onChange={ ( v ) => update( 'reg_rate_limit_window', v ) }
								min={ 5 }
								max={ 1440 }
							/>
						</PanelRow>
					</>
				) }
			</PanelBody>

			{ /* --- reCAPTCHA on Registration (Pro) --- */ }
			<PanelBody title={ __( 'reCAPTCHA on Registration', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
					<ToggleControl
						label={
							<span>
								{ __( 'Enable reCAPTCHA on Registration', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						checked={ isPro && !! s.reg_recaptcha_enabled }
						onChange={ ( v ) => update( 'reg_recaptcha_enabled', v ) }
						disabled={ ! isPro }
					/>
				</PanelRow>
				{ isPro && s.reg_recaptcha_enabled && (
					<>
						{ ( ! s.recaptcha_site_key || ! s.recaptcha_secret_key ) ? (
							<Notice status="warning" isDismissible={ false }>
								{ __( 'reCAPTCHA keys are not configured. Please set them in the Login Protection → reCAPTCHA section first.', 'wp-edit-password-protected' ) }
							</Notice>
						) : (
							<Notice status="info" isDismissible={ false }>
								{ __( 'reCAPTCHA will appear on the registration form using the same keys configured in Login Protection.', 'wp-edit-password-protected' ) }
							</Notice>
						) }
					</>
				) }
			</PanelBody>

			{ /* --- Disposable Email Blocker (Pro) --- */ }
			<PanelBody title={ __( 'Disposable Email Blocker', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
					<ToggleControl
						label={
							<span>
								{ __( 'Block Disposable Email Addresses', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						checked={ isPro && !! s.reg_block_disposable_emails }
						onChange={ ( v ) => update( 'reg_block_disposable_emails', v ) }
						disabled={ ! isPro }
					/>
				</PanelRow>
				{ isPro && s.reg_block_disposable_emails && (
					<Notice status="info" isDismissible={ false }>
						{ __( 'Registrations from known disposable/temporary email services (Mailinator, Guerrilla Mail, YOPmail, etc.) will be blocked. The list covers 200+ domains.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
			</PanelBody>

			{ /* --- Email Domain Whitelist / Blacklist (Pro) --- */ }
			<PanelBody title={ __( 'Email Domain Whitelist / Blacklist', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
					<SelectControl
						label={
							<span>
								{ __( 'Email Domain Filter', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						value={ isPro ? ( s.reg_email_domain_mode || 'off' ) : 'off' }
						options={ [
							{ label: __( 'Off', 'wp-edit-password-protected' ), value: 'off' },
							{ label: __( 'Whitelist — Only allow these domains', 'wp-edit-password-protected' ), value: 'whitelist' },
							{ label: __( 'Blacklist — Block these domains', 'wp-edit-password-protected' ), value: 'blacklist' },
						] }
						onChange={ ( v ) => update( 'reg_email_domain_mode', v ) }
						disabled={ ! isPro }
					/>
				</PanelRow>
				{ isPro && 'off' !== s.reg_email_domain_mode && (
					<>
						{ 'whitelist' === s.reg_email_domain_mode && (
							<Notice status="info" isDismissible={ false }>
								{ __( 'Only users with email addresses from the domains listed below can register.', 'wp-edit-password-protected' ) }
							</Notice>
						) }
						{ 'blacklist' === s.reg_email_domain_mode && (
							<Notice status="info" isDismissible={ false }>
								{ __( 'Users with email addresses from the domains listed below will be blocked from registering.', 'wp-edit-password-protected' ) }
							</Notice>
						) }
						<PanelRow>
							<TextareaControl
								label={ __( 'Email Domains', 'wp-edit-password-protected' ) }
								help={ __( 'Enter one domain per line (e.g., gmail.com, company.com). Do not include the @ symbol.', 'wp-edit-password-protected' ) }
								value={ s.reg_email_domain_list || '' }
								onChange={ ( v ) => update( 'reg_email_domain_list', v ) }
								rows={ 6 }
							/>
						</PanelRow>
					</>
				) }
			</PanelBody>

			{ /* --- Admin Approval (Pro) --- */ }
			<PanelBody title={ __( 'Admin Approval for New Users', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
					<ToggleControl
						label={
							<span>
								{ __( 'Require Admin Approval', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						checked={ isPro && !! s.reg_admin_approval }
						onChange={ ( v ) => update( 'reg_admin_approval', v ) }
						disabled={ ! isPro }
					/>
				</PanelRow>
				{ isPro && s.reg_admin_approval && (
					<>
						<Notice status="warning" isDismissible={ false }>
							{ __( 'New users will not be able to log in until an administrator approves their account. An email notification is sent to the site admin for each new registration.', 'wp-edit-password-protected' ) }
						</Notice>
						<Notice status="info" isDismissible={ false }>
							{ __( 'To approve pending users, go to Users → All Users and look for users with the "Pending" meta. Remove the pending flag to grant access.', 'wp-edit-password-protected' ) }
						</Notice>
					</>
				) }
			</PanelBody>
		</div>
	);
};

export default RegistrationProtection;
