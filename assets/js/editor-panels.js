/**
 * Block Editor sidebar panels for Content Lock & Conditional Display.
 *
 * Replaces PHP meta boxes in the block editor with native
 * PluginDocumentSettingPanel components so the bottom "Meta Boxes"
 * area is not triggered.
 *
 * @package wpepp
 */
( function ( wp ) {
	'use strict';

	var el                         = wp.element.createElement;
	var Fragment                   = wp.element.Fragment;
	var registerPlugin             = wp.plugins.registerPlugin;
	var PluginDocumentSettingPanel  = ( wp.editor || wp.editPost ).PluginDocumentSettingPanel;
	var useSelect                  = wp.data.useSelect;
	var useEntityProp              = wp.coreData.useEntityProp;
	var CheckboxControl            = wp.components.CheckboxControl;
	var SelectControl              = wp.components.SelectControl;
	var TextareaControl            = wp.components.TextareaControl;
	var TextControl                = wp.components.TextControl;
	var FormTokenField             = wp.components.FormTokenField;
	var __                         = wp.i18n.__;

	var data    = window.wpeppEditorData || {};
	var isPro   = !! data.isPro;
	var roles   = data.roles || [];
	var ptList  = data.postTypes || [];

	/* ── helpers ── */

	function useMeta() {
		var postType = useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		} );
		var ref = useEntityProp( 'postType', postType, 'meta' );
		return { meta: ref[ 0 ] || {}, setMeta: ref[ 1 ] };
	}

	function update( setMeta, key, value ) {
		var obj = {};
		obj[ key ] = value;
		setMeta( obj );
	}

	function multiCheckbox( label, options, current, onChange ) {
		if ( ! Array.isArray( current ) ) {
			current = [];
		}
		return el( Fragment, null,
			el( 'p', { style: { fontWeight: 600, marginBottom: 4, fontSize: '11.5px', color: 'rgba(255,255,255,.55)' } }, label ),
			options.map( function ( opt ) {
				return el( CheckboxControl, {
					key: opt.value,
					label: opt.label,
					checked: current.indexOf( opt.value ) !== -1,
					onChange: function ( checked ) {
						var next = current.slice();
						if ( checked ) {
							if ( next.indexOf( opt.value ) === -1 ) { next.push( opt.value ); }
						} else {
							next = next.filter( function ( v ) { return v !== opt.value; } );
						}
						onChange( next );
					},
				} );
			} )
		);
	}

	/**
	 * Select2-style multi-select using FormTokenField.
	 * Maps between value slugs and display labels.
	 */
	function multiSelect( label, options, current, onChange, placeholder ) {
		if ( ! Array.isArray( current ) ) {
			current = [];
		}
		// Build lookup maps.
		var valueToLabel = {};
		var labelToValue = {};
		var suggestions  = [];
		options.forEach( function ( opt ) {
			valueToLabel[ opt.value ] = opt.label;
			labelToValue[ opt.label ] = opt.value;
			suggestions.push( opt.label );
		} );
		// Convert stored values to display labels.
		var tokens = current.map( function ( v ) {
			return valueToLabel[ v ] || v;
		} );
		return el( 'div', { className: 'wpepp-multi-select', style: { marginBottom: 16 } },
			el( 'p', { className: 'components-base-control__label', style: { fontWeight: 600, marginBottom: 4, fontSize: '11px', textTransform: 'uppercase' } }, label ),
			el( FormTokenField, {
				value: tokens,
				suggestions: suggestions,
				onChange: function ( nextTokens ) {
					// Only keep tokens that match a known option.
					var nextValues = [];
					nextTokens.forEach( function ( t ) {
						var val = labelToValue[ t ];
						if ( val ) { nextValues.push( val ); }
					} );
					onChange( nextValues );
				},
				placeholder: tokens.length ? '' : placeholder || '',
				__experimentalExpandOnFocus: true,
				__experimentalShowHowTo: false,
				__experimentalValidateInput: function ( token ) {
					return suggestions.indexOf( token ) !== -1;
				},
			} )
		);
	}

	/* ========================================
	   Content Lock Panel
	   ======================================== */

	function ContentLockPanel() {
		var ref     = useMeta();
		var meta    = ref.meta;
		var setMeta = ref.setMeta;

		var enabled     = meta._wpepp_content_lock_enabled === 'yes';
		var action      = meta._wpepp_content_lock_action || 'link';
		var message     = meta._wpepp_content_lock_message || '';
		var redirect    = meta._wpepp_content_lock_redirect || '';
		var header      = meta._wpepp_content_lock_header || '';
		var lockRoles   = meta._wpepp_content_lock_roles || [];
		var expiry      = meta._wpepp_content_lock_expiry || '';
		var showExcerpt = meta._wpepp_content_lock_show_excerpt === 'yes';
		var excerptText = meta._wpepp_content_lock_excerpt_text || '';

		return el( PluginDocumentSettingPanel, {
			name: 'wpepp-content-lock',
			title: __( 'Content Lock', 'wp-edit-password-protected' ),
			icon: 'lock',
			className: 'wpepp-editor-panel-content-lock',
		},
			! isPro && el( 'p', { style: { color: '#f59e0b', fontStyle: 'italic', fontSize: '12px', margin: '0 0 8px' } },
				__( 'Content Lock requires the Pro version.', 'wp-edit-password-protected' )
			),
			el( CheckboxControl, {
				label: __( 'Lock this content', 'wp-edit-password-protected' ),
				checked: enabled,
				onChange: function ( v ) { update( setMeta, '_wpepp_content_lock_enabled', v ? 'yes' : 'no' ); },
				disabled: ! isPro,
			} ),
			enabled && isPro && el( Fragment, null,
				el( TextareaControl, {
					label: __( 'Locked message:', 'wp-edit-password-protected' ),
					value: message,
					onChange: function ( v ) { update( setMeta, '_wpepp_content_lock_message', v ); },
					rows: 3,
				} ),
				el( TextControl, {
					label: __( 'Popup Header:', 'wp-edit-password-protected' ),
					value: header,
					onChange: function ( v ) { update( setMeta, '_wpepp_content_lock_header', v ); },
					placeholder: __( 'Members Only', 'wp-edit-password-protected' ),
					help: __( 'Custom title for the popup. Leave empty for default.', 'wp-edit-password-protected' ),
				} ),
				el( SelectControl, {
					label: __( 'Action:', 'wp-edit-password-protected' ),
					value: action,
					options: [
						{ value: 'link', label: __( 'Show login link', 'wp-edit-password-protected' ) },
						{ value: 'form', label: __( 'Show login form', 'wp-edit-password-protected' ) },
						{ value: 'popup', label: __( 'Popup login (blur content)', 'wp-edit-password-protected' ) },
						{ value: 'redirect', label: __( 'Redirect to URL', 'wp-edit-password-protected' ) },
					],
					onChange: function ( v ) { update( setMeta, '_wpepp_content_lock_action', v ); },
				} ),
				action === 'redirect' && el( TextControl, {
					label: __( 'Redirect URL:', 'wp-edit-password-protected' ),
					value: redirect,
					onChange: function ( v ) { update( setMeta, '_wpepp_content_lock_redirect', v ); },
					type: 'url',
				} ),
				multiSelect(
					__( 'Lock for:', 'wp-edit-password-protected' ),
					[ { value: 'logged_out', label: __( 'Logged-out users', 'wp-edit-password-protected' ) } ].concat( roles ),
					lockRoles,
					function ( v ) { update( setMeta, '_wpepp_content_lock_roles', v ); },
					__( 'Lock for all — select to restrict', 'wp-edit-password-protected' )
				),
				el( 'p', { style: { fontSize: '11px', color: 'rgba(255,255,255,.35)', margin: '-4px 0 12px' } },
					__( 'Leave empty to lock for all logged-out users.', 'wp-edit-password-protected' )
				),
				el( TextControl, {
					label: __( 'Auto-unlock after:', 'wp-edit-password-protected' ),
					value: expiry,
					onChange: function ( v ) { update( setMeta, '_wpepp_content_lock_expiry', v ); },
					type: 'datetime-local',
					help: __( 'Leave empty for no expiry.', 'wp-edit-password-protected' ),
				} ),
				el( CheckboxControl, {
					label: __( 'Show excerpt on blog page', 'wp-edit-password-protected' ),
					checked: showExcerpt,
					onChange: function ( v ) { update( setMeta, '_wpepp_content_lock_show_excerpt', v ? 'yes' : 'no' ); },
				} ),
				showExcerpt && el( TextareaControl, {
					label: __( 'Custom excerpt text:', 'wp-edit-password-protected' ),
					value: excerptText,
					onChange: function ( v ) { update( setMeta, '_wpepp_content_lock_excerpt_text', v ); },
					rows: 2,
					placeholder: __( 'Leave empty to auto-generate from content', 'wp-edit-password-protected' ),
				} )
			)
		);
	}

	/* ========================================
	   Conditional Display Panel
	   ======================================== */

	function conditionFields( condition, meta, setMeta ) {
		switch ( condition ) {
			case 'device_type':
				return el( SelectControl, {
					label: __( 'Device:', 'wp-edit-password-protected' ),
					value: meta._wpepp_conditional_device_type || 'desktop',
					options: [
						{ value: 'desktop', label: __( 'Desktop', 'wp-edit-password-protected' ) },
						{ value: 'mobile', label: __( 'Mobile', 'wp-edit-password-protected' ) },
						{ value: 'tablet', label: __( 'Tablet', 'wp-edit-password-protected' ) },
					],
					onChange: function ( v ) { update( setMeta, '_wpepp_conditional_device_type', v ); },
				} );

			case 'user_role':
				return multiSelect(
					__( 'Roles:', 'wp-edit-password-protected' ),
					roles,
					meta._wpepp_conditional_user_role || [],
					function ( v ) { update( setMeta, '_wpepp_conditional_user_role', v ); },
					__( 'Select roles…', 'wp-edit-password-protected' )
				);

			case 'day_of_week':
				return multiCheckbox(
					__( 'Days:', 'wp-edit-password-protected' ),
					[
						{ value: 'monday', label: __( 'Monday' ) },
						{ value: 'tuesday', label: __( 'Tuesday' ) },
						{ value: 'wednesday', label: __( 'Wednesday' ) },
						{ value: 'thursday', label: __( 'Thursday' ) },
						{ value: 'friday', label: __( 'Friday' ) },
						{ value: 'saturday', label: __( 'Saturday' ) },
						{ value: 'sunday', label: __( 'Sunday' ) },
					],
					meta._wpepp_conditional_day_of_week || [],
					function ( v ) { update( setMeta, '_wpepp_conditional_day_of_week', v ); }
				);

			case 'time_of_day':
				return el( Fragment, null,
					el( TextControl, {
						label: __( 'Start time:', 'wp-edit-password-protected' ),
						value: meta._wpepp_conditional_time_start || '',
						onChange: function ( v ) { update( setMeta, '_wpepp_conditional_time_start', v ); },
						type: 'time',
					} ),
					el( TextControl, {
						label: __( 'End time:', 'wp-edit-password-protected' ),
						value: meta._wpepp_conditional_time_end || '',
						onChange: function ( v ) { update( setMeta, '_wpepp_conditional_time_end', v ); },
						type: 'time',
					} )
				);

			case 'date_range':
				return el( Fragment, null,
					el( TextControl, {
						label: __( 'Start date:', 'wp-edit-password-protected' ),
						value: meta._wpepp_conditional_date_start || '',
						onChange: function ( v ) { update( setMeta, '_wpepp_conditional_date_start', v ); },
						type: 'date',
					} ),
					el( TextControl, {
						label: __( 'End date:', 'wp-edit-password-protected' ),
						value: meta._wpepp_conditional_date_end || '',
						onChange: function ( v ) { update( setMeta, '_wpepp_conditional_date_end', v ); },
						type: 'date',
					} )
				);

			case 'recurring_schedule':
				return el( Fragment, null,
					el( TextControl, {
						label: __( 'Start time:', 'wp-edit-password-protected' ),
						value: meta._wpepp_conditional_recurring_time_start || '09:00',
						onChange: function ( v ) { update( setMeta, '_wpepp_conditional_recurring_time_start', v ); },
						type: 'time',
					} ),
					el( TextControl, {
						label: __( 'End time:', 'wp-edit-password-protected' ),
						value: meta._wpepp_conditional_recurring_time_end || '17:00',
						onChange: function ( v ) { update( setMeta, '_wpepp_conditional_recurring_time_end', v ); },
						type: 'time',
					} ),
					multiCheckbox(
						__( 'Days:', 'wp-edit-password-protected' ),
						[
							{ value: 'monday', label: __( 'Monday' ) },
							{ value: 'tuesday', label: __( 'Tuesday' ) },
							{ value: 'wednesday', label: __( 'Wednesday' ) },
							{ value: 'thursday', label: __( 'Thursday' ) },
							{ value: 'friday', label: __( 'Friday' ) },
							{ value: 'saturday', label: __( 'Saturday' ) },
							{ value: 'sunday', label: __( 'Sunday' ) },
						],
						meta._wpepp_conditional_recurring_days || [],
						function ( v ) { update( setMeta, '_wpepp_conditional_recurring_days', v ); }
					)
				);

			case 'post_type':
				return multiSelect(
					__( 'Post types:', 'wp-edit-password-protected' ),
					ptList,
					meta._wpepp_conditional_post_type || [],
					function ( v ) { update( setMeta, '_wpepp_conditional_post_type', v ); },
					__( 'Select post types…', 'wp-edit-password-protected' )
				);

			case 'browser_type':
				return multiCheckbox(
					__( 'Browsers:', 'wp-edit-password-protected' ),
					[
						{ value: 'chrome', label: 'Chrome' },
						{ value: 'firefox', label: 'Firefox' },
						{ value: 'safari', label: 'Safari' },
						{ value: 'edge', label: 'Edge' },
						{ value: 'opera', label: 'Opera' },
						{ value: 'ie', label: 'IE' },
					],
					meta._wpepp_conditional_browser_type || [],
					function ( v ) { update( setMeta, '_wpepp_conditional_browser_type', v ); }
				);

			case 'url_parameter':
				return el( Fragment, null,
					el( TextControl, {
						label: __( 'Parameter name:', 'wp-edit-password-protected' ),
						value: meta._wpepp_conditional_url_parameter_key || '',
						onChange: function ( v ) { update( setMeta, '_wpepp_conditional_url_parameter_key', v ); },
					} ),
					el( TextControl, {
						label: __( 'Parameter value:', 'wp-edit-password-protected' ),
						value: meta._wpepp_conditional_url_parameter_value || '',
						onChange: function ( v ) { update( setMeta, '_wpepp_conditional_url_parameter_value', v ); },
					} )
				);

			case 'referrer_source':
				return el( TextControl, {
					label: __( 'Referrer URL contains:', 'wp-edit-password-protected' ),
					value: meta._wpepp_conditional_referrer_source || '',
					onChange: function ( v ) { update( setMeta, '_wpepp_conditional_referrer_source', v ); },
					placeholder: 'google.com',
				} );

			default:
				return null;
		}
	}

	function ConditionalDisplayPanel() {
		var ref     = useMeta();
		var meta    = ref.meta;
		var setMeta = ref.setMeta;

		var enabled     = meta._wpepp_conditional_display_enable === 'yes';
		var condition   = meta._wpepp_conditional_display_condition || 'user_logged_in';
		var action      = meta._wpepp_conditional_action || 'show';
		var ctrlTitle   = meta._wpepp_conditional_control_title === 'yes';
		var ctrlImg     = meta._wpepp_conditional_control_featured_image === 'yes';
		var ctrlCmts    = meta._wpepp_conditional_control_comments === 'yes';
		var noticeOn    = meta._wpepp_conditional_notice_enable === 'yes';
		var noticeTxt   = meta._wpepp_conditional_notice_text || '';

		return el( PluginDocumentSettingPanel, {
			name: 'wpepp-conditional-display',
			title: __( 'Conditional Display', 'wp-edit-password-protected' ),
			icon: 'visibility',
			className: 'wpepp-editor-panel-conditional-display',
		},
			el( CheckboxControl, {
				label: __( 'Enable conditional display', 'wp-edit-password-protected' ),
				checked: enabled,
				onChange: function ( v ) { update( setMeta, '_wpepp_conditional_display_enable', v ? 'yes' : 'no' ); },
			} ),
			enabled && el( Fragment, null,
				el( SelectControl, {
					label: __( 'Condition:', 'wp-edit-password-protected' ),
					value: condition,
					options: [
						{ value: 'user_logged_in', label: __( 'User is logged in', 'wp-edit-password-protected' ) },
						{ value: 'user_logged_out', label: __( 'User is logged out', 'wp-edit-password-protected' ) },
						{ value: 'user_role', label: __( 'User role', 'wp-edit-password-protected' ) },
						{ value: 'device_type', label: __( 'Device type', 'wp-edit-password-protected' ) },
						{ value: 'day_of_week', label: __( 'Day of week', 'wp-edit-password-protected' ) },
						{ value: 'time_of_day', label: __( 'Time of day', 'wp-edit-password-protected' ) },
						{ value: 'date_range', label: __( 'Date range', 'wp-edit-password-protected' ) },
						{ value: 'recurring_schedule', label: __( 'Recurring schedule', 'wp-edit-password-protected' ) },
						{ value: 'post_type', label: __( 'Post type', 'wp-edit-password-protected' ) },
						{ value: 'browser_type', label: __( 'Browser type', 'wp-edit-password-protected' ) },
						{ value: 'url_parameter', label: __( 'URL parameter', 'wp-edit-password-protected' ) },
						{ value: 'referrer_source', label: __( 'Referrer source', 'wp-edit-password-protected' ) },
					],
					onChange: function ( v ) { update( setMeta, '_wpepp_conditional_display_condition', v ); },
				} ),
				conditionFields( condition, meta, setMeta ),
				el( SelectControl, {
					label: __( 'Action:', 'wp-edit-password-protected' ),
					value: action,
					options: [
						{ value: 'show', label: __( 'Show content when condition is met', 'wp-edit-password-protected' ) },
						{ value: 'hide', label: __( 'Hide content when condition is met', 'wp-edit-password-protected' ) },
					],
					onChange: function ( v ) { update( setMeta, '_wpepp_conditional_action', v ); },
				} ),
				el( 'hr', { style: { borderColor: 'rgba(255,255,255,.08)', margin: '12px 0' } } ),
				el( CheckboxControl, {
					label: __( 'Also control title visibility', 'wp-edit-password-protected' ),
					checked: ctrlTitle,
					onChange: function ( v ) { update( setMeta, '_wpepp_conditional_control_title', v ? 'yes' : 'no' ); },
				} ),
				el( CheckboxControl, {
					label: __( 'Also control featured image', 'wp-edit-password-protected' ),
					checked: ctrlImg,
					onChange: function ( v ) { update( setMeta, '_wpepp_conditional_control_featured_image', v ? 'yes' : 'no' ); },
				} ),
				el( CheckboxControl, {
					label: __( 'Also hide comments', 'wp-edit-password-protected' ),
					checked: ctrlCmts,
					onChange: function ( v ) { update( setMeta, '_wpepp_conditional_control_comments', v ? 'yes' : 'no' ); },
				} ),
				el( 'hr', { style: { borderColor: 'rgba(255,255,255,.08)', margin: '12px 0' } } ),
				el( CheckboxControl, {
					label: __( 'Show notice when hidden', 'wp-edit-password-protected' ),
					checked: noticeOn,
					onChange: function ( v ) { update( setMeta, '_wpepp_conditional_notice_enable', v ? 'yes' : 'no' ); },
				} ),
				noticeOn && el( TextareaControl, {
					label: __( 'Notice text:', 'wp-edit-password-protected' ),
					value: noticeTxt,
					onChange: function ( v ) { update( setMeta, '_wpepp_conditional_notice_text', v ); },
					rows: 3,
				} )
			)
		);
	}

	/* ── Register plugins ── */

	registerPlugin( 'wpepp-content-lock-panel', {
		render: ContentLockPanel,
		icon: 'lock',
	} );

	registerPlugin( 'wpepp-conditional-display-panel', {
		render: ConditionalDisplayPanel,
		icon: 'visibility',
	} );

	/* ── Keep our panels at the bottom of the sidebar ── */

	wp.domReady( function () {
		var selectors = [
			'.wpepp-editor-panel-content-lock',
			'.wpepp-editor-panel-conditional-display',
		];

		function reorderPanels() {
			selectors.forEach( function ( sel ) {
				var panel = document.querySelector( sel );
				if ( panel && panel.parentNode && panel.nextElementSibling ) {
					panel.parentNode.appendChild( panel );
				}
			} );
		}

		// Observe the sidebar for new panels being added.
		var observer = new MutationObserver( reorderPanels );
		var interval = setInterval( function () {
			var sidebar = document.querySelector(
				'.interface-complementary-area .components-panel, ' +
				'.editor-sidebar__panel-tab, ' +
				'.edit-post-sidebar__panel-tab'
			);
			if ( sidebar ) {
				clearInterval( interval );
				observer.observe( sidebar, { childList: true } );
				reorderPanels();
			}
		}, 300 );
	} );

} )( window.wp );
