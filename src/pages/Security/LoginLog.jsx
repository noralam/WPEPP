/**
 * Login Log viewer (Pro).
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	Button,
	Spinner,
	ToggleControl,
	Notice,
} from '@wordpress/components';
import { useState, useEffect, useMemo, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ProBadge from '../../components/ProBadge';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';

const LoginLog = () => {
	const [ log, setLog ] = useState( [] );
	const [ loading, setLoading ] = useState( true );

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

	return (
		<div className="wpepp-login-log">
			<ToggleControl
				label={
					<span>
						{ __( 'Enable Login Logging', 'wp-edit-password-protected' ) }
						{ ! isPro && <ProBadge /> }
					</span>
				}
				checked={ isPro && s.login_log_enabled !== false }
				onChange={ ( v ) => updateSetting( 'security', 'login_log_enabled', v ) }
				disabled={ ! isPro }
				__nextHasNoMarginBottom
			/>

			{ isPro && s.login_log_enabled !== false && (
				<>
					<div className="wpepp-login-log__header">
						<h3>{ __( 'Login Activity Log', 'wp-edit-password-protected' ) }</h3>
						<div>
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
						<p className="wpepp-empty-state">
							{ __( 'No login activity recorded yet.', 'wp-edit-password-protected' ) }
						</p>
					) : (
						<table className="wpepp-table widefat striped">
							<thead>
								<tr>
									<th>{ __( 'Username', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'IP Address', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'Status', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'Date', 'wp-edit-password-protected' ) }</th>
								</tr>
							</thead>
							<tbody>
								{ log.map( ( entry ) => (
									<tr key={ entry.id }>
										<td>{ entry.user_login }</td>
										<td>{ entry.ip_address }</td>
										<td>
											<span className={ `wpepp-status wpepp-status--${ entry.status }` }>
												{ entry.status }
											</span>
										</td>
										<td>{ entry.created_at }</td>
									</tr>
								) ) }
							</tbody>
						</table>
					) }
				</>
			) }
		</div>
	);
};

export default LoginLog;
