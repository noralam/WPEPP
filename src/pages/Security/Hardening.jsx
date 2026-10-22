/**
 * Hardening — security headers, server tools, diagnostics.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback, useMemo, useRef } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	SelectControl,
	ToggleControl,
	TextControl,
	TextareaControl,
	Button,
	Spinner,
	Notice,
} from '@wordpress/components';
import Card from '../../components/Card';
import ToggleCard from '../../components/ToggleCard';
import apiFetch from '@wordpress/api-fetch';
import { SECURITY_DEFAULTS } from '../../utils/defaults';
import { useSaveBar } from '../../components/SaveBar';

const STATUS_CLS = {
	ok: '#00a32a',
	warn: '#dba617',
	fail: '#d63638',
	info: '#2271b1',
};

const CSP_TEMPLATES = {
	default:
		"default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:",
	blog:
		"default-src 'self'; script-src 'self' 'unsafe-inline' https://securepubads.g.doubleclick.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:",
	shop:
		"default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.stripe.com https://www.paypal.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; frame-src https://js.stripe.com https://www.paypal.com; font-src 'self' data:",
};

const Hardening = () => {
	const { settings, isSaving } = useSelect( ( select ) => ( {
		settings: select( 'wpepp/settings' ).getSettings(),
		isSaving: select( 'wpepp/settings' ).isSaving(),
	} ) );
	const s = useMemo( () => ( { ...SECURITY_DEFAULTS, ...( settings?.security || {} ) } ), [ settings ] );

	const [ status, setStatus ] = useState( null );
	const [ diag, setDiag ] = useState( null );
	const [ diagBusy, setDiagBusy ] = useState( false );
	const [ busyTool, setBusyTool ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ notice, setNotice ] = useState( '' );
	const [ savedFlash, setSavedFlash ] = useState( '' );

	const loadStatus = useCallback( () => {
		apiFetch( { path: '/wpepp/v1/hardening/status' } )
			.then( setStatus )
			.catch( ( err ) => setError( err.message || __( 'Status failed.', 'wp-edit-password-protected' ) ) );
	}, [] );

	useEffect( loadStatus, [ loadStatus ] );

	const runDiagnostics = useCallback( () => {
		setDiag( null );
		setDiagBusy( true );
		setError( '' );

		apiFetch( { path: '/wpepp/v1/diagnostics' } )
			.then( setDiag )
			.catch( ( err ) => setError( err.message ) )
			.finally( () => setDiagBusy( false ) );
	}, [] );

	const toggleTool = useCallback( async ( tool, value ) => {
		setBusyTool( tool );
		setError( '' );
		setNotice( '' );

		try {
			const res = await apiFetch( {
				path: '/wpepp/v1/hardening/toggle',
				method: 'POST',
				data: { tool, value },
			} );

			if ( res.manual && res.snippet ) {
				setNotice(
					sprintf(
						/* translators: %s: nginx config snippet */
						__( 'Automatic configuration is unavailable on this server. Add this manually:\n%s', 'wp-edit-password-protected' ),
						res.snippet
					)
				);
			}

			// The toggle response already carries fresh tool status — skip the
			// extra status roundtrip (file-backed tools are slow to re-read).
			if ( res.tools ) {
				setStatus( ( prev ) => ( prev ? { ...prev, tools: res.tools } : prev ) );
			} else {
				loadStatus();
			}
		} catch ( err ) {
			setError( err.message || __( 'Toggle failed.', 'wp-edit-password-protected' ) );
			loadStatus();
		}

		setBusyTool( '' );
	}, [ loadStatus ] );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );

	const update = useCallback( ( key, value ) => updateSetting( 'security', key, value ), [ updateSetting ] );
	const saveHeaders = useCallback( () => saveSettings( 'security', s ), [ saveSettings, s ] );
	const resetHeaders = useCallback( () => resetSettings( 'security' ), [ resetSettings ] );

	useSaveBar( saveHeaders, resetHeaders );

	// Surface a transient success flash whenever a save round completes.
	const prevSaving = useRef( false );
	const errorRef = useRef( '' );
	errorRef.current = error;

	useEffect( () => {
		if ( prevSaving.current && ! isSaving && ! errorRef.current ) {
			setSavedFlash( Date.now() );
		}
		prevSaving.current = isSaving;
	}, [ isSaving ] );

	useEffect( () => {
		if ( ! savedFlash ) {
			return undefined;
		}
		const t = setTimeout( () => setSavedFlash( '' ), 4000 );
		return () => clearTimeout( t );
	}, [ savedFlash ] );

	if ( status === null ) {
		return <Spinner />;
	}

	return (
		<div className="wpepp-hardening">
			<h3>{ __( 'Hardening', 'wp-edit-password-protected' ) }</h3>

			{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }
			{ notice && <Notice status="info" isDismissible={ false }><pre style={ { whiteSpace: 'pre-wrap', margin: 0 } }>{ notice }</pre></Notice> }
			{ !! savedFlash && ! error && (
				<Notice status="success" isDismissible={ false }>
					{ __( 'Settings saved.', 'wp-edit-password-protected' ) }
				</Notice>
			) }

			<Card title={ __( 'Server tools', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={
							<span style={ { display: 'inline-flex', alignItems: 'center', gap: 6 } }>
								{ __( 'Block PHP execution in uploads', 'wp-edit-password-protected' ) }
								{ busyTool === 'uploads_php' && <Spinner style={ { margin: 0 } } /> }
							</span>
						}
						description={ __( 'Kills webshells dropped into /uploads — the most common infection vector.', 'wp-edit-password-protected' ) }
						help={ ! status.tools.is_apache ? __( 'Apache not detected — use the manual snippet below.', 'wp-edit-password-protected' ) : undefined }
						checked={ !! status.tools.uploads_php_blocked }
						disabled={ busyTool === 'uploads_php' }
						onChange={ ( v ) => toggleTool( 'uploads_php', v ) }
					/>
					<ToggleCard
						title={
							<span style={ { display: 'inline-flex', alignItems: 'center', gap: 6 } }>
								{ __( 'Disable directory listing', 'wp-edit-password-protected' ) }
								{ busyTool === 'dirlist' && <Spinner style={ { margin: 0 } } /> }
							</span>
						}
						description={ __( 'Adds "Options -Indexes" so folder contents are never exposed.', 'wp-edit-password-protected' ) }
						checked={ !! status.tools.dirlisting_disabled }
						disabled={ busyTool === 'dirlist' }
						onChange={ ( v ) => toggleTool( 'dirlist', v ) }
					/>
					<ToggleCard
						title={
							<span style={ { display: 'inline-flex', alignItems: 'center', gap: 6 } }>
								{ __( 'Disable plugin/theme file editor', 'wp-edit-password-protected' ) }
								{ busyTool === 'file_edit' && <Spinner style={ { margin: 0 } } /> }
							</span>
						}
						description={ __( "Writes DISALLOW_FILE_EDIT to wp-config.php. Recommended — one typo in the editor takes down the whole site.", 'wp-edit-password-protected' ) }
						help={ status.tools.file_edit_disabled ? __( 'Current state: already disabled', 'wp-edit-password-protected' ) : __( 'Current state: enabled', 'wp-edit-password-protected' ) }
						checked={ !! status.tools.file_edit_disabled }
						disabled={ busyTool === 'file_edit' }
						onChange={ ( v ) => toggleTool( 'file_edit', v ) }
					/>
				</div>

				{ ! status.tools.is_apache && (
					<details style={ { marginTop: 14 } }>
						<summary>{ __( 'nginx equivalent snippet', 'wp-edit-password-protected' ) }</summary>
						<pre style={ { background: 'var(--wpepp-surface)', padding: 12, borderRadius: 6, fontSize: 12, marginTop: 8 } }>
							{ status.tools.nginx_snippet }
						</pre>
					</details>
				) }
			</Card>

			{ status.tools?.permissions && (
				<Card
					title={ __( 'Critical Files Permission & Lock', 'wp-edit-password-protected' ) }
					subtitle={ __( 'Inspect file permissions of essential WordPress configuration and directory roots.', 'wp-edit-password-protected' ) }
					padded={ false }
				>
					<table className="wpepp-blocks__table widefat" style={ { border: 0 } }>
						<thead>
							<tr>
								<th>{ __( 'Target', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Current Permission', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Recommended', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Status', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Details', 'wp-edit-password-protected' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ Object.values( status.tools.permissions ).map( ( item ) => (
								<tr key={ item.key }>
									<td>
										<strong>{ item.label }</strong>
										<div style={ { fontSize: '11px', color: 'var(--wpepp-text-3)' } }><code>{ item.path }</code></div>
									</td>
									<td>
										<code>{ item.exists ? ( item.perms_octal || '—' ) : __( 'Missing', 'wp-edit-password-protected' ) }</code>
										{ item.writable && (
											<span style={ { marginLeft: 6, fontSize: '11px', color: '#dc2626' } }>
												({ __( 'Writable', 'wp-edit-password-protected' ) })
											</span>
										) }
									</td>
									<td><code>{ item.recommended }</code></td>
									<td>
										<span
											style={ {
												display: 'inline-block',
												fontSize: '11px',
												fontWeight: 700,
												textTransform: 'uppercase',
												padding: '2px 8px',
												borderRadius: 4,
												background:
													item.status === 'ok'
														? '#dcfce7'
														: item.status === 'warn'
															? '#fef3c7'
															: item.status === 'fail'
																? '#fee2e2'
																: 'var(--wpepp-hover-soft)',
												color:
													item.status === 'ok'
														? '#15803d'
														: item.status === 'warn'
															? '#b45309'
															: item.status === 'fail'
																? '#b91c1c'
																: 'var(--wpepp-text-2)',
											} }
										>
											{ item.status === 'ok'
												? __( 'Protected', 'wp-edit-password-protected' )
												: item.status === 'warn'
													? __( 'Attention', 'wp-edit-password-protected' )
													: item.status === 'fail'
														? __( 'At Risk', 'wp-edit-password-protected' )
														: __( 'Notice', 'wp-edit-password-protected' ) }
										</span>
									</td>
									<td style={ { fontSize: '12px', color: 'var(--wpepp-text-2)' } }>{ item.message }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</Card>
			) }

			<Card
				title={ __( 'Security response headers', 'wp-edit-password-protected' ) }
				subtitle={ __( 'Header changes take effect on the front-end after saving.', 'wp-edit-password-protected' ) }
				action={
					<Button
						variant="primary"
						onClick={ saveHeaders }
						isBusy={ isSaving }
						disabled={ isSaving }
					>
						{ isSaving
							? __( 'Saving…', 'wp-edit-password-protected' )
							: __( 'Save Headers', 'wp-edit-password-protected' ) }
					</Button>
				}
			>
				<div className="wpepp-form-grid">
					<SelectControl
						label={ __( 'X-Frame-Options (clickjacking)', 'wp-edit-password-protected' ) }
						value={ s.hdr_x_frame || 'SAMEORIGIN' }
						onChange={ ( v ) => update( 'hdr_x_frame', v ) }
						options={ [
							{ value: '', label: __( 'Off', 'wp-edit-password-protected' ) },
							{ value: 'SAMEORIGIN', label: 'SAMEORIGIN' },
							{ value: 'DENY', label: 'DENY' },
						] }
					/>
					<SelectControl
						label={ __( 'Referrer-Policy', 'wp-edit-password-protected' ) }
						value={ s.hdr_referrer || 'strict-origin-when-cross-origin' }
						onChange={ ( v ) => update( 'hdr_referrer', v ) }
						options={ [
							{ value: 'strict-origin-when-cross-origin', label: 'strict-origin-when-cross-origin' },
							{ value: 'strict-origin', label: 'strict-origin' },
							{ value: 'same-origin', label: 'same-origin' },
							{ value: 'no-referrer', label: 'no-referrer' },
						] }
					/>

					<div className="wpepp-form-grid__full">
						<div className="wpepp-toggle-grid">
							<ToggleCard
								title={ __( 'X-Content-Type-Options: nosniff', 'wp-edit-password-protected' ) }
								description={ __( 'Stops browsers from MIME-sniffing responses away from the declared type.', 'wp-edit-password-protected' ) }
								checked={ s.hdr_nosniff !== false }
								onChange={ ( v ) => update( 'hdr_nosniff', v ) }
							/>
							<ToggleCard
								title={ __( 'Strict-Transport-Security (HSTS)', 'wp-edit-password-protected' ) }
								description={ __( 'HTTPS only — enable ONLY when the site is fully HTTPS.', 'wp-edit-password-protected' ) }
								checked={ !! s.hdr_hsts }
								onChange={ ( v ) => update( 'hdr_hsts', v ) }
							/>
						</div>
					</div>

					{ s.hdr_hsts && ! status.is_ssl && (
						<div className="wpepp-form-grid__full">
							<Notice status="warning" isDismissible={ false }>
								{ __( 'This site is not served over HTTPS. Browsers ignore HSTS on plain HTTP responses, so this header will not be sent. Switch the site to HTTPS first.', 'wp-edit-password-protected' ) }
							</Notice>
						</div>
					) }

					{ s.hdr_hsts && (
						<div className="wpepp-form-grid__full">
							<div className="wpepp-toggle-grid">
								<ToggleCard
									title={ __( 'includeSubDomains', 'wp-edit-password-protected' ) }
									checked={ s.hdr_hsts_subdomains !== false }
									onChange={ ( v ) => update( 'hdr_hsts_subdomains', v ) }
								/>
								<ToggleCard
									title={ __( 'preload', 'wp-edit-password-protected' ) }
									description={ __( 'Only if you intend to submit to hstspreload.org — hard to undo!', 'wp-edit-password-protected' ) }
									checked={ !! s.hdr_hsts_preload }
									onChange={ ( v ) => update( 'hdr_hsts_preload', v ) }
								/>
							</div>
						</div>
					) }

					<div className="wpepp-form-grid__full">
						<TextControl
							label={ __( 'Permissions-Policy (optional)', 'wp-edit-password-protected' ) }
							help={ __( 'e.g. camera=(), microphone=(), geolocation=()', 'wp-edit-password-protected' ) }
							value={ s.hdr_permissions_policy || '' }
							onChange={ ( v ) => update( 'hdr_permissions_policy', v ) }
						/>
					</div>

					<div className="wpepp-form-grid__full">
						<TextareaControl
							label={ __( 'Content-Security-Policy (report-only)', 'wp-edit-password-protected' ) }
							help={ __( 'Report-only mode logs violations without blocking — safe way to build a real policy.', 'wp-edit-password-protected' ) }
							rows={ 3 }
							value={ s.hdr_csp_report_only || '' }
							onChange={ ( v ) => update( 'hdr_csp_report_only', v ) }
						/>
					</div>

					<div className="wpepp-form-grid__full wpepp-form-actions">
						<Button variant="secondary" onClick={ () => update( 'hdr_csp_report_only', CSP_TEMPLATES.default ) }>
							{ __( 'Default site', 'wp-edit-password-protected' ) }
						</Button>
						<Button variant="secondary" onClick={ () => update( 'hdr_csp_report_only', CSP_TEMPLATES.blog ) }>
							{ __( 'Blog/ads', 'wp-edit-password-protected' ) }
						</Button>
						<Button variant="secondary" onClick={ () => update( 'hdr_csp_report_only', CSP_TEMPLATES.shop ) }>
							{ __( 'WooCommerce', 'wp-edit-password-protected' ) }
						</Button>
					</div>
				</div>
			</Card>

			<Card
				title={ __( 'Diagnostics', 'wp-edit-password-protected' ) }
				action={
					<Button
						variant="secondary"
						onClick={ runDiagnostics }
						isBusy={ diagBusy }
						disabled={ diagBusy }
					>
						{ diagBusy
							? __( 'Running…', 'wp-edit-password-protected' )
							: diag
								? __( 'Run again', 'wp-edit-password-protected' )
								: __( 'Run diagnostics', 'wp-edit-password-protected' ) }
					</Button>
				}
				padded={ false }
			>
				{ diagBusy ? (
					<div className="wpepp-card-sec__pad">
						<p style={ { margin: 0 } }>
							<Spinner />{ ' ' }
							{ __( 'Checking server configuration…', 'wp-edit-password-protected' ) }
						</p>
					</div>
				) : diag ? (
					<table className="wpepp-blocks__table widefat">
						<tbody>
							{ Object.entries( diag ).flatMap( ( [ group, rows ] ) =>
								rows.map( ( row ) => (
									<tr key={ group + row.label }>
										<td style={ { width: 130 } }>
											<span className="wpepp-sev is-info">{ group }</span>
										</td>
										<td>{ row.label }</td>
										<td style={ { color: STATUS_CLS[ row.status ] || 'inherit', fontWeight: 600 } }>
											● { row.status.toUpperCase() }
										</td>
										<td><code style={ { whiteSpace: 'pre-wrap' } }>{ String( row.detail ) }</code></td>
									</tr>
								) )
							) }
						</tbody>
					</table>
				) : (
					<div className="wpepp-card-sec__pad">
						<p style={ { margin: 0, opacity: 0.7 } }>
							{ __( 'Run a quick audit of server-level hardening (headers, PHP execution, directory listing) and live response headers.', 'wp-edit-password-protected' ) }
						</p>
					</div>
				) }
			</Card>
		</div>
	);
};

export default Hardening;
