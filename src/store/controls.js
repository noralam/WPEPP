/**
 * Store controls — side effects (API calls).
 */

import apiFetch from '@wordpress/api-fetch';

const controls = {
	API_FETCH( action ) {
		return apiFetch( action.request );
	},
};

export default controls;
