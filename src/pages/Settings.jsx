/**
 * Settings page — inner tabs for General and Custom CSS.
 */
import { __ } from '@wordpress/i18n';
import { NavLink, Routes, Route, Navigate } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import { lazy, Suspense } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
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
				<NavLink to="/settings/custom-css" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Custom CSS', 'wp-edit-password-protected' ) }
					{ ! isPro && <ProBadge /> }
				</NavLink>
			</nav>

			<Suspense fallback={ <Spinner /> }>
				<Routes>
					<Route index element={ <Navigate to="general" replace /> } />
					<Route path="general" element={ <General /> } />
					<Route
						path="custom-css"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'Custom CSS Editor', 'wp-edit-password-protected' ) }>
								<CustomCss />
							</ProLock>
						}
					/>
				</Routes>
			</Suspense>
		</div>
	);
};

export default Settings;
