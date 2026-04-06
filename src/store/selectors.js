/**
 * Store selectors.
 */

/**
 * Get all settings.
 *
 * @param {Object} state Store state.
 * @return {Object}
 */
export function getSettings( state ) {
	return state.settings;
}

/**
 * Get settings for a section.
 *
 * @param {Object} state   Store state.
 * @param {string} section Section name.
 * @return {Object}
 */
export function getSectionSettings( state, section ) {
	return state.settings[ section ] || {};
}

/**
 * Get a single setting value.
 *
 * @param {Object} state   Store state.
 * @param {string} section Section name.
 * @param {string} key     Setting key (dot notation).
 * @return {*}
 */
export function getSetting( state, section, key ) {
	const sectionSettings = state.settings[ section ] || {};
	const keys = key.split( '.' );
	let value = sectionSettings;
	for ( const k of keys ) {
		if ( value === undefined || value === null ) {
			return undefined;
		}
		value = value[ k ];
	}
	return value;
}

/**
 * Is Pro active?
 *
 * @param {Object} state Store state.
 * @return {boolean}
 */
export function isPro( state ) {
	return state.isPro;
}

/**
 * Is loading?
 *
 * @param {Object} state Store state.
 * @return {boolean}
 */
export function isLoading( state ) {
	return state.isLoading;
}

/**
 * Is saving?
 *
 * @param {Object} state Store state.
 * @return {boolean}
 */
export function isSaving( state ) {
	return state.isSaving;
}

/**
 * Has unsaved changes?
 *
 * @param {Object} state Store state.
 * @return {boolean}
 */
export function hasChanges( state ) {
	return state.hasChanges;
}

/**
 * Get error message.
 *
 * @param {Object} state Store state.
 * @return {string|null}
 */
export function getError( state ) {
	return state.error;
}

/**
 * Get templates list.
 *
 * @param {Object} state Store state.
 * @return {Array}
 */
export function getTemplates( state ) {
	return state.templates;
}

/**
 * Get login log.
 *
 * @param {Object} state Store state.
 * @return {Array}
 */
export function getLoginLog( state ) {
	return state.loginLog;
}
