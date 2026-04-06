/**
 * Toast notifications from @wordpress/notices store.
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { SnackbarList } from '@wordpress/components';
import { store as noticesStore } from '@wordpress/notices';

const Notices = () => {
	const notices = useSelect( ( select ) =>
		select( noticesStore ).getNotices().filter( ( n ) => n.type === 'snackbar' )
	);
	const { removeNotice } = useDispatch( noticesStore );

	if ( ! notices.length ) {
		return null;
	}

	return (
		<div className="wpepp-notices">
			<SnackbarList
				notices={ notices }
				onRemove={ removeNotice }
			/>
		</div>
	);
};

export default Notices;
