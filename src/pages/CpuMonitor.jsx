/**
 * CPU Monitor page — inner tabs for Overview, Slow Queries, Cron Jobs, etc.
 */
import { __ } from '@wordpress/i18n';
import { NavLink, Routes, Route, Navigate } from 'react-router-dom';
import { useSelect } from '@wordpress/data';
import { lazy, Suspense } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import ProBadge from '../components/ProBadge';
import ProLock from '../components/ProLock';

const Overview          = lazy( () => import( './CpuMonitor/Overview' ) );
const SlowQueries       = lazy( () => import( './CpuMonitor/SlowQueries' ) );
const CronJobs          = lazy( () => import( './CpuMonitor/CronJobs' ) );
const ErrorLog          = lazy( () => import( './CpuMonitor/ErrorLog' ) );
const PluginPerformance = lazy( () => import( './CpuMonitor/PluginPerformance' ) );
const OptionsBloat      = lazy( () => import( './CpuMonitor/OptionsBloat' ) );

const CpuMonitor = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	return (
		<div className="wpepp-cpu-monitor-page">
			<nav className="wpepp-inner-tabs">
				<NavLink to="/cpu-monitor/overview" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Overview', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/cpu-monitor/slow-queries" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Slow Queries', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/cpu-monitor/cron-jobs" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Cron Jobs', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/cpu-monitor/error-log" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Error Log', 'wp-edit-password-protected' ) }
					{ ! isPro && <ProBadge /> }
				</NavLink>
				<NavLink to="/cpu-monitor/plugin-performance" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Plugin Performance', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/cpu-monitor/options-bloat" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Options Bloat', 'wp-edit-password-protected' ) }
				</NavLink>
			</nav>

			<Suspense fallback={ <Spinner /> }>
				<Routes>
					<Route index element={ <Navigate to="overview" replace /> } />
					<Route path="overview" element={ <Overview /> } />
					<Route path="slow-queries" element={ <SlowQueries /> } />
					<Route path="cron-jobs" element={ <CronJobs /> } />
					<Route
						path="error-log"
						element={
							<ProLock isPro={ isPro } featureName={ __( 'Error Log', 'wp-edit-password-protected' ) }>
								<ErrorLog />
							</ProLock>
						}
					/>
					<Route path="plugin-performance" element={ <PluginPerformance /> } />
					<Route path="options-bloat" element={ <OptionsBloat /> } />
				</Routes>
			</Suspense>
		</div>
	);
};

export default CpuMonitor;
