/**
 * Main App component — HashRouter with route-based code splitting.
 */
import { lazy, Suspense, useState, useCallback, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import Notices from './components/Notices';
import SaveBar, { SaveBarProvider } from './components/SaveBar';
import ErrorBoundary from './components/ErrorBoundary';

// Eagerly loaded (most visited).
import Dashboard from './pages/Dashboard';
import FormStyle from './pages/FormStyle';

// Lazy-loaded.
const Content     = lazy( () => import( './pages/Content' ) );
const Templates   = lazy( () => import( './pages/Templates' ) );
const Logins      = lazy( () => import( './pages/Logins' ) );
const BlockCenter = lazy( () => import( './pages/BlockCenter' ) );
const Firewall    = lazy( () => import( './pages/Security/Firewall' ) );
const Scanner     = lazy( () => import( './pages/Security/Scanner' ) );
const AntiSpam    = lazy( () => import( './pages/Security/AntiSpam' ) );
const AiCrawler   = lazy( () => import( './pages/AiCrawler' ) );
const SiteAccess  = lazy( () => import( './pages/SiteAccess' ) );
const Tools       = lazy( () => import( './pages/Tools' ) );
const Activity    = lazy( () => import( './pages/Activity' ) );
const Hardening   = lazy( () => import( './pages/Security/Hardening' ) );
const Settings    = lazy( () => import( './pages/Settings' ) );

const App = () => {
	// Trigger the getSettings resolver on every entry route so all sections are
	// available even when the user navigates directly to a sub-page.
	useSelect( ( select ) => select( 'wpepp/settings' ).getSettings() );

	// Admin color theme — persisted server-side via the /theme endpoint.
	const [ theme, setThemeState ] = useState(
		window.wpeppData?.adminTheme === 'light' ? 'light' : 'dark'
	);

	useEffect( () => {
		if ( typeof document !== 'undefined' ) {
			document.body.classList.toggle( 'wpepp-theme-light', theme === 'light' );
			document.body.classList.toggle( 'wpepp-theme-dark', theme === 'dark' );
		}
	}, [ theme ] );

	const setTheme = useCallback( ( next ) => {
		const value = next === 'light' ? 'light' : 'dark';
		setThemeState( value );

		apiFetch( {
			path: '/wpepp/v1/theme',
			method: 'POST',
			data: { theme: value },
		} ).catch( () => {} );
	}, [] );

	return (
		<HashRouter>
			<SaveBarProvider>
				<div className="wpepp-admin-wrap" data-theme={ theme }>
					<Sidebar />
					<div className="wpepp-admin-main">
						<Header theme={ theme } onThemeChange={ setTheme } />
						<Notices />
						<div className="wpepp-admin-content">
							<ErrorBoundary>
							<Suspense fallback={ <Spinner /> }>
								<Routes>
									<Route path="/" element={ <Dashboard /> } />
									<Route path="/form-style/*" element={ <FormStyle /> } />
									<Route path="/content/*" element={ <Content /> } />
									<Route path="/templates/*" element={ <Templates /> } />

									{/* Protect group */}
									<Route path="/firewall" element={ <Firewall /> } />
									<Route path="/scanner" element={ <Scanner /> } />
									<Route path="/anti-spam/*" element={ <AntiSpam /> } />
									<Route path="/blocks/*" element={ <BlockCenter /> } />
									<Route path="/logins/*" element={ <Logins /> } />
									<Route path="/site-access/*" element={ <SiteAccess /> } />

									{/* System group */}
									<Route path="/ai-crawler" element={ <AiCrawler /> } />
									<Route path="/tools/*" element={ <Tools /> } />
						<Route path="/activity" element={ <Activity /> } />
						<Route path="/hardening" element={ <Hardening /> } />
									<Route path="/settings/*" element={ <Settings /> } />

									{/* Legacy deep-link redirects */}
									<Route path="/security/protection" element={ <Navigate to="/logins/protection" replace /> } />
									<Route path="/security/registration" element={ <Navigate to="/logins/registration" replace /> } />
									<Route path="/security/two-factor" element={ <Navigate to="/logins/two-factor" replace /> } />
									<Route path="/security/custom-url" element={ <Navigate to="/logins/custom-url" replace /> } />
									<Route path="/security/auto-login" element={ <Navigate to="/logins/auto-login" replace /> } />
									<Route path="/security/firewall" element={ <Navigate to="/firewall" replace /> } />
									<Route path="/security/scanner" element={ <Navigate to="/scanner" replace /> } />
									<Route path="/security/blocks" element={ <Navigate to="/blocks/list" replace /> } />
									<Route path="/security/ip-management" element={ <Navigate to="/blocks/ip-lists" replace /> } />
									<Route path="/security/log" element={ <Navigate to="/tools/log" replace /> } />
									<Route path="/cpu-monitor/*" element={ <Navigate to="/tools/cpu-monitor" replace /> } />
									<Route path="/security" element={ <Navigate to="/logins" replace /> } />

									<Route path="*" element={ <Navigate to="/" replace /> } />
								</Routes>
								</Suspense>
							</ErrorBoundary>
						</div>
						<SaveBar />
					</div>
				</div>
			</SaveBarProvider>
		</HashRouter>
	);
};

export default App;
