/**
 * ProLock — Overlay for Pro-only sections.
 */
import { Icon, lock } from '@wordpress/icons';
import { __, sprintf } from '@wordpress/i18n';

const ProLock = ( { isPro, featureName, children } ) => {
	// DEV MODE: ProLock disabled — always show children.
	// TODO: Re-enable ProLock before production release.
	return children;
};

export default ProLock;
