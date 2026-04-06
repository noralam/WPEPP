<?php
/**
 * Content Lock — post editor meta box.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Content_Lock
 */
class WPEPP_Content_Lock {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
		add_action( 'save_post', [ $this, 'save_meta_box' ] );
	}

	/**
	 * Register the Content Lock meta box on post types.
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
				'wpepp-content-lock',
				__( 'Content Lock', 'wp-edit-password-protected' ),
				[ $this, 'render_meta_box' ],
				$pt,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render meta box HTML.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render_meta_box( $post ) {
		wp_nonce_field( 'wpepp_save_content_lock', 'wpepp_content_lock_nonce' );

		$enabled      = get_post_meta( $post->ID, '_wpepp_content_lock_enabled', true );
		$message      = get_post_meta( $post->ID, '_wpepp_content_lock_message', true );
		$header       = get_post_meta( $post->ID, '_wpepp_content_lock_header', true );
		$action       = get_post_meta( $post->ID, '_wpepp_content_lock_action', true ) ?: 'link';
		$redirect     = get_post_meta( $post->ID, '_wpepp_content_lock_redirect', true );
		$lock_roles   = get_post_meta( $post->ID, '_wpepp_content_lock_roles', true );
		$lock_expiry  = get_post_meta( $post->ID, '_wpepp_content_lock_expiry', true );
		$show_excerpt = get_post_meta( $post->ID, '_wpepp_content_lock_show_excerpt', true );
		$excerpt_text = get_post_meta( $post->ID, '_wpepp_content_lock_excerpt_text', true );

		if ( ! is_array( $lock_roles ) ) {
			$lock_roles = [];
		}

		$is_pro = wpepp_has_pro_check();
		?>
		<div class="wpepp-meta-content-lock">
			<?php if ( ! $is_pro ) : ?>
				<p class="wpepp-pro-notice">
					<em><?php esc_html_e( 'Content Lock requires the Pro version.', 'wp-edit-password-protected' ); ?></em>
				</p>
			<?php endif; ?>

			<p>
				<label>
					<input type="checkbox" id="wpepp-content-lock-enabled" name="_wpepp_content_lock_enabled" value="yes"
						<?php checked( 'yes', $enabled ); ?>
						<?php disabled( ! $is_pro ); ?>>
					<?php esc_html_e( 'Lock this content', 'wp-edit-password-protected' ); ?>
				</label>
			</p>

			<div class="wpepp-content-lock-fields"<?php echo 'yes' !== $enabled ? ' style="display:none;"' : ''; ?>>

			<p>
				<label for="wpepp-lock-message"><?php esc_html_e( 'Locked message:', 'wp-edit-password-protected' ); ?></label>
				<textarea id="wpepp-lock-message" name="_wpepp_content_lock_message" rows="3" class="widefat"
					<?php disabled( ! $is_pro ); ?>><?php echo esc_textarea( $message ); ?></textarea>
			</p>

			<p>
				<label for="wpepp-lock-action"><?php esc_html_e( 'Action:', 'wp-edit-password-protected' ); ?></label>
				<select id="wpepp-lock-action" name="_wpepp_content_lock_action" class="widefat"
					<?php disabled( ! $is_pro ); ?>>
					<option value="link" <?php selected( 'link', $action ); ?>><?php esc_html_e( 'Show login link', 'wp-edit-password-protected' ); ?></option>
					<option value="form" <?php selected( 'form', $action ); ?>><?php esc_html_e( 'Show login form', 'wp-edit-password-protected' ); ?></option>
					<option value="popup" <?php selected( 'popup', $action ); ?>><?php esc_html_e( 'Popup login (blur content)', 'wp-edit-password-protected' ); ?></option>
					<option value="redirect" <?php selected( 'redirect', $action ); ?>><?php esc_html_e( 'Redirect to URL', 'wp-edit-password-protected' ); ?></option>
				</select>
			</p>

			<p>
				<label for="wpepp-lock-header"><?php esc_html_e( 'Popup header:', 'wp-edit-password-protected' ); ?></label>
				<input type="text" id="wpepp-lock-header" name="_wpepp_content_lock_header"
					value="<?php echo esc_attr( $header ); ?>" class="widefat"
					placeholder="<?php esc_attr_e( 'Members Only', 'wp-edit-password-protected' ); ?>"
					<?php disabled( ! $is_pro ); ?>>
			</p>

			<p class="wpepp-lock-redirect-field" style="<?php echo 'redirect' !== $action ? 'display:none;' : ''; ?>">
				<label for="wpepp-lock-redirect"><?php esc_html_e( 'Redirect URL:', 'wp-edit-password-protected' ); ?></label>
				<input type="url" id="wpepp-lock-redirect" name="_wpepp_content_lock_redirect"
					value="<?php echo esc_url( $redirect ); ?>" class="widefat"
					<?php disabled( ! $is_pro ); ?>>
			</p>

			<p>
				<label><?php esc_html_e( 'Lock for:', 'wp-edit-password-protected' ); ?></label>
				<select name="_wpepp_content_lock_roles[]" multiple="multiple" class="widefat wpepp-select2"
					<?php disabled( ! $is_pro ); ?>>
					<option value="logged_out" <?php selected( in_array( 'logged_out', $lock_roles, true ) ); ?>>
						<?php esc_html_e( 'Logged-out users', 'wp-edit-password-protected' ); ?>
					</option>
					<?php foreach ( wp_roles()->get_names() as $slug => $name ) : ?>
						<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( in_array( $slug, $lock_roles, true ) ); ?>>
							<?php echo esc_html( $name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<span class="description"><?php esc_html_e( 'Leave empty to lock for all logged-out users.', 'wp-edit-password-protected' ); ?></span>
			</p>

			<p>
				<label for="wpepp-lock-expiry"><?php esc_html_e( 'Auto-unlock after:', 'wp-edit-password-protected' ); ?></label>
				<input type="datetime-local" id="wpepp-lock-expiry" name="_wpepp_content_lock_expiry"
					value="<?php echo esc_attr( $lock_expiry ); ?>" class="widefat"
					<?php disabled( ! $is_pro ); ?>>
				<span class="description"><?php esc_html_e( 'Leave empty for no expiry. Uses site timezone.', 'wp-edit-password-protected' ); ?></span>
			</p>

			<p>
				<label>
					<input type="checkbox" name="_wpepp_content_lock_show_excerpt" value="yes"
						<?php checked( 'yes', $show_excerpt ); ?>
						<?php disabled( ! $is_pro ); ?>>
					<?php esc_html_e( 'Show excerpt on blog page', 'wp-edit-password-protected' ); ?>
				</label>
			</p>

			<p class="wpepp-lock-excerpt-field" style="<?php echo 'yes' !== $show_excerpt ? 'display:none;' : ''; ?>">
				<label for="wpepp-lock-excerpt-text"><?php esc_html_e( 'Custom excerpt text:', 'wp-edit-password-protected' ); ?></label>
				<textarea id="wpepp-lock-excerpt-text" name="_wpepp_content_lock_excerpt_text" rows="2" class="widefat"
					placeholder="<?php esc_attr_e( 'Leave empty to auto-generate from content', 'wp-edit-password-protected' ); ?>"
					<?php disabled( ! $is_pro ); ?>><?php echo esc_textarea( $excerpt_text ); ?></textarea>
			</p>

			</div><!-- .wpepp-content-lock-fields -->
		</div>
		<?php
	}

	/**
	 * Save meta box values.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_meta_box( $post_id ) {
		if ( ! isset( $_POST['wpepp_content_lock_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wpepp_content_lock_nonce'] ) ), 'wpepp_save_content_lock' )
		) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! wpepp_has_pro_check() ) {
			return;
		}

		$enabled = isset( $_POST['_wpepp_content_lock_enabled'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wpepp_content_lock_enabled', $enabled );

		if ( isset( $_POST['_wpepp_content_lock_message'] ) ) {
			update_post_meta(
				$post_id,
				'_wpepp_content_lock_message',
				wp_kses_post( wp_unslash( $_POST['_wpepp_content_lock_message'] ) )
			);
		}

		if ( isset( $_POST['_wpepp_content_lock_header'] ) ) {
			update_post_meta(
				$post_id,
				'_wpepp_content_lock_header',
				sanitize_text_field( wp_unslash( $_POST['_wpepp_content_lock_header'] ) )
			);
		}

		if ( isset( $_POST['_wpepp_content_lock_action'] ) ) {
			$action  = sanitize_text_field( wp_unslash( $_POST['_wpepp_content_lock_action'] ) );
			$allowed = [ 'form', 'link', 'popup', 'redirect' ];
			if ( in_array( $action, $allowed, true ) ) {
				update_post_meta( $post_id, '_wpepp_content_lock_action', $action );
			}
		}

		if ( isset( $_POST['_wpepp_content_lock_redirect'] ) ) {
			update_post_meta(
				$post_id,
				'_wpepp_content_lock_redirect',
				esc_url_raw( wp_unslash( $_POST['_wpepp_content_lock_redirect'] ) )
			);
		}

		// Lock for roles.
		$lock_roles = isset( $_POST['_wpepp_content_lock_roles'] ) && is_array( $_POST['_wpepp_content_lock_roles'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['_wpepp_content_lock_roles'] ) )
			: [];
		update_post_meta( $post_id, '_wpepp_content_lock_roles', $lock_roles );

		// Lock expiry.
		$expiry = isset( $_POST['_wpepp_content_lock_expiry'] )
			? sanitize_text_field( wp_unslash( $_POST['_wpepp_content_lock_expiry'] ) )
			: '';
		update_post_meta( $post_id, '_wpepp_content_lock_expiry', $expiry );

		$show_excerpt = isset( $_POST['_wpepp_content_lock_show_excerpt'] ) ? 'yes' : 'no';
		update_post_meta( $post_id, '_wpepp_content_lock_show_excerpt', $show_excerpt );

		if ( isset( $_POST['_wpepp_content_lock_excerpt_text'] ) ) {
			update_post_meta(
				$post_id,
				'_wpepp_content_lock_excerpt_text',
				sanitize_textarea_field( wp_unslash( $_POST['_wpepp_content_lock_excerpt_text'] ) )
			);
		}

	}
}
