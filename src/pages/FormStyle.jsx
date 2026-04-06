/**
 * FormStyle page — inner tabs for Login, Register, LostPassword, Password.
 */
import { __ } from '@wordpress/i18n';
import { NavLink, Routes, Route, Navigate } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import ProBadge from '../components/ProBadge';
import LoginForm from './FormStyle/LoginForm';
import ProLock from '../components/ProLock';

import { lazy, Suspense } from '@wordpress/element';
import { Spinner, Notice } from '@wordpress/components';

const RegisterForm     = lazy( () => import( /* webpackPrefetch: true */ './FormStyle/RegisterForm' ) );
const LostPasswordForm = lazy( () => import( /* webpackPrefetch: true */ './FormStyle/LostPasswordForm' ) );
const PasswordForm     = lazy( () => import( /* webpackPrefetch: true */ './FormStyle/PasswordForm' ) );

const FormStyle = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	return (
		<div className="wpepp-form-style">
			<nav className="wpepp-inner-tabs">
				<NavLink to="/form-style/login" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Login Form', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/form-style/register" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Register Form', 'wp-edit-password-protected' ) }
					{ ! isPro && <ProBadge /> }
				</NavLink>
				<NavLink to="/form-style/lost-password" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Lost Password', 'wp-edit-password-protected' ) }
					{ ! isPro && <ProBadge /> }
				</NavLink>
				<NavLink to="/form-style/password" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Password Protected Form', 'wp-edit-password-protected' ) }
				</NavLink>
			</nav>

			<Suspense fallback={ <Spinner /> }>
				<Routes>
					<Route index element={ <Navigate to="login" replace /> } />
					<Route path="login" element={ <LoginForm key="login" /> } />
					<Route
						path="register"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'Register Page Styling', 'wp-edit-password-protected' ) }>
								{ ! isPro && (
									<Notice status="warning" isDismissible={ false } className="wpepp-pro-form-notice">
										{ __( 'The live preview is available for styling exploration, but Register Form customizations will only apply on the frontend with an active Pro license.', 'wp-edit-password-protected' ) }
										{ ' ' }
										<a href={ window.wpeppData?.proUrl || '#' } target="_blank" rel="noopener noreferrer">
											{ __( 'Upgrade to Pro', 'wp-edit-password-protected' ) } →
										</a>
									</Notice>
								) }
								<RegisterForm key="register" />
							</ProLock>
						}
					/>
					<Route
						path="lost-password"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'Lost Password Page Styling', 'wp-edit-password-protected' ) }>
								{ ! isPro && (
									<Notice status="warning" isDismissible={ false } className="wpepp-pro-form-notice">
										{ __( 'The live preview is available for styling exploration, but Lost Password Form customizations will only apply on the frontend with an active Pro license.', 'wp-edit-password-protected' ) }
										{ ' ' }
										<a href={ window.wpeppData?.proUrl || '#' } target="_blank" rel="noopener noreferrer">
											{ __( 'Upgrade to Pro', 'wp-edit-password-protected' ) } →
										</a>
									</Notice>
								) }
								<LostPasswordForm key="lost-password" />
							</ProLock>
						}
					/>
					<Route path="password" element={ <PasswordForm key="password" /> } />
				</Routes>
			</Suspense>
		</div>
	);
};

export default FormStyle;
