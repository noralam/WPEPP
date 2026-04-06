/**
 * Store reducer.
 */

import {
	SET_SETTINGS,
	SET_SECTION_SETTINGS,
	UPDATE_SETTING,
	SET_LOADING,
	SET_SAVING,
	SET_ERROR,
	SET_PRO_STATUS,
	SET_TEMPLATES,
	SET_LOGIN_LOG,
	MARK_SAVED,
} from './actions';

const DEFAULT_STATE = {
	settings: {},
	isLoading: false,
	isSaving: false,
	error: null,
	isPro: window.wpeppData?.isPro || false,
	templates: null,
	loginLog: [],
	hasChanges: false,
};

/**
 * Set a nested value using dot-notation key.
 *
 * @param {Object} obj   Target object.
 * @param {string} path  Dot-notation path.
 * @param {*}      value New value.
 * @return {Object} New object with value set.
 */
function setNestedValue( obj, path, value ) {
	const keys = path.split( '.' );
	const last = keys.pop();
	const clone = { ...obj };
	let current = clone;

	for ( const key of keys ) {
		current[ key ] = { ...( current[ key ] || {} ) };
		current = current[ key ];
	}

	current[ last ] = value;
	return clone;
}

export default function reducer( state = DEFAULT_STATE, action ) {
	switch ( action.type ) {
		case SET_SETTINGS:
			return {
				...state,
				settings: action.settings,
				hasChanges: false,
			};

		case SET_SECTION_SETTINGS:
			return {
				...state,
				settings: {
					...state.settings,
					[ action.section ]: action.settings,
				},
				hasChanges: false,
			};

		case UPDATE_SETTING: {
			const section = state.settings[ action.section ] || {};
			const updated = setNestedValue( section, action.key, action.value );
			return {
				...state,
				settings: {
					...state.settings,
					[ action.section ]: updated,
				},
				hasChanges: true,
			};
		}

		case SET_LOADING:
			return { ...state, isLoading: action.isLoading };

		case SET_SAVING:
			return { ...state, isSaving: action.isSaving };

		case SET_ERROR:
			return { ...state, error: action.error };

		case SET_PRO_STATUS:
			return { ...state, isPro: action.isPro };

		case SET_TEMPLATES:
			return { ...state, templates: action.templates };

		case SET_LOGIN_LOG:
			return { ...state, loginLog: action.log };

		case MARK_SAVED:
			return { ...state, hasChanges: false };

		default:
			return state;
	}
}
