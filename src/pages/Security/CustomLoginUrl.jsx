/**
 * Custom Login URL settings (Pro).
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { TextControl, Notice } from '@wordpress/components';
import Card from '../../components/Card';
import ToggleCard from '../../components/ToggleCard';
import { useMemo, useCallback, useState, useEffect } from '@wordpress/element';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';
import Loading from '../../components/Loading';

const CustomLoginUrl = () => {
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
	const isEnabled = Boolean( s.custom_login_url && s.custom_login_url.trim() !== '' );
	const [ savedSlug, setSavedSlug ] = useState( '' );

	useEffect( () => {
		if ( s.custom_login_url ) {
			setSavedSlug( s.custom_login_url );
		}
	}, [ s.custom_login_url ] );

	const update = useCallback( ( key, value ) => {
		updateSetting( 'security', key, value );
	}, [ updateSetting ] );

	const handleToggle = useCallback( ( checked ) => {
		if ( checked ) {
			update( 'custom_login_url', savedSlug || s.custom_login_url || 'my-login' );
		} else {
			if ( s.custom_login_url ) {
				setSavedSlug( s.custom_login_url );
			}
			update( 'custom_login_url', '' );
		}
	}, [ update, savedSlug, s.custom_login_url ] );

	const handleSave = useCallback( () => {
		saveSettings( 'security', s );
	}, [ saveSettings, s ] );

	const handleReset = useCallback( () => {
		resetSettings( 'security' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	if ( isLoading ) {
		return <Loading variant="form" count={ 2 } />;
	}

	const siteUrl = window.wpeppData?.adminUrl?.replace( /\/wp-admin\/$/, '' ) || '';

	return (
		<div className="wpepp-custom-login-url">
			<h3>{ __( 'Custom Login URL', 'wp-edit-password-protected' ) }</h3>
			<p>{ __( 'Replace the default wp-login.php URL with a custom secret slug to prevent brute-force attacks.', 'wp-edit-password-protected' ) }</p>

			<Card
				title={ __( 'Login URL Masking', 'wp-edit-password-protected' ) }
				subtitle={ __( 'Conceals wp-login.php and wp-admin from automated bots and scripts.', 'wp-edit-password-protected' ) }
			>
				<div className="wpepp-toggle-grid wpepp-toggle-grid--single" style={ { marginBottom: isEnabled ? '18px' : '0' } }>
					<ToggleCard
						title={ __( 'Enable Custom Login URL', 'wp-edit-password-protected' ) }
						description={ __( 'Redirect standard wp-login.php requests to a 404 or custom slug.', 'wp-edit-password-protected' ) }
						checked={ isEnabled }
						onChange={ handleToggle }
					/>
				</div>

				{ isEnabled && (
					<div style={ { display: 'flex', flexDirection: 'column', gap: '16px' } }>
						<TextControl
							__nextHasNoMarginBottom
							label={ __( 'Custom Login Slug', 'wp-edit-password-protected' ) }
							help={ __( 'Allowed characters: lowercase letters, numbers, and hyphens.', 'wp-edit-password-protected' ) }
							value={ s.custom_login_url || '' }
							onChange={ ( v ) => {
								const cleaned = v.replace( /[^a-z0-9-]/gi, '' ).toLowerCase();
								update( 'custom_login_url', cleaned );
								if ( cleaned ) {
									setSavedSlug( cleaned );
								}
							} }
							placeholder="my-login"
						/>

						{ siteUrl && (
							<div style={ {
								padding: '12px 16px',
								background: 'var(--wpepp-surface)',
								borderRadius: '8px',
								border: '1px solid var(--wpepp-border)',
							} }>
								<div style={ { fontSize: '11px', fontWeight: 600, color: 'var(--wpepp-text-3)', textTransform: 'uppercase', letterSpacing: '0.06em', marginBottom: '6px' } }>
									{ __( 'Active Login Endpoint', 'wp-edit-password-protected' ) }
								</div>
								<code style={ { fontSize: '13.5px', color: 'var(--wpepp-accent)', fontFamily: 'var(--wpepp-mono)' } }>
									{ `${ siteUrl }/${ s.custom_login_url || 'my-login' }` }
								</code>
							</div>
						) }

						<Notice status="warning" isDismissible={ false }>
							{ __( 'Important: Bookmark your custom login URL before logging out. If you ever get locked out, you can restore access by adding define( "WPEPP_SAFE_MODE", true ); to your wp-config.php.', 'wp-edit-password-protected' ) }
						</Notice>
					</div>
				) }
			</Card>

			<Card title={ __( 'Hide Login Page', 'wp-edit-password-protected' ) } subtitle={ __( 'Block direct access to wp-login.php and wp-admin for non-logged-in users.', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={ __( 'Hide wp-login.php', 'wp-edit-password-protected' ) }
						description={ __( 'Direct access to wp-login.php and wp-admin will return a 404 page for non-logged-in visitors.', 'wp-edit-password-protected' ) }
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
					</div>
				) }
			</Card>
		</div>
	);
};

export default CustomLoginUrl;
