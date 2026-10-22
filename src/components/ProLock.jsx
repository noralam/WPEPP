/**
 * ProLock — Overlay for Pro-only sections.
 *
 * Renders children when Pro is active; otherwise shows a lock overlay with
 * an upgrade CTA and a dimmed, non-interactive preview of the section.
 */
import { Icon, lock } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

const ProLock = ( { isPro, featureName, children } ) => {
	const storePro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );
	const unlocked = isPro !== undefined ? !! isPro : !! storePro;

	if ( unlocked ) {
		return children;
	}

	return (
		<div className="wpepp-pro-lock" style={ { position: 'relative' } }>
			<div
				aria-hidden="true"
				style={ { opacity: 0.35, pointerEvents: 'none', userSelect: 'none', filter: 'grayscale(0.4)' } }
			>
				{ children }
			</div>
			<div
				className="wpepp-pro-lock__overlay"
				style={ {
					position: 'absolute',
					inset: 0,
					display: 'flex',
					flexDirection: 'column',
					alignItems: 'center',
					justifyContent: 'center',
					gap: 8,
					textAlign: 'center',
					background: 'rgba(30,30,30,0.55)',
					borderRadius: 8,
					padding: 16,
					zIndex: 5,
				} }
			>
				<Icon icon={ lock } size={ 28 } />
				<strong>{ featureName }</strong>
				<p style={ { margin: 0, opacity: 0.85 } }>
					{ __( 'This feature is available in WPEPP Pro.', 'wp-edit-password-protected' ) }
				</p>
				<Button
					variant="primary"
					href={ window.wpeppData?.proUrl || '#' }
					target="_blank"
					rel="noopener noreferrer"
				>
					{ __( 'Upgrade to Pro', 'wp-edit-password-protected' ) }
				</Button>
			</div>
		</div>
	);
};

export default ProLock;
