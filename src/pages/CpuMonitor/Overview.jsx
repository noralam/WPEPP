/**
 * CPU Monitor — Overview tab.
 *
 * Displays CPU/memory stats, health score, and summary cards.
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const formatBytes = ( bytes ) => {
	if ( bytes >= 1073741824 ) {
		return ( bytes / 1073741824 ).toFixed( 1 ) + ' GB';
	}
	return ( bytes / 1048576 ).toFixed( 1 ) + ' MB';
};

const HealthBadge = ( { status } ) => {
	const labels = {
		green: __( 'Healthy', 'wp-edit-password-protected' ),
		yellow: __( 'Warning', 'wp-edit-password-protected' ),
		red: __( 'Critical', 'wp-edit-password-protected' ),
	};

	return (
		<span className={ `wpepp-health-badge wpepp-health-badge--${ status }` }>
			{ labels[ status ] || status }
		</span>
	);
};

const Overview = () => {
	const [ stats, setStats ] = useState( null );
	const [ loading, setLoading ] = useState( true );

	const fetchStats = () => {
		setLoading( true );
		apiFetch( { path: '/wpepp/v1/cpu/stats' } )
			.then( ( data ) => setStats( data ) )
			.catch( () => setStats( null ) )
			.finally( () => setLoading( false ) );
	};

	useEffect( () => {
		fetchStats();
	}, [] );

	if ( loading ) {
		return <Spinner />;
	}

	if ( ! stats ) {
		return <p>{ __( 'Unable to load system stats.', 'wp-edit-password-protected' ) }</p>;
	}

	return (
		<div className="wpepp-cpu-overview">
			<div className="wpepp-cpu-overview__header">
				<h3>{ __( 'System Overview', 'wp-edit-password-protected' ) }</h3>
				<Button variant="secondary" onClick={ fetchStats }>
					{ __( 'Refresh', 'wp-edit-password-protected' ) }
				</Button>
			</div>

			<div className="wpepp-cpu-overview__health">
				<HealthBadge status={ stats.health } />
			</div>

			<div className="wpepp-cpu-cards">
				<div className="wpepp-cpu-card">
					<h4>{ __( 'CPU Usage', 'wp-edit-password-protected' ) }</h4>
					<div className="wpepp-cpu-card__value">
						{ stats.cpu_percent >= 0 ? `${ stats.cpu_percent }%` : __( 'N/A', 'wp-edit-password-protected' ) }
					</div>
					<div className="wpepp-cpu-card__meta">
						{ stats.cpu_cores } { __( 'cores', 'wp-edit-password-protected' ) }
					</div>
				</div>

				<div className="wpepp-cpu-card">
					<h4>{ __( 'Memory', 'wp-edit-password-protected' ) }</h4>
					<div className="wpepp-cpu-card__value">
						{ stats.memory?.usage_percent > 0 ? `${ stats.memory.usage_percent }%` : __( 'N/A', 'wp-edit-password-protected' ) }
					</div>
					<div className="wpepp-cpu-card__meta">
						{ stats.memory?.limit_mb === -1
							? `${ stats.memory?.current_mb } MB / ${ __( 'Unlimited', 'wp-edit-password-protected' ) }`
							: `${ stats.memory?.current_mb } / ${ stats.memory?.limit_mb } MB` }
					</div>
				</div>

				<div className="wpepp-cpu-card">
					<h4>{ __( 'Load Average', 'wp-edit-password-protected' ) }</h4>
					<div className="wpepp-cpu-card__value">
						{ stats.load?.load_1 ?? __( 'N/A', 'wp-edit-password-protected' ) }
					</div>
					<div className="wpepp-cpu-card__meta">
						{ stats.load?.load_5 !== undefined && (
							<>1m / 5m / 15m: { stats.load.load_1 } / { stats.load.load_5 } / { stats.load.load_15 }</>
						) }
					</div>
				</div>

				<div className="wpepp-cpu-card">
					<h4>{ __( 'Server', 'wp-edit-password-protected' ) }</h4>
					<div className="wpepp-cpu-card__value wpepp-cpu-card__value--small">
						PHP { stats.php_version }
					</div>
					<div className="wpepp-cpu-card__meta">
						{ stats.server }
					</div>
				</div>
			</div>

			<div className="wpepp-cpu-summary">
				<h4>{ __( 'Quick Summary', 'wp-edit-password-protected' ) }</h4>
				<table className="wpepp-table widefat striped">
					<tbody>
						<tr>
							<td>{ __( 'Slow Queries (last 7 days)', 'wp-edit-password-protected' ) }</td>
							<td>{ stats.slow_queries_count }</td>
						</tr>
						<tr>
							<td>{ __( 'Overdue Cron Jobs', 'wp-edit-password-protected' ) }</td>
							<td>
								{ stats.overdue_cron_count > 0 ? (
									<span className="wpepp-status wpepp-status--failed">{ stats.overdue_cron_count }</span>
								) : (
									<span className="wpepp-status wpepp-status--success">0</span>
								) }
							</td>
						</tr>
						<tr>
							<td>{ __( 'Expired Transients', 'wp-edit-password-protected' ) }</td>
							<td>{ stats.expired_transients }</td>
						</tr>
						<tr>
							<td>{ __( 'WP Memory Limit', 'wp-edit-password-protected' ) }</td>
							<td>{ stats.memory?.wp_limit_mb === -1 ? __( 'Unlimited', 'wp-edit-password-protected' ) : `${ stats.memory?.wp_limit_mb } MB` }</td>
						</tr>
						<tr>
							<td>{ __( 'Alternate Cron', 'wp-edit-password-protected' ) }</td>
							<td>{ stats.cron_alternate ? __( 'Yes', 'wp-edit-password-protected' ) : __( 'No', 'wp-edit-password-protected' ) }</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	);
};

export default Overview;
