/**
 * CPU Monitor — Plugin Performance tab (Pro).
 *
 * Shows active plugin stats and allows deactivation.
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const PluginPerformance = () => {
	const [ plugins, setPlugins ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ actionLoading, setActionLoading ] = useState( null );
	const [ notice, setNotice ] = useState( null );
	const [ error, setError ] = useState( null );

	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	const fetchPlugins = useCallback( () => {
		setLoading( true );
		setError( null );
		apiFetch( { path: '/wpepp/v1/cpu/plugin-stats' } )
			.then( ( data ) => setPlugins( Array.isArray( data ) ? data : [] ) )
			.catch( ( err ) => {
				setPlugins( [] );
				setError( err?.message || __( 'Failed to load plugin stats.', 'wp-edit-password-protected' ) );
			} )
			.finally( () => setLoading( false ) );
	}, [] );

	useEffect( () => {
		fetchPlugins();
	}, [] );

	const deactivate = ( pluginFile ) => {
		setActionLoading( pluginFile );
		setNotice( null );
		apiFetch( {
			path: '/wpepp/v1/cpu/plugin-deactivate',
			method: 'POST',
			data: { plugin: pluginFile },
		} )
			.then( () => {
				setNotice( { status: 'success', message: __( 'Plugin deactivated.', 'wp-edit-password-protected' ) } );
				// Remove from local list without a full reload/spinner.
				setPlugins( ( prev ) => prev.filter( ( p ) => p.file !== pluginFile ) );
			} )
			.catch( ( err ) => {
				setNotice( { status: 'error', message: err?.message || __( 'Failed to deactivate plugin.', 'wp-edit-password-protected' ) } );
			} )
			.finally( () => setActionLoading( null ) );
	};

	if ( loading ) {
		return <Spinner />;
	}

	return (
		<div className="wpepp-plugin-performance">
			<div className="wpepp-plugin-performance__header">
				<h3>{ __( 'Active Plugins', 'wp-edit-password-protected' ) }</h3>
				<Button variant="secondary" onClick={ fetchPlugins }>
					{ __( 'Refresh', 'wp-edit-password-protected' ) }
				</Button>
			</div>

			{ notice && (
				<Notice status={ notice.status } isDismissible onDismiss={ () => setNotice( null ) }>
					{ notice.message }
				</Notice>
			) }

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			{ ! error && plugins.length === 0 ? (
				<p className="wpepp-empty-state">
					{ __( 'No active plugins found.', 'wp-edit-password-protected' ) }
				</p>
			) : (
				<table className="wpepp-table widefat striped">
					<thead>
						<tr>
							<th>{ __( 'Plugin', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Version', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Author', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Actions', 'wp-edit-password-protected' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ plugins.map( ( plugin ) => (
							<tr key={ plugin.file }>
								<td>
									<strong>{ plugin.name }</strong>
								</td>
								<td>{ plugin.version }</td>
								<td>{ plugin.author }</td>
								<td>
									{ plugin.is_self ? (
										<em>{ __( 'This plugin', 'wp-edit-password-protected' ) }</em>
								) : isPro ? (
									<Button
										variant="secondary"
										isSmall
										isDestructive
										disabled={ !! actionLoading }
										isBusy={ actionLoading === plugin.file }
										onClick={ () => deactivate( plugin.file ) }
									>
										{ actionLoading === plugin.file ? __( 'Deactivating…', 'wp-edit-password-protected' ) : __( 'Deactivate', 'wp-edit-password-protected' ) }
									</Button>
								) : (
									<span className="wpepp-pro-action-hint">{ __( 'Pro only', 'wp-edit-password-protected' ) }</span>
									) }
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }
		</div>
	);
};

export default PluginPerformance;
