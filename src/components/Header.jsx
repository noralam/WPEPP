/**
 * Admin page header component.
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { Button, Spinner } from '@wordpress/components';

const Header = () => {
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
				{ ! window.wpeppData?.isPro && (
					<Button
						variant="primary"
						href={ window.wpeppData?.proUrl || 'https://wpthemespace.com/product/wpepp-essential-security-password-protect-login-page-customizer/#pricing' }
						target="_blank"
						className="wpepp-header__upgrade"
					>
						{ __( 'Upgrade to Pro', 'wp-edit-password-protected' ) }
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
