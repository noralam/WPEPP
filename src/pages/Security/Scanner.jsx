/**
 * Scanner — run integrity + malware scans, review findings.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import {
	Button,
	SelectControl,
	ToggleControl,
	Notice,
	Spinner,
	ProgressBar,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import Card from '../../components/Card';
import Pagination from '../../components/Pagination';

const PAGE_SIZE = 15;


const KIND_LABELS = {
	core_modified: __( 'Modified core file', 'wp-edit-password-protected' ),
	core_unknown: __( 'Unknown file in core dir', 'wp-edit-password-protected' ),
	plugin_modified: __( 'Modified plugin file', 'wp-edit-password-protected' ),
	theme_modified: __( 'Modified theme file', 'wp-edit-password-protected' ),
	plugin_unknown: __( 'Unknown plugin file', 'wp-edit-password-protected' ),
	theme_unknown: __( 'Unknown theme file', 'wp-edit-password-protected' ),
	plugin_unverified: __( 'Non-wp.org plugin (unverifiable)', 'wp-edit-password-protected' ),
	theme_unverified: __( 'Non-wp.org theme (unverifiable)', 'wp-edit-password-protected' ),
	core_unverified: __( 'Checksums unavailable', 'wp-edit-password-protected' ),
	missing_file: __( 'Missing file', 'wp-edit-password-protected' ),
	malware: __( 'Malware signature match', 'wp-edit-password-protected' ),
	suspicious_option: __( 'Suspicious setting', 'wp-edit-password-protected' ),
	recent_admin: __( 'Recent admin account', 'wp-edit-password-protected' ),
};

const SEVERITY = {
	3: { label: __( 'Critical', 'wp-edit-password-protected' ), cls: 'is-critical' },
	2: { label: __( 'Warning', 'wp-edit-password-protected' ), cls: 'is-warning' },
	1: { label: __( 'Info', 'wp-edit-password-protected' ), cls: 'is-info' },
};

const STAGE_LABELS = [
	__( 'Scanning files…', 'wp-edit-password-protected' ),
	__( 'Verifying checksums…', 'wp-edit-password-protected' ),
	__( 'Checking options…', 'wp-edit-password-protected' ),
	__( 'Checking users…', 'wp-edit-password-protected' ),
];

const Scanner = () => {
	const [ status, setStatus ] = useState( null );
	const [ issues, setIssues ] = useState( null );
	const [ filter, setFilter ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ schedule, setSchedule ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ syncingSigs, setSyncingSigs ] = useState( false );
	const [ syncNotice, setSyncNotice ] = useState( null );
	const pollRef = useRef( null );

	const loadStatus = useCallback( async () => {
		try {
			const res = await apiFetch( { path: '/wpepp/v1/scan/status' } );
			setStatus( res );
			setSchedule( res.scheduled );
			return res;
		} catch ( err ) {
			setError( err.message || __( 'Could not load scanner status.', 'wp-edit-password-protected' ) );
			// Exit the spinner even on failure — render the controls with
			// safe defaults so the user can retry instead of hanging.
			setStatus( {
				running: false,
				stage: '',
				stage_index: 0,
				stage_total: 4,
				scanned: 0,
				flagged: 0,
				issues_new: 0,
				last_scan: '',
				scheduled: false,
			} );
			return null;
		}
	}, [] );

	const loadIssues = useCallback( async () => {
		const q = filter ? `&status=${ filter }` : '';
		try {
			setIssues(
				await apiFetch( { path: `/wpepp/v1/scan/issues?per_page=100${ q }` } )
			);
		} catch {
			setIssues( [] );
		}
	}, [ filter ] );

	useEffect( () => {
		loadIssues();
	}, [ loadIssues ] );

	// Initial status load — without this the tab spins forever.
	useEffect( () => {
		loadStatus();
	}, [ loadStatus ] );

	// Poll while a scan runs.
	useEffect( () => {
		if ( status?.running ) {
			pollRef.current = setInterval( async () => {
				const res = await loadStatus();
				if ( res && ! res.running ) {
					clearInterval( pollRef.current );
					loadIssues();
				}
			}, 2500 );
		}

		return () => clearInterval( pollRef.current );
	}, [ status?.running, loadStatus, loadIssues ] );

	const startScan = useCallback( async ( profile ) => {
		setError( '' );

		try {
			await apiFetch( {
				path: '/wpepp/v1/scan/start',
				method: 'POST',
				data: { profile },
			} );
			let res = await loadStatus();

			// Drive the first slices from the browser for snappy progress.
			let guard = 0;
			while ( res?.running && guard++ < 200 ) {
				res = await apiFetch( { path: '/wpepp/v1/scan/tick', method: 'POST' } ); // eslint-disable-line no-await-in-loop
				setStatus( res );
			}

			if ( ! res?.running ) {
				loadIssues();
			}
		} catch ( err ) {
			setError( err.message || __( 'Scan failed to start.', 'wp-edit-password-protected' ) );
		}
	}, [ loadStatus, loadIssues ] );

	const stopScan = useCallback( async () => {
		await apiFetch( { path: '/wpepp/v1/scan/stop', method: 'POST' } ).catch( () => {} );
		loadStatus();
	}, [ loadStatus ] );

	const toggleSchedule = useCallback( ( enabled ) => {
		setSchedule( enabled );
		apiFetch( {
			path: '/wpepp/v1/settings/security',
			method: 'POST',
			data: { scan_schedule_enabled: enabled },
		} ).catch( () => setSchedule( ! enabled ) );
	}, [] );

	const actOnIssue = useCallback( async ( id, action ) => {
		await apiFetch( { path: `/wpepp/v1/scan/issues/${ id }/${ action }`, method: 'POST' } ).catch( () => {} );
		loadIssues();
	}, [ loadIssues ] );

	const handleSyncSignatures = useCallback( async () => {
		setSyncingSigs( true );
		setSyncNotice( null );
		try {
			await apiFetch( {
				path: '/wpepp/v1/scan/signatures/sync',
				method: 'POST',
			} );
			setSyncNotice( { status: 'success', message: __( 'Malware signatures synchronized successfully.', 'wp-edit-password-protected' ) } );
			loadStatus();
		} catch ( err ) {
			setSyncNotice( { status: 'warning', message: err.message || __( 'Could not reach signature feed server. Built-in signatures remain active.', 'wp-edit-password-protected' ) } );
		} finally {
			setSyncingSigs( false );
		}
	}, [ loadStatus ] );

	if ( status === null ) {
		return <Spinner />;
	}

	return (
		<div className="wpepp-scanner">
			<h3>{ __( 'Scanner', 'wp-edit-password-protected' ) }</h3>

			{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }
			{ syncNotice && (
				<Notice
					status={ syncNotice.status }
					isDismissible={ true }
					onDismiss={ () => setSyncNotice( null ) }
				>
					{ syncNotice.message }
				</Notice>
			) }

			<Card
				title={ __( 'Scan Settings', 'wp-edit-password-protected' ) }
				subtitle={
					status.running
						? STAGE_LABELS[ status.stage_index ] ?? status.stage
						: (
							status.last_scan
								? sprintf(
									/* translators: %s: datetime of last scan */
									__( 'Last scan: %s · %d open findings', 'wp-edit-password-protected' ),
									status.last_scan,
									status.issues_new
								)
								: __( 'Never scanned.', 'wp-edit-password-protected' )
						)
				}
				action={
					<>
						{ status.running ? (
							<Button variant="secondary" isDestructive onClick={ stopScan }>
								{ __( 'Stop', 'wp-edit-password-protected' ) }
							</Button>
						) : (
							<>
								<Button
									variant="secondary"
									onClick={ () => startScan( 'full' ) }
									disabled={ !! status.running }
								>
									{ __( 'Full Scan', 'wp-edit-password-protected' ) }
								</Button>
								<Button
									variant="primary"
									onClick={ () => startScan( 'quick' ) }
									isBusy={ !! status.running }
									disabled={ !! status.running }
								>
									{ __( 'Quick Scan', 'wp-edit-password-protected' ) }
								</Button>
							</>
						) }
					</>
				}
			>
				{ status.running && (
					<div className="wpepp-scanner__progress">
						<div className="wpepp-scanner__progress-top">
							<span className="wpepp-scanner__progress-label">
								{ STAGE_LABELS[ status.stage_index ] ?? status.stage }
							</span>
							<span className="wpepp-scanner__progress-pct">
								{ Math.round( ( status.stage_index / status.stage_total ) * 100 ) }%
							</span>
						</div>
						<div
							className="wpepp-scanner__progressbar-track"
							role="progressbar"
							aria-valuenow={ Math.round( ( status.stage_index / status.stage_total ) * 100 ) }
							aria-valuemin="0"
							aria-valuemax="100"
						>
							<div
								className="wpepp-scanner__progressbar-fill"
								style={ {
									width: `${ Math.max( 4, Math.round( ( status.stage_index / status.stage_total ) * 100 ) ) }%`,
								} }
							/>
						</div>
						<div className="wpepp-scanner__stages" aria-hidden="true" style={ { marginBottom: 16 } }>
							{ [
								__( 'Files', 'wp-edit-password-protected' ),
								__( 'Checksums', 'wp-edit-password-protected' ),
								__( 'Options', 'wp-edit-password-protected' ),
								__( 'Users', 'wp-edit-password-protected' ),
							].map( ( label, i ) => (
								<span
									key={ label }
									className={ `wpepp-scanner__stage${ i < status.stage_index ? ' is-done' : '' }${ i === status.stage_index ? ' is-current' : '' }` }
								>
									{ label }
								</span>
							) ) }
						</div>
						<p className="wpepp-scanner__meta" style={ { marginTop: 16, marginBottom: 0 } }>
							{ sprintf(
								/* translators: 1: files scanned, 2: findings so far */
								__( '%1$d files scanned · %2$d findings', 'wp-edit-password-protected' ),
								status.scanned,
								status.flagged
							) }
						</p>
					</div>
				) }

				<div className="wpepp-scanner__controls" style={ { marginTop: 20 } }>
					<ToggleControl
						label={ __( 'Daily automatic scan', 'wp-edit-password-protected' ) }
						checked={ schedule }
						onChange={ toggleSchedule }
					/>
				</div>

				<div
					className="wpepp-scanner__signatures-bar"
					style={ {
						marginTop: 20,
						paddingTop: 16,
						borderTop: '1px solid var(--wpepp-border)',
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'space-between',
						flexWrap: 'wrap',
						gap: 12,
					} }
				>
					<div>
						<strong>{ __( 'Malware Signatures:', 'wp-edit-password-protected' ) }</strong>{ ' ' }
						<span style={ { color: 'var(--wpepp-text-2)' } }>
							{ sprintf(
								__( '%d active detection patterns', 'wp-edit-password-protected' ),
								status.signatures?.total || 14
							) }
						</span>
						{ !! status.signatures?.last_sync && (
							<span style={ { marginLeft: 8, fontSize: '12px', color: 'var(--wpepp-text-3)' } }>
								({ sprintf( __( 'Last sync: %s', 'wp-edit-password-protected' ), status.signatures.last_sync ) })
							</span>
						) }
					</div>
					<Button
						variant="secondary"
						isSmall
						isBusy={ syncingSigs }
						disabled={ syncingSigs || !! status.running }
						onClick={ handleSyncSignatures }
					>
						{ __( 'Check for Signature Updates', 'wp-edit-password-protected' ) }
					</Button>
				</div>
			</Card>

			<Card
				title={ __( 'Scan Results', 'wp-edit-password-protected' ) }
				action={
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						value={ filter }
						onChange={ ( val ) => {
							setFilter( val );
							setPage( 1 );
						} }
						options={ [
							{ value: '', label: __( 'All issues', 'wp-edit-password-protected' ) },
							{ value: 'new', label: __( 'Open', 'wp-edit-password-protected' ) },
							{ value: 'ignored', label: __( 'Ignored', 'wp-edit-password-protected' ) },
							{ value: 'fixed', label: __( 'Resolved', 'wp-edit-password-protected' ) },
						] }
					/>
				}
				padded={ false }
			>
				{ issues === null ? (
					<div className="wpepp-card-sec__pad"><Spinner /></div>
				) : issues.length === 0 ? (
					<div className="wpepp-card-sec__pad"><p>✅ { __( 'No issues found.', 'wp-edit-password-protected' ) }</p></div>
				) : (
				<>
				<table className="wpepp-table wpepp-blocks__table widefat striped">
					<thead>
						<tr>
							<th>{ __( 'Severity', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Type', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Path / Object', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Actions', 'wp-edit-password-protected' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ issues.slice( ( page - 1 ) * PAGE_SIZE, page * PAGE_SIZE ).map( ( issue ) => (
							<tr key={ issue.id }>
								<td>
									<span className={ `wpepp-sev ${ SEVERITY[ issue.severity ]?.cls || '' }` }>
										{ SEVERITY[ issue.severity ]?.label || issue.severity }
									</span>
								</td>
								<td>{ KIND_LABELS[ issue.kind ] || issue.kind }</td>
								<td className="wpepp-scanner__path"><code>{ issue.object_path }</code></td>
								<td>
									{ issue.status === 'new' ? (
										<>
											<Button variant="tertiary" onClick={ () => actOnIssue( issue.id, 'resolve' ) }>
												{ __( 'Resolve', 'wp-edit-password-protected' ) }
											</Button>
											<Button variant="tertiary" onClick={ () => actOnIssue( issue.id, 'ignore' ) }>
												{ __( 'Ignore', 'wp-edit-password-protected' ) }
											</Button>
										</>
									) : (
										<em>{ issue.status }</em>
									) }
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
				<Pagination
					currentPage={ page }
					totalItems={ issues.length }
					pageSize={ PAGE_SIZE }
					onPageChange={ setPage }
					itemName={ __( 'findings', 'wp-edit-password-protected' ) }
				/>
				</>
			) }
			</Card>
		</div>
	);
};

export default Scanner;
