<?php
/**
 * Documentation page for the plugin admin.
 *
 * @package wpepp
 * @since   2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class WPEPP_Docs
 */
class WPEPP_Docs {

	/**
	 * Hook suffix for the docs page.
	 *
	 * @var string
	 */
	private $hook_suffix = '';

	/**
	 * Boot the docs page.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_submenu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register the Documentation submenu page.
	 */
	public function add_submenu() {
		$this->hook_suffix = add_submenu_page(
			'wpepp-settings',
			__( 'Documentation', 'wp-edit-password-protected' ),
			__( 'Documentation', 'wp-edit-password-protected' ),
			'manage_options',
			'wpepp-docs',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Enqueue styles only on the docs page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( $this->hook_suffix !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpepp-docs',
			WPEPP_URL . '/assets/css/docs.css',
			[],
			WPEPP_VERSION
		);
	}

	/**
	 * Get the documentation sections.
	 *
	 * @return array
	 */
	private function get_sections() {
		return [
			'getting-started'      => [
				'icon'  => 'dashicons-admin-home',
				'title' => __( 'Getting Started', 'wp-edit-password-protected' ),
				'steps' => [
					__( 'Install and activate the <strong>WPEPP</strong> plugin from the Plugins page.', 'wp-edit-password-protected' ),
					__( 'Navigate to <strong>WPEPP Security</strong> in the left sidebar of your WordPress dashboard.', 'wp-edit-password-protected' ),
					__( 'You will see a modern tabbed settings panel — this is the main control center for every feature.', 'wp-edit-password-protected' ),
				],
			],
			'site-access'          => [
				'icon'  => 'dashicons-admin-network',
				'title' => __( 'Site Access Control', 'wp-edit-password-protected' ),
				'steps' => [
					__( 'Go to <strong>WPEPP Security → Site Access</strong> tab.', 'wp-edit-password-protected' ),
					__( '<strong>Admin Only Mode</strong> — Restrict the entire site to logged-in administrators only.', 'wp-edit-password-protected' ),
					__( '<strong>Site Password</strong> — Require a global password for all visitors to access the site.', 'wp-edit-password-protected' ),
					__( 'Non-authorized visitors will be redirected to the login page or a custom page.', 'wp-edit-password-protected' ),
				],
			],
			'content-lock'         => [
				'icon'  => 'dashicons-shield',
				'title' => __( 'Content Lock', 'wp-edit-password-protected' ),
				'badge' => 'pro',
				'steps' => [
					__( 'Go to <strong>WPEPP Security → Content Lock</strong> tab (Pro only).', 'wp-edit-password-protected' ),
					__( 'Enable content lock and configure the locked message visitors will see.', 'wp-edit-password-protected' ),
					__( 'Edit any post/page and enable <strong>Content Lock</strong> from the meta box.', 'wp-edit-password-protected' ),
					__( 'Non-logged-in users will see the locked message instead of the post content.', 'wp-edit-password-protected' ),
				],
			],
			'conditional-display'  => [
				'icon'  => 'dashicons-visibility',
				'title' => __( 'Conditional Content Display', 'wp-edit-password-protected' ),
				'steps' => [
					__( 'Edit any Post or Page and find the <strong>"Conditional Display"</strong> meta box in the sidebar.', 'wp-edit-password-protected' ),
					__( 'Toggle <strong>Enable Conditional Display</strong> on.', 'wp-edit-password-protected' ),
					__( 'Choose a basic condition — <em>User is logged in</em> or <em>User is logged out</em>.', 'wp-edit-password-protected' ),
					[ 'text' => __( 'Advanced conditions: User Role, Device Type, Day of Week, Time Range, Date Range, Recurring Schedule, Post Type, Browser Type, URL Parameter, Referrer Source.', 'wp-edit-password-protected' ), 'pro' => true ],
					__( 'Select an action: <strong>Show</strong> or <strong>Hide</strong> the content when the condition is met.', 'wp-edit-password-protected' ),
					__( 'Optionally control whether the title and featured image are also hidden.', 'wp-edit-password-protected' ),
					__( 'Save the post. The content will now display conditionally on the frontend.', 'wp-edit-password-protected' ),
				],
			],
			'member-only'          => [
				'icon'  => 'dashicons-groups',
				'title' => __( 'Member-Only Page', 'wp-edit-password-protected' ),
				'steps' => [
					__( 'Create a new <strong>Page</strong> in WordPress.', 'wp-edit-password-protected' ),
					__( 'In the Page editor, open <strong>Page Attributes</strong> and select the <em>"Member Only Template"</em>.', 'wp-edit-password-protected' ),
					__( 'Logged-in users will see the page content; logged-out users see the login form.', 'wp-edit-password-protected' ),
					__( 'Configure the member-only form settings under the <strong>WPEPP Security</strong> settings panel.', 'wp-edit-password-protected' ),
				],
			],
			'security'             => [
				'icon'  => 'dashicons-shield-alt',
				'title' => __( 'Security Features', 'wp-edit-password-protected' ),
				'steps' => [
					__( 'Go to <strong>WPEPP Security → Security</strong> tab.', 'wp-edit-password-protected' ),
					__( '<strong>Login Limiter</strong> — Set the max number of failed login attempts and lockout duration.', 'wp-edit-password-protected' ),
					__( '<strong>Honeypot</strong> — Enable the invisible honeypot field to catch bots.', 'wp-edit-password-protected' ),
					__( '<strong>Disable XML-RPC</strong> — Block XML-RPC requests to prevent brute-force attacks.', 'wp-edit-password-protected' ),
					__( '<strong>Hide WP Version</strong> — Remove the WordPress version number from your site source.', 'wp-edit-password-protected' ),
					__( '<strong>Disable REST User Enumeration</strong> — Prevent user discovery via the REST API.', 'wp-edit-password-protected' ),
					[ 'text' => __( '<strong>reCAPTCHA</strong> — Add Google reCAPTCHA to the login form.', 'wp-edit-password-protected' ), 'pro' => true ],
					[ 'text' => __( '<strong>Custom Login URL</strong> — Change the default login URL for added security.', 'wp-edit-password-protected' ), 'pro' => true ],
					[ 'text' => __( '<strong>Login Log</strong> — Track all login attempts with IP and timestamp.', 'wp-edit-password-protected' ), 'pro' => true ],
				],
			],
			'password-form'        => [
				'icon'  => 'dashicons-lock',
				'title' => __( 'Form Style', 'wp-edit-password-protected' ),
				'steps' => [
					__( 'Go to <strong>WPEPP Security → Form Style</strong> tab.', 'wp-edit-password-protected' ),
					__( 'Choose a form style — Style 1 & Style 2 are available for free.', 'wp-edit-password-protected' ),
					[ 'text' => __( 'Style 3 & Style 4 — Additional premium form styles with advanced layouts.', 'wp-edit-password-protected' ), 'pro' => true ],
					__( 'Customize the form label, button text, error message, top/bottom text, and social icons.', 'wp-edit-password-protected' ),
					[ 'text' => __( '<strong>Custom CSS</strong> — Write your own CSS for full control over form styling.', 'wp-edit-password-protected' ), 'pro' => true ],
					__( 'Use the <strong>live preview</strong> on the right side to see changes in real time.', 'wp-edit-password-protected' ),
					__( 'Click <strong>Save Settings</strong> when you are done.', 'wp-edit-password-protected' ),
					__( 'To test, password-protect any post or page from the editor\'s <em>Visibility</em> setting and view it.', 'wp-edit-password-protected' ),
				],
			],
			'login-page'           => [
				'icon'  => 'dashicons-admin-users',
				'title' => __( 'Login Page Customization', 'wp-edit-password-protected' ),
				'steps' => [
					__( 'Go to <strong>WPEPP Security → Login</strong> tab.', 'wp-edit-password-protected' ),
					__( 'Upload a custom logo, set background color/image, and style the login form colors.', 'wp-edit-password-protected' ),
					__( 'Customize the heading text displayed above the login form.', 'wp-edit-password-protected' ),
					__( 'Toggle the <strong>video background</strong> option for a dynamic login experience.', 'wp-edit-password-protected' ),
					[ 'text' => __( '<strong>Custom CSS</strong> — Add custom CSS to the login page.', 'wp-edit-password-protected' ), 'pro' => true ],
					[ 'text' => __( '<strong>Google Fonts</strong> — Choose from hundreds of premium fonts for the login form.', 'wp-edit-password-protected' ), 'pro' => true ],
					__( 'Click <strong>Save Settings</strong> and visit <code>wp-login.php</code> to preview.', 'wp-edit-password-protected' ),
				],
			],
			'templates'            => [
				'icon'  => 'dashicons-layout',
				'title' => __( 'Templates Gallery', 'wp-edit-password-protected' ),
				'steps' => [
					__( 'Go to <strong>WPEPP Security → Templates</strong> tab.', 'wp-edit-password-protected' ),
					__( 'Browse the template gallery — 3 free templates are included.', 'wp-edit-password-protected' ),
					__( 'Click <strong>Apply</strong> on any template to load its style settings instantly.', 'wp-edit-password-protected' ),
					__( 'After applying, customize any setting further to match your brand.', 'wp-edit-password-protected' ),
					[ 'text' => __( '<strong>Premium Templates</strong> — Unlock all 10+ professionally designed templates.', 'wp-edit-password-protected' ), 'pro' => true ],
					[ 'text' => __( '<strong>Import/Export</strong> — Save and share custom template configurations.', 'wp-edit-password-protected' ), 'pro' => true ],
				],
			],
			'faq'                  => [
				'icon'  => 'dashicons-editor-help',
				'title' => __( 'FAQ & Troubleshooting', 'wp-edit-password-protected' ),
				'items' => [
					[
						'q' => __( 'The password form does not show my custom styles.', 'wp-edit-password-protected' ),
						'a' => __( 'Make sure you clicked <strong>Save Settings</strong>. If you use a caching plugin, clear the cache. Also check that your theme does not override the <code>the_password_form</code> filter.', 'wp-edit-password-protected' ),
					],
					[
						'q' => __( 'The login page looks the same as default WordPress.', 'wp-edit-password-protected' ),
						'a' => __( 'Verify that the Login tab settings are saved. Some security plugins (e.g., Wordfence, iThemes Security) may override login page output.', 'wp-edit-password-protected' ),
					],
					[
						'q' => __( 'Conditional display is not working.', 'wp-edit-password-protected' ),
						'a' => __( 'Ensure the meta box is enabled on the post/page and that the condition is set correctly. Clear any page cache after saving.', 'wp-edit-password-protected' ),
					],
					[
						'q' => __( 'I upgraded to Pro but features are still locked.', 'wp-edit-password-protected' ),
						'a' => __( 'Make sure you activated the Pro license key. Go to <strong>WPEPP Security → License</strong> and verify the status shows "Active".', 'wp-edit-password-protected' ),
					],
					[
						'q' => __( 'Can I use this with WooCommerce product pages?', 'wp-edit-password-protected' ),
						'a' => __( 'Yes! Password protection works on any post type that supports it. Conditional Display also works on WooCommerce products.', 'wp-edit-password-protected' ),
					],
					[
						'q' => __( 'How do I reset all settings to default?', 'wp-edit-password-protected' ),
						'a' => __( 'Deactivate the plugin, delete the <code>wpepp_*</code> options from your database (or use a plugin like WP Reset), then reactivate.', 'wp-edit-password-protected' ),
					],
				],
			],
		];
	}

	/**
	 * Render the documentation page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'wp-edit-password-protected' ) );
		}

		$sections   = $this->get_sections();
		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'getting-started'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( ! array_key_exists( $active_tab, $sections ) ) {
			$active_tab = 'getting-started';
		}
		?>
		<div class="wrap wpepp-docs-wrap">
			<h1 class="screen-reader-text"><?php esc_html_e( 'Documentation', 'wp-edit-password-protected' ); ?></h1>

			<div class="wpepp-docs-header">
				<div class="wpepp-docs-header-inner">
					<div class="wpepp-docs-title">
						<span class="dashicons dashicons-book"></span>
						<?php esc_html_e( 'Documentation', 'wp-edit-password-protected' ); ?>
					</div>
					<p class="wpepp-docs-subtitle">
						<?php esc_html_e( 'Learn how to use WPEPP — step by step.', 'wp-edit-password-protected' ); ?>
					</p>
				</div>
				<div class="wpepp-docs-header-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpepp-settings' ) ); ?>" class="button">
						<span class="dashicons dashicons-admin-generic"></span>
						<?php esc_html_e( 'Back to Settings', 'wp-edit-password-protected' ); ?>
					</a>
				</div>
			</div>

			<div class="wpepp-docs-container">

				<!-- Sidebar Navigation -->
				<nav class="wpepp-docs-sidebar">
					<ul>
						<?php foreach ( $sections as $slug => $section ) : ?>
							<li class="<?php echo esc_attr( $slug === $active_tab ? 'active' : '' ); ?>">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpepp-docs&tab=' . $slug ) ); ?>">
									<span class="dashicons <?php echo esc_attr( $section['icon'] ); ?>"></span>
									<?php echo esc_html( $section['title'] ); ?>
									<?php if ( ! empty( $section['badge'] ) && 'pro' === $section['badge'] ) : ?>
										<span class="wpepp-docs-pro-tag"><?php esc_html_e( 'PRO', 'wp-edit-password-protected' ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>

				<!-- Main Content -->
				<div class="wpepp-docs-content">
					<?php
					$current = $sections[ $active_tab ];
					?>
					<div class="wpepp-docs-section">
						<div class="wpepp-docs-section-header">
							<span class="dashicons <?php echo esc_attr( $current['icon'] ); ?>"></span>
							<h2><?php echo esc_html( $current['title'] ); ?></h2>
							<?php if ( ! empty( $current['badge'] ) && 'pro' === $current['badge'] ) : ?>
								<span class="wpepp-docs-pro-tag"><?php esc_html_e( 'PRO', 'wp-edit-password-protected' ); ?></span>
							<?php endif; ?>
						</div>

						<?php if ( ! empty( $current['steps'] ) ) : ?>
							<ol class="wpepp-docs-steps">
							<?php foreach ( $current['steps'] as $i => $step ) :
								$is_pro  = is_array( $step ) && ! empty( $step['pro'] );
								$text    = $is_pro ? $step['text'] : $step;
							?>
								<li class="<?php echo esc_attr( $is_pro ? 'wpepp-docs-step-pro' : '' ); ?>">
									<div class="wpepp-docs-step-number"><?php echo (int) ( $i + 1 ); ?></div>
									<div class="wpepp-docs-step-text">
										<?php echo wp_kses_post( $text ); ?>
										<?php if ( $is_pro ) : ?>
											<span class="wpepp-docs-pro-tag"><?php esc_html_e( 'PRO', 'wp-edit-password-protected' ); ?></span>
										<?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
							</ol>
						<?php endif; ?>

						<?php if ( ! empty( $current['items'] ) ) : ?>
							<div class="wpepp-docs-faq">
								<?php foreach ( $current['items'] as $item ) : ?>
									<details class="wpepp-docs-faq-item">
										<summary><?php echo wp_kses_post( $item['q'] ); ?></summary>
										<div class="wpepp-docs-faq-answer">
											<?php echo wp_kses_post( $item['a'] ); ?>
										</div>
									</details>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>
					</div>

					<!-- Quick Links Card -->
					<div class="wpepp-docs-card">
						<h3><?php esc_html_e( 'Need More Help?', 'wp-edit-password-protected' ); ?></h3>
						<div class="wpepp-docs-links-grid">
							<a href="https://wordpress.org/support/plugin/wp-edit-password-protected/" target="_blank" rel="noopener noreferrer" class="wpepp-docs-link-card">
								<span class="dashicons dashicons-sos"></span>
								<strong><?php esc_html_e( 'Support Forum', 'wp-edit-password-protected' ); ?></strong>
								<span><?php esc_html_e( 'Ask a question on WordPress.org', 'wp-edit-password-protected' ); ?></span>
							</a>
							<a href="https://wordpress.org/plugins/wp-edit-password-protected/#reviews" target="_blank" rel="noopener noreferrer" class="wpepp-docs-link-card">
								<span class="dashicons dashicons-star-filled"></span>
								<strong><?php esc_html_e( 'Leave a Review', 'wp-edit-password-protected' ); ?></strong>
								<span><?php esc_html_e( 'Love the plugin? Rate us 5 stars!', 'wp-edit-password-protected' ); ?></span>
							</a>
							<?php if ( ! wpepp_has_pro_check() ) : ?>
								<a href="<?php echo esc_url( 'https://wpthemespace.com/product/wp-edit-password-protected-pro/' ); ?>" target="_blank" rel="noopener noreferrer" class="wpepp-docs-link-card wpepp-docs-link-pro">
									<span class="dashicons dashicons-superhero"></span>
									<strong><?php esc_html_e( 'Upgrade to Pro', 'wp-edit-password-protected' ); ?></strong>
									<span><?php esc_html_e( 'Unlock all features & premium support', 'wp-edit-password-protected' ); ?></span>
								</a>
							<?php endif; ?>
						</div>
					</div>

				</div>
			</div>
		</div>
		<?php
	}
}
