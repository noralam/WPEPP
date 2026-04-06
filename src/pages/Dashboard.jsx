/**
 * Dashboard page — overview stats, feature status, and quick navigation.
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { useNavigate } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import { SECURITY_DEFAULTS } from '../utils/defaults';
import { SITE_ACCESS_DEFAULTS } from '../utils/defaults';

const Dashboard = () => {
	const navigate = useNavigate();

	const { isPro, settings } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			isPro: store.isPro(),
			settings: store.getSettings(),
		};
	} );

	const security  = { ...SECURITY_DEFAULTS, ...( settings?.security || {} ) };
	const siteAccess = { ...SITE_ACCESS_DEFAULTS, ...( settings?.site_access || {} ) };

	/* ── Feature Status Helpers ── */
	const features = [
		{
			key: 'site-password',
			label: __( 'Site Password', 'wp-edit-password-protected' ),
			desc: __( 'Protect the entire site with a single password.', 'wp-edit-password-protected' ),
			icon: '🔑',
			active: !! siteAccess.site_password_enabled,
			route: '/site-access',
		},
		{
			key: 'admin-only',
			label: __( 'Admin Only Mode', 'wp-edit-password-protected' ),
			desc: __( 'Restrict front-end access to admins only.', 'wp-edit-password-protected' ),
			icon: '🛡️',
			active: !! siteAccess.admin_only_enabled,
			route: '/site-access',
		},
		{
			key: 'login-limiter',
			label: __( 'Login Limiter', 'wp-edit-password-protected' ),
			desc: __( 'Limit failed login attempts to prevent brute-force.', 'wp-edit-password-protected' ),
			icon: '🚫',
			active: security.login_limit_enabled !== false,
			route: '/security',
		},
		{
			key: 'honeypot',
			label: __( 'Login Honeypot', 'wp-edit-password-protected' ),
			desc: __( 'Invisible bot trap on the login form.', 'wp-edit-password-protected' ),
			icon: '🍯',
			active: security.honeypot_enabled !== false,
			route: '/security',
		},
		{
			key: 'reg-honeypot',
			label: __( 'Registration Honeypot', 'wp-edit-password-protected' ),
			desc: __( 'Invisible bot trap on the registration form.', 'wp-edit-password-protected' ),
			icon: '📝',
			active: security.reg_honeypot_enabled !== false,
			route: '/security',
		},
		{
			key: 'xmlrpc',
			label: __( 'Disable XML-RPC', 'wp-edit-password-protected' ),
			desc: __( 'Block XML-RPC requests to reduce attack surface.', 'wp-edit-password-protected' ),
			icon: '🔌',
			active: !! security.disable_xmlrpc,
			route: '/security',
		},
		{
			key: 'hide-version',
			label: __( 'Hide WP Version', 'wp-edit-password-protected' ),
			desc: __( 'Remove WordPress version from source code.', 'wp-edit-password-protected' ),
			icon: '👁️',
			active: !! security.hide_wp_version,
			route: '/security',
		},
		{
			key: '2fa',
			label: __( 'Two-Factor Auth', 'wp-edit-password-protected' ),
			desc: __( 'TOTP-based 2FA for selected user roles.', 'wp-edit-password-protected' ),
			icon: '📱',
			active: isPro && !! security.two_factor_enabled,
			pro: true,
			route: '/security',
		},
		{
			key: 'recaptcha',
			label: __( 'reCAPTCHA', 'wp-edit-password-protected' ),
			desc: __( 'Google reCAPTCHA on login/registration forms.', 'wp-edit-password-protected' ),
			icon: '🤖',
			active: isPro && !! security.recaptcha_enabled,
			pro: true,
			route: '/security',
		},
		{
			key: 'ai-crawler',
			label: __( 'AI Crawler Blocker', 'wp-edit-password-protected' ),
			desc: __( 'Block AI bots via robots.txt and user-agent.', 'wp-edit-password-protected' ),
			icon: '🛑',
			active: !! security.ai_crawler_blocker_enabled,
			route: '/ai-crawler',
		},
		{
			key: 'content-lock',
			label: __( 'Content Lock', 'wp-edit-password-protected' ),
			desc: __( 'Password-protect individual posts or pages.', 'wp-edit-password-protected' ),
			icon: '📄',
			active: null,
			pro: true,
			route: '/content',
		},
		{
			key: 'conditional-display',
			label: __( 'Conditional Display', 'wp-edit-password-protected' ),
			desc: __( 'Show or hide content blocks by conditions.', 'wp-edit-password-protected' ),
			icon: '🎯',
			active: null,
			route: '/content',
		},
		{
			key: 'login-log',
			label: __( 'Login Log', 'wp-edit-password-protected' ),
			desc: __( 'Track every login attempt with IP and status.', 'wp-edit-password-protected' ),
			icon: '📊',
			active: isPro && security.login_log_enabled !== false,
			pro: true,
			route: '/security',
		},
	];

	const activeCount  = features.filter( ( f ) => f.active === true ).length;

	/* ── Dashboard stats from REST API ── */
	const [ dashStats, setDashStats ] = useState( null );
	const statsLoading = dashStats === null;

	useEffect( () => {
		apiFetch( { path: '/wpepp/v1/dashboard/stats' } )
			.then( ( data ) => setDashStats( data ) )
			.catch( () => setDashStats( {} ) );
	}, [] );

	const StatValue = ( { value } ) => (
		statsLoading
			? <span className="wpepp-stat-card__loader" />
			: <>{ value ?? 0 }</>
	);

	/* ── Quick-nav sections ── */
	const quickNav = [
		{
			icon: '🌐',
			title: __( 'Site Access', 'wp-edit-password-protected' ),
			desc: __( 'Admin-only mode & site-wide password.', 'wp-edit-password-protected' ),
			route: '/site-access',
		},
		{
			icon: '🔒',
			title: __( 'Security', 'wp-edit-password-protected' ),
			desc: __( 'Login protection, 2FA, IP management.', 'wp-edit-password-protected' ),
			route: '/security',
		},
		{
			icon: '📄',
			title: __( 'Content', 'wp-edit-password-protected' ),
			desc: __( 'Content lock & conditional display rules.', 'wp-edit-password-protected' ),
			route: '/content',
		},
		{
			icon: '🛑',
			title: __( 'AI Crawler Blocker', 'wp-edit-password-protected' ),
			desc: __( 'Block AI training bots & scrapers.', 'wp-edit-password-protected' ),
			route: '/ai-crawler',
		},
		{
			icon: '🎨',
			title: __( 'Form Style', 'wp-edit-password-protected' ),
			desc: __( 'Customize login, register & password forms.', 'wp-edit-password-protected' ),
			route: '/form-style',
		},
		{
			icon: '🖼️',
			title: __( 'Templates', 'wp-edit-password-protected' ),
			desc: __( 'One-click design templates for all forms.', 'wp-edit-password-protected' ),
			route: '/templates',
		},
		{
			icon: '⚙️',
			title: __( 'Settings', 'wp-edit-password-protected' ),
			desc: __( 'General options, custom CSS & data.', 'wp-edit-password-protected' ),
			route: '/settings',
		},
	];

	return (
		<div className="wpepp-dashboard">
			{ /* ── Header ── */ }
			<div className="wpepp-dashboard__header">
				<div>
					<h2>{ __( 'Dashboard', 'wp-edit-password-protected' ) }</h2>
					<p className="wpepp-dashboard__subtitle">
						{ __( 'Overview of your password protection & security settings.', 'wp-edit-password-protected' ) }
					</p>
				</div>
				<div className="wpepp-dashboard__header-badge">
					<span className={ `wpepp-plan-badge ${ isPro ? 'is-pro' : 'is-free' }` }>
						{ isPro ? __( 'Pro', 'wp-edit-password-protected' ) : __( 'Free', 'wp-edit-password-protected' ) }
					</span>
				</div>
			</div>

			{ /* ── Summary Stats ── */ }
			<div className="wpepp-dashboard__stats">
				<div className="wpepp-stat-card is-active" onClick={ () => navigate( '/security' ) } role="button" tabIndex={ 0 }>
					<div className="wpepp-stat-card__icon-wrap">🟢</div>
					<div className="wpepp-stat-card__info">
						<span className="wpepp-stat-card__value">{ activeCount }</span>
						<span className="wpepp-stat-card__label">{ __( 'Active Features', 'wp-edit-password-protected' ) }</span>
					</div>
				</div>
				<div className="wpepp-stat-card is-crawler" onClick={ () => navigate( '/ai-crawler' ) } role="button" tabIndex={ 0 }>
					<div className="wpepp-stat-card__icon-wrap">🛑</div>
					<div className="wpepp-stat-card__info">
						<span className="wpepp-stat-card__value"><StatValue value={ dashStats?.ai_bots_blocked } /></span>
						<span className="wpepp-stat-card__label">{ __( 'AI Bots Blocked', 'wp-edit-password-protected' ) }</span>
					</div>
				</div>
				<div className="wpepp-stat-card is-success" onClick={ () => navigate( '/security/login-log' ) } role="button" tabIndex={ 0 }>
					<div className="wpepp-stat-card__icon-wrap">✅</div>
					<div className="wpepp-stat-card__info">
						<span className="wpepp-stat-card__value"><StatValue value={ dashStats?.login_success_30d } /></span>
						<span className="wpepp-stat-card__label">{ __( 'Logins (30d)', 'wp-edit-password-protected' ) }</span>
					</div>
				</div>
				<div className="wpepp-stat-card is-failed" onClick={ () => navigate( '/security/login-log' ) } role="button" tabIndex={ 0 }>
					<div className="wpepp-stat-card__icon-wrap">⚠️</div>
					<div className="wpepp-stat-card__info">
						<span className="wpepp-stat-card__value"><StatValue value={ dashStats?.login_failed_30d } /></span>
						<span className="wpepp-stat-card__label">{ __( 'Failed (30d)', 'wp-edit-password-protected' ) }</span>
					</div>
				</div>
				<div className="wpepp-stat-card" onClick={ () => navigate( '/security/login-log' ) } role="button" tabIndex={ 0 }>
					<div className="wpepp-stat-card__icon-wrap">📊</div>
					<div className="wpepp-stat-card__info">
						<span className="wpepp-stat-card__value"><StatValue value={ dashStats?.logins_today } /></span>
						<span className="wpepp-stat-card__label">{ __( 'Logins Today', 'wp-edit-password-protected' ) }</span>
					</div>
				</div>
				<div className="wpepp-stat-card" onClick={ () => navigate( '/content' ) } role="button" tabIndex={ 0 }>
					<div className="wpepp-stat-card__icon-wrap">🔒</div>
					<div className="wpepp-stat-card__info">
						<span className="wpepp-stat-card__value"><StatValue value={ dashStats?.locked_posts } /></span>
						<span className="wpepp-stat-card__label">{ __( 'Locked Posts', 'wp-edit-password-protected' ) }</span>
					</div>
				</div>
			</div>

			{ /* ── Feature Status Grid ── */ }
			<div className="wpepp-dashboard__section">
				<h3>{ __( 'Feature Status', 'wp-edit-password-protected' ) }</h3>
				<div className="wpepp-feature-grid">
					{ features.map( ( f ) => (
						<button
							key={ f.key }
							type="button"
							className={ `wpepp-feature-item${ f.active === true ? ' is-on' : '' }${ f.active === false ? ' is-off' : '' }${ f.pro && ! isPro ? ' is-locked' : '' }` }
							onClick={ () => navigate( f.route ) }
						>
							<span className="wpepp-feature-item__icon">{ f.icon }</span>
							<span className="wpepp-feature-item__body">
								<span className="wpepp-feature-item__name">
									{ f.label }
									{ f.pro && ! isPro && <span className="wpepp-feature-item__pro">PRO</span> }
								</span>
								<span className="wpepp-feature-item__desc">{ f.desc }</span>
							</span>
							{ f.active !== null && (
								<span className={ `wpepp-feature-item__status ${ f.active ? 'is-on' : 'is-off' }` }>
									{ f.active ? __( 'On', 'wp-edit-password-protected' ) : __( 'Off', 'wp-edit-password-protected' ) }
								</span>
							) }
						</button>
					) ) }
				</div>
			</div>

			{ /* ── Quick Navigation ── */ }
			<div className="wpepp-dashboard__section">
				<h3>{ __( 'Quick Navigation', 'wp-edit-password-protected' ) }</h3>
				<div className="wpepp-quick-nav">
					{ quickNav.map( ( item ) => (
						<button
							key={ item.route }
							type="button"
							className="wpepp-quick-nav__item"
							onClick={ () => navigate( item.route ) }
						>
							<span className="wpepp-quick-nav__icon">{ item.icon }</span>
							<span className="wpepp-quick-nav__title">{ item.title }</span>
							<span className="wpepp-quick-nav__desc">{ item.desc }</span>
						</button>
					) ) }
				</div>
			</div>

			{ /* ── Pro Upgrade CTA ── */ }
			{ ! isPro && (
				<div className="wpepp-dashboard__pro-card">
					<div className="wpepp-dashboard__pro-card-content">
						<h3>{ __( 'Upgrade to Pro', 'wp-edit-password-protected' ) }</h3>
						<p>
							{ __( 'Unlock Content Lock, Two-Factor Auth, Login Log, IP Management, reCAPTCHA, extra form styles, conditional display conditions, custom CSS, and more.', 'wp-edit-password-protected' ) }
						</p>
						<a
							href={ window.wpeppData?.proUrl || '#' }
							className="wpepp-dashboard__pro-btn"
							target="_blank"
							rel="noopener noreferrer"
						>
							{ __( 'Get Pro →', 'wp-edit-password-protected' ) }
						</a>
					</div>
				</div>
			) }
		</div>
	);
};

export default Dashboard;
