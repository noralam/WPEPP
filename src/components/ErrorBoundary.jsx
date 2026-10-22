/**
 * ErrorBoundary — catches render/lazy-load failures inside the SPA content
 * area and shows the real error instead of silently blanking the page.
 */
import { Component } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Notice } from '@wordpress/components';

class ErrorBoundary extends Component {
	constructor( props ) {
		super( props );
		this.state = { error: null };
	}

	static getDerivedStateFromError( error ) {
		return { error };
	}

	componentDidCatch( error, info ) {
		// Surface in console too — devs expect it there.
		// eslint-disable-next-line no-console
		console.error( '[WPEPP]', error, info?.componentStack );
	}

	render() {
		if ( this.state.error ) {
			return (
				<div className="wpepp-error-boundary" style={ { padding: 24 } }>
					<Notice status="error" isDismissible={ false }>
						<p>
							<strong>{ __( 'This panel failed to load.', 'wp-edit-password-protected' ) }</strong>
						</p>
						<pre style={ {
							whiteSpace: 'pre-wrap',
							fontSize: 11,
							maxHeight: 160,
							overflow: 'auto',
							margin: '8px 0',
						} }>
							{ String( this.state.error?.message || this.state.error ) }
						</pre>
						<Button variant="secondary" onClick={ () => window.location.reload() }>
							{ __( 'Reload', 'wp-edit-password-protected' ) }
						</Button>
					</Notice>
				</div>
			);
		}

		return this.props.children;
	}
}

export default ErrorBoundary;
