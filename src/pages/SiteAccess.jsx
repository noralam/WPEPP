/**
 * Site Access page — inner tabs for Admin Only and Site Password.
 */
import { __ } from '@wordpress/i18n';
import { NavLink, Routes, Route, Navigate } from 'react-router-dom';
import { lazy, Suspense } from '@wordpress/element';
import { Spinner } from '@wordpress/components';

const AdminOnly    = lazy( () => import( './SiteAccess/AdminOnly' ) );
const SitePassword = lazy( () => import( './SiteAccess/SitePassword' ) );
const CountryRestriction = lazy( () => import( './SiteAccess/CountryRestriction' ) );

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
				<NavLink to="/site-access/country-restriction" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Country Restriction', 'wp-edit-password-protected' ) }
				</NavLink>
			</nav>

			<Suspense fallback={ <Spinner /> }>
				<Routes>
					<Route index element={ <Navigate to="/site-access/admin-only" replace /> } />
					<Route path="admin-only" element={ <AdminOnly /> } />
					<Route path="site-password" element={ <SitePassword /> } />
					<Route path="password" element={ <Navigate to="/site-access/site-password" replace /> } />
					<Route path="country-restriction" element={ <CountryRestriction /> } />
					<Route path="country" element={ <Navigate to="/site-access/country-restriction" replace /> } />
					<Route path="*" element={ <Navigate to="/site-access/admin-only" replace /> } />
				</Routes>
			</Suspense>
		</div>
	);
};

export default SiteAccess;
