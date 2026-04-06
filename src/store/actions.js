/**
 * Store action types and action creators.
 */

// Action types.
export const SET_SETTINGS = 'SET_SETTINGS';
export const SET_SECTION_SETTINGS = 'SET_SECTION_SETTINGS';
export const UPDATE_SETTING = 'UPDATE_SETTING';
export const SET_LOADING = 'SET_LOADING';
export const SET_SAVING = 'SET_SAVING';
export const SET_ERROR = 'SET_ERROR';
export const SET_PRO_STATUS = 'SET_PRO_STATUS';
export const SET_TEMPLATES = 'SET_TEMPLATES';
export const SET_LOGIN_LOG = 'SET_LOGIN_LOG';
export const MARK_SAVED = 'MARK_SAVED';

/**
 * Set all settings.
 *
 * @param {Object} settings All settings keyed by section.
 * @return {Object} Action.
 */
export function setSettings( settings ) {
	return { type: SET_SETTINGS, settings };
}

/**
 * Set settings for a single section.
 *
 * @param {string} section  Section name.
 * @param {Object} settings Section settings object.
 * @return {Object} Action.
 */
export function setSectionSettings( section, settings ) {
	return { type: SET_SECTION_SETTINGS, section, settings };
}

/**
 * Update a single setting value.
 *
 * @param {string} section Section name.
 * @param {string} key     Setting key (supports dot notation).
 * @param {*}      value   New value.
 * @return {Object} Action.
 */
export function updateSetting( section, key, value ) {
	return { type: UPDATE_SETTING, section, key, value };
}

/**
 * Set loading state.
 *
 * @param {boolean} isLoading Loading flag.
 * @return {Object} Action.
 */
export function setLoading( isLoading ) {
	return { type: SET_LOADING, isLoading };
}

/**
 * Set saving state.
 *
 * @param {boolean} isSaving Saving flag.
 * @return {Object} Action.
 */
export function setSaving( isSaving ) {
	return { type: SET_SAVING, isSaving };
}

/**
 * Set error message.
 *
 * @param {string|null} error Error message.
 * @return {Object} Action.
 */
export function setError( error ) {
	return { type: SET_ERROR, error };
}

/**
 * Set Pro status.
 *
 * @param {boolean} isPro Pro flag.
 * @return {Object} Action.
 */
export function setProStatus( isPro ) {
	return { type: SET_PRO_STATUS, isPro };
}

/**
 * Set templates list.
 *
 * @param {Array} templates Templates array.
 * @return {Object} Action.
 */
export function setTemplates( templates ) {
	return { type: SET_TEMPLATES, templates };
}

/**
 * Set login log entries.
 *
 * @param {Array} log Log entries.
 * @return {Object} Action.
 */
export function setLoginLog( log ) {
	return { type: SET_LOGIN_LOG, log };
}

/**
 * Mark settings as saved (clears dirty flag).
 *
 * @return {Object} Action.
 */
export function markSaved() {
	return { type: MARK_SAVED };
}

/**
 * Save settings for a section (thunk).
 *
 * @param {string} section  Section name.
 * @param {Object} settings Settings data.
 */
export function* saveSettings( section, settings ) {
	yield setSaving( true );
	yield setError( null );

	try {
		// If settings not passed, get from current store state via the section.
		const data = settings || {};
		yield {
			type: 'API_FETCH',
			request: {
				path: `/wpepp/v1/settings/${ section }`,
				method: 'POST',
				data,
			},
		};
		yield setSectionSettings( section, data );
		yield markSaved();
	} catch ( err ) {
		yield setError( err.message || 'Save failed.' );
	}

	yield setSaving( false );
}

/**
 * Reset settings for a section — delete from server and restore to defaults.
 *
 * @param {string} section Section name.
 */
export function* resetSettings( section ) {
	yield setLoading( true );
	yield setError( null );

	try {
		yield {
			type: 'API_FETCH',
			request: {
				path: `/wpepp/v1/settings/${ section }`,
				method: 'DELETE',
			},
		};
		// Clear the section in the store so the UI falls back to client-side defaults.
		yield setSectionSettings( section, {} );
		yield markSaved();
	} catch ( err ) {
		yield setError( err.message || 'Reset failed.' );
	}

	yield setLoading( false );
}

/**
 * Apply a template.
 *
 * @param {string} templateId Template ID.
 * @param {string} section    Optional section to apply (login, register, password, lostpassword).
 */
export function* applyTemplate( templateId, section ) {
	yield setSaving( true );
	yield setError( null );

	try {
		const data = { template_id: templateId };
		if ( section ) {
			data.section = section;
		}
		yield {
			type: 'API_FETCH',
			request: {
				path: '/wpepp/v1/templates/apply',
				method: 'POST',
				data,
			},
		};
		// Re-fetch all settings to get the applied template values.
		const freshSettings = yield {
			type: 'API_FETCH',
			request: { path: '/wpepp/v1/settings' },
		};
		yield setSettings( freshSettings );
		yield markSaved();
		yield setSaving( false );
	} catch ( err ) {
		yield setError( err.message || 'Apply failed.' );
		yield setSaving( false );
		throw err;
	}
}

/**
 * Import settings JSON.
 *
 * @param {string} json JSON string.
 */
export function* importSettings( json ) {
	yield setSaving( true );
	yield setError( null );

	try {
		const parsed = typeof json === 'string' ? JSON.parse( json ) : json;
		yield {
			type: 'API_FETCH',
			request: {
				path: '/wpepp/v1/templates/import',
				method: 'POST',
				data: parsed,
			},
		};
		// Re-fetch all settings.
		const freshSettings = yield {
			type: 'API_FETCH',
			request: { path: '/wpepp/v1/settings' },
		};
		yield setSettings( freshSettings );
		yield markSaved();
	} catch ( err ) {
		yield setError( err.message || 'Import failed.' );
	}

	yield setSaving( false );
}

/**
 * Fetch login log entries.
 */
export function* fetchLoginLog() {
	yield setLoading( true );

	try {
		const result = yield {
			type: 'API_FETCH',
			request: { path: '/wpepp/v1/security/log' },
		};
		yield setLoginLog( Array.isArray( result ) ? result : [] );
	} catch ( err ) {
		yield setError( err.message || 'Failed to fetch log.' );
	}

	yield setLoading( false );
}
