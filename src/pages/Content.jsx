/**
 * Content page — inner tabs for Content Lock, Conditional Display, Member Template.
 */
import { __ } from '@wordpress/i18n';
import { NavLink, Routes, Route, Navigate } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import { lazy, Suspense } from '@wordpress/element';
import { CardSkeleton } from '../components/Skeleton';
import ProBadge from '../components/ProBadge';
import ProLock from '../components/ProLock';

const ContentLock        = lazy( () => import( './Content/ContentLock' ) );
const ConditionalDisplay = lazy( () => import( './Content/ConditionalDisplay' ) );
const MemberTemplate     = lazy( () => import( './Content/MemberTemplate' ) );

const Content = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	return (
		<div className="wpepp-content-page">
			<nav className="wpepp-inner-tabs">
				<NavLink to="/content/lock" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Content Lock', 'wp-edit-password-protected' ) }
					{ ! isPro && <ProBadge /> }
				</NavLink>
				<NavLink to="/content/conditional" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Conditional Display', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/content/member-template" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Member Template', 'wp-edit-password-protected' ) }
				</NavLink>
			</nav>

			<Suspense fallback={ <CardSkeleton rows={ 4 } /> }>
				<Routes>
					<Route index element={ <Navigate to="/content/lock" replace /> } />
					<Route
						path="lock"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'Content Lock', 'wp-edit-password-protected' ) }>
								<ContentLock />
							</ProLock>
						}
					/>
					<Route path="conditional" element={ <ConditionalDisplay /> } />
					<Route path="member-template" element={ <MemberTemplate /> } />
					<Route path="template" element={ <Navigate to="/content/member-template" replace /> } />
					<Route path="*" element={ <Navigate to="/content/lock" replace /> } />
				</Routes>
			</Suspense>
		</div>
	);
};

export default Content;
