/**
 * Login Log viewer (Pro).
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	Button,
	ToggleControl,
} from '@wordpress/components';
import { useState, useEffect, useMemo, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ProBadge from '../../components/ProBadge';
import Pagination from '../../components/Pagination';
import Card from '../../components/Card';
import { TableSkeleton } from '../../components/Skeleton';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';

const SAMPLE_LOG = [
	{ id: 'sample-1', user_login: 'admin', ip_address: '192.168.1.105', status: 'success', created_at: '2026-10-25 14:30:12' },
	{ id: 'sample-2', user_login: 'administrator', ip_address: '45.142.120.8', status: 'failed', created_at: '2026-10-25 14:18:45' },
	{ id: 'sample-3', user_login: 'root', ip_address: '185.220.101.4', status: 'blocked', created_at: '2026-10-25 13:55:01' },
	{ id: 'sample-4', user_login: 'editor_sarah', ip_address: '10.0.0.14', status: 'success', created_at: '2026-10-25 12:40:22' },
	{ id: 'sample-5', user_login: 'test_user', ip_address: '91.240.118.2', status: 'failed', created_at: '2026-10-25 11:15:39' },
];

const LoginLog = () => {
	const [ log, setLog ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ page, setPage ] = useState( 1 );
	const pageSize = 15;

	const { settings, isLoading, isPro } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'security' ),
			isLoading: store.isLoading(),
			isPro: store.isPro(),
		};
	} );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );

	const s = useMemo( () => ( { ...SECURITY_DEFAULTS, ...settings } ), [ settings ] );

	const handleSave = useCallback( () => {
		saveSettings( 'security', s );
	}, [ saveSettings, s ] );

	const handleReset = useCallback( () => {
		resetSettings( 'security' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	const fetchLog = () => {
		setLoading( true );
		apiFetch( { path: '/wpepp/v1/security/log' } )
			.then( ( data ) => setLog( Array.isArray( data ) ? data : [] ) )
			.catch( () => setLog( [] ) )
			.finally( () => setLoading( false ) );
	};

	useEffect( () => {
		if ( isPro ) {
			fetchLog();
		} else {
			setLoading( false );
		}
	}, [ isPro ] );

	const clearLog = () => {
		apiFetch( { path: '/wpepp/v1/security/log', method: 'DELETE' } )
			.then( () => setLog( [] ) );
	};

	if ( loading || isLoading ) {
		return <TableSkeleton rows={ 6 } columns={ 4 } />;
	}

	const paginatedLog = log.slice( ( page - 1 ) * pageSize, page * pageSize );

	return (
		<div className="wpepp-login-log" style={ { minHeight: 460 } }>
			<Card
				title={ __( 'Login Activity Log', 'wp-edit-password-protected' ) }
				subtitle={ __( 'Track all authentication attempts, successes, failures, and brute-force patterns across your site in real time.', 'wp-edit-password-protected' ) }
			>
				<ToggleControl
					__nextHasNoMarginBottom
					label={
						<span>
							{ __( 'Enable Login Logging', 'wp-edit-password-protected' ) }
							{ ! isPro && <ProBadge /> }
						</span>
					}
					help={ ! isPro ? __( 'Requires WPEPP Pro to view and retain detailed login history.', 'wp-edit-password-protected' ) : null }
					checked={ isPro ? s.login_log_enabled !== false : true }
					onChange={ ( v ) => updateSetting( 'security', 'login_log_enabled', v ) }
					disabled={ ! isPro }
				/>

				<div className="wpepp-login-log__header" style={ { marginTop: 20 } }>
					<h3 style={ { margin: 0, fontSize: '15px', fontWeight: 600 } }>{ __( 'Recent Authentication Events', 'wp-edit-password-protected' ) }</h3>
					<div style={ { display: 'flex', gap: 8 } }>
						<Button variant="secondary" onClick={ fetchLog } disabled={ ! isPro }>
							{ __( 'Refresh', 'wp-edit-password-protected' ) }
						</Button>
						{ isPro && log.length > 0 && (
							<Button variant="secondary" isDestructive onClick={ clearLog }>
								{ __( 'Clear Log', 'wp-edit-password-protected' ) }
							</Button>
						) }
					</div>
				</div>

				{ isPro ? (
					log.length === 0 ? (
						<div className="wpepp-empty-state" style={ { padding: '40px 20px' } }>
							<div className="wpepp-empty-state__icon" style={ { fontSize: 36, marginBottom: 8 } }>🔐</div>
							<h4 className="wpepp-empty-state__title" style={ { fontSize: 16, margin: '0 0 6px' } }>{ __( 'No Login Activity Recorded Yet', 'wp-edit-password-protected' ) }</h4>
							<p className="wpepp-empty-state__desc" style={ { margin: 0, color: 'var(--wpepp-text-2)', fontSize: 13 } }>
								{ __( 'Login attempts (both successful and failed logins) will be logged here with usernames, timestamps, and IP addresses.', 'wp-edit-password-protected' ) }
							</p>
						</div>
					) : (
						<>
							<table className="wpepp-table widefat striped" style={ { marginTop: 12 } }>
								<thead>
									<tr>
										<th>{ __( 'Username', 'wp-edit-password-protected' ) }</th>
										<th>{ __( 'IP Address', 'wp-edit-password-protected' ) }</th>
										<th>{ __( 'Status', 'wp-edit-password-protected' ) }</th>
										<th>{ __( 'Date', 'wp-edit-password-protected' ) }</th>
									</tr>
								</thead>
								<tbody>
									{ paginatedLog.map( ( entry ) => (
										<tr key={ entry.id }>
											<td><strong>{ entry.user_login }</strong></td>
											<td><code>{ entry.ip_address }</code></td>
											<td>
												<span className={ `wpepp-status wpepp-status--${ entry.status }` }>
													{ entry.status }
												</span>
											</td>
											<td style={ { fontVariantNumeric: 'tabular-nums' } }>{ entry.created_at }</td>
										</tr>
									) ) }
								</tbody>
							</table>
							<Pagination
								currentPage={ page }
								totalItems={ log.length }
								pageSize={ pageSize }
								onPageChange={ setPage }
								itemName={ __( 'logins', 'wp-edit-password-protected' ) }
							/>
						</>
					)
				) : (
					<>
						<table className="wpepp-table widefat striped" style={ { marginTop: 12 } }>
							<thead>
								<tr>
									<th>{ __( 'Username', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'IP Address', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'Status', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'Date', 'wp-edit-password-protected' ) }</th>
								</tr>
							</thead>
							<tbody>
								{ SAMPLE_LOG.map( ( entry ) => (
									<tr key={ entry.id }>
										<td><strong>{ entry.user_login }</strong></td>
										<td><code>{ entry.ip_address }</code></td>
										<td>
											<span className={ `wpepp-status wpepp-status--${ entry.status }` }>
												{ entry.status }
											</span>
										</td>
										<td style={ { fontVariantNumeric: 'tabular-nums' } }>{ entry.created_at }</td>
									</tr>
								) ) }
							</tbody>
						</table>
						<Pagination
							currentPage={ 1 }
							totalItems={ 28 }
							pageSize={ 5 }
							onPageChange={ () => {} }
							itemName={ __( 'logins', 'wp-edit-password-protected' ) }
						/>
					</>
				) }
			</Card>
		</div>
	);
};

export default LoginLog;
