/**
 * Meta box — admin JavaScript for content lock + conditional meta boxes.
 *
 * Handles toggling condition fields visibility based on condition type select.
 *
 * @package wpepp
 * @since   2.0.0
 */
( function( $ ) {
	'use strict';

	$( document ).ready( function() {
		// Content lock meta box — toggle password fields.
		$( '#wpepp-content-lock-enabled' ).on( 'change', function() {
			$( '.wpepp-content-lock-fields' ).toggle( this.checked );
		} ).trigger( 'change' );

		// Content lock — toggle redirect URL field visibility.
		$( '#wpepp-lock-action' ).on( 'change', function() {
			$( '.wpepp-lock-redirect-field' ).toggle( $( this ).val() === 'redirect' );
		} );

		// Content lock — toggle excerpt text field visibility.
		// Show custom text field when unchecked (checked = auto excerpt).
		$( 'input[name="_wpepp_content_lock_show_excerpt"]' ).on( 'change', function() {
			$( '.wpepp-lock-excerpt-field' ).toggle( this.checked );
		} );

		// ── Conditional Display meta box ──

		// Initialize select2 on multi-select fields.
		$( '.wpepp-select2' ).select2( { width: '100%' } );

		// Toggle condition-specific fields when condition dropdown changes.
		$( '#wpepp-cond-condition' ).on( 'change', function() {
			var selected = $( this ).val();
			$( '.wpepp-cond-field' ).hide();
			$( '.wpepp-cond-' + selected ).show();
		} );

		// Toggle enable/disable options.
		$( 'input[name="_wpepp_conditional_display_enable"]' ).on( 'change', function() {
			$( this ).closest( '.wpepp-meta-conditional' ).find( '.wpepp-cond-options' ).toggle( this.checked );
		} ).trigger( 'change' );

		// Toggle notice text field when notice checkbox changes.
		$( '#wpepp-cond-notice-enable' ).on( 'change', function() {
			$( '.wpepp-cond-notice-field' ).toggle( this.checked );
		} );
	} );
} )( jQuery );
