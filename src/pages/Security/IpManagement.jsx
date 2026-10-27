/**
 * IP Management settings — block/allow IPs, reverse proxy & Cloudflare support.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	PanelRow,
	TextareaControl,
	Notice,
	Button,
} from '@wordpress/components';
import Card from '../../components/Card';
import { useMemo, useCallback, useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ProBadge from '../../components/ProBadge';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';
import Loading from '../../components/Loading';

const IpManagement = () => {
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

	const [ proxyStatus, setProxyStatus ] = useState( null );
	const [ addingCf, setAddingCf ] = useState( false );
	const [ cfNotice, setCfNotice ] = useState( null );

	const loadProxyStatus = useCallback( () => {
		apiFetch( { path: '/wpepp/v1/proxy/status' } )
			.then( setProxyStatus )
			.catch( () => setProxyStatus( null ) );
	}, [] );

	useEffect( () => {
		loadProxyStatus();
	}, [ loadProxyStatus ] );

	const update = useCallback( ( key, value ) => {
		updateSetting( 'security', key, value );
	}, [ updateSetting ] );

	const handleSave = useCallback( () => {
		saveSettings( 'security', s );
	}, [ saveSettings, s ] );

	const handleReset = useCallback( () => {
		resetSettings( 'security' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	const handleAddCloudflare = useCallback( async () => {
		setAddingCf( true );
		setCfNotice( null );
		try {
			const res = await apiFetch( {
				path: '/wpepp/v1/proxy/cloudflare-preset',
				method: 'POST',
			} );

			// Update the store setting with the new trusted proxies list
			const currentList = s.trusted_proxies ? s.trusted_proxies.split( /[\r\n]+/ ) : [];
			const merged = Array.from( new Set( [ ...currentList, ...( res.proxy?.cloudflare_cidrs || [] ) ] ) ).join( '\n' );
			update( 'trusted_proxies', merged );

			setCfNotice( {
				status: 'success',
				message: sprintf(
					__( 'Added %d Cloudflare IP ranges (%d total trusted proxies configured). Remember to click Save Changes to persist.', 'wp-edit-password-protected' ),
					res.added,
					res.total
				),
			} );
			loadProxyStatus();
		} catch ( err ) {
			setCfNotice( {
				status: 'error',
				message: err.message || __( 'Could not apply Cloudflare preset.', 'wp-edit-password-protected' ),
			} );
		} finally {
			setAddingCf( false );
		}
	}, [ s.trusted_proxies, update, loadProxyStatus ] );

	if ( isLoading ) {
		return <Loading variant="table" count={ 5 } />;
	}

	const blockedCount = ( s.ip_blocklist || '' ).split( /[\n,]+/ ).filter( ( l ) => l.trim() && ! l.trim().startsWith( '#' ) ).length;
	const allowedCount = ( s.ip_allowlist || '' ).split( /[\n,]+/ ).filter( ( l ) => l.trim() && ! l.trim().startsWith( '#' ) ).length;
	const proxyCount = ( s.trusted_proxies || '' ).split( /[\n,]+/ ).filter( ( l ) => l.trim() && ! l.trim().startsWith( '#' ) ).length;

	return (
		<div className="wpepp-ip-management">
			<h3>{ __( 'IP Management', 'wp-edit-password-protected' ) }</h3>

			{ /* --- Reverse Proxy & Cloudflare --- */ }
			<Card
				title={ __( 'Reverse Proxy & Cloudflare Configuration', 'wp-edit-password-protected' ) }
				subtitle={ __( 'Prevent header spoofing by explicitly defining upstream proxy servers.', 'wp-edit-password-protected' ) }
			>
				<Notice status="info" isDismissible={ false }>
					{ __(
						'To stop attackers from forging headers (like X-Forwarded-For or CF-Connecting-IP), WPEPP only trusts forwarding headers when the direct network peer (REMOTE_ADDR) matches a trusted proxy listed below.',
						'wp-edit-password-protected'
					) }
				</Notice>

				{ proxyStatus && (
					<div
						style={ {
							margin: '14px 0',
							padding: '12px 16px',
							borderRadius: 6,
							background: proxyStatus.is_cloudflare_detected ? 'rgba(59, 130, 246, 0.12)' : 'var(--wpepp-surface)',
							border: `1px solid ${ proxyStatus.is_cloudflare_detected ? 'rgba(59, 130, 246, 0.35)' : 'var(--wpepp-border)' }`,
						} }
					>
						<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', flexWrap: 'wrap', gap: 10 } }>
							<div>
								<div style={ { fontSize: '13px', color: 'var(--wpepp-text)' } }>
									<strong>{ __( 'Direct Peer IP (REMOTE_ADDR):', 'wp-edit-password-protected' ) }</strong>{' '}
									<code>{ proxyStatus.remote_addr }</code>
									<span style={ { marginLeft: 10 } }>
										<strong>{ __( 'Resolved Client IP:', 'wp-edit-password-protected' ) }</strong>{' '}
										<code>{ proxyStatus.client_ip }</code>
									</span>
								</div>
								<div style={ { fontSize: '12px', marginTop: 4, color: 'var(--wpepp-text-2)' } }>
									{ proxyStatus.is_trusted_proxy ? (
										<span style={ { color: '#15803d', fontWeight: 600 } }>
											{ __( '✓ Peer is recognized as a trusted proxy. Forwarded headers are verified.', 'wp-edit-password-protected' ) }
										</span>
									) : (
										<span>
											{ __( 'Peer is direct connection or untrusted proxy. Client IP is taken directly from REMOTE_ADDR.', 'wp-edit-password-protected' ) }
										</span>
									) }
								</div>
							</div>
							<Button
								variant="secondary"
								isSmall
								isBusy={ addingCf }
								disabled={ addingCf }
								onClick={ handleAddCloudflare }
							>
								{ __( '1-Click Add Cloudflare IP Ranges', 'wp-edit-password-protected' ) }
							</Button>
						</div>

						{ proxyStatus.is_cloudflare_detected && ! proxyStatus.is_trusted_proxy && (
							<p style={ { margin: '8px 0 0', fontSize: '12px', color: 'var(--wpepp-info)', fontWeight: 500 } }>
								{ __( 'Cloudflare traffic detected on this request! Click the button above to add Cloudflare CIDRs so real visitor IPs are correctly logged.', 'wp-edit-password-protected' ) }
							</p>
						) }
					</div>
				) }

				{ cfNotice && (
					<Notice
						status={ cfNotice.status }
						isDismissible={ true }
						onDismiss={ () => setCfNotice( null ) }
						style={ { marginBottom: 14 } }
					>
						{ cfNotice.message }
					</Notice>
				) }

				<PanelRow>
					<TextareaControl
						label={ __( 'Trusted Reverse Proxies (IPs or CIDRs)', 'wp-edit-password-protected' ) }
						help={ __( 'Enter one IP or CIDR per line (e.g. 173.245.48.0/20). Forwarded headers are ONLY trusted when sent from these servers.', 'wp-edit-password-protected' ) }
						value={ s.trusted_proxies || '' }
						onChange={ ( v ) => update( 'trusted_proxies', v ) }
						rows={ 6 }
						placeholder={
							'# Example:\n173.245.48.0/20 # Cloudflare\n10.0.0.1 # Local Nginx Proxy\n192.168.1.0/24'
						}
					/>
				</PanelRow>

				{ proxyCount > 0 && (
					<div style={ { fontSize: '12px', color: 'var(--wpepp-text-3)', marginTop: 4 } }>
						{ sprintf( __( '%d trusted proxy ranges currently configured.', 'wp-edit-password-protected' ), proxyCount ) }
					</div>
				) }
			</Card>

			{ /* --- IP Blocklist --- */ }
			<Card title={ __( 'IP Blocklist', 'wp-edit-password-protected' ) }>
				<Notice status="info" isDismissible={ false }>
					{ __( 'Block specific IP addresses from accessing your site. Blocked IPs will receive a 403 Forbidden error. Supports single IPs and CIDR ranges (e.g. 192.168.1.0/24).', 'wp-edit-password-protected' ) }
				</Notice>
				<PanelRow>
					<TextareaControl
						label={
							<span>
								{ __( 'Blocked IPs', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						help={ ! isPro ? __( 'Requires WPEPP Pro to manage custom IP blocklist.', 'wp-edit-password-protected' ) : __( 'Enter one IP address per line. You can add comments with # (e.g. 1.2.3.4 # spam bot). Supports CIDR notation.', 'wp-edit-password-protected' ) }
						value={ s.ip_blocklist || '' }
						onChange={ ( v ) => update( 'ip_blocklist', v ) }
						rows={ 8 }
						disabled={ ! isPro }
						placeholder={
							'# Example:\n192.168.1.100 # Suspicious user\n10.0.0.0/8 # Block entire range\n203.0.113.50'
						}
					/>
				</PanelRow>
				{ isPro && blockedCount > 0 && (
					<Notice status="warning" isDismissible={ false }>
						{ /* translators: %d: number of blocked IPs */ }
						{ blockedCount === 1
							? __( '1 IP address is currently blocked.', 'wp-edit-password-protected' )
							: blockedCount + __( ' IP addresses are currently blocked.', 'wp-edit-password-protected' )
						}
					</Notice>
				) }
			</Card>

			{ /* --- IP Allowlist --- */ }
			<Card title={ __( 'IP Allowlist', 'wp-edit-password-protected' ) }>
				<Notice status="info" isDismissible={ false }>
					{ __( 'Allowed IPs bypass all security restrictions including login limiting, IP blocking, and brute force protection. Use this for trusted IPs like your office or home network.', 'wp-edit-password-protected' ) }
				</Notice>
				<PanelRow>
					<TextareaControl
						label={
							<span>
								{ __( 'Allowed IPs', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</span>
						}
						help={ ! isPro ? __( 'Requires WPEPP Pro to manage custom IP allowlist.', 'wp-edit-password-protected' ) : __( 'Enter one IP address per line. These IPs will never be blocked by any security feature. Supports CIDR notation.', 'wp-edit-password-protected' ) }
						value={ s.ip_allowlist || '' }
						onChange={ ( v ) => update( 'ip_allowlist', v ) }
						rows={ 6 }
						disabled={ ! isPro }
						placeholder={
							'# Example:\n203.0.113.10 # Office IP\n198.51.100.0/24 # VPN range'
						}
					/>
				</PanelRow>
				{ isPro && allowedCount > 0 && (
					<Notice status="success" isDismissible={ false }>
						{ allowedCount === 1
							? __( '1 IP address is currently allowed.', 'wp-edit-password-protected' )
							: allowedCount + __( ' IP addresses are currently allowed.', 'wp-edit-password-protected' )
						}
					</Notice>
				) }
			</Card>

			{ /* --- Your Current IP --- */ }
			<Card title={ __( 'Your IP Information', 'wp-edit-password-protected' ) }>
				<Notice status="info" isDismissible={ false }>
					{ __( 'Your current IP address is detected by the server. Make sure you do not accidentally block your own IP.', 'wp-edit-password-protected' ) }
				</Notice>
				{ window.wpeppData?.clientIp && (
					<PanelRow>
						<div>
							<strong>{ __( 'Your IP:', 'wp-edit-password-protected' ) }</strong>{ ' ' }
							<code style={ { background: 'rgba(255,255,255,0.1)', padding: '2px 6px', borderRadius: 3 } }>
								{ window.wpeppData.clientIp }
							</code>
						</div>
					</PanelRow>
				) }
			</Card>
		</div>
	);
};

export default IpManagement;
