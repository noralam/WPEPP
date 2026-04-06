/**
 * Conditional display — front-end script.
 *
 * Handles client-side show/hide of conditionally-displayed elements.
 * Used for JavaScript-only conditions when AJAX evaluation is enabled.
 *
 * @package wpepp
 * @since   2.0.0
 */
( function() {
	'use strict';

	if ( typeof wpeppCondition === 'undefined' ) {
		return;
	}

	var config = wpeppCondition;

	/**
	 * Check a device-type condition client-side.
	 */
	function checkDeviceType( expected ) {
		var w = window.innerWidth;
		if ( expected === 'mobile' ) {
			return w < 768;
		}
		if ( expected === 'tablet' ) {
			return w >= 768 && w < 1024;
		}
		if ( expected === 'desktop' ) {
			return w >= 1024;
		}
		return true;
	}

	/**
	 * Check URL parameter condition.
	 */
	function checkUrlParam( param, value ) {
		if ( ! param ) {
			return false;
		}
		var params = new URLSearchParams( window.location.search );
		if ( value ) {
			return params.get( param ) === value;
		}
		return params.has( param );
	}

	/**
	 * Process each conditional element.
	 */
	function processConditions() {
		if ( ! config.conditions || ! config.conditions.length ) {
			return;
		}

		config.conditions.forEach( function( cond ) {
			var el = document.getElementById( cond.element_id );
			if ( ! el ) {
				return;
			}

			var met = true;

			if ( cond.type === 'device_type' ) {
				met = checkDeviceType( cond.value );
			} else if ( cond.type === 'url_parameter' ) {
				met = checkUrlParam( cond.param, cond.value );
			}

			if ( cond.action === 'hide' ) {
				el.style.display = met ? 'none' : '';
			} else {
				el.style.display = met ? '' : 'none';
			}
		} );
	}

	// Run on DOMContentLoaded and on resize (for device type checks).
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', processConditions );
	} else {
		processConditions();
	}

	var resizeTimer;
	window.addEventListener( 'resize', function() {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( processConditions, 250 );
	} );
} )();
