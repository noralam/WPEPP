/**
 * Settings backup — export/import configuration as JSON.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useRef, useState, useCallback } from '@wordpress/element';
import {  Button, Notice } from '@wordpress/components';
import Card from '../../components/Card';
import apiFetch from '@wordpress/api-fetch';

const Backup = () => {
	const fileRef = useRef( null );
	const [ busy, setBusy ] = useState( '' );
	const [ msg, setMsg ] = useState( null );

	const exportBackup = useCallback( async () => {
		setBusy( 'export' );
		setMsg( null );

		try {
			const sections = await apiFetch( { path: '/wpepp/v1/settings' } );

			const payload = {
				schema: 'wpepp/1',
				exported: new Date().toISOString(),
				sections,
			};

			const blob = new Blob( [ JSON.stringify( payload, null, 2 ) ], { type: 'application/json' } );
			const url  = URL.createObjectURL( blob );
			const a    = document.createElement( 'a' );
			a.href     = url;
			const now = new Date();
			const datePart = now.toISOString().slice( 0, 10 );
			const timePart = now.toTimeString().slice( 0, 8 ).replace( /:/g, '' );
			a.download = `wpepp-settings-backup-${ datePart }-${ timePart }.json`;
			document.body.appendChild( a );
			a.click();
			document.body.removeChild( a );
			window.setTimeout( () => URL.revokeObjectURL( url ), 1000 );

			setMsg( { type: 'success', text: __( 'Backup downloaded.', 'wp-edit-password-protected' ) } );
		} catch ( err ) {
			setMsg( { type: 'error', text: err.message || __( 'Export failed.', 'wp-edit-password-protected' ) } );
		}

		setBusy( '' );
	}, [] );

	const importBackup = useCallback( async ( file ) => {
		if ( ! file ) {
			return;
		}

		setBusy( 'import' );
		setMsg( null );

		try {
			const text   = await file.text();
			JSON.parse( text ); // Client-side sanity before hitting the API.

			const res = await apiFetch( {
				path: '/wpepp/v1/settings/import',
				method: 'POST',
				data: { payload: text },
			} );

			setMsg( {
				type: 'success',
				text: sprintf(
					/* translators: %s: comma-separated section list */
					__( 'Imported: %s', 'wp-edit-password-protected' ),
					res.imported.join( ', ' )
				),
			} );
		} catch ( err ) {
			setMsg( { type: 'error', text: err.message || __( 'Import failed.', 'wp-edit-password-protected' ) } );
		}

		setBusy( '' );
	}, [] );

	return (
		<Card title={ __( 'Backup & Restore', 'wp-edit-password-protected' ) }>
			<p style={ { color: '#8b93a7', marginTop: 0 } }>
				{ __(
					'Exports all WPEPP settings as JSON. Secrets (API keys) are never included — they stay safely in your database.',
					'wp-edit-password-protected'
				) }
			</p>

			<div style={ { display: 'flex', gap: 8, flexWrap: 'wrap' } }>
				<Button variant="secondary" onClick={ exportBackup } isBusy={ busy === 'export' }>
					⬇ { __( 'Export settings', 'wp-edit-password-protected' ) }
				</Button>

				<Button
					variant="secondary"
					isBusy={ busy === 'import' }
					onClick={ () => fileRef.current?.click() }
				>
					⬆ { __( 'Import settings', 'wp-edit-password-protected' ) }
				</Button>

				<input
					ref={ fileRef }
					type="file"
					accept="application/json,.json"
					style={ { display: 'none' } }
					onChange={ ( e ) => {
						importBackup( e.target.files?.[ 0 ] );
						e.target.value = '';
					} }
				/>
			</div>

			{ msg && (
				<Notice status={ msg.type } isDismissible={ false } style={ { marginTop: 10 } }>
					{ msg.text }
				</Notice>
			) }
		</Card>
	);
};

export default Backup;
