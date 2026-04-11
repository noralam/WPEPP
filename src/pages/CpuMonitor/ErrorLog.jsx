/**
 * CPU Monitor — Error Log tab (Pro).
 *
 * Displays parsed PHP error log entries.
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner, ToggleControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const ErrorLog = () => {
	const [ entries, setEntries ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ debugLog, setDebugLog ] = useState( false );
	const [ wpDebug, setWpDebug ] = useState( false );
	const [ configWritable, setConfigWritable ] = useState( true );
	const [ toggling, setToggling ] = useState( null );

	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	const fetchLog = useCallback( () => {
		if ( ! isPro ) {
			setLoading( false );
			return;
		}
		setLoading( true );
		setError( null );
		apiFetch( { path: '/wpepp/v1/cpu/error-log' } )
			.then( ( data ) => setEntries( Array.isArray( data ) ? data : [] ) )
			.catch( ( err ) => {
				setEntries( [] );
				setError( err?.message || __( 'Failed to load error log.', 'wp-edit-password-protected' ) );
			} )
			.finally( () => setLoading( false ) );
	}, [ isPro ] );

	const fetchConfigStatus = useCallback( () => {
		apiFetch( { path: '/wpepp/v1/cpu/wp-config/status' } )
			.then( ( data ) => {
				setWpDebug( !! data?.WP_DEBUG );
				setDebugLog( !! data?.WP_DEBUG_LOG );
				setConfigWritable( !! data?.writable );
			} )
			.catch( () => {} );
	}, [] );

	useEffect( () => {
		fetchLog();
		fetchConfigStatus();
	}, [] );

	const handleToggle = ( constant, value ) => {
		setToggling( constant );
		apiFetch( {
			path: '/wpepp/v1/cpu/wp-config/toggle',
			method: 'POST',
			data: { constant, value },
		} )
			.then( ( res ) => {
				if ( res?.status ) {
					setWpDebug( !! res.status.WP_DEBUG );
					setDebugLog( !! res.status.WP_DEBUG_LOG );
				}
			} )
			.catch( () => {} )
			.finally( () => setToggling( null ) );
	};

	if ( loading ) {
		return <Spinner />;
	}

	return (
		<div className="wpepp-error-log">
			<div className="wpepp-error-log__header">
				<h3>{ __( 'PHP Error Log', 'wp-edit-password-protected' ) }</h3>
				<Button variant="secondary" onClick={ fetchLog }>
					{ __( 'Refresh', 'wp-edit-password-protected' ) }
				</Button>
			</div>

			<div className="wpepp-config-toggle">
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Enable Debug Mode (WP_DEBUG)', 'wp-edit-password-protected' ) }
					help={ __( 'Enables WordPress debug mode to capture PHP errors.', 'wp-edit-password-protected' ) }
					checked={ wpDebug }
					onChange={ ( val ) => handleToggle( 'WP_DEBUG', val ) }
					disabled={ toggling !== null || ! configWritable }
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Log Errors to File (WP_DEBUG_LOG)', 'wp-edit-password-protected' ) }
					help={
						! configWritable
							? __( 'wp-config.php is not writable. Please check file permissions.', 'wp-edit-password-protected' )
							: __( 'Writes PHP errors to a debug.log file so they can be viewed here.', 'wp-edit-password-protected' )
					}
					checked={ debugLog }
					onChange={ ( val ) => handleToggle( 'WP_DEBUG_LOG', val ) }
					disabled={ toggling !== null || ! configWritable }
				/>
			</div>

			{ ! wpDebug && (
				<p className="wpepp-empty-state">
					{ __( 'Enable Debug Mode first to capture and view PHP errors.', 'wp-edit-password-protected' ) }
				</p>
			) }

			{ wpDebug && error && (
				<p className="wpepp-empty-state">{ error }</p>
			) }

			{ wpDebug && ! error && entries.length === 0 && (
				<p className="wpepp-empty-state">
					{ __( 'No error log entries found. Your site is running clean!', 'wp-edit-password-protected' ) }
				</p>
			) }

			{ wpDebug && entries.length > 0 && (
				<table className="wpepp-table widefat striped">
					<thead>
						<tr>
							<th>{ __( 'Type', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Message', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'File', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Count', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Last Seen', 'wp-edit-password-protected' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ entries.map( ( entry, idx ) => (
							<tr key={ idx }>
								<td>
									<span className={ `wpepp-status wpepp-status--${ entry.type === 'fatal' ? 'failed' : 'lockout' }` }>
										{ entry.type }
									</span>
								</td>
								<td className="wpepp-error-log__message">{ entry.message }</td>
								<td className="wpepp-error-log__file">
									<code>{ entry.file }</code>
								</td>
								<td>{ entry.count }</td>
								<td>{ entry.last_seen }</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }
		</div>
	);
};

export default ErrorLog;
