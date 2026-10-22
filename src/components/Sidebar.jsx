/**
 * Sidebar navigation — grouped sections.
 */
import { __ } from '@wordpress/i18n';
import { NavLink } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import {
	home as DashboardIcon,
	styles as FormStyleIcon,
	category as ContentIcon,
	layout as TemplatesIcon,
	shield as FirewallIcon,
	search as ScannerIcon,
	bug as AntiSpamIcon,
	blockDefault as BlocksIcon,
	key as LoginsIcon,
	globe as SiteAccessIcon,
	list as ToolsIcon,
	filter as AiCrawlerIcon,
	chartBar as AnalyticsIcon,
	tool as ToolIcon,
	settings as SettingsIcon,
} from '@wordpress/icons';
import { Icon } from '@wordpress/icons';
import ProBadge from './ProBadge';

const navGroups = [
	{
		items: [
			{ to: '/', icon: DashboardIcon, label: __( 'Dashboard', 'wp-edit-password-protected' ), end: true },
		],
	},
	{
		label: __( 'Protect', 'wp-edit-password-protected' ),
		items: [
			{ to: '/firewall', icon: FirewallIcon, label: __( 'Firewall', 'wp-edit-password-protected' ) },
			{ to: '/scanner', icon: ScannerIcon, label: __( 'Scanner', 'wp-edit-password-protected' ) },
			{ to: '/anti-spam', icon: AntiSpamIcon, label: __( 'Anti-Spam', 'wp-edit-password-protected' ), badge: 'NEW' },
			{ to: '/blocks', icon: BlocksIcon, label: __( 'Block Center', 'wp-edit-password-protected' ) },
			{ to: '/logins', icon: LoginsIcon, label: __( 'Logins', 'wp-edit-password-protected' ) },
			{ to: '/site-access', icon: SiteAccessIcon, label: __( 'Site Access', 'wp-edit-password-protected' ) },
			{ to: '/hardening', icon: ToolIcon, label: __( 'Hardening', 'wp-edit-password-protected' ) },
		],
	},
	{
		label: __( 'Content', 'wp-edit-password-protected' ),
		items: [
			{ to: '/content', icon: ContentIcon, label: __( 'Content Lock', 'wp-edit-password-protected' ), pro: true },
			{ to: '/form-style', icon: FormStyleIcon, label: __( 'Form Style', 'wp-edit-password-protected' ) },
			{ to: '/templates', icon: TemplatesIcon, label: __( 'Templates', 'wp-edit-password-protected' ) },
		],
	},
	{
		label: __( 'System', 'wp-edit-password-protected' ),
		items: [
			{ to: '/ai-crawler', icon: AiCrawlerIcon, label: __( 'AI Crawler Blocker', 'wp-edit-password-protected' ) },
			{ to: '/activity', icon: AnalyticsIcon, label: __( 'Activity', 'wp-edit-password-protected' ) },
			{ to: '/tools', icon: ToolsIcon, label: __( 'Logs & Tools', 'wp-edit-password-protected' ), pro: true },
			{ to: '/settings', icon: SettingsIcon, label: __( 'Settings', 'wp-edit-password-protected' ) },
		],
	},
];

const Sidebar = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	return (
		<nav className="wpepp-sidebar" role="navigation" aria-label={ __( 'Plugin Navigation', 'wp-edit-password-protected' ) }>
		<div className="wpepp-sidebar__logo">
			<span className="wpepp-sidebar__logo-tile">
				<Icon icon={ FirewallIcon } size={ 20 } />
			</span>
			<span>{ __( 'WPEPP', 'wp-edit-password-protected' ) }</span>
		</div>
			<ul className="wpepp-sidebar__menu">
				{ navGroups.map( ( group, gi ) => (
					<li key={ gi } style={ { listStyle: 'none' } }>
						{ group.label && (
							<span className="wpepp-sidebar__group-label">{ group.label }</span>
						) }
						<ul className="wpepp-sidebar__menu" style={ { padding: 0 } }>
							{ group.items.map( ( item ) => (
								<li key={ item.to }>
									<NavLink
										to={ item.to }
										end={ item.end }
										className={ ( { isActive } ) =>
											`wpepp-sidebar__link${ isActive ? ' is-active' : '' }`
										}
									>
										<Icon icon={ item.icon } size={ 20 } />
										<span>{ item.label }</span>
										{ item.pro && ! isPro && <ProBadge /> }
										{ item.badge && <span className="wpepp-new-badge">{ item.badge }</span> }
									</NavLink>
								</li>
							) ) }
						</ul>
					</li>
				) ) }
			</ul>
			<div className="wpepp-sidebar__footer">
				<span>{ `v${ window.wpeppData?.version || '2.0.0' }` }</span>
			</div>
		</nav>
	);
};

export default Sidebar;
