/**
 * Block Center — manual blocks + IP list management.
 */
import { __ } from '@wordpress/i18n';
import { NavLink, Routes, Route, Navigate } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import { lazy, Suspense } from '@wordpress/element';
import { TableSkeleton } from '../components/Skeleton';
import ProLock from '../components/ProLock';

const Blocks       = lazy( () => import( './Security/Blocks' ) );
const IpManagement = lazy( () => import( './Security/IpManagement' ) );

const BlockCenter = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	return (
		<div className="wpepp-security-page">
			<nav className="wpepp-inner-tabs">
				<NavLink to="/blocks/list" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Blocks', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/blocks/ip-lists" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'IP Lists', 'wp-edit-password-protected' ) }
					{ ! isPro && <span className="wpepp-pro-badge">PRO</span> }
				</NavLink>
			</nav>

			<Suspense fallback={ <TableSkeleton rows={ 6 } columns={ 5 } /> }>
				<Routes>
					<Route index element={ <Navigate to="/blocks/list" replace /> } />
					<Route path="list" element={ <Blocks /> } />
					<Route
						path="ip-lists"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'IP Lists', 'wp-edit-password-protected' ) }>
								<IpManagement />
							</ProLock>
						}
					/>
					<Route path="*" element={ <Navigate to="/blocks/list" replace /> } />
				</Routes>
			</Suspense>
		</div>
	);
};

export default BlockCenter;
