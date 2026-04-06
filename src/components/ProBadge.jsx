/**
 * ProBadge — Small "PRO" badge for individual controls.
 */
import { __ } from '@wordpress/i18n';

const ProBadge = () => (
	<span className="wpepp-pro-badge" aria-label={ __( 'Pro feature', 'wp-edit-password-protected' ) }>
		{ __( 'PRO', 'wp-edit-password-protected' ) }
	</span>
);

export default ProBadge;
