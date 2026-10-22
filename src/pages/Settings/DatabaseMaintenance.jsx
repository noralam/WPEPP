/**
 * Database & Log Maintenance component.
 *
 * Provides real-time log table diagnostics, automatic log pruning controls,
 * and 1-click database cleanup to prevent table bloat.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	Button,
	Spinner,
	ToggleControl,
	SelectControl,
	Notice,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import Card from '../../components/Card';
import { SECURITY_DEFAULTS } from '../../utils/defaults';

const DatabaseMaintenance = () => {
	const { settings } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'security' ),
		};
	} );

	const { updateSetting } = useDispatch( 'wpepp/settings' );

	const s = useMemo( () => ( { ...SECURITY_DEFAULTS, ...( settings || {} ) } ), [ settings ] );

	const [ stats, setStats ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ purging, setPurging ] = useState( false );
	const [ purgeNotice, setPurgeNotice ] = useState( null );

	const loadStats = useCallback( () => {
		setLoading( true );
		apiFetch( { path: '/wpepp/v1/database/stats' } )
			.then( ( res ) => setStats( res ) )
			.catch( () => setStats( null ) )
			.finally( () => setLoading( false ) );
	}, [] );

	useEffect( () => {
		loadStats();
	}, [ loadStats ] );

	const handlePurgeAll = useCallback( async () => {
		setPurging( true );
		setPurgeNotice( null );
		try {
			const res = await apiFetch( {
				path: '/wpepp/v1/database/purge',
				method: 'POST',
			} );
			if ( res?.stats ) {
				setStats( res.stats );
			}
			const total = res?.purged?.total ?? 0;
			setPurgeNotice( {
				status: 'success',
				message: sprintf(
					/* translators: %d: number of rows pruned */
					__( 'Database maintenance complete! Removed %d old log entries.', 'wp-edit-password-protected' ),
					total
				),
			} );
		} catch ( err ) {
			setPurgeNotice( {
				status: 'error',
				message: err.message || __( 'Could not execute database maintenance.', 'wp-edit-password-protected' ),
			} );
		} finally {
			setPurging( false );
		}
	}, [] );

	const handlePurgeSingle = useCallback( async ( tableKey, isAll = false ) => {
		try {
			const res = await apiFetch( {
				path: '/wpepp/v1/database/purge-table',
				method: 'POST',
				data: { table: tableKey, all: isAll },
			} );
			if ( res?.stats ) {
				setStats( res.stats );
			}
			setPurgeNotice( {
				status: 'success',
				message: sprintf(
					/* translators: 1: table key, 2: count */
					__( 'Pruned %2$d rows from %1$s.', 'wp-edit-password-protected' ),
					tableKey,
					res?.purged_rows ?? 0
				),
			} );
		} catch {
			// Handled gracefully.
		}
	}, [] );

	const update = ( key, val ) => {
		updateSetting( 'security', key, val );
	};

	return (
		<Card
			title={ __( 'Database & Log Maintenance', 'wp-edit-password-protected' ) }
			subtitle={ __( 'Monitor table growth, set automated log retention windows, and prevent database bloat.', 'wp-edit-password-protected' ) }
		>
			{ purgeNotice && (
				<Notice
					status={ purgeNotice.status }
					isDismissible
					onDismiss={ () => setPurgeNotice( null ) }
					style={ { marginBottom: 16 } }
				>
					{ purgeNotice.message }
				</Notice>
			) }

			{ loading && ! stats ? (
				<Spinner />
			) : (
				<>
					{ /* Table Footprint Overview */ }
					<div
						style={ {
							display: 'grid',
							gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
							gap: 16,
							marginBottom: 20,
						} }
					>
						{ stats?.tables &&
							Object.entries( stats.tables ).map( ( [ key, item ] ) => {
								const icons = {
									events: '⚡',
									logins: '🔐',
									slow_queries: '🐢',
									blocks: '🛑',
								};
								return (
									<div
										key={ key }
										className="wpepp-stat-card"
										style={ {
											padding: '16px 18px',
											borderRadius: '10px',
										} }
									>
										<div style={ { display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: 8 } }>
											<span style={ { fontSize: 13, color: 'var(--wpepp-text-2)', fontWeight: 600 } }>
												{ item.label }
											</span>
											<span style={ { fontSize: 18 } }>{ icons[ key ] || '📊' }</span>
										</div>
										<div
											style={ {
												fontSize: 22,
												fontWeight: 700,
												color: 'var(--wpepp-text)',
												lineHeight: 1.2,
												fontVariantNumeric: 'tabular-nums',
											} }
										>
											{ item.rows.toLocaleString() }{ ' ' }
											<span style={ { fontSize: 13, fontWeight: 500, color: 'var(--wpepp-text-3)' } }>
												{ __( 'rows', 'wp-edit-password-protected' ) }
											</span>
										</div>
										<div style={ { fontSize: 12, color: 'var(--wpepp-text-3)', marginTop: 6, fontWeight: 500 } }>
											{ item.data_size }
										</div>
									</div>
								);
							} ) }
					</div>

					<div
						style={ {
							display: 'flex',
							justifyContent: 'space-between',
							alignItems: 'center',
							padding: '14px 20px',
							background: 'rgba(46, 230, 168, 0.08)',
							border: '1px solid rgba(46, 230, 168, 0.25)',
							borderRadius: 10,
							marginBottom: 24,
							flexWrap: 'wrap',
							gap: 12,
						} }
					>
						<div>
							<strong style={ { color: 'var(--wpepp-text)', fontSize: 14 } }>
								{ __( 'Total Log Storage:', 'wp-edit-password-protected' ) }
							</strong>{ ' ' }
							<span style={ { color: 'var(--wpepp-accent)', fontWeight: 700, fontSize: 16 } }>
								{ stats?.total_size || '0 KB' }
							</span>{ ' ' }
							<span style={ { color: 'var(--wpepp-text-2)', fontSize: 13 } }>
								({ ( stats?.total_rows || 0 ).toLocaleString() } { __( 'total rows across all tables', 'wp-edit-password-protected' ) })
							</span>
						</div>
						<div style={ { display: 'flex', gap: 10 } }>
							<Button
								variant="primary"
								isBusy={ purging }
								disabled={ purging }
								onClick={ handlePurgeAll }
								style={ { minHeight: 36, fontWeight: 600 } }
							>
								{ __( 'Clean Old Logs Now', 'wp-edit-password-protected' ) }
							</Button>
							<Button
								variant="secondary"
								onClick={ loadStats }
								disabled={ loading }
								style={ { minHeight: 36 } }
							>
								{ __( 'Refresh Stats', 'wp-edit-password-protected' ) }
							</Button>
						</div>
					</div>

					{ /* Controls */ }
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Enable Automatic Daily Log Pruning', 'wp-edit-password-protected' ) }
						help={ __( 'Automatically prunes old records during the daily background cron to prevent tables from growing indefinitely.', 'wp-edit-password-protected' ) }
						checked={ s.auto_prune_logs_enabled !== false }
						onChange={ ( v ) => update( 'auto_prune_logs_enabled', v ) }
					/>

					<div
						style={ {
							display: 'grid',
							gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
							gap: 14,
							marginTop: 14,
						} }
					>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Activity Log Retention', 'wp-edit-password-protected' ) }
							value={ s.events_retention_days ?? 30 }
							options={ [
								{ value: 7, label: __( '7 Days', 'wp-edit-password-protected' ) },
								{ value: 14, label: __( '14 Days', 'wp-edit-password-protected' ) },
								{ value: 30, label: __( '30 Days (Recommended)', 'wp-edit-password-protected' ) },
								{ value: 60, label: __( '60 Days', 'wp-edit-password-protected' ) },
								{ value: 90, label: __( '90 Days', 'wp-edit-password-protected' ) },
							] }
							onChange={ ( v ) => update( 'events_retention_days', parseInt( v, 10 ) ) }
						/>

						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Login Activity Log Retention', 'wp-edit-password-protected' ) }
							value={ s.login_log_retention_days ?? 30 }
							options={ [
								{ value: 7, label: __( '7 Days', 'wp-edit-password-protected' ) },
								{ value: 14, label: __( '14 Days', 'wp-edit-password-protected' ) },
								{ value: 30, label: __( '30 Days (Recommended)', 'wp-edit-password-protected' ) },
								{ value: 60, label: __( '60 Days', 'wp-edit-password-protected' ) },
								{ value: 90, label: __( '90 Days', 'wp-edit-password-protected' ) },
							] }
							onChange={ ( v ) => update( 'login_log_retention_days', parseInt( v, 10 ) ) }
						/>

						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Slow Queries Retention', 'wp-edit-password-protected' ) }
							value={ s.slow_queries_retention_days ?? 7 }
							options={ [
								{ value: 3, label: __( '3 Days', 'wp-edit-password-protected' ) },
								{ value: 7, label: __( '7 Days (Recommended)', 'wp-edit-password-protected' ) },
								{ value: 14, label: __( '14 Days', 'wp-edit-password-protected' ) },
								{ value: 30, label: __( '30 Days', 'wp-edit-password-protected' ) },
							] }
							onChange={ ( v ) => update( 'slow_queries_retention_days', parseInt( v, 10 ) ) }
						/>

						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Flood Protection (Max Rows Cap)', 'wp-edit-password-protected' ) }
							value={ s.max_log_rows_cap ?? 5000 }
							options={ [
								{ value: 1000, label: __( '1,000 Rows per log (Lightweight)', 'wp-edit-password-protected' ) },
								{ value: 2500, label: __( '2,500 Rows per log', 'wp-edit-password-protected' ) },
								{ value: 5000, label: __( '5,000 Rows (Recommended)', 'wp-edit-password-protected' ) },
								{ value: 10000, label: __( '10,000 Rows', 'wp-edit-password-protected' ) },
							] }
							onChange={ ( v ) => update( 'max_log_rows_cap', parseInt( v, 10 ) ) }
						/>
					</div>
				</>
			) }
		</Card>
	);
};

export default DatabaseMaintenance;
