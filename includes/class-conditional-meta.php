<?php
/**
 * Conditional Display — post editor meta box.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Conditional_Meta
 */
class WPEPP_Conditional_Meta {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_meta_box' ] );
	}

	/**
	 * Register conditional display meta box.
	 */
	public function register_meta_box() {
		// Skip in block editor — native sidebar panels handle meta there.
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
			return;
		}

		$post_types = get_post_types( [ 'public' => true ] );

		foreach ( $post_types as $pt ) {
			add_meta_box(
				'wpepp-conditional-display',
				__( 'Conditional Display', 'wp-edit-password-protected' ),
				[ $this, 'render_meta_box' ],
				$pt,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render the meta box.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'wpepp_save_conditional', 'wpepp_conditional_nonce' );

		$enabled   = get_post_meta( $post->ID, '_wpepp_conditional_display_enable', true );
		$condition = get_post_meta( $post->ID, '_wpepp_conditional_display_condition', true );
		$action    = get_post_meta( $post->ID, '_wpepp_conditional_action', true ) ?: 'show';
		$ctrl_title = get_post_meta( $post->ID, '_wpepp_conditional_control_title', true );
		$ctrl_img   = get_post_meta( $post->ID, '_wpepp_conditional_control_featured_image', true );
		$ctrl_comments = get_post_meta( $post->ID, '_wpepp_conditional_control_comments', true );
		$notice_on  = get_post_meta( $post->ID, '_wpepp_conditional_notice_enable', true );
		$notice_txt = get_post_meta( $post->ID, '_wpepp_conditional_notice_text', true );

		$condition_labels = [
			'user_logged_in'     => __( 'User is logged in', 'wp-edit-password-protected' ),
			'user_logged_out'    => __( 'User is logged out', 'wp-edit-password-protected' ),
			'user_role'          => __( 'User role', 'wp-edit-password-protected' ),
			'device_type'        => __( 'Device type', 'wp-edit-password-protected' ),
			'day_of_week'        => __( 'Day of week', 'wp-edit-password-protected' ),
			'time_of_day'        => __( 'Time of day', 'wp-edit-password-protected' ),
			'date_range'         => __( 'Date range', 'wp-edit-password-protected' ),
			'recurring_schedule' => __( 'Recurring schedule', 'wp-edit-password-protected' ),
			'post_type'          => __( 'Post type', 'wp-edit-password-protected' ),
			'browser_type'       => __( 'Browser type', 'wp-edit-password-protected' ),
			'url_parameter'      => __( 'URL parameter', 'wp-edit-password-protected' ),
			'referrer_source'    => __( 'Referrer source', 'wp-edit-password-protected' ),
		];
		?>
		<div class="wpepp-meta-conditional">
			<p>
				<label>
					<input type="checkbox" name="_wpepp_conditional_display_enable" value="yes"
						<?php checked( 'yes', $enabled ); ?>>
					<?php esc_html_e( 'Enable conditional display', 'wp-edit-password-protected' ); ?>
				</label>
			</p>

			<div class="wpepp-cond-options"<?php echo 'yes' !== $enabled ? ' style="display:none;"' : ''; ?>>

			<p>
				<label for="wpepp-cond-condition"><?php esc_html_e( 'Condition:', 'wp-edit-password-protected' ); ?></label>
				<select id="wpepp-cond-condition" name="_wpepp_conditional_display_condition" class="widefat">
					<?php foreach ( $condition_labels as $cond => $label ) : ?>
						<option value="<?php echo esc_attr( $cond ); ?>"
							<?php selected( $condition, $cond ); ?>>
							<?php echo esc_html( $label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<?php self::render_condition_fields( $post->ID, $condition ); ?>

			<p>
				<label for="wpepp-cond-action"><?php esc_html_e( 'Action:', 'wp-edit-password-protected' ); ?></label>
				<select id="wpepp-cond-action" name="_wpepp_conditional_action" class="widefat">
					<option value="show" <?php selected( 'show', $action ); ?>><?php esc_html_e( 'Show content when condition is met', 'wp-edit-password-protected' ); ?></option>
					<option value="hide" <?php selected( 'hide', $action ); ?>><?php esc_html_e( 'Hide content when condition is met', 'wp-edit-password-protected' ); ?></option>
				</select>
			</p>

			<hr>

			<p>
				<label>
					<input type="checkbox" name="_wpepp_conditional_control_title" value="yes"
						<?php checked( 'yes', $ctrl_title ); ?>>
					<?php esc_html_e( 'Also control title visibility', 'wp-edit-password-protected' ); ?>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" name="_wpepp_conditional_control_featured_image" value="yes"
						<?php checked( 'yes', $ctrl_img ); ?>>
					<?php esc_html_e( 'Also control featured image', 'wp-edit-password-protected' ); ?>
				</label>
			</p>

			<p>
				<label>
					<input type="checkbox" name="_wpepp_conditional_control_comments" value="yes"
						<?php checked( 'yes', $ctrl_comments ); ?>>
					<?php esc_html_e( 'Also hide comments', 'wp-edit-password-protected' ); ?>
				</label>
			</p>

			<hr>

			<p>
				<label>
					<input type="checkbox" id="wpepp-cond-notice-enable" name="_wpepp_conditional_notice_enable" value="yes"
						<?php checked( 'yes', $notice_on ); ?>>
					<?php esc_html_e( 'Show notice when hidden', 'wp-edit-password-protected' ); ?>
				</label>
			</p>

			<div class="wpepp-cond-notice-field"<?php echo 'yes' !== $notice_on ? ' style="display:none;"' : ''; ?>>
				<p>
					<label for="wpepp-cond-notice-text"><?php esc_html_e( 'Notice text:', 'wp-edit-password-protected' ); ?></label>
					<textarea id="wpepp-cond-notice-text" name="_wpepp_conditional_notice_text" class="widefat" rows="3"><?php echo esc_textarea( $notice_txt ); ?></textarea>
				</p>
			</div>

			</div><!-- .wpepp-cond-options -->
		</div>
		<?php
	}

	/**
	 * Render ALL condition-specific fields (hidden by default, visible for active).
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $condition Active condition.
	 */
	private static function render_condition_fields( $post_id, $condition ) {
		// ── User Role ──
		$roles = get_post_meta( $post_id, '_wpepp_conditional_user_role', true );
		if ( ! is_array( $roles ) ) {
			$roles = [];
		}
		$wpepp_role_names = wp_roles()->get_names();
		echo '<div class="wpepp-cond-field wpepp-cond-user_role"';
		if ( 'user_role' !== $condition ) {
			echo ' style="display:none;"';
		}
		echo '>';
		echo '<label>' . esc_html__( 'Roles:', 'wp-edit-password-protected' ) . '</label>';
		echo '<select name="_wpepp_conditional_user_role[]" multiple="multiple" class="widefat wpepp-select2">';
		foreach ( $wpepp_role_names as $slug => $name ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $slug ),
				selected( in_array( $slug, $roles, true ), true, false ),
				esc_html( $name )
			);
		}
		echo '</select>';
		echo '</div>';

		// ── Device Type ──
		$device = get_post_meta( $post_id, '_wpepp_conditional_device_type', true );
		echo '<div class="wpepp-cond-field wpepp-cond-device_type"';
		if ( 'device_type' !== $condition ) {
			echo ' style="display:none;"';
		}
		echo '>';
		echo '<label for="wpepp-cond-device">' . esc_html__( 'Device:', 'wp-edit-password-protected' ) . '</label>';
		echo '<select id="wpepp-cond-device" name="_wpepp_conditional_device_type" class="widefat">';
		foreach ( [ 'desktop', 'mobile', 'tablet' ] as $d ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $d ),
				selected( $device, $d, false ),
				esc_html( ucfirst( $d ) )
			);
		}
		echo '</select>';
		echo '</div>';

		// ── Day of Week ──
		$days = get_post_meta( $post_id, '_wpepp_conditional_day_of_week', true );
		if ( ! is_array( $days ) ) {
			$days = [];
		}
		$day_names = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];
		echo '<div class="wpepp-cond-field wpepp-cond-day_of_week"';
		if ( 'day_of_week' !== $condition ) {
			echo ' style="display:none;"';
		}
		echo '>';
		echo '<label>' . esc_html__( 'Days:', 'wp-edit-password-protected' ) . '</label>';
		echo '<select name="_wpepp_conditional_day_of_week[]" multiple="multiple" class="widefat wpepp-select2">';
		foreach ( $day_names as $day ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $day ),
				selected( in_array( $day, $days, true ), true, false ),
				esc_html( ucfirst( $day ) )
			);
		}
		echo '</select>';
		echo '</div>';

		// ── Time of Day ──
		$time_start = get_post_meta( $post_id, '_wpepp_conditional_time_start', true );
		$time_end   = get_post_meta( $post_id, '_wpepp_conditional_time_end', true );
		echo '<div class="wpepp-cond-field wpepp-cond-time_of_day"';
		if ( 'time_of_day' !== $condition ) {
			echo ' style="display:none;"';
		}
		echo '>';
		echo '<p><label>' . esc_html__( 'Start time:', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="time" name="_wpepp_conditional_time_start" value="' . esc_attr( $time_start ) . '" class="widefat"></p>';
		echo '<p><label>' . esc_html__( 'End time:', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="time" name="_wpepp_conditional_time_end" value="' . esc_attr( $time_end ) . '" class="widefat"></p>';
		echo '</div>';

		// ── Date Range ──
		$ds = get_post_meta( $post_id, '_wpepp_conditional_date_start', true );
		$de = get_post_meta( $post_id, '_wpepp_conditional_date_end', true );
		echo '<div class="wpepp-cond-field wpepp-cond-date_range"';
		if ( 'date_range' !== $condition ) {
			echo ' style="display:none;"';
		}
		echo '>';
		echo '<p><label>' . esc_html__( 'Start date:', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="date" name="_wpepp_conditional_date_start" value="' . esc_attr( $ds ) . '" class="widefat"></p>';
		echo '<p><label>' . esc_html__( 'End date:', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="date" name="_wpepp_conditional_date_end" value="' . esc_attr( $de ) . '" class="widefat"></p>';
		echo '</div>';

		// ── Recurring Schedule ──
		$rec_time_start = get_post_meta( $post_id, '_wpepp_conditional_recurring_time_start', true );
		$rec_time_end   = get_post_meta( $post_id, '_wpepp_conditional_recurring_time_end', true );
		$rec_days       = get_post_meta( $post_id, '_wpepp_conditional_recurring_days', true );
		if ( ! is_array( $rec_days ) ) {
			$rec_days = [];
		}
		echo '<div class="wpepp-cond-field wpepp-cond-recurring_schedule"';
		if ( 'recurring_schedule' !== $condition ) {
			echo ' style="display:none;"';
		}
		echo '>';
		echo '<p><label>' . esc_html__( 'Start time:', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="time" name="_wpepp_conditional_recurring_time_start" value="' . esc_attr( $rec_time_start ?: '09:00' ) . '" class="widefat"></p>';
		echo '<p><label>' . esc_html__( 'End time:', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="time" name="_wpepp_conditional_recurring_time_end" value="' . esc_attr( $rec_time_end ?: '17:00' ) . '" class="widefat"></p>';
		echo '<label>' . esc_html__( 'Days:', 'wp-edit-password-protected' ) . '</label>';
		echo '<select name="_wpepp_conditional_recurring_days[]" multiple="multiple" class="widefat wpepp-select2">';
		foreach ( $day_names as $day ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $day ),
				selected( in_array( $day, $rec_days, true ), true, false ),
				esc_html( ucfirst( $day ) )
			);
		}
		echo '</select>';
		echo '</div>';

		// ── Post Type ──
		$saved_types = get_post_meta( $post_id, '_wpepp_conditional_post_type', true );
		if ( ! is_array( $saved_types ) ) {
			$saved_types = [];
		}
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		echo '<div class="wpepp-cond-field wpepp-cond-post_type"';
		if ( 'post_type' !== $condition ) {
			echo ' style="display:none;"';
		}
		echo '>';
		echo '<label>' . esc_html__( 'Post types:', 'wp-edit-password-protected' ) . '</label>';
		echo '<select name="_wpepp_conditional_post_type[]" multiple="multiple" class="widefat wpepp-select2">';
		foreach ( $post_types as $pt ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $pt->name ),
				selected( in_array( $pt->name, $saved_types, true ), true, false ),
				esc_html( $pt->labels->singular_name )
			);
		}
		echo '</select>';
		echo '</div>';

		// ── Browser Type ──
		$browsers = get_post_meta( $post_id, '_wpepp_conditional_browser_type', true );
		if ( ! is_array( $browsers ) ) {
			$browsers = [];
		}
		$browser_list = [ 'chrome', 'firefox', 'safari', 'edge', 'opera', 'ie' ];
		echo '<div class="wpepp-cond-field wpepp-cond-browser_type"';
		if ( 'browser_type' !== $condition ) {
			echo ' style="display:none;"';
		}
		echo '>';
		echo '<label>' . esc_html__( 'Browsers:', 'wp-edit-password-protected' ) . '</label>';
		echo '<select name="_wpepp_conditional_browser_type[]" multiple="multiple" class="widefat wpepp-select2">';
		foreach ( $browser_list as $b ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $b ),
				selected( in_array( $b, $browsers, true ), true, false ),
				esc_html( ucfirst( $b ) )
			);
		}
		echo '</select>';
		echo '</div>';

		// ── URL Parameter ──
		$param = get_post_meta( $post_id, '_wpepp_conditional_url_parameter_key', true );
		$val   = get_post_meta( $post_id, '_wpepp_conditional_url_parameter_value', true );
		echo '<div class="wpepp-cond-field wpepp-cond-url_parameter"';
		if ( 'url_parameter' !== $condition ) {
			echo ' style="display:none;"';
		}
		echo '>';
		echo '<p><label>' . esc_html__( 'Parameter name:', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="text" name="_wpepp_conditional_url_parameter_key" value="' . esc_attr( $param ) . '" class="widefat"></p>';
		echo '<p><label>' . esc_html__( 'Parameter value:', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="text" name="_wpepp_conditional_url_parameter_value" value="' . esc_attr( $val ) . '" class="widefat"></p>';
		echo '</div>';

		// ── Referrer Source ──
		$ref = get_post_meta( $post_id, '_wpepp_conditional_referrer_source', true );
		echo '<div class="wpepp-cond-field wpepp-cond-referrer_source"';
		if ( 'referrer_source' !== $condition ) {
			echo ' style="display:none;"';
		}
		echo '>';
		echo '<p><label>' . esc_html__( 'Referrer URL contains:', 'wp-edit-password-protected' ) . '</label>';
		echo '<input type="text" name="_wpepp_conditional_referrer_source" value="' . esc_attr( $ref ) . '" class="widefat" placeholder="google.com"></p>';
		echo '</div>';
	}

	/**
	 * Save meta box data.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['wpepp_conditional_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpepp_conditional_nonce'] ) ), 'wpepp_save_conditional' )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Enable toggle.
		$enabled = isset( $_POST['_wpepp_conditional_display_enable'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wpepp_conditional_display_enable', $enabled );

		// Action.
		if ( isset( $_POST['_wpepp_conditional_action'] ) ) {
			$action = sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_action'] ) );
			if ( in_array( $action, [ 'show', 'hide' ], true ) ) {
				update_post_meta( $post_id, '_wpepp_conditional_action', $action );
			}
		}

		// Control toggles.
		$ctrl_title = isset( $_POST['_wpepp_conditional_control_title'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wpepp_conditional_control_title', $ctrl_title );

		$ctrl_img = isset( $_POST['_wpepp_conditional_control_featured_image'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wpepp_conditional_control_featured_image', $ctrl_img );

		$ctrl_comments = isset( $_POST['_wpepp_conditional_control_comments'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wpepp_conditional_control_comments', $ctrl_comments );

		// Notice fields.
		$notice_on = isset( $_POST['_wpepp_conditional_notice_enable'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wpepp_conditional_notice_enable', $notice_on );

		if ( isset( $_POST['_wpepp_conditional_notice_text'] ) ) {
			update_post_meta( $post_id, '_wpepp_conditional_notice_text', sanitize_textarea_field( wp_unslash( $_POST['_wpepp_conditional_notice_text'] ) ) );
		}

		// Condition type.
		$condition = '';
		$allowed   = [
			'user_logged_in', 'user_logged_out', 'user_role', 'device_type',
			'day_of_week', 'time_of_day', 'date_range', 'recurring_schedule',
			'post_type', 'browser_type', 'url_parameter', 'referrer_source',
		];
		if ( isset( $_POST['_wpepp_conditional_display_condition'] ) ) {
			$cond = sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_display_condition'] ) );
			if ( in_array( $cond, $allowed, true ) ) {
				$condition = $cond;
			}
		}
		update_post_meta( $post_id, '_wpepp_conditional_display_condition', $condition );

		// Condition-specific fields.
		$this->save_condition_fields( $post_id, $condition );
	}

	/**
	 * Save condition-specific fields.
	 *
	 * @param int    $post_id   Post ID.
	 * @param string $condition Active condition.
	 */
	private function save_condition_fields( $post_id, $condition ) {
		// Nonce already verified in save_meta_box() which is the only caller.
		// phpcs:disable WordPress.Security.NonceVerification.Missing
		switch ( $condition ) {
			case 'user_role':
				$roles = isset( $_POST['_wpepp_conditional_user_role'] ) && is_array( $_POST['_wpepp_conditional_user_role'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['_wpepp_conditional_user_role'] ) )
					: [];
				update_post_meta( $post_id, '_wpepp_conditional_user_role', $roles );
				break;

			case 'device_type':
				if ( isset( $_POST['_wpepp_conditional_device_type'] ) ) {
					$device = sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_device_type'] ) );
					if ( in_array( $device, [ 'desktop', 'mobile', 'tablet' ], true ) ) {
						update_post_meta( $post_id, '_wpepp_conditional_device_type', $device );
					}
				}
				break;

			case 'day_of_week':
				$days = isset( $_POST['_wpepp_conditional_day_of_week'] ) && is_array( $_POST['_wpepp_conditional_day_of_week'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['_wpepp_conditional_day_of_week'] ) )
					: [];
				update_post_meta( $post_id, '_wpepp_conditional_day_of_week', $days );
				break;

			case 'time_of_day':
				if ( isset( $_POST['_wpepp_conditional_time_start'] ) ) {
					update_post_meta( $post_id, '_wpepp_conditional_time_start', sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_time_start'] ) ) );
				}
				if ( isset( $_POST['_wpepp_conditional_time_end'] ) ) {
					update_post_meta( $post_id, '_wpepp_conditional_time_end', sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_time_end'] ) ) );
				}
				break;

			case 'date_range':
				if ( isset( $_POST['_wpepp_conditional_date_start'] ) ) {
					update_post_meta( $post_id, '_wpepp_conditional_date_start', sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_date_start'] ) ) );
				}
				if ( isset( $_POST['_wpepp_conditional_date_end'] ) ) {
					update_post_meta( $post_id, '_wpepp_conditional_date_end', sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_date_end'] ) ) );
				}
				break;

			case 'recurring_schedule':
				if ( isset( $_POST['_wpepp_conditional_recurring_time_start'] ) ) {
					update_post_meta( $post_id, '_wpepp_conditional_recurring_time_start', sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_recurring_time_start'] ) ) );
				}
				if ( isset( $_POST['_wpepp_conditional_recurring_time_end'] ) ) {
					update_post_meta( $post_id, '_wpepp_conditional_recurring_time_end', sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_recurring_time_end'] ) ) );
				}
				$rec_days = isset( $_POST['_wpepp_conditional_recurring_days'] ) && is_array( $_POST['_wpepp_conditional_recurring_days'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['_wpepp_conditional_recurring_days'] ) )
					: [];
				update_post_meta( $post_id, '_wpepp_conditional_recurring_days', $rec_days );
				break;

			case 'post_type':
				$types = isset( $_POST['_wpepp_conditional_post_type'] ) && is_array( $_POST['_wpepp_conditional_post_type'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['_wpepp_conditional_post_type'] ) )
					: [];
				update_post_meta( $post_id, '_wpepp_conditional_post_type', $types );
				break;

			case 'browser_type':
				$browsers = isset( $_POST['_wpepp_conditional_browser_type'] ) && is_array( $_POST['_wpepp_conditional_browser_type'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['_wpepp_conditional_browser_type'] ) )
					: [];
				update_post_meta( $post_id, '_wpepp_conditional_browser_type', $browsers );
				break;

			case 'url_parameter':
				if ( isset( $_POST['_wpepp_conditional_url_parameter_key'] ) ) {
					update_post_meta( $post_id, '_wpepp_conditional_url_parameter_key', sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_url_parameter_key'] ) ) );
				}
				if ( isset( $_POST['_wpepp_conditional_url_parameter_value'] ) ) {
					update_post_meta( $post_id, '_wpepp_conditional_url_parameter_value', sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_url_parameter_value'] ) ) );
				}
				break;

			case 'referrer_source':
				if ( isset( $_POST['_wpepp_conditional_referrer_source'] ) ) {
					update_post_meta( $post_id, '_wpepp_conditional_referrer_source', sanitize_text_field( wp_unslash( $_POST['_wpepp_conditional_referrer_source'] ) ) );
				}
				break;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
