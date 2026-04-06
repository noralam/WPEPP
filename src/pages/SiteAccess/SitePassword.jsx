/**
 * Site Access → Site Password — password-protect the entire site.
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
	Notice,
	Spinner,
} from '@wordpress/components';
import { useMemo, useCallback } from '@wordpress/element';
import { useSaveBar } from '../../components/SaveBar';
import { SITE_ACCESS_DEFAULTS } from '../../utils/defaults';

const SitePassword = () => {
	const { settings, isLoading, isSaving } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'site_access' ),
			isLoading: store.isLoading(),
			isSaving: store.isSaving(),
		};
	} );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );

	const s = useMemo( () => ( { ...SITE_ACCESS_DEFAULTS, ...settings } ), [ settings ] );

	const update = useCallback( ( key, value ) => {
		updateSetting( 'site_access', key, value );
	}, [ updateSetting ] );

	const handleSave = useCallback( () => {
		saveSettings( 'site_access', s );
	}, [ saveSettings, s ] );

	const handleReset = useCallback( () => {
		resetSettings( 'site_access' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	if ( isLoading ) {
		return <Spinner />;
	}

	return (
		<div className="wpepp-site-password-settings">
			<h3>{ __( 'Site Password Protection', 'wp-edit-password-protected' ) }</h3>

			<PanelBody title={ __( 'Password Protect Entire Site', 'wp-edit-password-protected' ) } initialOpen>
				<PanelRow>
					<ToggleControl
						label={ __( 'Enable Site Password', 'wp-edit-password-protected' ) }
						help={ __( 'When enabled, all visitors must enter a password before they can view any page on the site. Logged-in administrators bypass the password.', 'wp-edit-password-protected' ) }
						checked={ !! s.site_password_enabled }
						onChange={ ( v ) => update( 'site_password_enabled', v ) }
					/>
				</PanelRow>

				{ !! s.site_password_enabled && (
					<>
						{ ! s.site_password && (
							<Notice status="warning" isDismissible={ false }>
								{ __( 'Site password protection will not activate until you set a password.', 'wp-edit-password-protected' ) }
							</Notice>
						) }

						<PanelRow>
							<TextControl
								label={ __( 'Site Password', 'wp-edit-password-protected' ) }
								help={ __( 'Enter the password visitors must provide to access the site.', 'wp-edit-password-protected' ) }
								value={ s.site_password || '' }
								onChange={ ( v ) => update( 'site_password', v ) }
								type="text"
								autoComplete="off"
							/>
						</PanelRow>

						<PanelRow>
							<TextareaControl
								label={ __( 'Custom Message', 'wp-edit-password-protected' ) }
								help={ __( 'Message shown above the password form.', 'wp-edit-password-protected' ) }
								value={ s.site_password_message || '' }
								onChange={ ( v ) => update( 'site_password_message', v ) }
								rows={ 3 }
							/>
						</PanelRow>

						<PanelRow>
							<RangeControl
								label={ __( 'Cookie Duration (days)', 'wp-edit-password-protected' ) }
								help={ __( 'How many days the access cookie lasts before the visitor must re-enter the password.', 'wp-edit-password-protected' ) }
								value={ s.site_password_cookie_days || 7 }
								onChange={ ( v ) => update( 'site_password_cookie_days', v ) }
								min={ 1 }
								max={ 365 }
							/>
						</PanelRow>

						<PanelRow>
							<ToggleControl
								label={ __( 'Allow Logged-In Users to Bypass', 'wp-edit-password-protected' ) }
								help={ __( 'When enabled, all logged-in users bypass the site password. When disabled, only administrators bypass it.', 'wp-edit-password-protected' ) }
								checked={ s.site_password_bypass_logged_in !== false }
								onChange={ ( v ) => update( 'site_password_bypass_logged_in', v ) }
							/>
						</PanelRow>

						<Notice status="info" isDismissible={ false }>
							{ __( 'Administrators always bypass the site password.', 'wp-edit-password-protected' ) }
						</Notice>
					</>
				) }
			</PanelBody>
		</div>
	);
};

export default SitePassword;
