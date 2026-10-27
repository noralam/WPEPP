/**
 * Logins — login protection, registration, 2FA, custom URL, auto-login.
 */
import { __ } from '@wordpress/i18n';
import { NavLink, Routes, Route, Navigate } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import { lazy, Suspense } from '@wordpress/element';
import { CardSkeleton } from '../components/Skeleton';
import ProLock from '../components/ProLock';

const LoginProtection        = lazy( () => import( './Security/LoginProtection' ) );
const RegistrationProtection = lazy( () => import( './Security/RegistrationProtection' ) );
const TwoFactor              = lazy( () => import( './Security/TwoFactor' ) );
const CustomLoginUrl         = lazy( () => import( './Security/CustomLoginUrl' ) );
const AutoLogin              = lazy( () => import( './Security/AutoLogin' ) );
const Passwords              = lazy( () => import( './Security/Passwords' ) );
const Passkeys               = lazy( () => import( './Security/Passkeys' ) );

const TABS = [
	{ to: '/logins/protection', label: __( 'Login Protection', 'wp-edit-password-protected' ) },
	{ to: '/logins/registration', label: __( 'Registration', 'wp-edit-password-protected' ) },
	{ to: '/logins/passwords', label: __( 'Passwords', 'wp-edit-password-protected' ) },
	{ to: '/logins/two-factor', label: __( 'Two-Factor Auth', 'wp-edit-password-protected' ) },
	{ to: '/logins/passkeys', label: __( 'Passkeys', 'wp-edit-password-protected' ), pro: true },
	{ to: '/logins/custom-url', label: __( 'Custom Login URL', 'wp-edit-password-protected' ) },
	{ to: '/logins/auto-login', label: __( 'Auto Login', 'wp-edit-password-protected' ) },
];

const Logins = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	return (
		<div className="wpepp-security-page">
			<nav className="wpepp-inner-tabs">
				{ TABS.map( ( tab ) => (
					<NavLink key={ tab.to } to={ tab.to } className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
						{ tab.label }
						{ tab.pro && ! isPro && <span className="wpepp-pro-badge">PRO</span> }
					</NavLink>
				) ) }
			</nav>

			<Suspense fallback={ <CardSkeleton rows={ 4 } /> }>
				<Routes>
					<Route index element={ <Navigate to="/logins/protection" replace /> } />
					<Route path="protection" element={ <LoginProtection /> } />
					<Route path="registration" element={ <RegistrationProtection /> } />
					<Route path="passwords" element={ <Passwords /> } />
					<Route path="two-factor" element={ <TwoFactor /> } />
					<Route
						path="passkeys"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'Passkeys', 'wp-edit-password-protected' ) }>
								<Passkeys />
							</ProLock>
						}
					/>
					<Route
						path="custom-url"
						element={ <CustomLoginUrl /> }
					/>
					<Route path="auto-login" element={ <AutoLogin /> } />
					<Route path="*" element={ <Navigate to="/logins/protection" replace /> } />
				</Routes>
			</Suspense>
		</div>
	);
};

export default Logins;
