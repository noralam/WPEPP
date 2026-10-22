/**
 * AuditReportModal — 1-Click Site Health & Security Audit Report.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Modal, Button, Spinner, Notice } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import ScoreRing from './ScoreRing';

const AuditReportModal = ( { onClose } ) => {
	const [ report, setReport ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState( '' );

	useEffect( () => {
		apiFetch( { path: '/wpepp/v1/security/audit-report' } )
			.then( ( data ) => setReport( data ) )
			.catch( ( err ) => setError( err.message || __( 'Failed to generate audit report.', 'wp-edit-password-protected' ) ) )
			.finally( () => setLoading( false ) );
	}, [] );

	const handlePrint = useCallback( () => {
		window.print();
	}, [] );

	const handleExportJson = useCallback( () => {
		if ( ! report ) {
			return;
		}
		const jsonString = `data:text/json;charset=utf-8,${ encodeURIComponent( JSON.stringify( report, null, 2 ) ) }`;
		const downloadAnchor = document.createElement( 'a' );
		const filename = `security-audit-${ ( report.site?.name || 'site' ).toLowerCase().replace( /[^a-z0-9]/g, '-' ) }-${ new Date().toISOString().slice( 0, 10 ) }.json`;
		downloadAnchor.setAttribute( 'href', jsonString );
		downloadAnchor.setAttribute( 'download', filename );
		document.body.appendChild( downloadAnchor );
		downloadAnchor.click();
		downloadAnchor.remove();
	}, [ report ] );

	return (
		<Modal
			title={ __( '1-Click Site Security & Health Audit Report', 'wp-edit-password-protected' ) }
			onRequestClose={ onClose }
			className="wpepp-audit-modal"
			style={ { maxWidth: '820px', width: '95%' } }
		>
			{ loading && (
				<div style={ { padding: '40px 0', textAlign: 'center' } }>
					<Spinner />
					<p style={ { marginTop: 12, color: '#64748b' } }>
						{ __( 'Compiling site health, firewall, scanner, and hardening status…', 'wp-edit-password-protected' ) }
					</p>
				</div>
			) }

			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ error }
				</Notice>
			) }

			{ report && ! loading && (
				<div className="wpepp-audit-report-body" style={ { maxHeight: '72vh', overflowY: 'auto', paddingRight: 4 } }>
					{ /* Header / Overview */ }
					<div
						style={ {
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'space-between',
							flexWrap: 'wrap',
							padding: '16px 20px',
							background: '#f8fafc',
							borderRadius: 8,
							border: '1px solid #e2e8f0',
							marginBottom: 20,
							gap: 16,
						} }
					>
						<div>
							<h2 style={ { margin: '0 0 6px', fontSize: '18px', color: '#0f172a' } }>
								{ report.site?.name || 'WordPress Site' }
							</h2>
							<div style={ { fontSize: '13px', color: '#475569', lineHeight: 1.5 } }>
								<div><strong>{ __( 'URL:', 'wp-edit-password-protected' ) }</strong> { report.site?.url }</div>
								<div><strong>{ __( 'Generated:', 'wp-edit-password-protected' ) }</strong> { report.generated_at } (Local)</div>
								<div>
									<span><strong>{ __( 'WordPress:', 'wp-edit-password-protected' ) }</strong> { report.site?.wp_version }</span>
									<span style={ { margin: '0 8px' } }>·</span>
									<span><strong>{ __( 'PHP:', 'wp-edit-password-protected' ) }</strong> { report.site?.php_version }</span>
									<span style={ { margin: '0 8px' } }>·</span>
									<span><strong>{ __( 'Server:', 'wp-edit-password-protected' ) }</strong> { report.site?.server }</span>
								</div>
							</div>
						</div>

						<div style={ { display: 'flex', alignItems: 'center', gap: 14 } }>
							<ScoreRing
								score={ report.score?.score ?? 0 }
								grade={ report.score?.grade ?? '—' }
							/>
							<div>
								<div style={ { fontSize: '12px', textTransform: 'uppercase', color: '#64748b', fontWeight: 600 } }>
									{ __( 'Security Score', 'wp-edit-password-protected' ) }
								</div>
								<div style={ { fontSize: '20px', fontWeight: 700, color: '#0f172a' } }>
									{ report.score?.score } / 100
								</div>
								<div style={ { fontSize: '12px', color: '#15803d', fontWeight: 600 } }>
									{ sprintf( __( '%d of %d checks passing', 'wp-edit-password-protected' ), report.score?.passed || 0, report.score?.total || 0 ) }
								</div>
							</div>
						</div>
					</div>

					{ /* Key Modules Grid */ }
					<div
						style={ {
							display: 'grid',
							gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))',
							gap: 14,
							marginBottom: 20,
						} }
					>
						{ /* Firewall */ }
						<div style={ { padding: 14, borderRadius: 6, border: '1px solid #e2e8f0', background: '#ffffff' } }>
							<div style={ { fontWeight: 700, fontSize: '13px', marginBottom: 8, display: 'flex', justifyContent: 'space-between' } }>
								<span>🧱 { __( 'Web App Firewall', 'wp-edit-password-protected' ) }</span>
								<span style={ { color: report.firewall?.basic_enabled ? '#15803d' : '#b45309' } }>
									{ report.firewall?.basic_enabled ? __( 'Active', 'wp-edit-password-protected' ) : __( 'Disabled', 'wp-edit-password-protected' ) }
								</span>
							</div>
							<div style={ { fontSize: '12px', color: '#475569', lineHeight: 1.6 } }>
								<div>{ __( 'Active Rules:', 'wp-edit-password-protected' ) } <strong>{ report.firewall?.rule_count || 0 }</strong></div>
								<div>{ __( 'Total Blocks:', 'wp-edit-password-protected' ) } <strong>{ report.firewall?.blocked_total || 0 }</strong></div>
								<div>{ __( 'Extended Mode:', 'wp-edit-password-protected' ) } <strong>{ report.firewall?.extended_enabled ? __( 'Yes', 'wp-edit-password-protected' ) : __( 'Standard', 'wp-edit-password-protected' ) }</strong></div>
							</div>
						</div>

						{ /* Malware Scanner */ }
						<div style={ { padding: 14, borderRadius: 6, border: '1px solid #e2e8f0', background: '#ffffff' } }>
							<div style={ { fontWeight: 700, fontSize: '13px', marginBottom: 8, display: 'flex', justifyContent: 'space-between' } }>
								<span>🔍 { __( 'Malware Scanner', 'wp-edit-password-protected' ) }</span>
								<span style={ { color: report.scanner?.new_issues === 0 ? '#15803d' : '#dc2626' } }>
									{ report.scanner?.new_issues === 0 ? __( 'Clean', 'wp-edit-password-protected' ) : sprintf( __( '%d Issues', 'wp-edit-password-protected' ), report.scanner?.new_issues ) }
								</span>
							</div>
							<div style={ { fontSize: '12px', color: '#475569', lineHeight: 1.6 } }>
								<div>{ __( 'Signatures Active:', 'wp-edit-password-protected' ) } <strong>{ report.scanner?.signatures?.total || 14 }</strong></div>
								<div>{ __( 'Last Scan:', 'wp-edit-password-protected' ) } <strong>{ report.scanner?.last_scan || __( 'None', 'wp-edit-password-protected' ) }</strong></div>
								<div>{ __( 'Feed Sync:', 'wp-edit-password-protected' ) } <strong>{ report.scanner?.signatures?.remote_count > 0 ? __( 'Synced', 'wp-edit-password-protected' ) : __( 'Built-in', 'wp-edit-password-protected' ) }</strong></div>
							</div>
						</div>

						{ /* Email & SMTP */ }
						<div style={ { padding: 14, borderRadius: 6, border: '1px solid #e2e8f0', background: '#ffffff' } }>
							<div style={ { fontWeight: 700, fontSize: '13px', marginBottom: 8, display: 'flex', justifyContent: 'space-between' } }>
								<span>📧 { __( 'Alerts & Delivery', 'wp-edit-password-protected' ) }</span>
								<span style={ { color: report.smtp?.configured ? '#15803d' : '#b45309' } }>
									{ report.smtp?.configured ? __( 'SMTP Configured', 'wp-edit-password-protected' ) : __( 'PHP Mail', 'wp-edit-password-protected' ) }
								</span>
							</div>
							<div style={ { fontSize: '12px', color: '#475569', lineHeight: 1.6 } }>
								<div>{ __( 'Provider:', 'wp-edit-password-protected' ) } <strong>{ report.smtp?.provider }</strong></div>
								<div>{ __( 'Alert Recipient:', 'wp-edit-password-protected' ) } <strong>{ report.smtp?.recipient || __( 'Not set', 'wp-edit-password-protected' ) }</strong></div>
							</div>
						</div>

						{ /* Reverse Proxy & IP */ }
						<div style={ { padding: 14, borderRadius: 6, border: '1px solid #e2e8f0', background: '#ffffff' } }>
							<div style={ { fontWeight: 700, fontSize: '13px', marginBottom: 8, display: 'flex', justifyContent: 'space-between' } }>
								<span>🌐 { __( 'Proxy & Network', 'wp-edit-password-protected' ) }</span>
								<span style={ { color: report.proxy?.is_trusted_proxy ? '#15803d' : '#475569' } }>
									{ report.proxy?.is_trusted_proxy ? __( 'Verified Proxy', 'wp-edit-password-protected' ) : __( 'Direct IP', 'wp-edit-password-protected' ) }
								</span>
							</div>
							<div style={ { fontSize: '12px', color: '#475569', lineHeight: 1.6 } }>
								<div>{ __( 'Peer IP:', 'wp-edit-password-protected' ) } <code>{ report.proxy?.remote_addr }</code></div>
								<div>{ __( 'Client IP:', 'wp-edit-password-protected' ) } <code>{ report.proxy?.client_ip }</code></div>
								<div>{ __( 'Cloudflare Traffic:', 'wp-edit-password-protected' ) } <strong>{ report.proxy?.is_cloudflare_detected ? __( 'Yes', 'wp-edit-password-protected' ) : __( 'No', 'wp-edit-password-protected' ) }</strong></div>
							</div>
						</div>
					</div>

					{ /* Critical File Permissions */ }
					{ report.hardening?.permissions && (
						<div style={ { marginBottom: 20 } }>
							<h3 style={ { fontSize: '14px', margin: '0 0 10px', color: '#0f172a' } }>
								📁 { __( 'Critical File Permissions & Lock Status', 'wp-edit-password-protected' ) }
							</h3>
							<table className="widefat striped" style={ { fontSize: '12px' } }>
								<thead>
									<tr>
										<th>{ __( 'Target', 'wp-edit-password-protected' ) }</th>
										<th>{ __( 'Permission', 'wp-edit-password-protected' ) }</th>
										<th>{ __( 'Recommended', 'wp-edit-password-protected' ) }</th>
										<th>{ __( 'Status', 'wp-edit-password-protected' ) }</th>
										<th>{ __( 'Evaluation', 'wp-edit-password-protected' ) }</th>
									</tr>
								</thead>
								<tbody>
									{ Object.values( report.hardening.permissions ).map( ( p ) => (
										<tr key={ p.key }>
											<td><strong>{ p.label }</strong></td>
											<td><code>{ p.exists ? ( p.perms_octal || '—' ) : __( 'Missing', 'wp-edit-password-protected' ) }</code></td>
											<td><code>{ p.recommended }</code></td>
											<td>
												<span
													style={ {
														fontSize: '11px',
														fontWeight: 700,
														padding: '1px 6px',
														borderRadius: 3,
														background: p.status === 'ok' ? '#dcfce7' : p.status === 'warn' ? '#fef3c7' : '#fee2e2',
														color: p.status === 'ok' ? '#15803d' : p.status === 'warn' ? '#b45309' : '#b91c1c',
													} }
												>
													{ p.status === 'ok' ? __( 'OK', 'wp-edit-password-protected' ) : p.status === 'warn' ? __( 'Review', 'wp-edit-password-protected' ) : __( 'Risk', 'wp-edit-password-protected' ) }
												</span>
											</td>
											<td>{ p.message }</td>
										</tr>
									) ) }
								</tbody>
							</table>
						</div>
					) }

					{ /* Security Checklist Breakdown */ }
					<div>
						<h3 style={ { fontSize: '14px', margin: '0 0 10px', color: '#0f172a' } }>
							📋 { __( 'Security Checks Summary', 'wp-edit-password-protected' ) }
						</h3>
						<div
							style={ {
								display: 'grid',
								gridTemplateColumns: 'repeat(auto-fit, minmax(260px, 1fr))',
								gap: 8,
							} }
						>
							{ ( report.checklist || [] ).map( ( item ) => (
								<div
									key={ item.id }
									style={ {
										padding: '8px 12px',
										borderRadius: 6,
										border: `1px solid ${ item.done ? '#bbf7d0' : '#fde68a' }`,
										background: item.done ? '#f0fdf4' : '#fffbeb',
										display: 'flex',
										alignItems: 'center',
										justifyContent: 'space-between',
										fontSize: '12px',
									} }
								>
									<span>{ item.label }</span>
									<span style={ { fontWeight: 700, color: item.done ? '#15803d' : '#b45309' } }>
										{ item.done ? '✓' : 'Pending' }
									</span>
								</div>
							) ) }
						</div>
					</div>
				</div>
			) }

			<div
				style={ {
					display: 'flex',
					justifyContent: 'flex-end',
					alignItems: 'center',
					gap: 10,
					marginTop: 20,
					paddingTop: 14,
					borderTop: '1px solid #e2e8f0',
				} }
			>
				<Button variant="secondary" onClick={ handleExportJson } disabled={ ! report }>
					📥 { __( 'Export JSON', 'wp-edit-password-protected' ) }
				</Button>
				<Button variant="secondary" onClick={ handlePrint } disabled={ ! report }>
					🖨️ { __( 'Print / Save as PDF', 'wp-edit-password-protected' ) }
				</Button>
				<Button variant="primary" onClick={ onClose }>
					{ __( 'Close', 'wp-edit-password-protected' ) }
				</Button>
			</div>
		</Modal>
	);
};

export default AuditReportModal;
