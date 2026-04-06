/**
 * WordPress data store for plugin settings.
 *
 * @package wpepp
 */

import { createReduxStore, register } from '@wordpress/data';
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import * as resolvers from './resolvers';
import controls from './controls';

const STORE_NAME = 'wpepp/settings';

const store = createReduxStore( STORE_NAME, {
	reducer,
	actions,
	selectors,
	resolvers,
	controls,
} );

register( store );

export { STORE_NAME };
export default store;
