/**
 * Firewall — mode toggles, stats, and a live rule tester.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	ToggleControl,
	Button,
	TextControl,
	SelectControl,
	TextareaControl,
	Notice,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import Card from '../../components/Card';
import ToggleCard from '../../components/ToggleCard';
import Loading from '../../components/Loading';
import ProBadge from '../../components/ProBadge';
import ProLock from '../../components/ProLock';

const Firewall = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );
	const [ status, setStatus ] = useState( null );
	const [ busy, setBusy ] = useState( '' );
	const [ error, setError ] = useState( '' );

	// Tester state.
	const [ testUrl, setTestUrl ] = useState( 'https://example.com/?id=1 UNION SELECT password FROM users' );
	const [ testMethod, setTestMethod ] = useState( 'GET' );
	const [ testUa, setTestUa ] = useState( '' );
	const [ testBody, setTestBody ] = useState( '' );
	const [ testResult, setTestResult ] = useState( null );

	const loadStatus = useCallback( () => {
		apiFetch( { path: '/wpepp/v1/firewall/status' } )
			.then( setStatus )
			.catch( ( err ) => setError( err.message ) );
	}, [] );

	useEffect( loadStatus, [ loadStatus ] );

	const toggle = useCallback( async ( setting, value ) => {
		setBusy( setting );
		setError( '' );

		try {
			const res = await apiFetch( {
				path: '/wpepp/v1/firewall/toggle',
				method: 'POST',
				data: { setting, value },
			} );
			setStatus( ( s ) => ( { ...s, [ setting ]: value, extended_enabled: res.extended_enabled } ) );
		} catch ( err ) {
			setError( err.message || __( 'Toggle failed.', 'wp-edit-password-protected' ) );
			loadStatus();
		}

		setBusy( '' );
	}, [ loadStatus ] );

	const runTest = useCallback( async () => {
		setTestResult( null );

		try {
			const res = await apiFetch( {
				path: '/wpepp/v1/firewall/test',
				method: 'POST',
				data: {
					url: testUrl,
					method: testMethod,
					ua: testUa,
					body: Object.fromEntries(
						testBody.split( '\n' )
							.filter( Boolean )
							.map( ( line ) => line.split( '=' ).map( ( s ) => s.trim() ) )
							.filter( ( parts ) => parts.length >= 2 )
							.map( ( parts ) => [ parts[ 0 ], parts.slice( 1 ).join( '=' ) ] )
					),
				},
			} );
			setTestResult( res.matched );
		} catch ( err ) {
			setError( err.message );
		}
	}, [ testUrl, testMethod, testUa, testBody ] );

	if ( status === null && ! error ) {
		return <Loading variant="cards" count={ 3 } />;
	}

	return (
		<div className="wpepp-firewall">
			<h3>{ __( 'Firewall', 'wp-edit-password-protected' ) }</h3>

			{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }

			{ status && (
				<div className="wpepp-firewall__stats">
					<span><strong>{ status.rule_count }</strong> { __( 'rules active', 'wp-edit-password-protected' ) }</span>
					<span>·</span>
					<span><strong>{ status.blocked_total }</strong> { __( 'requests blocked', 'wp-edit-password-protected' ) }</span>
				</div>
			) }

			<Card title={ __( 'Modes', 'wp-edit-password-protected' ) } padded={ false }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={ __( 'Firewall enabled (basic mode)', 'wp-edit-password-protected' ) }
						description={ __( 'Rules run inside WordPress on every request.', 'wp-edit-password-protected' ) }
						checked={ !! status?.basic_enabled }
						disabled={ busy === 'basic_enabled' }
						onChange={ ( v ) => toggle( 'basic_enabled', v ) }
					/>
					<ToggleCard
						title={ __( 'Extended protection (early boot)', 'wp-edit-password-protected' ) }
						badge={ ! isPro ? <ProBadge /> : null }
						help={ ! isPro ? __( 'Requires WPEPP Pro.', 'wp-edit-password-protected' ) : null }
						description={
							status?.extended_enabled
								? __( 'Active — requests are filtered before WordPress loads. If the bootstrap ever fails it disables itself automatically.', 'wp-edit-password-protected' )
								: __( 'Runs the firewall before WordPress loads via a generated drop-in. Recommended. Requires writable .user.ini or .htaccess.', 'wp-edit-password-protected' )
						}
						checked={ isPro && !! status?.extended_enabled }
						disabled={ ! isPro || busy === 'extended_enabled' }
						onChange={ ( v ) => toggle( 'extended_enabled', v ) }
					/>
					<ToggleCard
						title={ __( 'Learning mode', 'wp-edit-password-protected' ) }
						help={ ! status?.basic_enabled ? __( 'Disabled — enable basic protection above first.', 'wp-edit-password-protected' ) : null }
						description={ __( 'Matches are logged, nothing is blocked. Use for the first days to spot false positives.', 'wp-edit-password-protected' ) }
						checked={ !! status?.learning_mode }
						disabled={ busy === 'learning_mode' || ! status?.basic_enabled }
						onChange={ ( v ) => toggle( 'learning_mode', v ) }
					/>
				</div>
			</Card>

			<Card title={ __( 'Recent matches (debugging)', 'wp-edit-password-protected' ) }>
				{ ! status?.recent_matches?.length ? (
					<p>{ __( 'No rule matches recorded yet.', 'wp-edit-password-protected' ) }</p>
				) : (
					<table className="wpepp-blocks__table widefat">
						<thead>
							<tr>
								<th>{ __( 'Rule', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Result', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Path', 'wp-edit-password-protected' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ [ ...status.recent_matches ].reverse().map( ( m, i ) => (
								<tr key={ i }>
									<td><code>{ m.id }</code></td>
									<td>{ m.blocked ? '🚫 ' + __( 'Blocked', 'wp-edit-password-protected' ) : '📝 Log' }</td>
									<td className="wpepp-scanner__path"><code>{ m.path }</code></td>
								</tr>
							) ) }
						</tbody>
					</table>
				)}
			</Card>

			<ProLock isPro={ isPro } featureName={ __( 'Firewall Rule Tester', 'wp-edit-password-protected' ) }>
				<Card title={ __( 'Rule tester', 'wp-edit-password-protected' ) }>
					<div className="wpepp-form-grid wpepp-form-grid--tester">
						<div className="wpepp-form-grid__url">
							<TextControl
								label={ __( 'URL to test', 'wp-edit-password-protected' ) }
								value={ testUrl }
								onChange={ setTestUrl }
							/>
						</div>
						<div className="wpepp-form-grid__method">
							<SelectControl
								label={ __( 'Method', 'wp-edit-password-protected' ) }
								value={ testMethod }
								onChange={ setTestMethod }
								options={ [ 'GET', 'POST' ].map( ( m ) => ( { value: m, label: m } ) ) }
							/>
						</div>
						<div className="wpepp-form-grid__full">
							<TextControl
								label={ __( 'User-Agent (optional)', 'wp-edit-password-protected' ) }
								value={ testUa }
								onChange={ setTestUa }
								placeholder="sqlmap/1.5"
							/>
						</div>
						<div className="wpepp-form-grid__full">
							<TextareaControl
								label={ __( 'POST body — one field per line (name=value)', 'wp-edit-password-protected' ) }
								value={ testBody }
								onChange={ setTestBody }
								rows={ 3 }
								placeholder={ 'cmd=eval(base64_decode($x))' }
							/>
						</div>
						<div className="wpepp-form-grid__full wpepp-form-actions">
							<Button variant="secondary" onClick={ runTest }>
								{ __( 'Test request', 'wp-edit-password-protected' ) }
							</Button>
						</div>
					</div>

					{ testResult !== null && (
						testResult ? (
							<Notice status="warning" isDismissible={ false } style={ { marginTop: 12 } }>
								{
									sprintf(
										/* translators: 1: rule name, 2: rule id */
										__( 'Would be BLOCKED by rule "%1$s" (%2$s).', 'wp-edit-password-protected' ),
										testResult.name,
										testResult.id
									)
								}
							</Notice>
						) : (
							<Notice status="success" isDismissible={ false } style={ { marginTop: 12 } }>
								{ __( 'No rule matches this request.', 'wp-edit-password-protected' ) }
							</Notice>
						)
					) }
				</Card>
			</ProLock>
		</div>
	);
};

export default Firewall;
