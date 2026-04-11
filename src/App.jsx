/**
 * Main App component — HashRouter with route-based code splitting.
 */
import { lazy, Suspense } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { HashRouter, Routes, Route, Navigate } from 'react-router-dom';
import Sidebar from './components/Sidebar';
import Header from './components/Header';
import Notices from './components/Notices';
import SaveBar, { SaveBarProvider } from './components/SaveBar';

// Eagerly loaded (most visited).
import Dashboard from './pages/Dashboard';
import FormStyle from './pages/FormStyle';

// Lazy-loaded.
const Content       = lazy( () => import( './pages/Content' ) );
const Templates     = lazy( () => import( './pages/Templates' ) );
const Security      = lazy( () => import( './pages/Security' ) );
const AiCrawler     = lazy( () => import( './pages/AiCrawler' ) );
const SiteAccess    = lazy( () => import( './pages/SiteAccess' ) );
const Settings      = lazy( () => import( './pages/Settings' ) );
const CpuMonitor    = lazy( () => import( './pages/CpuMonitor' ) );

const App = () => {
	// Trigger the getSettings resolver on every entry route so all sections are
	// available even when the user navigates directly to a sub-page.
	useSelect( ( select ) => select( 'wpepp/settings' ).getSettings() );

	return (
	<HashRouter>
		<SaveBarProvider>
			<div className="wpepp-admin-wrap">
				<Sidebar />
				<div className="wpepp-admin-main">
					<Header />
					<Notices />
					<div className="wpepp-admin-content">
						<Suspense fallback={ <Spinner /> }>
							<Routes>
								<Route path="/" element={ <Dashboard /> } />
								<Route path="/form-style/*" element={ <FormStyle /> } />
								<Route path="/content/*" element={ <Content /> } />
								<Route path="/templates/*" element={ <Templates /> } />
								<Route path="/security/*" element={ <Security /> } />
						<Route path="/ai-crawler" element={ <AiCrawler /> } />							<Route path="/site-access/*" element={ <SiteAccess /> } />								<Route path="/settings/*" element={ <Settings /> } />
								<Route path="/cpu-monitor/*" element={ <CpuMonitor /> } />
								<Route path="*" element={ <Navigate to="/" replace /> } />
							</Routes>
						</Suspense>
					</div>
					<SaveBar />
				</div>
			</div>
		</SaveBarProvider>
	</HashRouter>
	);
};

export default App;
