/**
 * Sidebar navigation component.
 */
import { __ } from '@wordpress/i18n';
import { NavLink } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import {
	home as DashboardIcon,
	styles as FormStyleIcon,
	lock as ContentIcon,
	layout as TemplatesIcon,
	shield as SecurityIcon,
	globe as SiteAccessIcon,
	settings as SettingsIcon,
	blockDefault as AiCrawlerIcon,
	desktop as CpuMonitorIcon,
} from '@wordpress/icons';
import { Icon } from '@wordpress/icons';
import ProBadge from './ProBadge';

const navItems = [
	{ to: '/', icon: DashboardIcon, label: __( 'Dashboard', 'wp-edit-password-protected' ), end: true },
	{ to: '/site-access', icon: SiteAccessIcon, label: __( 'Site Access', 'wp-edit-password-protected' ) },
	{ to: '/content', icon: ContentIcon, label: __( 'Content', 'wp-edit-password-protected' ), pro: true },
	{ to: '/security', icon: SecurityIcon, label: __( 'Security', 'wp-edit-password-protected' ) },
	{ to: '/ai-crawler', icon: AiCrawlerIcon, label: __( 'AI Crawler Blocker', 'wp-edit-password-protected' ) },
	{ to: '/form-style', icon: FormStyleIcon, label: __( 'Form Style', 'wp-edit-password-protected' ) },
	{ to: '/templates', icon: TemplatesIcon, label: __( 'Templates', 'wp-edit-password-protected' ) },
	{ to: '/cpu-monitor', icon: CpuMonitorIcon, label: __( 'CPU Monitor', 'wp-edit-password-protected' ) },
	{ to: '/settings', icon: SettingsIcon, label: __( 'Settings', 'wp-edit-password-protected' ) },
];

const Sidebar = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	return (
		<nav className="wpepp-sidebar" role="navigation" aria-label={ __( 'Plugin Navigation', 'wp-edit-password-protected' ) }>
			<div className="wpepp-sidebar__logo">
				<Icon icon={ ContentIcon } size={ 28 } />
				<span>{ __( 'WPEPP', 'wp-edit-password-protected' ) }</span>
			</div>
			<ul className="wpepp-sidebar__menu">
				{ navItems.map( ( item ) => (
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
						</NavLink>
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
