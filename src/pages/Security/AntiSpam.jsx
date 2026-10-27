/**
 * Anti-Spam & Bot Shield settings page — WooCommerce, WordPress Registration, Comments, & CAPTCHA.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	ToggleControl,
	TextControl,
	SelectControl,
	RangeControl,
	Notice,
} from '@wordpress/components';
import { useMemo, useCallback, useState } from '@wordpress/element';
import Card from '../../components/Card';
import ToggleCard from '../../components/ToggleCard';
import { useSaveBar } from '../../components/SaveBar';
import ProBadge from '../../components/ProBadge';
import ProLock from '../../components/ProLock';
import Loading from '../../components/Loading';
import { SECURITY_DEFAULTS } from '../../utils/defaults';

const AntiSpam = () => {
	const { settings, isLoading, isPro } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'security' ),
			isLoading: store.isLoading(),
			isPro: store.isPro(),
		};
	} );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );

	const s = useMemo( () => ( { ...SECURITY_DEFAULTS, ...( settings || {} ) } ), [ settings ] );

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

	const [ activeSubTab, setActiveSubTab ] = useState( 'all' );

	const hasCaptchaProvider = !! s.captcha_provider;
	const captchaHelpText = ! isPro
		? __( 'Requires WPEPP Pro.', 'wp-edit-password-protected' )
		: ( ! hasCaptchaProvider
			? __( 'Disabled — select and configure a provider in CAPTCHA Engine first.', 'wp-edit-password-protected' )
			: null );

	if ( isLoading ) {
		return <Loading variant="cards" count={ 3 } />;
	}

	const providerOptions = [
		{ value: '', label: __( 'Disabled', 'wp-edit-password-protected' ) },
		{ value: 'turnstile', label: __( 'Cloudflare Turnstile (Recommended - Smart, Private & Frictionless)', 'wp-edit-password-protected' ) },
		{ value: 'recaptcha_v3', label: __( 'Google reCAPTCHA v3 (Invisible Score-based)', 'wp-edit-password-protected' ) },
		{ value: 'recaptcha_v2', label: __( 'Google reCAPTCHA v2 (Checkbox "I am not a robot")', 'wp-edit-password-protected' ) },
	];

	return (
		<div className="wpepp-page wpepp-anti-spam-page">
			{ /* Header Banner */ }
			<div
				className="wpepp-page-header"
				style={ {
					display: 'flex',
					justifyContent: 'space-between',
					alignItems: 'center',
					marginBottom: '20px',
					padding: '16px 20px',
					background: 'var(--wpepp-card)',
					border: '1px solid var(--wpepp-border)',
					borderRadius: '12px',
					flexWrap: 'wrap',
					gap: '12px',
				} }
			>
				<div>
					<div style={ { display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '4px' } }>
						<h2 style={ { margin: 0, fontSize: '20px', fontWeight: 700 } }>
							🛡️ { __( 'Anti-Spam & Bot Shield', 'wp-edit-password-protected' ) }
						</h2>
						<span
							style={ {
								background: 'var(--wpepp-accent)',
								color: '#000',
								fontWeight: 700,
								fontSize: '11px',
								padding: '2px 8px',
								borderRadius: '999px',
							} }
						>
							ACTIVE
						</span>
					</div>
					<p style={ { margin: 0, fontSize: '13px', color: 'var(--wpepp-text-2)' } }>
						{ __(
							'Comprehensive bot protection against automated user registrations, fake WooCommerce accounts, comment spam, and form abuse.',
							'wp-edit-password-protected'
						) }
					</p>
				</div>

				<div style={ { display: 'flex', gap: '8px' } }>
					<button
						type="button"
						className={ `components-button ${ activeSubTab === 'all' ? 'is-primary' : 'is-secondary' }` }
						onClick={ () => setActiveSubTab( 'all' ) }
					>
						{ __( 'All Controls', 'wp-edit-password-protected' ) }
					</button>
					<button
						type="button"
						className={ `components-button ${ activeSubTab === 'woocommerce' ? 'is-primary' : 'is-secondary' }` }
						onClick={ () => setActiveSubTab( 'woocommerce' ) }
					>
						🛍️ { __( 'WooCommerce Shield', 'wp-edit-password-protected' ) }
					</button>
					<button
						type="button"
						className={ `components-button ${ activeSubTab === 'comments' ? 'is-primary' : 'is-secondary' }` }
						onClick={ () => setActiveSubTab( 'comments' ) }
					>
						💬 { __( 'Comment Spam', 'wp-edit-password-protected' ) }
					</button>
					<button
						type="button"
						className={ `components-button ${ activeSubTab === 'captcha' ? 'is-primary' : 'is-secondary' }` }
						onClick={ () => setActiveSubTab( 'captcha' ) }
					>
						🤖 { __( 'CAPTCHA Engine', 'wp-edit-password-protected' ) }
					</button>
				</div>
			</div>

			{ /* ─── 1. WooCommerce Bot Shield ─── */ }
			{ ( activeSubTab === 'all' || activeSubTab === 'woocommerce' ) && (
				<Card
					title={ __( 'WooCommerce Bot & Fake Account Shield', 'wp-edit-password-protected' ) }
					subtitle={ __(
						'Prevent botnets from automatically creating customer accounts on WooCommerce /my-account/ and checkout pages.',
						'wp-edit-password-protected'
					) }
				>
					<div className="wpepp-toggle-grid">
						<ToggleCard
							title={ __( 'WooCommerce Registration Honeypot', 'wp-edit-password-protected' ) }
							description={ __(
								'Injects an invisible trap field into WooCommerce My Account registration. Automated bots that fill it out are silently rejected.',
								'wp-edit-password-protected'
							) }
							checked={ s.wc_honeypot_enabled !== false }
							onChange={ ( v ) => update( 'wc_honeypot_enabled', v ) }
						/>

						<ToggleCard
							title={ __( 'Block Disposable & Temp Emails on WooCommerce', 'wp-edit-password-protected' ) }
							description={ __(
								'Automatically rejects fake accounts registered with temporary throwaway emails (e.g. Mailinator, TempMail, Yopmail).',
								'wp-edit-password-protected'
							) }
							checked={ isPro && s.wc_disposable_email_block !== false }
							onChange={ ( v ) => update( 'wc_disposable_email_block', v ) }
							badge={ ! isPro ? <ProBadge /> : null }
							disabled={ ! isPro }
							help={ ! isPro ? __( 'Requires WPEPP Pro.', 'wp-edit-password-protected' ) : null }
						/>

						<ToggleCard
							title={ __( 'WooCommerce Registration Rate Limiting', 'wp-edit-password-protected' ) }
							description={ __(
								'Prevents single bot IP addresses from rapidly spamming multiple account registrations in short succession.',
								'wp-edit-password-protected'
							) }
							checked={ s.wc_rate_limit_enabled !== false }
							onChange={ ( v ) => update( 'wc_rate_limit_enabled', v ) }
						/>

						<ToggleCard
							title={ __( 'Enforce CAPTCHA on WooCommerce Forms', 'wp-edit-password-protected' ) }
							description={ __(
								'Displays Cloudflare Turnstile or Google reCAPTCHA challenge directly on WooCommerce login and registration forms.',
								'wp-edit-password-protected'
							) }
							checked={ isPro && hasCaptchaProvider && !! s.wc_captcha_enabled }
							onChange={ ( v ) => update( 'wc_captcha_enabled', v ) }
							badge={ ! isPro ? <ProBadge /> : null }
							disabled={ ! isPro || ! hasCaptchaProvider }
							help={ captchaHelpText }
						/>
					</div>
				</Card>
			) }

			{ /* ─── 2. WordPress Comment Spam Guard ─── */ }
			{ ( activeSubTab === 'all' || activeSubTab === 'comments' ) && (
				<Card
					title={ __( 'WordPress Comment Spam Guard', 'wp-edit-password-protected' ) }
					subtitle={ __(
						'Stop bot comment spam across all blog posts and pages without relying on third-party plugins like Akismet.',
						'wp-edit-password-protected'
					) }
				>
					<div className="wpepp-toggle-grid">
						<ToggleCard
							title={ __( 'Comment Form Honeypot', 'wp-edit-password-protected' ) }
							description={ __(
								'Adds an invisible decoy field to your comment section. Spambots filling in every form input are automatically caught and blocked.',
								'wp-edit-password-protected'
							) }
							checked={ s.comment_honeypot_enabled !== false }
							onChange={ ( v ) => update( 'comment_honeypot_enabled', v ) }
						/>

						<ToggleCard
							title={ __( 'Submission Time-Gate (Speed Check)', 'wp-edit-password-protected' ) }
							description={ __(
								'Human beings take at least a few seconds to type and submit a comment. Submissions executed in under 3 seconds are blocked as automated bots.',
								'wp-edit-password-protected'
							) }
							checked={ s.comment_timegate_enabled !== false }
							onChange={ ( v ) => update( 'comment_timegate_enabled', v ) }
						/>

						<ToggleCard
							title={ __( 'Require CAPTCHA for Comment Submissions', 'wp-edit-password-protected' ) }
							description={ __(
								'Challenges visitors with Turnstile or reCAPTCHA before their comment is accepted. Admins and moderators are automatically exempt.',
								'wp-edit-password-protected'
							) }
							checked={ isPro && hasCaptchaProvider && !! s.comment_captcha_enabled }
							onChange={ ( v ) => update( 'comment_captcha_enabled', v ) }
							badge={ ! isPro ? <ProBadge /> : null }
							disabled={ ! isPro || ! hasCaptchaProvider }
							help={ captchaHelpText }
						/>
					</div>
				</Card>
			) }

			{ /* ─── 3. Login & Lost Password Form Protection ─── */ }
			{ ( activeSubTab === 'all' || activeSubTab === 'login' ) && (
				<Card
					title={ __( 'Login & Password Reset Protection', 'wp-edit-password-protected' ) }
					subtitle={ __(
						'Prevent brute force login attempts and stop automated bot scripts from abusing password reset forms.',
						'wp-edit-password-protected'
					) }
				>
					<div className="wpepp-toggle-grid">
						<ToggleCard
							title={ __( 'Login Form Honeypot', 'wp-edit-password-protected' ) }
							description={ __(
								'Catches automated credential stuffing bots that fill in all fields on the login page.',
								'wp-edit-password-protected'
							) }
							checked={ !! s.honeypot_enabled }
							onChange={ ( v ) => update( 'honeypot_enabled', v ) }
						/>

						<ToggleCard
							title={ __( 'Lost Password Form Honeypot', 'wp-edit-password-protected' ) }
							description={ __(
								'Blocks bot scripts from spamming the password reset form and overloading your mail server (Email Bombing protection).',
								'wp-edit-password-protected'
							) }
							checked={ s.lostpassword_honeypot_enabled !== false }
							onChange={ ( v ) => update( 'lostpassword_honeypot_enabled', v ) }
						/>

						<ToggleCard
							title={ __( 'Require CAPTCHA on Lost Password', 'wp-edit-password-protected' ) }
							description={ __(
								'Requires solving a CAPTCHA challenge before requesting a password reset email.',
								'wp-edit-password-protected'
							) }
							checked={ isPro && hasCaptchaProvider && !! s.lostpassword_captcha_enabled }
							onChange={ ( v ) => update( 'lostpassword_captcha_enabled', v ) }
							badge={ ! isPro ? <ProBadge /> : null }
							disabled={ ! isPro || ! hasCaptchaProvider }
							help={ captchaHelpText }
						/>
					</div>
				</Card>
			) }

			{ /* ─── 4. Universal CAPTCHA Engine ─── */ }
			{ ( activeSubTab === 'all' || activeSubTab === 'captcha' ) && (
				<ProLock isPro={ isPro } featureName={ __( 'Universal CAPTCHA Engine (Turnstile & reCAPTCHA)', 'wp-edit-password-protected' ) }>
					<Card
						title={ __( 'Universal CAPTCHA Engine', 'wp-edit-password-protected' ) }
						subtitle={ __(
							'Choose your bot challenge provider. Supported across Login, Registration, WooCommerce, Comments, and Password Reset.',
							'wp-edit-password-protected'
						) }
					>
						<div style={ { maxWidth: '640px', display: 'flex', flexDirection: 'column', gap: '16px' } }>
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'CAPTCHA Provider', 'wp-edit-password-protected' ) }
								value={ s.captcha_provider || '' }
								options={ providerOptions }
								onChange={ ( v ) => update( 'captcha_provider', v ) }
							/>

							{ s.captcha_provider === 'turnstile' && (
								<div
									style={ {
										padding: '16px',
										background: 'var(--wpepp-surface)',
										border: '1px solid var(--wpepp-border)',
										borderRadius: '8px',
										display: 'flex',
										flexDirection: 'column',
										gap: '12px',
									} }
								>
									<div style={ { fontSize: '13px', color: 'var(--wpepp-text-2)' } }>
										{ __(
											'Cloudflare Turnstile delivers a modern, frictionless visitor experience without frustrating puzzles.',
											'wp-edit-password-protected'
										) }
									</div>
									<TextControl
										__next40pxDefaultSize
										__nextHasNoMarginBottom
										label={ __( 'Turnstile Site Key', 'wp-edit-password-protected' ) }
										value={ s.turnstile_site_key || '' }
										onChange={ ( v ) => update( 'turnstile_site_key', v ) }
										placeholder="0x4AAAAAA..."
									/>
									<TextControl
										__next40pxDefaultSize
										__nextHasNoMarginBottom
										label={ __( 'Turnstile Secret Key', 'wp-edit-password-protected' ) }
										value={ s.turnstile_secret_key || '' }
										onChange={ ( v ) => update( 'turnstile_secret_key', v ) }
										type="password"
										placeholder="0x4AAAAAA..."
									/>
								</div>
							) }

							{ ( s.captcha_provider === 'recaptcha_v2' || s.captcha_provider === 'recaptcha_v3' ) && (
								<div
									style={ {
										padding: '16px',
										background: 'var(--wpepp-surface)',
										border: '1px solid var(--wpepp-border)',
										borderRadius: '8px',
										display: 'flex',
										flexDirection: 'column',
										gap: '12px',
									} }
								>
									<TextControl
										__next40pxDefaultSize
										__nextHasNoMarginBottom
										label={ __( 'reCAPTCHA Site Key', 'wp-edit-password-protected' ) }
										value={ s.recaptcha_site_key || '' }
										onChange={ ( v ) => update( 'recaptcha_site_key', v ) }
									/>
									<TextControl
										__next40pxDefaultSize
										__nextHasNoMarginBottom
										label={ __( 'reCAPTCHA Secret Key', 'wp-edit-password-protected' ) }
										value={ s.recaptcha_secret_key || '' }
										onChange={ ( v ) => update( 'recaptcha_secret_key', v ) }
										type="password"
									/>
									{ s.captcha_provider === 'recaptcha_v3' && (
										<RangeControl
											__next40pxDefaultSize
											__nextHasNoMarginBottom
											label={ __( 'v3 Passing Score Threshold', 'wp-edit-password-protected' ) }
											help={ __( 'Scores range from 0.0 (likely a bot) to 1.0 (likely a human). 0.5 is the recommended standard.', 'wp-edit-password-protected' ) }
											value={ s.captcha_threshold ?? 0.5 }
											min={ 0.1 }
											max={ 0.9 }
											step={ 0.1 }
											onChange={ ( v ) => update( 'captcha_threshold', v ) }
										/>
									) }
								</div>
							) }

							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Test Mode (Do not block submissions)', 'wp-edit-password-protected' ) }
								help={ __( 'When enabled, the CAPTCHA widget renders and logs validation, but failing it will not block visitors.', 'wp-edit-password-protected' ) }
								checked={ !! s.recaptcha_test_mode }
								onChange={ ( v ) => update( 'recaptcha_test_mode', v ) }
							/>
						</div>
					</Card>
				</ProLock>
			) }
		</div>
	);
};

export default AntiSpam;
