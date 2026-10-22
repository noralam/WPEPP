/**
 * Site Access → Country Restriction — block or allow visitors by country.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	SelectControl,
	TextControl,
	TextareaControl,
	FormTokenField,
	Notice,
	Spinner,
} from '@wordpress/components';
import Card from '../../components/Card';
import ToggleCard from '../../components/ToggleCard';
import { useMemo, useCallback } from '@wordpress/element';
import { useSaveBar } from '../../components/SaveBar';
import { SITE_ACCESS_DEFAULTS } from '../../utils/defaults';
import { COUNTRIES, labelForCode, codeForLabel } from '../../utils/countries';
import ProBadge from '../../components/ProBadge';

const FREE_LIMIT = 2;

const CountryRestriction = () => {
	const { settings, isLoading, isPro } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'site_access' ),
			isLoading: store.isLoading(),
			isPro: store.isPro(),
		};
	} );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );

	const s = useMemo( () => ( { ...SITE_ACCESS_DEFAULTS, ...settings } ), [ settings ] );

	const update = useCallback( ( key, value ) => {
		updateSetting( 'site_access', key, value );
	}, [ updateSetting ] );

	const handleSave = useCallback( () => {
		saveSettings( 'site_access', s );
	}, [ saveSettings, s ] );

	const handleReset = useCallback( () => {
		resetSettings( 'site_access' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	const selectedCodes = useMemo(
		() => ( Array.isArray( s.country_restriction_list ) ? s.country_restriction_list : [] ),
		[ s.country_restriction_list ]
	);

	// FormTokenField works with display labels; map codes <-> labels.
	const tokenValues = useMemo(
		() => selectedCodes.map( ( c ) => labelForCode( c ) ),
		[ selectedCodes ]
	);

	const suggestions = useMemo( () => COUNTRIES.map( ( c ) => c.label ), [] );

	const atLimit = ! isPro && selectedCodes.length >= FREE_LIMIT;

	const onTokensChange = useCallback( ( tokens ) => {
		// Convert each token (label) back to a code; drop unknown entries.
		const codes = [];
		tokens.forEach( ( t ) => {
			const code = codeForLabel( t );
			if ( code && ! codes.includes( code ) ) {
				codes.push( code );
			}
		} );
		// Enforce the free-tier cap on the client (server enforces too).
		const capped = isPro ? codes : codes.slice( 0, FREE_LIMIT );
		update( 'country_restriction_list', capped );
	}, [ isPro, update ] );

	if ( isLoading ) {
		return <Spinner />;
	}

	return (
		<div className="wpepp-country-restriction-settings">
			<h3>{ __( 'Country Restriction', 'wp-edit-password-protected' ) }</h3>

			<Card title={ __( 'Restrict Site by Country', 'wp-edit-password-protected' ) }>
				<div className="wpepp-toggle-grid" style={ { gridTemplateColumns: '1fr' } }>
					<ToggleCard
						title={ __( 'Enable Country Restriction', 'wp-edit-password-protected' ) }
						description={ __( 'Hide your site from — or restrict it to — visitors in specific countries, based on their IP address.', 'wp-edit-password-protected' ) }
						checked={ !! s.country_restriction_enabled }
						onChange={ ( v ) => update( 'country_restriction_enabled', v ) }
					/>
				</div>

				{ !! s.country_restriction_enabled && (
					<div className="wpepp-form-grid wpepp-form-grid--stack" style={ { marginTop: 16 } }>
						<div className="wpepp-form-grid__full">
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Restriction Mode', 'wp-edit-password-protected' ) }
								help={ 'allow' === s.country_restriction_mode
									? __( 'Allow list: ONLY visitors from the selected countries can view the site. Everyone else is blocked.', 'wp-edit-password-protected' )
									: __( 'Block list: visitors from the selected countries are blocked. Everyone else is allowed.', 'wp-edit-password-protected' )
								}
								value={ s.country_restriction_mode || 'block' }
								options={ [
									{ label: __( 'Block list (hide from selected countries)', 'wp-edit-password-protected' ), value: 'block' },
									{ label: __( 'Allow list (show only to selected countries)', 'wp-edit-password-protected' ), value: 'allow' },
								] }
								onChange={ ( v ) => update( 'country_restriction_mode', v ) }
							/>
						</div>

						<div style={ { width: '100%' } }>
								<FormTokenField
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={
										<>
											{ __( 'Countries', 'wp-edit-password-protected' ) }
											{ ! isPro && <ProBadge /> }
										</>
									}
									value={ tokenValues }
									suggestions={ atLimit ? [] : suggestions }
									onChange={ onTokensChange }
									placeholder={ __( 'Select countries…', 'wp-edit-password-protected' ) }
									__experimentalExpandOnFocus
									__experimentalShowHowTo={ false }
								/>
								<p className="components-form-token-field__help" style={ { marginTop: '4px' } }>
									{ isPro
										? __( 'Type to search and add countries. Unlimited countries on Pro.', 'wp-edit-password-protected' )
										: __( 'Free version: up to 2 countries. Upgrade to Pro for unlimited countries.', 'wp-edit-password-protected' )
									}
								</p>
							</div>
						

						{ atLimit && (
							<Notice status="warning" isDismissible={ false }>
								{ __( 'You have reached the free limit of 2 countries. Upgrade to Pro to add more.', 'wp-edit-password-protected' ) }
							</Notice>
						) }

													<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Block Page Title', 'wp-edit-password-protected' ) }
								help={ __( 'Heading shown to blocked visitors.', 'wp-edit-password-protected' ) }
								value={ s.country_restriction_title || '' }
								onChange={ ( v ) => update( 'country_restriction_title', v ) }
							/>
						

													<TextareaControl
								__nextHasNoMarginBottom
								label={ __( 'Block Page Message', 'wp-edit-password-protected' ) }
								help={ __( 'Message shown to blocked visitors.', 'wp-edit-password-protected' ) }
								value={ s.country_restriction_message || '' }
								onChange={ ( v ) => update( 'country_restriction_message', v ) }
								rows={ 3 }
							/>
						

						<Notice status="info" isDismissible={ false }>
							{ __( 'How it works: country is detected from the visitor IP (Cloudflare header when available, otherwise the ipapi.co lookup service — visitor IPs are sent to it). Administrators are never blocked. If the country cannot be determined, the visitor is allowed through, so a lookup outage never takes your whole site offline.', 'wp-edit-password-protected' ) }
						</Notice>
					</div>
				) }
			</Card>
		</div>
	);
};

export default CountryRestriction;
