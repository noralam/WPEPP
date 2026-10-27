/**
 * Activity — event stream + alert settings.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback, useMemo, useRef } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	ToggleControl,
	SelectControl,
	TextControl,
	Button,
	Notice,
} from '@wordpress/components';
import Card from '../components/Card';
import Pagination from '../components/Pagination';
import apiFetch from '@wordpress/api-fetch';
import ProBadge from '../components/ProBadge';
import { TableSkeleton } from '../components/Skeleton';
import { SECURITY_DEFAULTS } from '../utils/defaults';

const TYPE_LABELS = {
	login_success: [ '✅', __( 'Login', 'wp-edit-password-protected' ) ],
	login_failed: [ '❌', __( 'Failed login', 'wp-edit-password-protected' ) ],
	lockout: [ '⛔', __( 'Lockout', 'wp-edit-password-protected' ) ],
	block_manual: [ '🚫', __( 'Manual block', 'wp-edit-password-protected' ) ],
	block_auto: [ '🤖', __( 'Auto block', 'wp-edit-password-protected' ) ],
	firewall_blocked: [ '🧱', __( 'Firewall block', 'wp-edit-password-protected' ) ],
	firewall_logged: [ '📝', __( 'Firewall log', 'wp-edit-password-protected' ) ],
	scan_started: [ '🔍', __( 'Scan started', 'wp-edit-password-protected' ) ],
	scan_finished: [ '🏁', __( 'Scan finished', 'wp-edit-password-protected' ) ],
	passkey_registered: [ '🔑', __( 'Passkey added', 'wp-edit-password-protected' ) ],
	passkey_login: [ '🔑', __( 'Passkey login', 'wp-edit-password-protected' ) ],
	alert_sent: [ '📧', __( 'Alert sent', 'wp-edit-password-protected' ) ],
	settings_changed: [ '⚙️', __( 'Settings changed', 'wp-edit-password-protected' ) ],
};

const SEV_CLS = { 1: 'is-info', 2: 'is-warning', 3: 'is-critical' };

const Activity = () => {
	const { isPro, settings, isSaving } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			isPro: store.isPro(),
			settings: store.getSettings(),
			isSaving: store.isSaving(),
		};
	} );

	const { updateSetting, saveSettings } = useDispatch( 'wpepp/settings' );

	const s = useMemo( () => ( { ...SECURITY_DEFAULTS, ...( settings?.security || {} ) } ), [ settings ] );

	const [ events, setEvents ] = useState( null );
	const [ stats, setStats ] = useState( null );
	const [ filter, setFilter ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const pageSize = 15;
	const [ error, setError ] = useState( '' );
	const [ saved, setSaved ] = useState( false );
	const [ alertsStatus, setAlertsStatus ] = useState( null );
	const [ sendingTest, setSendingTest ] = useState( false );
	const [ testNotice, setTestNotice ] = useState( null );

	const onFilterChange = useCallback( ( f ) => {
		setFilter( f );
		setPage( 1 );
	}, [] );

	const loadEvents = useCallback( () => {
		const q = filter ? `&type=${ encodeURIComponent( filter ) }` : '';
		apiFetch( { path: `/wpepp/v1/events?per_page=60${ q }` } )
			.then( ( res ) => setEvents( Array.isArray( res ) ? res : [] ) )
			.catch( ( err ) => {
				setError( err.message || __( 'Could not load events.', 'wp-edit-password-protected' ) );
				setEvents( [] ); // Never leave null — table guards rely on arrays.
			} );
	}, [ filter ] );

	useEffect( loadEvents, [ loadEvents ] );

	useEffect( () => {
		apiFetch( { path: '/wpepp/v1/events/stats?days=7' } )
			.then( setStats )
			.catch( () => setStats( null ) );
	}, [] );

	const loadAlertsStatus = useCallback( () => {
		apiFetch( { path: '/wpepp/v1/alerts/status' } )
			.then( setAlertsStatus )
			.catch( () => setAlertsStatus( null ) );
	}, [] );

	useEffect( () => {
		loadAlertsStatus();
	}, [ loadAlertsStatus ] );

	const sendTestAlert = useCallback( async () => {
		setSendingTest( true );
		setTestNotice( null );
		try {
			const res = await apiFetch( {
				path: '/wpepp/v1/alerts/test',
				method: 'POST',
				data: { to: s.alert_email || '' },
			} );
			setTestNotice( {
				status: 'success',
				message: sprintf(
					__( 'Test alert successfully sent to %s via %s.', 'wp-edit-password-protected' ),
					res.recipient,
					res.provider
				),
			} );
			loadEvents();
		} catch ( err ) {
			setTestNotice( {
				status: 'error',
				message: err.message || __( 'Failed to send test alert. Check your SMTP settings or server mail logs.', 'wp-edit-password-protected' ),
			} );
		} finally {
			setSendingTest( false );
		}
	}, [ s.alert_email, loadEvents ] );

	const update = useCallback(
		( key, value ) => updateSetting( 'security', key, value ),
		[ updateSetting ]
	);

	const saveAlerts = useCallback( () => {
		saveSettings( 'security', s );
	}, [ saveSettings, s ] );

	// Flip the "Saved" badge only after a successful save round-trip.
	const prevSaving = useRef( false );

	useEffect( () => {
		if ( prevSaving.current && ! isSaving && ! error ) {
			setSaved( true );
			window.setTimeout( () => setSaved( false ), 2500 );
		}
		prevSaving.current = isSaving;
	}, [ isSaving, error ] );

	if ( events === null && ! error ) {
		return <TableSkeleton rows={ 7 } columns={ 5 } />;
	}

	return (
		<div className="wpepp-activity">
			<h3>{ __( 'Activity', 'wp-edit-password-protected' ) }</h3>

			{ stats && (
				<div className="wpepp-firewall__stats">
					<span><strong>{ stats.total }</strong> { sprintf( __( 'events in %d days', 'wp-edit-password-protected' ), 7 ) }</span>
					<span>·</span>
					{ Object.entries( stats.by_type ).slice( 0, 5 ).map( ( [ type, n ] ) => (
						<span key={ type }>
							<strong>{ n }</strong> { TYPE_LABELS[ type ]?.[ 1 ] ?? type }
						</span>
					) ) }
				</div>
			) }

			<Card title={ __( 'Email alerts', 'wp-edit-password-protected' ) }>
				{ alertsStatus && (
					<div
						style={ {
							marginBottom: 16,
							padding: '10px 14px',
							borderRadius: 6,
							background: alertsStatus.smtp?.configured ? '#f0fdf4' : '#fffbeb',
							border: `1px solid ${ alertsStatus.smtp?.configured ? '#bbf7d0' : '#fde68a' }`,
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'space-between',
							flexWrap: 'wrap',
							gap: 8,
						} }
					>
						<div>
							<strong style={ { color: '#1e293b' } }>{ __( 'Email Delivery Service:', 'wp-edit-password-protected' ) }</strong>{ ' ' }
							<span style={ { fontWeight: 600, color: alertsStatus.smtp?.configured ? '#166534' : '#92400e' } }>
								{ alertsStatus.smtp?.provider }
							</span>
							{ ! alertsStatus.smtp?.configured && (
								<p style={ { margin: '4px 0 0', fontSize: '12px', color: '#b45309' } }>
									{ __( 'Recommended: Install an SMTP plugin (e.g. FluentSMTP, WP Mail SMTP) to guarantee alert deliverability.', 'wp-edit-password-protected' ) }
								</p>
							) }
						</div>
						<span
							style={ {
								fontSize: '11px',
								textTransform: 'uppercase',
								letterSpacing: '0.05em',
								fontWeight: 700,
								padding: '2px 8px',
								borderRadius: 4,
								background: alertsStatus.smtp?.configured ? '#dcfce7' : '#fef3c7',
								color: alertsStatus.smtp?.configured ? '#15803d' : '#b45309',
							} }
						>
							{ alertsStatus.smtp?.configured ? __( 'SMTP Active', 'wp-edit-password-protected' ) : __( 'PHP Mail Fallback', 'wp-edit-password-protected' ) }
						</span>
					</div>
				) }

				<ToggleControl
					label={ __( 'Enable email alerts', 'wp-edit-password-protected' ) }
					checked={ s.alerts_enabled !== false }
					onChange={ ( v ) => update( 'alerts_enabled', v ) }
				/>

				<TextControl
					label={ __( 'Alert email address', 'wp-edit-password-protected' ) }
					type="email"
					value={ s.alert_email || '' }
					placeholder={ window.wpeppData?.adminEmail || 'admin@example.com' }
					onChange={ ( v ) => update( 'alert_email', v ) }
				/>

				<ToggleControl
					label={ __( 'Lockouts & auto-blocks', 'wp-edit-password-protected' ) }
					help={ __( 'One email per IP per 30 minutes, max.', 'wp-edit-password-protected' ) }
					checked={ !! ( s.alert_lockout ?? true ) }
					onChange={ ( v ) => update( 'alert_lockout', v ) }
				/>

				<ToggleControl
					label={ __( 'Scan critical findings', 'wp-edit-password-protected' ) }
					checked={ !! ( s.alert_scan_criticals ?? true ) }
					onChange={ ( v ) => update( 'alert_scan_criticals', v ) }
				/>

				<div
					style={ {
						opacity: isPro ? 1 : 0.55,
						display: 'flex',
						flexDirection: 'column',
						gap: 12,
						marginTop: 12,
						marginBottom: 12,
					} }
				>
					<ToggleControl
						label={ <span>{ __( 'Administrator logins', 'wp-edit-password-protected' ) }{ ! isPro && <ProBadge /> }</span> }
						help={ ! isPro ? __( 'Requires WPEPP Pro.', 'wp-edit-password-protected' ) : null }
						checked={ isPro && !! s.alert_admin_login }
						disabled={ ! isPro }
						onChange={ ( v ) => update( 'alert_admin_login', v ) }
					/>
					<ToggleControl
						label={ <span>{ __( 'Firewall burst digest', 'wp-edit-password-protected' ) }{ ! isPro && <ProBadge /> }</span> }
						help={ ! isPro ? __( 'Requires WPEPP Pro.', 'wp-edit-password-protected' ) : null }
						checked={ isPro && !! s.alert_firewall_burst }
						disabled={ ! isPro }
						onChange={ ( v ) => update( 'alert_firewall_burst', v ) }
					/>
					<ToggleControl
						label={ <span>{ __( 'Weekly activity report', 'wp-edit-password-protected' ) }{ ! isPro && <ProBadge /> }</span> }
						help={ ! isPro ? __( 'Requires WPEPP Pro.', 'wp-edit-password-protected' ) : null }
						checked={ isPro && !! s.alert_weekly_report }
						disabled={ ! isPro }
						onChange={ ( v ) => update( 'alert_weekly_report', v ) }
					/>
				</div>

				<div style={ { display: 'flex', gap: 10, marginTop: 12, alignItems: 'center', flexWrap: 'wrap' } }>
					<Button variant="primary" onClick={ saveAlerts }>
						{ saved ? __( 'Saved ✓', 'wp-edit-password-protected' ) : __( 'Save Alert Settings', 'wp-edit-password-protected' ) }
					</Button>
					<Button
						variant="secondary"
						isBusy={ sendingTest }
						disabled={ sendingTest }
						onClick={ sendTestAlert }
					>
						{ __( 'Send Test Security Alert', 'wp-edit-password-protected' ) }
					</Button>
				</div>

				{ testNotice && (
					<Notice
						status={ testNotice.status }
						isDismissible={ true }
						onDismiss={ () => setTestNotice( null ) }
						style={ { marginTop: 12 } }
					>
						{ testNotice.message }
					</Notice>
				) }

				{ saved && <Notice status="success" isDismissible={ false } style={ { marginTop: 12 } }>{ __( 'Alert settings saved.', 'wp-edit-password-protected' ) }</Notice> }
			</Card>

			<div style={ { display: 'flex', justifyContent: 'flex-end', margin: '14px 0 10px' } }>
				<SelectControl
					value={ filter }
					onChange={ onFilterChange }
					options={ [
						{ value: '', label: __( 'All events', 'wp-edit-password-protected' ) },
						...Object.entries( TYPE_LABELS ).map( ( [ value, pair ] ) => ( { value, label: pair[ 1 ] } ) ),
					] }
				/>
			</div>

			{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }

			{ ! events || events.length === 0 ? (
				<div className="wpepp-empty-state">
					<div className="wpepp-empty-state__icon">📋</div>
					<h4 className="wpepp-empty-state__title">{ __( 'No Security Events Recorded Yet', 'wp-edit-password-protected' ) }</h4>
					<p className="wpepp-empty-state__desc">
						{ __( 'Your activity log is clear! As security scans, firewall blocks, or login attempts occur, a complete audit trail will appear here.', 'wp-edit-password-protected' ) }
					</p>
				</div>
			) : (
				<>
					<table className="wpepp-blocks__table widefat">
						<thead>
							<tr>
								<th>{ __( 'When', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Event', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Summary', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'IP', 'wp-edit-password-protected' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ events.slice( ( page - 1 ) * pageSize, page * pageSize ).map( ( ev ) => (
								<tr key={ ev.id }>
									<td style={ { fontVariantNumeric: 'tabular-nums', whiteSpace: 'nowrap' } }>
										{ new Date( ev.created_at.replace( ' ', 'T' ) + 'Z' ).toLocaleString() }
									</td>
									<td>
										<span className={ `wpepp-sev ${ SEV_CLS[ ev.severity ] || 'is-info' }` }>
											{ TYPE_LABELS[ ev.event_type ]?.[ 0 ] } { TYPE_LABELS[ ev.event_type ]?.[ 1 ] ?? ev.event_type }
										</span>
									</td>
									<td>{ ev.summary }</td>
									<td><code>{ ev.ip || '—' }</code></td>
								</tr>
							) ) }
						</tbody>
					</table>
					<Pagination
						currentPage={ page }
						totalItems={ events.length }
						pageSize={ pageSize }
						onPageChange={ setPage }
						itemName={ __( 'events', 'wp-edit-password-protected' ) }
					/>
				</>
			) }
		</div>
	);
};

export default Activity;
