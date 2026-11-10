/**
 * Site Access Gate — restrict frontend by login status or admin role.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	SelectControl,
	TextControl,
	TextareaControl,
	Notice,
} from '@wordpress/components';
import Card from '../../components/Card';
import ToggleCard from '../../components/ToggleCard';
import { useMemo, useCallback } from '@wordpress/element';
import { useSaveBar } from '../../components/SaveBar';
import { SITE_ACCESS_DEFAULTS } from '../../utils/defaults';
import ProBadge from '../../components/ProBadge';
import Loading from '../../components/Loading';

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
		return <Loading variant="form" count={ 3 } />;
	}

	return (
		<div className="wpepp-admin-only-settings">
			<h3>{ __( 'Site Access Gate', 'wp-edit-password-protected' ) }</h3>

			<Card title={ __( 'Site Access Gate', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={ __( 'Enable Site Access Gate', 'wp-edit-password-protected' ) }
						description={ __( 'When enabled, only users matching the Access Permission Scope below can view the frontend. Others are redirected or blocked.', 'wp-edit-password-protected' ) }
						checked={ !! s.admin_only_enabled }
						onChange={ ( v ) => update( 'admin_only_enabled', v ) }
					/>
				</div>

				{ !! s.admin_only_enabled && (
					<div className="wpepp-form-grid" style={ { marginTop: 16 } }>
						<div className="wpepp-form-grid__full">
							<Notice status="info" isDismissible={ false }>
								{ __( 'Restrict front-end access based on user role and authentication status.', 'wp-edit-password-protected' ) }
							</Notice>
						</div>

						<div className="wpepp-form-grid__full">
							<SelectControl
								__next40pxDefaultSize
								label={ __( 'Access Permission Scope', 'wp-edit-password-protected' ) }
								help={ __( 'Choose whether any authenticated logged-in user can view the frontend, or only administrators.', 'wp-edit-password-protected' ) }
								value={ s.admin_only_target || 'logged_in' }
								options={ [
									{ label: __( 'All Logged-in Users (Members Only)', 'wp-edit-password-protected' ), value: 'logged_in' },
									{ label: __( 'Administrators Only (Requires manage_options)', 'wp-edit-password-protected' ), value: 'admins_only' },
								] }
								onChange={ ( v ) => update( 'admin_only_target', v ) }
							/>
						</div>

						<div className="wpepp-form-grid__full">
							<SelectControl
								__next40pxDefaultSize
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
						</div>

						{ isPro && s.admin_only_action === 'popup' && (
							<>
								<div className="wpepp-form-grid__full">
									<TextControl
										label={ __( 'Popup Header', 'wp-edit-password-protected' ) }
										help={ __( 'Header text displayed on the login popup.', 'wp-edit-password-protected' ) }
										value={ s.admin_only_header || '' }
										onChange={ ( v ) => update( 'admin_only_header', v ) }
									/>
								</div>

								<div className="wpepp-form-grid__full">
									<TextareaControl
										label={ __( 'Custom Message', 'wp-edit-password-protected' ) }
										help={ __( 'Message displayed to visitors when the popup is shown.', 'wp-edit-password-protected' ) }
										value={ s.admin_only_message || '' }
										onChange={ ( v ) => update( 'admin_only_message', v ) }
										rows={ 3 }
									/>
								</div>

								<div className="wpepp-form-grid__full">
									<Notice status="info" isDismissible={ false }>
										{ __( 'The login popup will blur the page content and overlay a login form — the same style used by the Content Lock popup feature.', 'wp-edit-password-protected' ) }
									</Notice>
								</div>
							</>
						) }

						{ s.admin_only_action === 'redirect' && (
							<div className="wpepp-form-grid__full">
								<Notice status="info" isDismissible={ false }>
									{ s.admin_only_target === 'admins_only'
										? __( 'Logged-out visitors go to the login page. Logged-in non-administrators see an "Access Restricted" page instead of a redirect loop.', 'wp-edit-password-protected' )
										: __( 'Visitors will be redirected to the WordPress login page. After logging in, they will be sent back to the page they originally tried to visit.', 'wp-edit-password-protected' ) }
								</Notice>
							</div>
						) }
					</div>
				) }
			</Card>
		</div>
	);
};

export default AdminOnly;
