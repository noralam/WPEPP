/**
 * Site Access page — inner tabs for Admin Only and Site Password.
 */
import { __ } from '@wordpress/i18n';
import { NavLink, Routes, Route, Navigate } from 'react-router-dom';
import { lazy, Suspense } from '@wordpress/element';
import { Spinner } from '@wordpress/components';

const AdminOnly    = lazy( () => import( './SiteAccess/AdminOnly' ) );
const SitePassword = lazy( () => import( './SiteAccess/SitePassword' ) );

const SiteAccess = () => {
	return (
		<div className="wpepp-site-access-page">
			<nav className="wpepp-inner-tabs">
				<NavLink to="/site-access/admin-only" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Admin Only', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/site-access/site-password" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Site Password', 'wp-edit-password-protected' ) }
				</NavLink>
			</nav>

			<Suspense fallback={ <Spinner /> }>
				<Routes>
					<Route index element={ <Navigate to="admin-only" replace /> } />
					<Route path="admin-only" element={ <AdminOnly /> } />
					<Route path="site-password" element={ <SitePassword /> } />
				</Routes>
			</Suspense>
		</div>
	);
};

export default SiteAccess;
