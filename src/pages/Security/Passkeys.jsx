/**
 * Passkeys management for the current user (Pro).
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, TextControl, Notice, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

function b64urlToBuffer( value ) {
	value = String( value ).replace( /-/g, '+' ).replace( /_/g, '/' );
	while ( value.length % 4 ) {
		value += '=';
	}
	const bin = atob( value );
	const bytes = new Uint8Array( bin.length );
	for ( let i = 0; i < bin.length; i++ ) {
		bytes[ i ] = bin.charCodeAt( i );
	}
	return bytes.buffer;
}

function bufferToB64url( buffer ) {
	const bytes = new Uint8Array( buffer );
	let bin = '';
	for ( let i = 0; i < bytes.byteLength; i++ ) {
		bin += String.fromCharCode( bytes[ i ] );
	}
	return btoa( bin ).replace( /\+/g, '-' ).replace( /\//g, '_' ).replace( /=+$/, '' );
}

const Passkeys = () => {
	const [ list, setList ] = useState( null );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ name, setName ] = useState( '' );

	const load = useCallback( () => {
		apiFetch( { path: '/wpepp/v1/passkeys' } )
			.then( setList )
			.catch( ( err ) => setError( err.message || __( 'Could not load passkeys.', 'wp-edit-password-protected' ) ) );
	}, [] );

	useEffect( load, [ load ] );

	const register = useCallback( async () => {
		setBusy( true );
		setError( '' );

		try {
			// eslint-disable-next-line no-alert
			const label = name.trim() || window.prompt( __( 'Name this passkey:', 'wp-edit-password-protected' ), navigator.platform || 'Passkey' ) || 'Passkey';

			const createRes = await apiFetch( {
				path: '/wpepp/v1/passkeys/register/start',
				method: 'POST',
			} );

			const options = { ...createRes.publicKey ?? createRes };
			options.challenge = b64urlToBuffer( options.challenge );

			if ( options.excludeCredentials ) {
				options.excludeCredentials = options.excludeCredentials.map( ( c ) => ( { ...c, id: b64urlToBuffer( c.id ) } ) );
			}

			if ( options.user?.id ) {
				options.user = { ...options.user, id: b64urlToBuffer( options.user.id ) };
			}

			const credential = await navigator.credentials.create( { publicKey: options } );

			await apiFetch( {
				path: '/wpepp/v1/passkeys/register/finish',
				method: 'POST',
				data: {
					name: label,
					clientDataJSON: bufferToB64url( credential.response.clientDataJSON ),
					attestationObject: bufferToB64url( credential.response.attestationObject ),
				},
			} );

			load();
		} catch ( err ) {
			setError(
				err.name === 'NotAllowedError'
					? __( 'Passkey creation was cancelled or timed out.', 'wp-edit-password-protected' )
					: err.message || __( 'Passkey registration failed.', 'wp-edit-password-protected' )
			);
		}

		setBusy( false );
	}, [ name, load ] );

	const removeKey = useCallback( async ( id ) => {
		try {
			await apiFetch( { path: `/wpepp/v1/passkeys/${ encodeURIComponent( id ) }`, method: 'DELETE' } );
			load();
		} catch {
			// Ignore.
		}
	}, [ load ] );

	return (
		<div className="wpepp-passkeys">
			<h3>{ __( 'Passkeys', 'wp-edit-password-protected' ) }</h3>
			<p>
				{ __(
					'Sign in with your fingerprint, face, or device PIN — no password needed. Passkeys stay on this device.',
					'wp-edit-password-protected'
				) }
			</p>

			{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }

			<div style={ { display: 'flex', gap: 8, alignItems: 'flex-end', marginBottom: 16 } }>
				<TextControl
					label={ __( 'Label (optional)', 'wp-edit-password-protected' ) }
					value={ name }
					onChange={ setName }
					placeholder={ __( 'MacBook Touch ID', 'wp-edit-password-protected' ) }
				/>
				<Button variant="primary" onClick={ register } isBusy={ busy }>
					🔑 { __( 'Add passkey', 'wp-edit-password-protected' ) }
				</Button>
			</div>

			{ list === null ? (
				<Spinner />
			) : list.length === 0 ? (
				<p>{ __( 'No passkeys registered yet.', 'wp-edit-password-protected' ) }</p>
			) : (
				<table className="wpepp-blocks__table widefat">
					<thead>
						<tr>
							<th>{ __( 'Name', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Added', 'wp-edit-password-protected' ) }</th>
							<th aria-label="" />
						</tr>
					</thead>
					<tbody>
						{ list.map( ( key ) => (
							<tr key={ key.id }>
								<td>{ key.name }</td>
								<td style={ { fontVariantNumeric: 'tabular-nums' } }>
									{ key.created ? new Date( key.created * 1000 ).toLocaleDateString() : '—' }
								</td>
								<td>
									<Button variant="tertiary" isDestructive onClick={ () => removeKey( key.id ) }>
										{ __( 'Remove', 'wp-edit-password-protected' ) }
									</Button>
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }

			<Notice status="info" isDismissible={ false } style={ { marginTop: 16 } }>
				{ __(
					'A "Sign in with a passkey" button now appears on the login page. Users without a passkey keep using passwords normally.',
					'wp-edit-password-protected'
				) }
			</Notice>
		</div>
	);
};

export default Passkeys;
