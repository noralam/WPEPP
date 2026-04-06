/**
 * REST API helper — thin wrapper around wp.apiFetch.
 */
import apiFetch from '@wordpress/api-fetch';

const API_NAMESPACE = 'wpepp/v1';

/**
 * Fetch from the plugin REST API.
 *
 * @param {string} endpoint Path relative to namespace, e.g. '/settings'.
 * @param {Object} options  apiFetch options (method, data, etc.).
 * @return {Promise}
 */
export function wpeppFetch( endpoint, options = {} ) {
	return apiFetch( {
		path: `/${ API_NAMESPACE }${ endpoint }`,
		...options,
	} );
}
