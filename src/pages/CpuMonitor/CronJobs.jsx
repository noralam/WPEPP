/**
 * CPU Monitor — Cron Jobs tab.
 *
 * Lists all WP cron events. Run/Delete actions are Pro-only.
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import ProBadge from '../../components/ProBadge';

const CronJobs = () => {
	const [ jobs, setJobs ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ actionLoading, setActionLoading ] = useState( null );
	const [ notice, setNotice ] = useState( null );

	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	const fetchJobs = useCallback( () => {
		setLoading( true );
		apiFetch( { path: '/wpepp/v1/cpu/cron-jobs' } )
			.then( ( data ) => setJobs( Array.isArray( data ) ? data : [] ) )
			.catch( () => setJobs( [] ) )
			.finally( () => setLoading( false ) );
	}, [] );

	useEffect( () => {
		fetchJobs();
	}, [] );

	const runJob = ( job ) => {
		setActionLoading( `run-${ job.hook }-${ job.timestamp }` );
		setNotice( null );
		apiFetch( {
			path: '/wpepp/v1/cpu/cron-jobs/run',
			method: 'POST',
			data: { hook: job.hook, sig: job.sig, timestamp: job.timestamp },
		} )
			.then( () => {
				setNotice( { status: 'success', message: __( 'Cron event executed.', 'wp-edit-password-protected' ) } );
				fetchJobs();
			} )
			.catch( ( err ) => {
				setNotice( { status: 'error', message: err?.message || __( 'Failed to run cron event.', 'wp-edit-password-protected' ) } );
			} )
			.finally( () => setActionLoading( null ) );
	};

	const deleteJob = ( job ) => {
		setActionLoading( `del-${ job.hook }-${ job.timestamp }` );
		setNotice( null );
		apiFetch( {
			path: '/wpepp/v1/cpu/cron-jobs/delete',
			method: 'POST',
			data: { hook: job.hook, sig: job.sig, timestamp: job.timestamp },
		} )
			.then( () => {
				setNotice( { status: 'success', message: __( 'Cron event deleted.', 'wp-edit-password-protected' ) } );
				// Remove from local list without full spinner.
				setJobs( ( prev ) => prev.filter( ( j ) => ! ( j.hook === job.hook && j.timestamp === job.timestamp && j.sig === job.sig ) ) );
			} )
			.catch( ( err ) => {
				setNotice( { status: 'error', message: err?.message || __( 'Failed to delete cron event.', 'wp-edit-password-protected' ) } );
				fetchJobs();
			} )
			.finally( () => setActionLoading( null ) );
	};

	const formatDate = ( ts ) => {
		const d = new Date( ts * 1000 );
		return d.toLocaleString();
	};

	if ( loading ) {
		return <Spinner />;
	}

	const now = Math.floor( Date.now() / 1000 );

	return (
		<div className="wpepp-cron-jobs">
			<div className="wpepp-cron-jobs__header">
				<h3>{ __( 'WP-Cron Events', 'wp-edit-password-protected' ) }</h3>
				<Button variant="secondary" onClick={ fetchJobs }>
					{ __( 'Refresh', 'wp-edit-password-protected' ) }
				</Button>
			</div>

			{ notice && (
				<Notice status={ notice.status } isDismissible onDismiss={ () => setNotice( null ) }>
					{ notice.message }
				</Notice>
			) }

			{ ! isPro && (
				<Notice status="info" isDismissible={ false }>
					{ __( 'Upgrade to Pro to run or delete cron events.', 'wp-edit-password-protected' ) }
					<ProBadge />
				</Notice>
			) }

			{ jobs.length === 0 ? (
				<p className="wpepp-empty-state">
					{ __( 'No cron events found.', 'wp-edit-password-protected' ) }
				</p>
			) : (
				<table className="wpepp-table widefat striped">
					<thead>
						<tr>
							<th>{ __( 'Hook', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Schedule', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Next Run', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Status', 'wp-edit-password-protected' ) }</th>
							{ isPro && <th>{ __( 'Actions', 'wp-edit-password-protected' ) }</th> }
						</tr>
					</thead>
					<tbody>
						{ jobs.map( ( job, idx ) => {
							const overdue = job.timestamp < now;
							const actionKey = `${ job.hook }-${ job.timestamp }`;
							return (
								<tr key={ `${ job.hook }-${ job.timestamp }-${ idx }` }>
									<td><code>{ job.hook }</code></td>
									<td>{ job.schedule || __( 'One-time', 'wp-edit-password-protected' ) }</td>
									<td>{ formatDate( job.timestamp ) }</td>
									<td>
										{ overdue ? (
											<span className="wpepp-status wpepp-status--failed">
												{ __( 'Overdue', 'wp-edit-password-protected' ) }
											</span>
										) : (
											<span className="wpepp-status wpepp-status--success">
												{ __( 'Scheduled', 'wp-edit-password-protected' ) }
											</span>
										) }
									</td>
									{ isPro && (
										<td className="wpepp-cron-jobs__actions">
											<Button
												variant="secondary"
												isSmall
												disabled={ !! actionLoading }
												isBusy={ actionLoading === `run-${ actionKey }` }
												onClick={ () => runJob( job ) }
											>
												{ actionLoading === `run-${ actionKey }` ? __( 'Running…', 'wp-edit-password-protected' ) : __( 'Run', 'wp-edit-password-protected' ) }
											</Button>
											<Button
												variant="secondary"
												isSmall
												isDestructive
												disabled={ !! actionLoading }
												isBusy={ actionLoading === `del-${ actionKey }` }
												onClick={ () => deleteJob( job ) }
											>
												{ actionLoading === `del-${ actionKey }` ? __( 'Deleting…', 'wp-edit-password-protected' ) : __( 'Delete', 'wp-edit-password-protected' ) }
											</Button>
										</td>
									) }
								</tr>
							);
						} ) }
					</tbody>
				</table>
			) }
		</div>
	);
};

export default CronJobs;
