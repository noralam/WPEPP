/**
 * Store resolvers — fetch data on first selector access.
 */

import { setSettings, setLoading, setError, setTemplates } from './actions';

/**
 * Resolve getSettings — load all settings from REST API.
 */
export function* getSettings() {
	yield setLoading( true );

	try {
		const result = yield {
			type: 'API_FETCH',
			request: { path: '/wpepp/v1/settings' },
		};
		yield setSettings( result );
	} catch ( err ) {
		yield setError( err.message || 'Failed to load settings.' );
	}

	yield setLoading( false );

	// Pre-fetch templates so they're ready before navigating to the Templates tab.
	try {
		const templates = yield {
			type: 'API_FETCH',
			request: { path: '/wpepp/v1/templates' },
		};
		yield setTemplates( templates );
	} catch ( e ) {
		// Silent — templates will be fetched on demand by their own resolver.
	}
}

/**
 * Resolve getTemplates — load templates list.
 */
export function* getTemplates() {
	try {
		const result = yield {
			type: 'API_FETCH',
			request: { path: '/wpepp/v1/templates' },
		};
		yield setTemplates( result );
	} catch ( err ) {
		yield setError( err.message || 'Failed to load templates.' );
	}
}
