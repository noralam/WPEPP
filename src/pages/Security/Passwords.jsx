/**
 * Password policy settings.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useMemo, useCallback } from '@wordpress/element';
import {
	RangeControl,
	Notice,
} from '@wordpress/components';
import Card from '../../components/Card';
import ToggleCard from '../../components/ToggleCard';
import ProBadge from '../../components/ProBadge';
import { useSaveBar } from '../../components/SaveBar';
import { SECURITY_DEFAULTS } from '../../utils/defaults';
import Loading from '../../components/Loading';

const Passwords = () => {
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

	const update = useCallback( ( key, value ) => updateSetting( 'security', key, value ), [ updateSetting ] );

	useSaveBar(
		useCallback( () => saveSettings( 'security', s ), [ saveSettings, s ] ),
		useCallback( () => resetSettings( 'security' ), [ resetSettings ] )
	);

	if ( isLoading ) {
		return <Loading variant="form" count={ 3 } />;
	}

	return (
		<div className="wpepp-password-policy">
			<Card title={ __( 'Strength Rules', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid">
					<ToggleCard
						title={ __( 'Enforce password policy', 'wp-edit-password-protected' ) }
						description={ __( 'Applies on registration, profile updates and password resets.', 'wp-edit-password-protected' ) }
						checked={ !! s.pw_policy_enabled }
						onChange={ ( v ) => update( 'pw_policy_enabled', v ) }
					/>
				</div>

				{ s.pw_policy_enabled && (
					<div className="wpepp-form-grid wpepp-form-grid--stack" style={ { marginTop: 16 } }>
						<RangeControl
							label={ __( 'Minimum length', 'wp-edit-password-protected' ) }
							value={ Number( s.pw_min_length ?? 12 ) }
							onChange={ ( v ) => update( 'pw_min_length', v ) }
							min={ 6 }
							max={ 64 }
						/>

						<div className="wpepp-toggle-grid">
							<ToggleCard
								title={ __( 'Require uppercase + lowercase + number', 'wp-edit-password-protected' ) }
								checked={ !! s.pw_require_mixed }
								onChange={ ( v ) => update( 'pw_require_mixed', v ) }
							/>
							<ToggleCard
								title={ __( 'Require a special character', 'wp-edit-password-protected' ) }
								checked={ !! s.pw_require_symbol }
								onChange={ ( v ) => update( 'pw_require_symbol', v ) }
							/>
							<ToggleCard
								title={ __( 'Block common passwords', 'wp-edit-password-protected' ) }
								description={ __( 'Rejects passwords that appear on public breach lists.', 'wp-edit-password-protected' ) }
								checked={ !! s.pw_block_common }
								onChange={ ( v ) => update( 'pw_block_common', v ) }
							/>
							<ToggleCard
								title={ __( 'Check against breach database', 'wp-edit-password-protected' ) }
								badge={ ! isPro ? <ProBadge /> : null }
								help={ ! isPro ? __( 'Requires WPEPP Pro.', 'wp-edit-password-protected' ) : null }
								description={
									s.pw_breach_check
										? __( 'Privacy-safe k-anonymity check — only 5 characters of the password hash leave the server.', 'wp-edit-password-protected' )
										: __( 'Blocks passwords found in known breach corpora.', 'wp-edit-password-protected' )
								}
								checked={ isPro && !! s.pw_breach_check }
								onChange={ ( v ) => update( 'pw_breach_check', v ) }
								disabled={ ! isPro }
							/>
						</div>

						{ isPro && s.pw_breach_check && (
							<Notice status="info" isDismissible={ false }>
								{
									sprintf(
										/* translators: %s: API host */
										__( 'Breached checks query %s using a hashed prefix only. If the service is unreachable, passwords are allowed (fail-open).', 'wp-edit-password-protected' ),
										'api.pwnedpasswords.com'
									)
								}
							</Notice>
						) }
					</div>
				) }
			</Card>
		</div>
	);
};

export default Passwords;
