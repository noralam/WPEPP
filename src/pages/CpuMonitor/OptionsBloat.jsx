/**
 * CPU Monitor — Options Bloat tab.
 *
 * Shows options table stats and transient cleanup (Pro for full details).
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import ProBadge from '../../components/ProBadge';

const formatSize = ( bytes ) => {
	if ( bytes >= 1048576 ) {
		return ( bytes / 1048576 ).toFixed( 2 ) + ' MB';
	}
	if ( bytes >= 1024 ) {
		return ( bytes / 1024 ).toFixed( 1 ) + ' KB';
	}
	return bytes + ' B';
};

const OptionsBloat = () => {
	const [ stats, setStats ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ cleaning, setCleaning ] = useState( false );
	const [ cleanResult, setCleanResult ] = useState( null );

	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	const fetchStats = useCallback( () => {
		setLoading( true );
		apiFetch( { path: '/wpepp/v1/cpu/options-bloat' } )
			.then( ( data ) => setStats( data ) )
			.catch( () => setStats( null ) )
			.finally( () => setLoading( false ) );
	}, [] );

	useEffect( () => {
		fetchStats();
	}, [] );

	const cleanTransients = () => {
		setCleaning( true );
		setCleanResult( null );
		apiFetch( {
			path: '/wpepp/v1/cpu/transients/clean',
			method: 'POST',
		} )
			.then( ( data ) => {
				setCleanResult( data?.deleted ?? 0 );
				fetchStats();
			} )
			.catch( () => setCleanResult( -1 ) )
			.finally( () => setCleaning( false ) );
	};

	if ( loading ) {
		return <Spinner />;
	}

	if ( ! stats ) {
		return <p>{ __( 'Unable to load options data.', 'wp-edit-password-protected' ) }</p>;
	}

	return (
		<div className="wpepp-options-bloat">
			<div className="wpepp-options-bloat__header">
				<h3>{ __( 'Options Table Analysis', 'wp-edit-password-protected' ) }</h3>
				<Button variant="secondary" onClick={ fetchStats }>
					{ __( 'Refresh', 'wp-edit-password-protected' ) }
				</Button>
			</div>

			<div className="wpepp-cpu-cards">
				<div className="wpepp-cpu-card">
					<h4>{ __( 'Table Size', 'wp-edit-password-protected' ) }</h4>
					<div className="wpepp-cpu-card__value">
						{ formatSize( stats.table_size_bytes ?? 0 ) }
					</div>
				</div>

				<div className="wpepp-cpu-card">
					<h4>{ __( 'Autoloaded Size', 'wp-edit-password-protected' ) }</h4>
					<div className="wpepp-cpu-card__value">
						{ formatSize( stats.autoload_size_bytes ?? 0 ) }
					</div>
					<div className="wpepp-cpu-card__meta">
						{ stats.autoload_count } { __( 'options', 'wp-edit-password-protected' ) }
					</div>
				</div>

				<div className="wpepp-cpu-card">
					<h4>{ __( 'Total Options', 'wp-edit-password-protected' ) }</h4>
					<div className="wpepp-cpu-card__value">
						{ stats.total_options }
					</div>
				</div>

				<div className="wpepp-cpu-card">
					<h4>{ __( 'Expired Transients', 'wp-edit-password-protected' ) }</h4>
					<div className="wpepp-cpu-card__value">
						{ stats.expired_transients }
					</div>
					{ isPro ? (
						<Button
							variant="secondary"
							isSmall
							disabled={ cleaning || stats.expired_transients === 0 }
							isBusy={ cleaning }
							onClick={ cleanTransients }
						>
							{ __( 'Clean Now', 'wp-edit-password-protected' ) }
						</Button>
					) : (
						<ProBadge />
					) }
				</div>
			</div>

			{ cleanResult !== null && (
				<Notice
					status={ cleanResult >= 0 ? 'success' : 'error' }
					isDismissible
					onDismiss={ () => setCleanResult( null ) }
				>
					{ cleanResult >= 0
						? /* translators: %d: number of deleted transients */
						  sprintf( __( 'Cleaned %d expired transient(s).', 'wp-edit-password-protected' ), cleanResult )
						: __( 'Failed to clean transients.', 'wp-edit-password-protected' )
					}
				</Notice>
			) }

			{ isPro && stats.top_autoloaded && stats.top_autoloaded.length > 0 && (
				<div className="wpepp-options-bloat__top">
					<h4>{ __( 'Top Autoloaded Options by Size', 'wp-edit-password-protected' ) }</h4>
					<table className="wpepp-table widefat striped">
						<thead>
							<tr>
								<th>{ __( 'Option Name', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Size', 'wp-edit-password-protected' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ stats.top_autoloaded.map( ( opt ) => (
							<tr key={ opt.name }>
								<td><code>{ opt.name }</code></td>
									<td>{ formatSize( opt.size ) }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }

			{ ! isPro && (
				<Notice status="info" isDismissible={ false }>
					{ __( 'Upgrade to Pro to see top autoloaded options and clean transients.', 'wp-edit-password-protected' ) }
					<ProBadge />
				</Notice>
			) }
		</div>
	);
};

export default OptionsBloat;
