/**
 * Settings → Custom CSS — global CSS editor (Pro only).
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button, Spinner } from '@wordpress/components';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { useSaveBar } from '../../components/SaveBar';

const CustomCss = () => {
	const { css, generalSettings, isLoading, isSaving } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			css: store.getSetting( 'general', 'custom_css' ) || '',
			generalSettings: store.getSectionSettings( 'general' ),
			isLoading: store.isLoading(),
			isSaving: store.isSaving(),
		};
	} );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );
	const [ localCss, setLocalCss ] = useState( css );

	useEffect( () => {
		setLocalCss( css );
	}, [ css ] );

	const handleSave = useCallback( () => {
		saveSettings( 'general', { ...generalSettings, custom_css: localCss } );
	}, [ saveSettings, generalSettings, localCss ] );

	const handleReset = useCallback( () => {
		resetSettings( 'general' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	if ( isLoading ) {
		return <Spinner />;
	}

	const handleCssChange = ( e ) => {
		const val = e.target.value;
		setLocalCss( val );
		updateSetting( 'general', 'custom_css', val );
	};

	return (
		<div className="wpepp-custom-css">
			<p className="description">
				{ __( 'Add custom CSS that applies to all plugin front-end output (password forms, login page, member template).', 'wp-edit-password-protected' ) }
			</p>

			<textarea
				className="wpepp-css-editor"
				rows={ 20 }
				value={ localCss }
				onChange={ handleCssChange }
				placeholder={ __( '/* Your custom CSS here */', 'wp-edit-password-protected' ) }
				spellCheck={ false }
			/>

		</div>
	);
};

export default CustomCss;
