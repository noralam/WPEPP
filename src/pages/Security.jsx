/**
 * Security page — inner tabs for Login Protection, Custom Login URL, Login Log.
 */
import { __ } from '@wordpress/i18n';
import { NavLink, Routes, Route, Navigate } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import { lazy, Suspense } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import ProBadge from '../components/ProBadge';
import ProLock from '../components/ProLock';

const LoginProtection         = lazy( () => import( './Security/LoginProtection' ) );
const RegistrationProtection  = lazy( () => import( './Security/RegistrationProtection' ) );
const TwoFactor               = lazy( () => import( './Security/TwoFactor' ) );
const IpManagement            = lazy( () => import( './Security/IpManagement' ) );
const AutoLogin               = lazy( () => import( './Security/AutoLogin' ) );
const LoginLog                = lazy( () => import( './Security/LoginLog' ) );

const Security = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	return (
		<div className="wpepp-security-page">
			<nav className="wpepp-inner-tabs">
				<NavLink to="/security/protection" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Login Protection', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/security/registration" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Registration Protection', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/security/two-factor" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Two-Factor Auth', 'wp-edit-password-protected' ) }
					{ ! isPro && <ProBadge /> }
				</NavLink>
				<NavLink to="/security/ip-management" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'IP Management', 'wp-edit-password-protected' ) }
					{ ! isPro && <ProBadge /> }
				</NavLink>
				<NavLink to="/security/auto-login" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Auto Login', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/security/log" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Login Log', 'wp-edit-password-protected' ) }
					{ ! isPro && <ProBadge /> }
				</NavLink>
			</nav>

			<Suspense fallback={ <Spinner /> }>
				<Routes>
					<Route index element={ <Navigate to="protection" replace /> } />
					<Route path="protection" element={ <LoginProtection /> } />
					<Route path="registration" element={ <RegistrationProtection /> } />
					<Route
						path="two-factor"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'Two-Factor Authentication', 'wp-edit-password-protected' ) }>
								<TwoFactor />
							</ProLock>
						}
					/>
					<Route
						path="ip-management"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'IP Management', 'wp-edit-password-protected' ) }>
								<IpManagement />
							</ProLock>
						}
					/>
					<Route path="auto-login" element={ <AutoLogin /> } />
					<Route
						path="log"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'Login Log', 'wp-edit-password-protected' ) }>
								<LoginLog />
							</ProLock>
						}
					/>
				</Routes>
			</Suspense>
		</div>
	);
};

export default Security;
