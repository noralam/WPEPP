/**
 * Logs & Tools — login activity log (Pro) and CPU monitor.
 */
import { __ } from '@wordpress/i18n';
import { NavLink, Routes, Route, Navigate } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import { lazy, Suspense } from '@wordpress/element';
import ProLock from '../components/ProLock';
import Loading from '../components/Loading';

const LoginLog  = lazy( () => import( './Security/LoginLog' ) );
const CpuMonitor = lazy( () => import( './CpuMonitor' ) );

const Tools = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	return (
		<div className="wpepp-security-page">
			<nav className="wpepp-inner-tabs">
				<NavLink to="/tools/cpu-monitor" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'CPU Monitor', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/tools/log" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Login Log', 'wp-edit-password-protected' ) }
					{ ! isPro && <span className="wpepp-pro-badge">PRO</span> }
				</NavLink>
			</nav>

			<Suspense fallback={ <Loading variant="table" count={ 6 } /> }>
				<Routes>
					<Route index element={ <Navigate to="/tools/cpu-monitor" replace /> } />
					<Route path="cpu-monitor/*" element={ <CpuMonitor /> } />
					<Route
						path="log"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'Login Activity Log', 'wp-edit-password-protected' ) }>
								<LoginLog />
							</ProLock>
						}
					/>
					<Route path="overview" element={ <Navigate to="/tools/cpu-monitor/overview" replace /> } />
					<Route path="queries" element={ <Navigate to="/tools/cpu-monitor/slow-queries" replace /> } />
					<Route path="slow-queries" element={ <Navigate to="/tools/cpu-monitor/slow-queries" replace /> } />
					<Route path="cron" element={ <Navigate to="/tools/cpu-monitor/cron-jobs" replace /> } />
					<Route path="cron-jobs" element={ <Navigate to="/tools/cpu-monitor/cron-jobs" replace /> } />
					<Route path="errors" element={ <Navigate to="/tools/cpu-monitor/error-log" replace /> } />
					<Route path="error-log" element={ <Navigate to="/tools/cpu-monitor/error-log" replace /> } />
					<Route path="plugins" element={ <Navigate to="/tools/cpu-monitor/plugin-performance" replace /> } />
					<Route path="plugin-performance" element={ <Navigate to="/tools/cpu-monitor/plugin-performance" replace /> } />
					<Route path="options" element={ <Navigate to="/tools/cpu-monitor/options-bloat" replace /> } />
					<Route path="options-bloat" element={ <Navigate to="/tools/cpu-monitor/options-bloat" replace /> } />
					<Route path="*" element={ <Navigate to="/tools/cpu-monitor" replace /> } />
				</Routes>
			</Suspense>
		</div>
	);
};

export default Tools;
