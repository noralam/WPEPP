/**
 * Settings page — inner tabs for General, Database & Logs, Backup, and Custom CSS.
 */
import { __ } from '@wordpress/i18n';
import { NavLink, Routes, Route, Navigate } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import { lazy, Suspense } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import Backup from './Settings/Backup';
import DatabaseMaintenance from './Settings/DatabaseMaintenance';
import ProBadge from '../components/ProBadge';
import ProLock from '../components/ProLock';

const General   = lazy( () => import( './Settings/General' ) );
const CustomCss = lazy( () => import( './Settings/CustomCss' ) );

const Settings = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	return (
		<div className="wpepp-settings-page">
			<nav className="wpepp-inner-tabs">
				<NavLink to="/settings/general" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'General', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/settings/database" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Database & Logs', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/settings/backup" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Backup & Restore', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/settings/custom-css" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Custom CSS', 'wp-edit-password-protected' ) }
					{ ! isPro && <ProBadge /> }
				</NavLink>
			</nav>

			<Suspense fallback={ <Spinner /> }>
				<Routes>
					<Route index element={ <Navigate to="/settings/general" replace /> } />
					<Route path="general" element={ <General /> } />
					<Route path="database" element={ <DatabaseMaintenance /> } />
					<Route path="backup" element={ <Backup /> } />
					<Route
						path="custom-css"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'Custom CSS Editor', 'wp-edit-password-protected' ) }>
								<CustomCss />
							</ProLock>
						}
					/>
					<Route path="*" element={ <Navigate to="/settings/general" replace /> } />
				</Routes>
			</Suspense>
		</div>
	);
};

export default Settings;
