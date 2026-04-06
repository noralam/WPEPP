/**
 * IP Management settings — block/allow IPs from dashboard (Pro).
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	PanelBody,
	PanelRow,
	TextareaControl,
	Notice,
	Spinner,
} from '@wordpress/components';
import { useMemo, useCallback } from '@wordpress/element';
import ProBadge from '../../components/ProBadge';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';

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

	if ( isLoading ) {
		return <Spinner />;
	}

	const blockedCount = ( s.ip_blocklist || '' ).split( /[\n,]+/ ).filter( ( l ) => l.trim() && ! l.trim().startsWith( '#' ) ).length;
	const allowedCount = ( s.ip_allowlist || '' ).split( /[\n,]+/ ).filter( ( l ) => l.trim() && ! l.trim().startsWith( '#' ) ).length;

	return (
		<div className="wpepp-ip-management">
			<h3>{ __( 'IP Management', 'wp-edit-password-protected' ) }</h3>

			{ /* --- IP Blocklist --- */ }
			<PanelBody title={ __( 'IP Blocklist', 'wp-edit-password-protected' ) } initialOpen>
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
						help={ __( 'Enter one IP address per line. You can add comments with # (e.g. 1.2.3.4 # spam bot). Supports CIDR notation.', 'wp-edit-password-protected' ) }
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
			</PanelBody>

			{ /* --- IP Allowlist --- */ }
			<PanelBody title={ __( 'IP Allowlist', 'wp-edit-password-protected' ) } initialOpen={ false }>
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
						help={ __( 'Enter one IP address per line. These IPs will never be blocked by any security feature. Supports CIDR notation.', 'wp-edit-password-protected' ) }
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
			</PanelBody>

			{ /* --- Your Current IP --- */ }
			<PanelBody title={ __( 'Your IP Information', 'wp-edit-password-protected' ) } initialOpen={ false }>
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
			</PanelBody>
		</div>
	);
};

export default IpManagement;
