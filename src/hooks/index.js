/**
 * Custom hooks for the admin SPA.
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useRef, useEffect, useCallback, useState } from '@wordpress/element';

const STORE = 'wpepp/settings';

/**
 * Get settings for a section with common store state.
 *
 * @param {string} section Section name.
 * @return {Object} { settings, isLoading, isSaving, isPro, update, save }
 */
export function useSettings( section ) {
	const { settings, isLoading, isSaving, isPro } = useSelect( ( select ) => {
		const store = select( STORE );
		return {
			settings: store.getSectionSettings( section ),
			isLoading: store.isLoading(),
			isSaving: store.isSaving(),
			isPro: store.isPro(),
		};
	}, [ section ] );

	const { updateSetting, saveSettings } = useDispatch( STORE );

	const update = useCallback(
		( key, value ) => updateSetting( section, key, value ),
		[ section, updateSetting ]
	);

	const save = useCallback(
		() => saveSettings( section, settings ),
		[ section, settings, saveSettings ]
	);

	return { settings, isLoading, isSaving, isPro, update, save };
}

/**
 * Debounce a value.
 *
 * @param {*}      value Value to debounce.
 * @param {number} delay Milliseconds.
 * @return {*} Debounced value.
 */
export function useDebounce( value, delay = 300 ) {
	const [ debounced, setDebounced ] = useState( value );

	useEffect( () => {
		const timer = setTimeout( () => setDebounced( value ), delay );
		return () => clearTimeout( timer );
	}, [ value, delay ] );

	return debounced;
}

/**
 * Hook for live preview — manages CSS injection via postMessage to an iframe.
 *
 * @param {Object} ref   React ref to the iframe element.
 * @param {string} css   CSS string to inject.
 * @param {number} delay Debounce delay.
 */
export function useLivePreview( ref, css, delay = 200 ) {
	const debouncedCss = useDebounce( css, delay );

	useEffect( () => {
		const iframe = ref?.current;
		if ( ! iframe?.contentWindow ) {
			return;
		}
		iframe.contentWindow.postMessage(
			{ type: 'wpepp-css', css: debouncedCss },
			'*'
		);
	}, [ debouncedCss, ref ] );
}

/**
 * Check Pro status.
 *
 * @return {boolean}
 */
export function usePro() {
	return useSelect( ( select ) => select( STORE ).isPro(), [] );
}
