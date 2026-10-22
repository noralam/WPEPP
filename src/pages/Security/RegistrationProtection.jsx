/**
 * Registration Protection settings — honeypot, rate limiter, disposable email blocker, etc.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	ToggleControl,
	TextControl,
	TextareaControl,
	RangeControl,
	SelectControl,
	Notice,
	Spinner,
} from '@wordpress/components';
import Card from '../../components/Card';
import ToggleCard from '../../components/ToggleCard';
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
			<Card title={ __( 'Registration Honeypot', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={ __( 'Enable Registration Honeypot', 'wp-edit-password-protected' ) }
						description={ __( 'An invisible field is added to the registration form. Bots that auto-fill it will be blocked. Real users never see this field.', 'wp-edit-password-protected' ) }
						checked={ !! s.reg_honeypot_enabled }
						onChange={ ( v ) => update( 'reg_honeypot_enabled', v ) }
					/>
				</div>
			</Card>

			{ /* --- Registration Rate Limiter (Free) --- */ }
			<Card title={ __( 'Registration Rate Limiter', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={ __( 'Enable Registration Rate Limiter', 'wp-edit-password-protected' ) }
						description={ __( 'Limits the number of registrations from the same IP address within a time window.', 'wp-edit-password-protected' ) }
						checked={ !! s.reg_rate_limit_enabled }
						onChange={ ( v ) => update( 'reg_rate_limit_enabled', v ) }
					/>
				</div>
				{ s.reg_rate_limit_enabled && (
					<div className="wpepp-form-grid" style={ { marginTop: 16 } }>
						<RangeControl
							label={ __( 'Max Registrations Per IP', 'wp-edit-password-protected' ) }
							value={ s.reg_rate_limit_max || 3 }
							onChange={ ( v ) => update( 'reg_rate_limit_max', v ) }
							min={ 1 }
							max={ 20 }
						/>
						<RangeControl
							label={ __( 'Time Window (minutes)', 'wp-edit-password-protected' ) }
							value={ s.reg_rate_limit_window || 60 }
							onChange={ ( v ) => update( 'reg_rate_limit_window', v ) }
							min={ 5 }
							max={ 1440 }
						/>
					</div>
				) }
			</Card>

			{ /* --- reCAPTCHA on Registration (Pro) --- */ }
			<Card title={ __( 'reCAPTCHA on Registration', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={
							<span>
								{ __( 'Enable reCAPTCHA on Registration', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						checked={ isPro && !! s.reg_captcha_enabled }
						onChange={ ( v ) => update( 'reg_captcha_enabled', v ) }
						disabled={ ! isPro }
					/>
				</div>
				{ isPro && s.reg_captcha_enabled && (
					<>
						{ ( ! s.recaptcha_site_key || ! s.recaptcha_secret_key ) ? (
							<Notice status="warning" isDismissible={ false } style={ { marginTop: 16 } }>
								{ __( 'reCAPTCHA keys are not configured. Please set them in the Login Protection → reCAPTCHA section first.', 'wp-edit-password-protected' ) }
							</Notice>
						) : (
							<Notice status="info" isDismissible={ false } style={ { marginTop: 16 } }>
								{ __( 'reCAPTCHA will appear on the registration form using the same keys configured in Login Protection.', 'wp-edit-password-protected' ) }
							</Notice>
						) }
					</>
				) }
			</Card>

			{ /* --- Disposable Email Blocker (Pro) --- */ }
			<Card title={ __( 'Disposable Email Blocker', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={
							<span>
								{ __( 'Block Disposable Email Addresses', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						description={ __( 'Registrations from known disposable/temporary email services (Mailinator, Guerrilla Mail, YOPmail, etc.) will be blocked. The list covers 200+ domains.', 'wp-edit-password-protected' ) }
						checked={ isPro && !! s.reg_block_disposable_emails }
						onChange={ ( v ) => update( 'reg_block_disposable_emails', v ) }
						disabled={ ! isPro }
					/>
				</div>
			</Card>

			{ /* --- Email Domain Whitelist / Blacklist (Pro) --- */ }
			<Card title={ __( 'Email Domain Whitelist / Blacklist', 'wp-edit-password-protected' ) }>
				<div className="wpepp-form-grid">
					<div className="wpepp-form-grid__full">
						<SelectControl
							__next40pxDefaultSize
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
					</div>
					{ isPro && 'off' !== s.reg_email_domain_mode && (
						<>
							{ 'whitelist' === s.reg_email_domain_mode && (
								<div className="wpepp-form-grid__full">
									<Notice status="info" isDismissible={ false }>
										{ __( 'Only users with email addresses from the domains listed below can register.', 'wp-edit-password-protected' ) }
									</Notice>
								</div>
							) }
							{ 'blacklist' === s.reg_email_domain_mode && (
								<div className="wpepp-form-grid__full">
									<Notice status="info" isDismissible={ false }>
										{ __( 'Users with email addresses from the domains listed below will be blocked from registering.', 'wp-edit-password-protected' ) }
									</Notice>
								</div>
							) }
							<div className="wpepp-form-grid__full">
								<TextareaControl
									label={ __( 'Email Domains', 'wp-edit-password-protected' ) }
									help={ __( 'Enter one domain per line (e.g., gmail.com, company.com). Do not include the @ symbol.', 'wp-edit-password-protected' ) }
									value={ s.reg_email_domain_list || '' }
									onChange={ ( v ) => update( 'reg_email_domain_list', v ) }
									rows={ 6 }
								/>
							</div>
						</>
					) }
				</div>
			</Card>

			{ /* --- Admin Approval (Pro) --- */ }
			<Card title={ __( 'Admin Approval for New Users', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={
							<span>
								{ __( 'Require Admin Approval', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						description={ __( 'New users will not be able to log in until an administrator approves their account. An email notification is sent to the site admin for each new registration.', 'wp-edit-password-protected' ) }
						checked={ isPro && !! s.reg_admin_approval }
						onChange={ ( v ) => update( 'reg_admin_approval', v ) }
						disabled={ ! isPro }
					/>
				</div>
				{ isPro && s.reg_admin_approval && (
					<Notice status="info" isDismissible={ false } style={ { marginTop: 16 } }>
						{ __( 'To approve pending users, go to Users → All Users and look for users with the "Pending" meta. Remove the pending flag to grant access.', 'wp-edit-password-protected' ) }
					</Notice>
				) }
			</Card>
		</div>
	);
};

export default RegistrationProtection;
