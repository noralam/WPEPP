/**
 * Scanner — run integrity + malware scans, review findings.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	Button,
	SelectControl,
	ToggleControl,
	Notice,
	ProgressBar,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import Card from '../../components/Card';
import Pagination from '../../components/Pagination';
import { TableSkeleton } from '../../components/Skeleton';
import ProBadge from '../../components/ProBadge';

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

const SCAN_STAGES = [
	{ id: 'files', label: __( 'Files', 'wp-edit-password-protected' ) },
	{ id: 'checksums', label: __( 'Checksums', 'wp-edit-password-protected' ) },
	{ id: 'options', label: __( 'Options', 'wp-edit-password-protected' ) },
	{ id: 'users', label: __( 'Users', 'wp-edit-password-protected' ) },
];

const getFixRecommendation = ( issue ) => {
	const path = issue.object_path || '';
	if ( issue.kind === 'malware' ) {
		if ( path.includes( 'wp-includes' ) || path.includes( 'wp-admin' ) ) {
			return __( 'WordPress Core file — Click "Ignore" if this is an unmodified core file, or restore via Dashboard > Updates.', 'wp-edit-password-protected' );
		}
		if ( path.includes( 'vendor' ) || path.includes( 'node_modules' ) ) {
			return __( 'Vendor library package — Click "Ignore" if part of a legitimate Composer/npm dependency.', 'wp-edit-password-protected' );
		}
		return __( 'Suspicious pattern detected — Inspect file content to remove malicious code or delete if unknown.', 'wp-edit-password-protected' );
	}
	if ( issue.kind === 'core_modified' ) {
		return __( 'Official core file modified — Reinstall WordPress from Dashboard > Updates to restore original clean code.', 'wp-edit-password-protected' );
	}
	if ( issue.kind === 'core_unknown' ) {
		return __( 'Unrecognized PHP file inside core directory — Delete this file if not intentionally added.', 'wp-edit-password-protected' );
	}
	if ( issue.kind === 'plugins_unverified' || issue.kind === 'themes_unverified' ) {
		return __( 'Custom or premium extension not in WordPress.org directory — Click "Ignore" to whitelist your legitimate theme/plugin.', 'wp-edit-password-protected' );
	}
	if ( issue.kind === 'missing_file' ) {
		return __( 'Missing WordPress file — Reinstall WordPress from Dashboard > Updates to restore it.', 'wp-edit-password-protected' );
	}
	if ( issue.kind === 'recent_admin' ) {
		return __( 'New administrator account — Verify this user in WordPress Users menu.', 'wp-edit-password-protected' );
	}
	if ( issue.kind === 'suspicious_option' ) {
		return __( 'Suspicious database option value — Review setting in WordPress admin.', 'wp-edit-password-protected' );
	}
	return __( 'Review this item and click Resolve once verified.', 'wp-edit-password-protected' );
};

const Scanner = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );
	const [ status, setStatus ] = useState( null );
	const [ issues, setIssues ] = useState( null );
	const [ filter, setFilter ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ schedule, setSchedule ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ syncingSigs, setSyncingSigs ] = useState( false );
	const [ syncNotice, setSyncNotice ] = useState( null );
	const [ scanResult, setScanResult ] = useState( null );

	const computeScanProgress = useCallback( ( st ) => {
		if ( ! st ) return 0;
		if ( ! st.running ) return 100;
		const stageIdx = st.stage_index ?? 0;
		if ( stageIdx === 0 ) {
			const scanned = st.scanned || 0;
			const targetFiles = st.profile === 'quick' ? 600 : 12000;
			return Math.min( 24, Math.max( 3, Math.round( ( scanned / targetFiles ) * 24 ) ) );
		}
		if ( stageIdx === 1 ) return 25;
		if ( stageIdx === 2 ) return 50;
		if ( stageIdx === 3 ) return 75;
		return 90;
	}, [] );

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

	// Drive scan sequentially while running without lock contention or overlapping slices.
	useEffect( () => {
		let isMounted = true;
		let timerId = null;

		const runTickLoop = async () => {
			if ( ! isMounted ) {
				return;
			}
			try {
				const res = await apiFetch( { path: '/wpepp/v1/scan/tick', method: 'POST' } );
				if ( ! isMounted ) {
					return;
				}
				if ( res ) {
					setStatus( res );
					if ( ! res.running ) {
						setScanResult( {
							scanned: res.scanned,
							flagged: res.flagged,
							issuesNew: res.issues_new,
							time: new Date().toLocaleTimeString( [], { hour: '2-digit', minute: '2-digit', second: '2-digit' } ),
						} );
						loadIssues();
						return;
					}
				}
			} catch {
				if ( ! isMounted ) {
					return;
				}
				const res = await loadStatus();
				if ( ! isMounted ) {
					return;
				}
				if ( res && ! res.running ) {
					setScanResult( {
						scanned: res.scanned,
						flagged: res.flagged,
						issuesNew: res.issues_new,
						time: new Date().toLocaleTimeString( [], { hour: '2-digit', minute: '2-digit', second: '2-digit' } ),
					} );
					loadIssues();
					return;
				}
			}

			if ( isMounted ) {
				timerId = setTimeout( runTickLoop, 500 );
			}
		};

		if ( status?.running ) {
			timerId = setTimeout( runTickLoop, 100 );
		}

		return () => {
			isMounted = false;
			if ( timerId ) {
				clearTimeout( timerId );
			}
		};
	}, [ status?.running, loadStatus, loadIssues ] );

	const startScan = useCallback( async ( profile ) => {
		setError( '' );
		setScanResult( null );

		try {
			await apiFetch( {
				path: '/wpepp/v1/scan/start',
				method: 'POST',
				data: { profile },
			} );
			const res = await loadStatus();
			if ( res ) {
				setStatus( res );
			}
		} catch ( err ) {
			setError( err.message || __( 'Scan failed to start.', 'wp-edit-password-protected' ) );
		}
	}, [ loadStatus ] );

	const stopScan = useCallback( async () => {
		setScanResult( null );
		await apiFetch( { path: '/wpepp/v1/scan/stop', method: 'POST' } ).catch( () => {} );
		const res = await loadStatus();
		if ( res ) {
			setStatus( res );
		}
	}, [ loadStatus ] );

	const toggleSchedule = useCallback( ( enabled ) => {
		setSchedule( enabled );
		apiFetch( {
			path: '/wpepp/v1/settings/security',
			method: 'POST',
			data: { scan_schedule_enabled: enabled },
		} ).catch( () => setSchedule( ! enabled ) );
	}, [] );

	const actOnIssue = useCallback( async ( id, action, pathName = '' ) => {
		if ( action === 'delete_file' ) {
			/* eslint-disable no-alert */
			const confirmed = window.confirm(
				/* translators: %s: file path */
				__( 'Are you sure you want to permanently delete this file from your server?\n\n' + pathName, 'wp-edit-password-protected' )
			);
			if ( ! confirmed ) {
				return;
			}
			/* eslint-enable no-alert */
		}
		try {
			await apiFetch( { path: `/wpepp/v1/scan/issues/${ id }/${ action }`, method: 'POST' } );
			loadIssues();
			loadStatus();
		} catch ( err ) {
			setError( err.message || __( 'Action failed.', 'wp-edit-password-protected' ) );
		}
	}, [ loadIssues, loadStatus ] );

	const handleBulkAction = useCallback( async ( action ) => {
		try {
			await apiFetch( {
				path: '/wpepp/v1/scan/issues/bulk',
				method: 'POST',
				data: { action },
			} );
			loadIssues();
			loadStatus();
		} catch ( err ) {
			setError( err.message || __( 'Could not execute bulk action.', 'wp-edit-password-protected' ) );
		}
	}, [ loadIssues, loadStatus ] );

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
		return <TableSkeleton rows={ 6 } columns={ 4 } />;
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
				{ ( status.running || scanResult ) && (
					<div className={ `wpepp-scanner__progress${ scanResult ? ' is-completed' : '' }` }>
						{ scanResult && ( () => {
							const activeThreats = Math.max( scanResult.flagged || 0, scanResult.issuesNew || 0 );
							const isClean = activeThreats === 0;

							return (
								<div className={ `wpepp-scan-summary-banner ${ isClean ? 'is-clean' : 'is-warning' }` }>
									<div className="wpepp-scan-summary-banner__icon">
										{ isClean ? '🛡️' : '⚠️' }
									</div>
									<div className="wpepp-scan-summary-banner__text">
										<div className="wpepp-scan-summary-banner__title">
											{ isClean
												? __( 'Scan Complete — Site is 100% Clean!', 'wp-edit-password-protected' )
												: sprintf(
													/* translators: %d: number of newly detected threats */
													__( 'Scan Complete — %d Active Threat(s) Flagged!', 'wp-edit-password-protected' ),
													activeThreats
												) }
										</div>
										<div className="wpepp-scan-summary-banner__desc">
											{ isClean
												? sprintf(
													/* translators: %d: files count */
													__( 'All %d files and objects scanned in this run are secure. No new threats or vulnerabilities were detected.', 'wp-edit-password-protected' ),
													scanResult.scanned
												)
												: __( 'Review the flagged items in the Scan Results table below to inspect, resolve, or ignore them.', 'wp-edit-password-protected' ) }
										</div>
									</div>
									<button
										type="button"
										className="wpepp-scan-summary-banner__close"
										onClick={ () => setScanResult( null ) }
										title={ __( 'Dismiss', 'wp-edit-password-protected' ) }
									>
										✕
									</button>
								</div>
							);
						} )() }

						<div className="wpepp-scanner__progress-top">
							<span className="wpepp-scanner__progress-label">
								{ scanResult
									? __( 'All Security Checks Finished', 'wp-edit-password-protected' )
									: ( STAGE_LABELS[ status.stage_index ] ?? status.stage ) }
							</span>
							<span className="wpepp-scanner__progress-pct">
								{ scanResult ? '100%' : `${ computeScanProgress( status ) }%` }
							</span>
						</div>
						<div
							className="wpepp-scanner__progressbar-track"
							role="progressbar"
							aria-valuenow={ scanResult ? 100 : computeScanProgress( status ) }
							aria-valuemin="0"
							aria-valuemax="100"
						>
							<div
								className={ `wpepp-scanner__progressbar-fill ${ scanResult ? 'is-finished' : '' }` }
								style={ {
									width: `${ scanResult ? 100 : Math.max( 4, computeScanProgress( status ) ) }%`,
								} }
							/>
						</div>
						<div className="wpepp-scanner__stages-card-grid" aria-hidden="true">
							{ SCAN_STAGES.map( ( stageItem, i ) => {
								const isDone = !! scanResult || i < status.stage_index;
								const isCurrent = ! scanResult && i === status.stage_index;
								const badgeStatus = isDone ? 'completed' : ( isCurrent ? 'progress' : 'upcoming' );
								const badgeIcon = isDone ? '✓' : ( isCurrent ? '⏳' : '○' );
								const badgeLabel = isDone
									? __( 'Completed', 'wp-edit-password-protected' )
									: ( isCurrent
										? __( 'In Progress', 'wp-edit-password-protected' )
										: __( 'Upcoming', 'wp-edit-password-protected' ) );

								return (
									<div
										key={ stageItem.id }
										className={ `wpepp-scanner-stage-pill is-${ badgeStatus }` }
									>
										<div className="wpepp-scanner-stage-pill__header">
											<span className="wpepp-scanner-stage-pill__icon">{ badgeIcon }</span>
											<span className="wpepp-scanner-stage-pill__name">{ stageItem.label }</span>
										</div>
										<span className={ `wpepp-scanner-stage-badge is-${ badgeStatus }` }>
											{ badgeLabel }
										</span>
									</div>
								);
							} ) }
						</div>
						<div className="wpepp-scanner__meta-bar">
							<span>
								📁 { sprintf(
									/* translators: %d: files scanned */
									__( '%d files scanned', 'wp-edit-password-protected' ),
									scanResult ? scanResult.scanned : status.scanned
								) }
							</span>
							<span>·</span>
							<span style={ { color: ( ( scanResult ? scanResult.flagged : status.flagged ) > 0 ? 'var(--wpepp-danger)' : 'inherit' ) } }>
								🛡️ { sprintf(
									/* translators: %d: findings flagged */
									__( '%d findings flagged', 'wp-edit-password-protected' ),
									scanResult ? scanResult.flagged : status.flagged
								) }
							</span>
							{ scanResult && (
								<>
									<span>·</span>
									<span style={ { color: 'var(--wpepp-text-3)' } }>
										⏱️ { sprintf( __( 'Finished at %s', 'wp-edit-password-protected' ), scanResult.time ) }
									</span>
								</>
							) }
						</div>
					</div>
				) }

				<div className="wpepp-scanner__controls" style={ { marginTop: 20 } }>
					<ToggleControl
						label={
							<span>
								{ __( 'Daily automatic scan', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						help={ ! isPro ? __( 'Requires WPEPP Pro for automated daily scans.', 'wp-edit-password-protected' ) : null }
						checked={ isPro && !! schedule }
						disabled={ ! isPro }
						onChange={ isPro ? toggleSchedule : undefined }
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
					<div style={ { display: 'flex', alignItems: 'center', gap: 8 } }>
						<span style={ { fontSize: '16px' } }>🛡️</span>
						<div>
							<strong style={ { color: 'var(--wpepp-text)' } }>
								{ __( 'Antivirus Definition Database:', 'wp-edit-password-protected' ) }
							</strong>{ ' ' }
							<span style={ { color: 'var(--wpepp-text-2)', fontSize: '13px' } }>
								{ sprintf(
									/* translators: %d: rule count */
									__( '%d security definition rules active', 'wp-edit-password-protected' ),
									status.signatures?.total || 23
								) }
							</span>
							{ !! status.signatures?.last_sync && (
								<span style={ { marginLeft: 8, fontSize: '12px', color: 'var(--wpepp-text-3)' } }>
									({ sprintf( __( 'Updated: %s', 'wp-edit-password-protected' ), status.signatures.last_sync ) })
								</span>
							) }
						</div>
					</div>
					<Button
						variant="secondary"
						isSmall
						isBusy={ syncingSigs }
						disabled={ ! isPro || syncingSigs || !! status.running }
						onClick={ isPro ? handleSyncSignatures : undefined }
					>
						{ __( 'Check for Signature Updates', 'wp-edit-password-protected' ) }
						{ ! isPro && <ProBadge /> }
					</Button>
				</div>
			</Card>

			<Card
				title={ __( 'Scan Results', 'wp-edit-password-protected' ) }
				action={
					<div style={ { display: 'flex', alignItems: 'center', gap: 8, flexWrap: 'wrap' } }>
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
						{ issues && issues.length > 0 && (
							<>
								<Button
									variant="secondary"
									isSmall
									onClick={ () => handleBulkAction( 'resolve_all' ) }
									title={ __( 'Mark all open issues as resolved', 'wp-edit-password-protected' ) }
								>
									{ __( 'Resolve All', 'wp-edit-password-protected' ) }
								</Button>
								<Button
									variant="secondary"
									isSmall
									onClick={ () => handleBulkAction( 'ignore_all' ) }
									title={ __( 'Ignore all open issues', 'wp-edit-password-protected' ) }
								>
									{ __( 'Ignore All', 'wp-edit-password-protected' ) }
								</Button>
								<Button
									variant="tertiary"
									isDestructive
									isSmall
									onClick={ () => handleBulkAction( 'clear_all' ) }
									title={ __( 'Clear issues table', 'wp-edit-password-protected' ) }
								>
									{ __( 'Clear Table', 'wp-edit-password-protected' ) }
								</Button>
							</>
						) }
					</div>
				}
				padded={ false }
			>
				{ issues === null ? (
					<div className="wpepp-card-sec__pad"><TableSkeleton rows={ 5 } columns={ 5 } /></div>
				) : issues.length === 0 ? (
					<div className="wpepp-card-sec__pad"><p>✅ { __( 'No open issues found. Everything is secure!', 'wp-edit-password-protected' ) }</p></div>
				) : (
				<>
				<table className="wpepp-table wpepp-scanner__table">
					<thead>
						<tr>
							<th>{ __( 'Severity', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Type', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Path / Object & Recommendation', 'wp-edit-password-protected' ) }</th>
							<th className="wpepp-scanner__actions-th">{ __( 'Actions', 'wp-edit-password-protected' ) }</th>
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
								<td className="wpepp-scanner__path">
									<div style={ { fontWeight: 600, marginBottom: 4 } }>
										<code>{ issue.object_path }</code>
									</div>
									{ issue.detail?.name && (
										<div style={ { fontSize: '12px', color: 'var(--wpepp-text-2)', marginBottom: 2 } }>
											<strong>{ __( 'Detection:', 'wp-edit-password-protected' ) }</strong> { issue.detail.name }
										</div>
									) }
									<div style={ { fontSize: '11.5px', color: 'var(--wpepp-text-3)', fontStyle: 'italic', marginTop: 3 } }>
										💡 <strong>{ __( 'How to fix:', 'wp-edit-password-protected' ) }</strong> { getFixRecommendation( issue ) }
									</div>
								</td>
								<td className="wpepp-scanner__actions-cell">
									{ issue.status === 'new' ? (
										<div className="wpepp-scanner__actions">
											{ ( issue.kind === 'malware' || issue.kind === 'core_unknown' ) ? (
												<Button
													variant="secondary"
													isDestructive
													isSmall
													className="wpepp-scanner__btn-delete"
													onClick={ () => actOnIssue( issue.id, 'delete_file', issue.object_path ) }
													title={ __( 'Permanently delete this file from server', 'wp-edit-password-protected' ) }
												>
													{ __( 'Delete File', 'wp-edit-password-protected' ) }
												</Button>
											) : (
												<Button
													variant="tertiary"
													isSmall
													className="wpepp-scanner__btn-resolve"
													onClick={ () => actOnIssue( issue.id, 'resolve' ) }
												>
													{ __( 'Resolve', 'wp-edit-password-protected' ) }
												</Button>
											) }
											<Button
												variant="tertiary"
												isSmall
												className="wpepp-scanner__btn-ignore"
												onClick={ () => actOnIssue( issue.id, 'ignore' ) }
												title={ __( 'Ignore and whitelist this item', 'wp-edit-password-protected' ) }
											>
												{ __( 'Ignore', 'wp-edit-password-protected' ) }
											</Button>
										</div>
									) : issue.status === 'ignored' ? (
										<div className="wpepp-scanner__actions">
											<em className="wpepp-scanner__status-badge" style={ { marginRight: 8 } }>{ issue.status }</em>
											<Button
												variant="tertiary"
												isSmall
												onClick={ () => actOnIssue( issue.id, 'unignore' ) }
												title={ __( 'Restore to open issues', 'wp-edit-password-protected' ) }
											>
												{ __( 'Unignore', 'wp-edit-password-protected' ) }
											</Button>
										</div>
									) : (
										<em className="wpepp-scanner__status-badge">{ issue.status }</em>
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
