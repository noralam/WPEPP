/**
 * Login Log viewer (Pro).
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	Button,
	Spinner,
	ToggleControl,
} from '@wordpress/components';
import { useState, useEffect, useMemo, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ProBadge from '../../components/ProBadge';
import Pagination from '../../components/Pagination';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';

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
		fetchLog();
	}, [] );

	const clearLog = () => {
		apiFetch( { path: '/wpepp/v1/security/log', method: 'DELETE' } )
			.then( () => setLog( [] ) );
	};

	if ( loading || isLoading ) {
		return <Spinner />;
	}

	const paginatedLog = log.slice( ( page - 1 ) * pageSize, page * pageSize );

	return (
		<div className="wpepp-login-log">
			<ToggleControl
				__nextHasNoMarginBottom
				label={
					<span>
						{ __( 'Enable Login Logging', 'wp-edit-password-protected' ) }
						{ ! isPro && <ProBadge /> }
					</span>
				}
				checked={ isPro && s.login_log_enabled !== false }
				onChange={ ( v ) => updateSetting( 'security', 'login_log_enabled', v ) }
				disabled={ ! isPro }
			/>

			{ isPro && s.login_log_enabled !== false && (
				<>
					<div className="wpepp-login-log__header" style={ { marginTop: 16 } }>
						<h3>{ __( 'Login Activity Log', 'wp-edit-password-protected' ) }</h3>
						<div style={ { display: 'flex', gap: 8 } }>
							<Button variant="secondary" onClick={ fetchLog }>
								{ __( 'Refresh', 'wp-edit-password-protected' ) }
							</Button>
							{ log.length > 0 && (
								<Button variant="secondary" isDestructive onClick={ clearLog }>
									{ __( 'Clear Log', 'wp-edit-password-protected' ) }
								</Button>
							) }
						</div>
					</div>

					{ log.length === 0 ? (
						<div className="wpepp-empty-state">
							<div className="wpepp-empty-state__icon">🔐</div>
							<h4 className="wpepp-empty-state__title">{ __( 'No Login Activity Recorded Yet', 'wp-edit-password-protected' ) }</h4>
							<p className="wpepp-empty-state__desc">
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
											<td>{ entry.user_login }</td>
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
					) }
				</>
			) }
		</div>
	);
};

export default LoginLog;
