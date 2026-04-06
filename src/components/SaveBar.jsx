/**
 * SaveBar — sticky bottom bar with Save/Reset, shown when settings have unsaved changes.
 * Uses a context so each page can register its own save/reset handlers.
 */
import { createContext, useContext, useState, useCallback, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

const SaveBarContext = createContext( {} );

export const SaveBarProvider = ( { children } ) => {
	const [ handlers, setHandlers ] = useState( null );

	const register = useCallback( ( onSave, onReset ) => {
		setHandlers( { onSave, onReset } );
	}, [] );

	const unregister = useCallback( () => {
		setHandlers( null );
	}, [] );

	return (
		<SaveBarContext.Provider value={ { register, unregister, handlers } }>
			{ children }
		</SaveBarContext.Provider>
	);
};

export const useSaveBar = ( onSave, onReset ) => {
	const { register, unregister } = useContext( SaveBarContext );

	useEffect( () => {
		register( onSave, onReset );
		return () => unregister();
	}, [ onSave, onReset, register, unregister ] );
};

const SaveBar = () => {
	const { handlers } = useContext( SaveBarContext );

	const { hasChanges, isSaving } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			hasChanges: store.hasChanges(),
			isSaving: store.isSaving(),
		};
	} );

	if ( ! hasChanges || ! handlers ) {
		return null;
	}

	return (
		<div className="wpepp-save-bar">
			<div className="wpepp-save-bar__inner">
				<span className="wpepp-save-bar__message">
					{ __( 'You have unsaved changes', 'wp-edit-password-protected' ) }
				</span>
				<div className="wpepp-save-bar__actions">
					<Button
						variant="secondary"
						onClick={ handlers.onReset }
						disabled={ isSaving }
					>
						{ __( 'Reset', 'wp-edit-password-protected' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ handlers.onSave }
						isBusy={ isSaving }
						disabled={ isSaving }
					>
						{ isSaving
							? __( 'Saving…', 'wp-edit-password-protected' )
							: __( 'Save Now', 'wp-edit-password-protected' )
						}
					</Button>
				</div>
			</div>
		</div>
	);
};

export default SaveBar;
