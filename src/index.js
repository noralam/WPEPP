/**
 * Entry point — boots the React admin SPA.
 */
import { createRoot } from '@wordpress/element';
import App from './App';
import './store';
import './styles/admin.scss';

const container = document.getElementById( 'wpepp-admin-root' );
if ( container ) {
	const root = createRoot( container );
	root.render( <App /> );
}
