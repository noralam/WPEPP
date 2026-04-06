/**
 * Custom Login URL settings (Pro).
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { PanelBody, PanelRow, TextControl, Spinner } from '@wordpress/components';
import { useMemo, useCallback } from '@wordpress/element';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';

const CustomLoginUrl = () => {
	const { settings, isLoading, isSaving } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'security' ),
			isLoading: store.isLoading(),
			isSaving: store.isSaving(),
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

	const siteUrl = window.wpeppData?.adminUrl?.replace( /\/wp-admin\/$/, '' ) || '';

	return (
		<div className="wpepp-custom-login-url">
			<h3>{ __( 'Custom Login URL', 'wp-edit-password-protected' ) }</h3>
			<p>{ __( 'Replace the default wp-login.php URL with a custom slug.', 'wp-edit-password-protected' ) }</p>

			<PanelBody initialOpen>
				<PanelRow>
					<TextControl
						label={ __( 'Custom Login Slug', 'wp-edit-password-protected' ) }
						help={ siteUrl ? `${ siteUrl }/${ s.custom_login_url || 'my-login' }` : '' }
						value={ s.custom_login_url || '' }
						onChange={ ( v ) => update( 'custom_login_url', v.replace( /[^a-z0-9-]/gi, '' ).toLowerCase() ) }
						placeholder="my-login"
					/>
				</PanelRow>
			</PanelBody>

		</div>
	);
};

export default CustomLoginUrl;
