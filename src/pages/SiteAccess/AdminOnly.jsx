/**
 * Site Access → Admin Only — require login to view the site.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	PanelBody,
	PanelRow,
	ToggleControl,
	SelectControl,
	TextControl,
	TextareaControl,
	Notice,
	Spinner,
} from '@wordpress/components';
import { useMemo, useCallback } from '@wordpress/element';
import { useSaveBar } from '../../components/SaveBar';
import { SITE_ACCESS_DEFAULTS } from '../../utils/defaults';
import ProBadge from '../../components/ProBadge';

const AdminOnly = () => {
	const { settings, isLoading, isSaving, isPro } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'site_access' ),
			isLoading: store.isLoading(),
			isSaving: store.isSaving(),
			isPro: store.isPro(),
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
		<div className="wpepp-admin-only-settings">
			<h3>{ __( 'Admin Only Mode', 'wp-edit-password-protected' ) }</h3>

			<PanelBody title={ __( 'Admin Only Site', 'wp-edit-password-protected' ) } initialOpen>
				<PanelRow>
					<ToggleControl
						label={ __( 'Enable Admin Only Mode', 'wp-edit-password-protected' ) }
						help={ __( 'When enabled, only logged-in users can view the site. All other visitors will be required to log in.', 'wp-edit-password-protected' ) }
						checked={ !! s.admin_only_enabled }
						onChange={ ( v ) => update( 'admin_only_enabled', v ) }
					/>
				</PanelRow>

				{ !! s.admin_only_enabled && (
					<>
						<Notice status="info" isDismissible={ false }>
							{ __( 'Your entire site will be hidden from non-logged-in visitors. They will either be redirected to the login page or shown a login popup depending on your selection below.', 'wp-edit-password-protected' ) }
						</Notice>

						<PanelRow>
							<SelectControl
								label={
									<>
										{ __( 'Action for Non-Logged-In Users', 'wp-edit-password-protected' ) }
										{ ! isPro && <ProBadge /> }
									</>
								}
								help={ ! isPro
									? __( 'The Login Popup option is available in the Pro version. Free users will use redirect.', 'wp-edit-password-protected' )
									: __( 'Choose what happens when a logged-out visitor tries to access any page.', 'wp-edit-password-protected' )
								}
								value={ ! isPro ? 'redirect' : ( s.admin_only_action || 'redirect' ) }
								options={ [
									{ label: __( 'Redirect to Login Page', 'wp-edit-password-protected' ), value: 'redirect' },
									{ label: __( 'Show Login Popup', 'wp-edit-password-protected' ) + ( ! isPro ? ' (Pro)' : '' ), value: 'popup', disabled: ! isPro },
								] }
								onChange={ ( v ) => {
									if ( ! isPro && v === 'popup' ) return;
									update( 'admin_only_action', v );
								} }
							/>
						</PanelRow>

						{ isPro && s.admin_only_action === 'popup' && (
							<>
								<PanelRow>
									<TextControl
										label={ __( 'Popup Header', 'wp-edit-password-protected' ) }
										help={ __( 'Header text displayed on the login popup.', 'wp-edit-password-protected' ) }
										value={ s.admin_only_header || '' }
										onChange={ ( v ) => update( 'admin_only_header', v ) }
									/>
								</PanelRow>

								<PanelRow>
									<TextareaControl
										label={ __( 'Custom Message', 'wp-edit-password-protected' ) }
										help={ __( 'Message displayed to visitors when the popup is shown.', 'wp-edit-password-protected' ) }
										value={ s.admin_only_message || '' }
										onChange={ ( v ) => update( 'admin_only_message', v ) }
										rows={ 3 }
									/>
								</PanelRow>
							</>
						) }

						{ isPro && s.admin_only_action === 'popup' && (
							<Notice status="info" isDismissible={ false }>
								{ __( 'The login popup will blur the page content and overlay a login form — the same style used by the Content Lock popup feature.', 'wp-edit-password-protected' ) }
							</Notice>
						) }

						{ s.admin_only_action === 'redirect' && (
							<Notice status="info" isDismissible={ false }>
								{ __( 'Visitors will be redirected to the WordPress login page. After logging in, they will be sent back to the page they originally tried to visit.', 'wp-edit-password-protected' ) }
							</Notice>
						) }
					</>
				) }
			</PanelBody>
		</div>
	);
};

export default AdminOnly;
