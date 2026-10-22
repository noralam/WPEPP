/**
 * Two-Factor Authentication settings (Pro).
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	Notice,
	Spinner,
	Button,
	TextControl,
	SelectControl,
	CheckboxControl,
} from '@wordpress/components';
import Card from '../../components/Card';
import ToggleCard from '../../components/ToggleCard';
import { useMemo, useCallback, useState, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';

const ROLE_OPTIONS = [
	{ value: 'administrator', label: __( 'Administrator', 'wp-edit-password-protected' ) },
	{ value: 'editor', label: __( 'Editor', 'wp-edit-password-protected' ) },
	{ value: 'author', label: __( 'Author', 'wp-edit-password-protected' ) },
	{ value: 'contributor', label: __( 'Contributor', 'wp-edit-password-protected' ) },
	{ value: 'subscriber', label: __( 'Subscriber', 'wp-edit-password-protected' ) },
];

const TwoFactor = () => {
	const { settings, isLoading } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'security' ),
			isLoading: store.isLoading(),
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

	// 2FA personal setup state.
	const [ setupLoading, setSetupLoading ] = useState( false );
	const [ setupData, setSetupData ] = useState( null );
	const [ verifyCode, setVerifyCode ] = useState( '' );
	const [ recoveryCodes, setRecoveryCodes ] = useState( null );
	const [ twoFaStatus, setTwoFaStatus ] = useState( null );
	const [ setupError, setSetupError ] = useState( '' );

	const loadStatus = useCallback( async () => {
		try {
			const res = await apiFetch( { path: '/wpepp/v1/2fa/status' } );
			setTwoFaStatus( res );
		} catch {
			// Ignore — not pro or not available.
		}
	}, [] );

	// Load status on first render.
	useEffect( () => {
		loadStatus();
	}, [ loadStatus ] );

	const startSetup = useCallback( async () => {
		setSetupLoading( true );
		setSetupError( '' );
		try {
			const res = await apiFetch( { path: '/wpepp/v1/2fa/setup', method: 'POST' } );
			setSetupData( res );
		} catch ( err ) {
			setSetupError( err.message || __( 'Setup failed.', 'wp-edit-password-protected' ) );
		}
		setSetupLoading( false );
	}, [] );

	// Draw the QR code locally — secrets must never leave the browser.
	const qrRef = useRef( null );
	useEffect( () => {
		if ( ! setupData || ! qrRef.current ) {
			return undefined;
		}

		let cancelled = false;
		let attempts = 0;

		const draw = () => {
			if ( cancelled ) {
				return;
			}
			if ( ! window.QRCode ) {
				if ( attempts++ > 50 ) {
					return; // Lib failed to load — manual entry remains available.
				}
				setTimeout( draw, 100 );
				return;
			}
			qrRef.current.innerHTML = '';
			// eslint-disable-next-line no-new
			new window.QRCode( qrRef.current, {
				text: setupData.uri,
				width: 200,
				height: 200,
				correctLevel: window.QRCode.CorrectLevel.M,
			} );
		};

		draw();

		return () => {
			cancelled = true;
		};
	}, [ setupData ] );

	const confirmSetup = useCallback( async () => {
		setSetupLoading( true );
		setSetupError( '' );
		try {
			const res = await apiFetch( {
				path: '/wpepp/v1/2fa/confirm',
				method: 'POST',
				data: { code: verifyCode },
			} );
			setRecoveryCodes( res.recovery_codes );
			setSetupData( null );
			setVerifyCode( '' );
			loadStatus();
		} catch ( err ) {
			setSetupError( err.message || __( 'Verification failed.', 'wp-edit-password-protected' ) );
		}
		setSetupLoading( false );
	}, [ verifyCode, loadStatus ] );

	const disableSetup = useCallback( async () => {
		setSetupLoading( true );
		try {
			await apiFetch( { path: '/wpepp/v1/2fa/disable', method: 'POST' } );
			setTwoFaStatus( { enabled: false, required: twoFaStatus?.required } );
			setSetupData( null );
			setRecoveryCodes( null );
		} catch {
			// Ignore.
		}
		setSetupLoading( false );
	}, [ twoFaStatus ] );

	const toggleRole = useCallback( ( role, checked ) => {
		const current = Array.isArray( s.two_factor_roles ) ? [ ...s.two_factor_roles ] : [ 'administrator' ];
		if ( checked ) {
			if ( ! current.includes( role ) ) {
				current.push( role );
			}
		} else {
			const idx = current.indexOf( role );
			if ( idx > -1 ) {
				current.splice( idx, 1 );
			}
		}
		update( 'two_factor_roles', current );
	}, [ s.two_factor_roles, update ] );

	if ( isLoading ) {
		return <Spinner />;
	}

	const roles = Array.isArray( s.two_factor_roles ) ? s.two_factor_roles : [ 'administrator' ];

	return (
		<div className="wpepp-two-factor">
			<h3>{ __( 'Two-Factor Authentication', 'wp-edit-password-protected' ) }</h3>

			{ /* --- Global 2FA Settings --- */ }
			<Card title={ __( 'Two-Factor Settings', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={ __( 'Enable Two-Factor Authentication', 'wp-edit-password-protected' ) }
						checked={ s.two_factor_enabled }
						onChange={ ( v ) => update( 'two_factor_enabled', v ) }
					/>
				</div>
				
				{ s.two_factor_enabled && (
					<div className="wpepp-form-grid wpepp-form-grid--stack" style={ { marginTop: 16 } }>
						<Notice status="info" isDismissible={ false }>
							{ __( 'Users in the selected roles will be prompted for a 6-digit code from their authenticator app (Google Authenticator, Authy, Microsoft Authenticator, etc.) when logging in.', 'wp-edit-password-protected' ) }
						</Notice>
													<fieldset className="wpepp-role-checkboxes">
								<legend className="wpepp-role-checkboxes__legend">
									{ __( 'Require 2FA for these roles:', 'wp-edit-password-protected' ) }
								</legend>
								<div className="wpepp-role-checkboxes__list">
									{ ROLE_OPTIONS.map( ( opt ) => (
										<CheckboxControl
											key={ opt.value }
											label={ opt.label }
											checked={ roles.includes( opt.value ) }
											onChange={ ( v ) => toggleRole( opt.value, v ) }
										/>
									) ) }
								</div>
							</fieldset>
						
													<SelectControl
								label={ __( 'Remember trusted devices', 'wp-edit-password-protected' ) }
								help={ __( 'After a successful code entry, the device skips 2FA for the chosen period.', 'wp-edit-password-protected' ) }
								value={ String( s.two_fa_remember_days ?? 0 ) }
								onChange={ ( v ) => update( 'two_fa_remember_days', Number( v ) ) }
								options={ [
									{ value: '0', label: __( 'Off — ask every login', 'wp-edit-password-protected' ) },
									{ value: '7', label: __( '7 days', 'wp-edit-password-protected' ) },
									{ value: '14', label: __( '14 days', 'wp-edit-password-protected' ) },
									{ value: '30', label: __( '30 days', 'wp-edit-password-protected' ) },
									{ value: '90', label: __( '90 days', 'wp-edit-password-protected' ) },
								] }
							/>
						
						<Notice status="warning" isDismissible={ false }>
							{ __( 'Users must set up 2FA from their WordPress Profile page. Until they do, they can still log in without a code.', 'wp-edit-password-protected' ) }
						</Notice>
					</div>
				) }
			</Card>

			{ /* --- Personal 2FA Setup (for current admin) --- */ }
			{ s.two_factor_enabled && (
				<Card title={ __( 'Your 2FA Setup', 'wp-edit-password-protected' ) }>
					{ twoFaStatus?.enabled ? (
						<>
							<Notice status="success" isDismissible={ false }>
								<strong>&#10003; { __( 'Two-factor authentication is active on your account.', 'wp-edit-password-protected' ) }</strong>
							</Notice>
															<Button
									variant="secondary"
									isDestructive
									onClick={ disableSetup }
									isBusy={ setupLoading }
								>
									{ __( 'Disable 2FA', 'wp-edit-password-protected' ) }
								</Button>
							
						</>
					) : (
						<>
							{ ! setupData && ! recoveryCodes && (
								<>
									<p>{ __( 'Set up two-factor authentication for your account using an authenticator app.', 'wp-edit-password-protected' ) }</p>
																			<Button
											variant="primary"
											onClick={ startSetup }
											isBusy={ setupLoading }
										>
											{ __( 'Start Setup', 'wp-edit-password-protected' ) }
										</Button>
									
								</>
							) }

							{ setupData && (
								<>
									<p><strong>{ __( 'Step 1:', 'wp-edit-password-protected' ) }</strong> { __( 'Scan this QR code with your authenticator app:', 'wp-edit-password-protected' ) }</p>
									<div style={ { textAlign: 'center', padding: 16 } }>
										<div
											ref={ qrRef }
											style={ {
												display: 'inline-block',
												minWidth: 200,
												minHeight: 200,
												background: '#fff',
												padding: 8,
												borderRadius: 4,
											} }
										/>
										{ ! window.QRCode && (
											<p style={ { marginTop: 8, color: '#757575' } }>
												{ __( 'QR preview unavailable — use the manual key below.', 'wp-edit-password-protected' ) }
											</p>
										) }
									</div>
									<p>
										<strong>{ __( 'Manual key:', 'wp-edit-password-protected' ) }</strong>{ ' ' }
										<code style={ { background: 'rgba(255,255,255,0.1)', padding: '2px 6px', borderRadius: 3 } }>
											{ setupData.secret }
										</code>
									</p>
									<p><strong>{ __( 'Step 2:', 'wp-edit-password-protected' ) }</strong> { __( 'Enter the 6-digit code from your app to verify:', 'wp-edit-password-protected' ) }</p>
									{ setupError && (
										<Notice status="error" isDismissible={ false }>{ setupError }</Notice>
									) }
																			<TextControl
											label={ __( 'Verification Code', 'wp-edit-password-protected' ) }
											value={ verifyCode }
											onChange={ setVerifyCode }
											placeholder="000000"
											style={ { maxWidth: 180 } }
										/>
									
																			<Button
											variant="primary"
											onClick={ confirmSetup }
											isBusy={ setupLoading }
											disabled={ verifyCode.length < 6 }
										>
											{ __( 'Verify & Enable', 'wp-edit-password-protected' ) }
										</Button>
									
								</>
							) }
						</>
					) }

					{ recoveryCodes && (
						<>
							<Notice status="warning" isDismissible={ false }>
								<strong>{ __( 'Save your recovery codes!', 'wp-edit-password-protected' ) }</strong>
								<br />
								{ __( 'These codes can be used to log in if you lose access to your authenticator app. Each code can only be used once. Store them in a safe place.', 'wp-edit-password-protected' ) }
							</Notice>
							<div style={ {
								background: 'rgba(255,255,255,0.05)',
								border: '1px solid rgba(255,255,255,0.15)',
								borderRadius: 4,
								padding: 16,
								fontFamily: 'monospace',
								fontSize: 14,
								lineHeight: 2,
								marginTop: 8,
							} }>
								{ recoveryCodes.map( ( code, i ) => (
									<div key={ i }>{ code }</div>
								) ) }
							</div>
															<Button
									variant="secondary"
									onClick={ () => {
										navigator.clipboard?.writeText( recoveryCodes.join( '\n' ) );
									} }
								>
									{ __( 'Copy to Clipboard', 'wp-edit-password-protected' ) }
								</Button>
							
						</>
					) }
				</Card>
			) }
		</div>
	);
};

export default TwoFactor;
