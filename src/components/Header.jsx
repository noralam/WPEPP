/**
 * Admin page header component — title, theme toggle, Docs, Upgrade.
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Button, Spinner } from '@wordpress/components';

const MoonIcon = () => (
	<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
		<path d="M12.4 3.1a8.4 8.4 0 0 0-8.3 7.2c-.1.6.6 1 1.1.7a6.9 6.9 0 0 1 9.3 9.2c-.3.5.1 1.2.7 1.1a8.4 8.4 0 0 0-2.8-18.2Zm-1.2 2.2a6.4 6.4 0 0 1 3.6 11.3 8.9 8.9 0 0 0-8.9-8.9 6.4 6.4 0 0 1 5.3-2.4Z" />
	</svg>
);

const SunIcon = () => (
	<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
		<path d="M12 7.2a4.8 4.8 0 1 0 0 9.6 4.8 4.8 0 0 0 0-9.6Zm0 7.6a2.8 2.8 0 1 1 0-5.6 2.8 2.8 0 0 1 0 5.6ZM11 2.8v2a1 1 0 1 0 2 0v-2a1 1 0 1 0-2 0Zm0 16.4v2a1 1 0 1 0 2 0v-2a1 1 0 1 0-2 0ZM2.8 11a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2h-2Zm16.4 0a1 1 0 1 0 0 2h2a1 1 0 1 0 0-2h-2ZM5.4 4a1 1 0 0 0-1.4 1.4l1.4 1.4A1 1 0 0 0 6.8 5.4L5.4 4Zm13.2 13.2a1 1 0 0 0-1.4 1.4l1.4 1.4a1 1 0 0 0 1.4-1.4l-1.4-1.4ZM4 18.6l1.4-1.4a1 1 0 0 0-1.4-1.4l-1.4 1.4A1 1 0 0 0 4 18.6ZM18.6 5.4 20 4a1 1 0 0 0-1.4-1.4l-1.4 1.4a1 1 0 0 0 1.4 1.4Z" />
	</svg>
);

const Header = ( { theme, onThemeChange } ) => {
	const { isSaving, hasChanges } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			isSaving: store.isSaving(),
			hasChanges: store.hasChanges(),
		};
	} );

	return (
		<header className="wpepp-header">
			<h1 className="wpepp-header__title">
				{ __( 'WPEPP – Essential Security, Password Protect & Login Page Customizer', 'wp-edit-password-protected' ) }
			</h1>
			<div className="wpepp-header__actions">
				{ isSaving && <Spinner /> }
				{ hasChanges && (
					<span className="wpepp-header__unsaved">
						{ __( 'Unsaved changes', 'wp-edit-password-protected' ) }
					</span>
				) }
				<div className="wpepp-theme-toggle" role="group" aria-label={ __( 'Color theme', 'wp-edit-password-protected' ) }>
					<button
						type="button"
						className={ theme === 'dark' ? 'is-active' : '' }
						aria-pressed={ theme === 'dark' }
						aria-label={ __( 'Dark theme', 'wp-edit-password-protected' ) }
						title={ __( 'Dark theme', 'wp-edit-password-protected' ) }
						onClick={ () => onThemeChange( 'dark' ) }
					>
						<MoonIcon />
					</button>
					<button
						type="button"
						className={ theme === 'light' ? 'is-active' : '' }
						aria-pressed={ theme === 'light' }
						aria-label={ __( 'Light theme', 'wp-edit-password-protected' ) }
						title={ __( 'Light theme', 'wp-edit-password-protected' ) }
						onClick={ () => onThemeChange( 'light' ) }
					>
						<SunIcon />
					</button>
				</div>
				{ ! window.wpeppData?.isPro && (
					<Button
						variant="primary"
						href={ window.wpeppData?.proUrl || 'https://wpthemespace.com/product/wpepp-essential-security-password-protect-login-page-customizer/#pricing' }
						target="_blank"
						className="wpepp-header__upgrade"
					>
						{ __( 'Upgrade to Pro', 'wp-edit-password-protected' ) } »
					</Button>
				) }
				<Button
					variant="link"
					href={ ( window.wpeppData?.adminUrl || '/wp-admin/' ) + 'admin.php?page=wpepp-docs' }
					className="wpepp-header__docs"
				>
					{ __( 'Docs', 'wp-edit-password-protected' ) }
				</Button>
			</div>
		</header>
	);
};

export default Header;
