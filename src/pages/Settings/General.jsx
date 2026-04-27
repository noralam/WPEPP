/**
 * Settings → General — toggle features on/off.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	PanelBody,
	ToggleControl,
	TextControl,
	Spinner,
} from '@wordpress/components';
import { useCallback } from '@wordpress/element';
import { useSaveBar } from '../../components/SaveBar';

const General = () => {
	const { settings, isLoading, isSaving } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'general' ),
			isLoading: store.isLoading(),
			isSaving: store.isSaving(),
		};
	} );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );

	const handleSave = useCallback( () => {
		saveSettings( 'general', settings );
	}, [ saveSettings, settings ] );

	const handleReset = useCallback( () => {
		resetSettings( 'general' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	if ( isLoading ) {
		return <Spinner />;
	}

	const set = ( key, value ) => updateSetting( 'general', key, value );

	return (
		<div className="wpepp-general-settings">
			<PanelBody title={ __( 'Miscellaneous', 'wp-edit-password-protected' ) } initialOpen>
				<TextControl
					label={ __( 'Password Cookie Expiration (days)', 'wp-edit-password-protected' ) }
					help={ __( 'Number of days the password cookie stays valid. Default is 10.', 'wp-edit-password-protected' ) }
					type="number"
					min={ 1 }
					max={ 365 }
					value={ settings?.cookie_expiration ?? 10 }
					onChange={ ( v ) => set( 'cookie_expiration', parseInt( v, 10 ) || 10 ) }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Delete Data on Uninstall', 'wp-edit-password-protected' ) }
					help={ __( 'Remove all plugin data when uninstalled.', 'wp-edit-password-protected' ) }
					checked={ !! settings?.delete_data_on_uninstall }
					onChange={ ( v ) => set( 'delete_data_on_uninstall', v ) }
				/>
			</PanelBody>

		</div>
	);
};

export default General;
