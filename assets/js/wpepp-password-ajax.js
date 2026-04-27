/* global wpeppPasswordAjax */
/**
 * Inline password error — no page reload.
 *
 * Intercepts submission of both:
 *   1. Post/page password forms  (.wpepp-password-form-inner → wp-login.php?action=postpass)
 *   2. Site-wide password forms  (.wpepp-password-form-inner[site] or .wpepp-site-password-form)
 *
 * Verifies the password via AJAX first.
 *   • Correct → lets the original form submit (sets cookie server-side).
 *   • Wrong   → shows inline error, clears input, re-enables submit button.
 *
 * Config is passed via wp_localize_script() as window.wpeppPasswordAjax:
 *   ajax_url   – admin-ajax.php URL
 *   error_text – error message string
 *   post_nonce – nonce for wpepp_check_password       (post forms only)
 *   post_id    – protected post ID                    (post forms only)
 *   site_nonce – nonce for wpepp_check_site_password  (site form only)
 */
( function () {
	'use strict';

	var cfg = window.wpeppPasswordAjax;
	if ( ! cfg || ! cfg.ajax_url ) {
		return;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Find or create the error message element nearest to the form.
	 *
	 * @param {HTMLFormElement} form
	 * @return {HTMLElement}
	 */
	function getOrCreateErrorEl( form ) {
		// Look inside the closest wpepp wrapper first.
		var wrapper = form.closest( '.wpepp-password-form, .wpepp-site-password-wrap' );
		var el = wrapper ? wrapper.querySelector( '.wpepp-error-message' ) : null;

		if ( ! el ) {
			el = document.createElement( 'div' );
			el.className = 'wpepp-error-message';
			form.parentNode.insertBefore( el, form );
		}

		return el;
	}

	function showError( form, msg ) {
		var el = getOrCreateErrorEl( form );
		el.textContent = msg;
		el.style.display = 'block';
	}

	function hideError( form ) {
		var wrapper = form.closest( '.wpepp-password-form, .wpepp-site-password-wrap' );
		var el = wrapper ? wrapper.querySelector( '.wpepp-error-message' ) : null;
		if ( el ) {
			el.style.display = 'none';
		}
	}

	function setLoading( btn, loading ) {
		if ( ! btn ) {
			return;
		}
		btn.disabled = loading;
		btn.style.opacity = loading ? '0.6' : '';
	}

	function getSubmitBtn( form ) {
		return form.querySelector( 'input[type="submit"], button[type="submit"]' );
	}

	/**
	 * Send AJAX password check and return a Promise<bool>.
	 *
	 * @param {string} action  WordPress AJAX action name.
	 * @param {string} nonce
	 * @param {Object} extra   Extra FormData key→value pairs.
	 * @return {Promise<boolean>}
	 */
	function checkPassword( action, nonce, extra ) {
		var fd = new FormData();
		fd.append( 'action', action );
		fd.append( 'nonce', nonce );
		Object.keys( extra ).forEach( function ( k ) {
			fd.append( k, extra[ k ] );
		} );

		return fetch( cfg.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			body: fd,
		} )
			.then( function ( r ) {
				return r.json();
			} )
			.then( function ( res ) {
				return !! res.success;
			} );
	}

	// -------------------------------------------------------------------------
	// Post password forms
	// -------------------------------------------------------------------------

	function bindPostForms() {
		if ( ! cfg.post_nonce || ! cfg.post_id ) {
			return;
		}

		var forms = document.querySelectorAll( '.wpepp-password-form-inner' );
		forms.forEach( function ( form ) {
			// Only target post-password forms (action points to postpass).
			if ( ! form.action || form.action.indexOf( 'postpass' ) === -1 ) {
				return;
			}

			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();

				var inp = form.querySelector( 'input[name="post_password"]' );
				var btn = getSubmitBtn( form );
				var pwd = inp ? inp.value : '';

				if ( ! pwd ) {
					return;
				}

				hideError( form );
				setLoading( btn, true );

				checkPassword( 'wpepp_check_password', cfg.post_nonce, {
					post_id: cfg.post_id,
					password: pwd,
				} )
					.then( function ( ok ) {
						if ( ok ) {
							form.submit();
						} else {
							setLoading( btn, false );
							showError( form, cfg.error_text );
							if ( inp ) {
								inp.value = '';
								inp.focus();
							}
						}
					} )
					.catch( function () {
						// On network error fall back to normal submit.
						form.submit();
					} );
			} );
		} );
	}

	// -------------------------------------------------------------------------
	// Site-wide password forms
	// -------------------------------------------------------------------------

	function bindSiteForm( form ) {
		if ( form.dataset.wpeppBound ) {
			return;
		}
		form.dataset.wpeppBound = '1';

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();

			var inp = form.querySelector( 'input[name="wpepp_site_password"]' );
			var btn = getSubmitBtn( form );
			var pwd = inp ? inp.value : '';

			if ( ! pwd ) {
				return;
			}

			hideError( form );
			setLoading( btn, true );

			checkPassword( 'wpepp_check_site_password', cfg.site_nonce, {
				password: pwd,
			} )
				.then( function ( ok ) {
					if ( ok ) {
						// Correct — submit to set the cookie server-side.
						form.submit();
					} else {
						setLoading( btn, false );
						showError( form, cfg.error_text );
						if ( inp ) {
							inp.value = '';
							inp.focus();
						}
					}
				} )
				.catch( function () {
					form.submit();
				} );
		} );
	}

	function bindSiteForms() {
		if ( ! cfg.site_nonce ) {
			return;
		}

		// Fallback plain form used when customizer returns empty.
		document.querySelectorAll( '.wpepp-site-password-form' ).forEach( bindSiteForm );

		// Custom form from Password Customizer (same inner class, contains site password field).
		document.querySelectorAll( '.wpepp-password-form-inner' ).forEach( function ( form ) {
			if ( form.querySelector( 'input[name="wpepp_site_password"]' ) ) {
				bindSiteForm( form );
			}
		} );
	}

	// -------------------------------------------------------------------------
	// Boot
	// -------------------------------------------------------------------------

	document.addEventListener( 'DOMContentLoaded', function () {
		bindPostForms();
		bindSiteForms();
	} );
}() );
