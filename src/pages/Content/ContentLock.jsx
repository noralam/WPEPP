/**
 * Content Lock — table of locked posts with quick toggle (Pro).
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner, ToggleControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

const ContentLock = () => {
	const [ posts, setPosts ] = useState( [] );
	const [ loading, setLoading ] = useState( true );

	useEffect( () => {
		apiFetch( { path: '/wpepp/v1/content-lock' } )
			.then( ( data ) => setPosts( Array.isArray( data ) ? data : [] ) )
			.catch( () => setPosts( [] ) )
			.finally( () => setLoading( false ) );
	}, [] );

	const toggleLock = ( postId, enabled ) => {
		// Optimistic update — toggle UI instantly.
		setPosts( ( prev ) =>
			prev.map( ( p ) =>
				p.id === postId ? { ...p, locked: enabled } : p
			)
		);

		apiFetch( {
			path: `/wpepp/v1/content-lock/${ postId }`,
			method: 'POST',
			data: { enabled },
		} ).catch( () => {
			// Revert on failure.
			setPosts( ( prev ) =>
				prev.map( ( p ) =>
					p.id === postId ? { ...p, locked: ! enabled } : p
				)
			);
		} );
	};

	if ( loading ) {
		return <Spinner />;
	}

	return (
		<div className="wpepp-content-lock">
			<h3>{ __( 'Locked Posts & Pages', 'wp-edit-password-protected' ) }</h3>
			<p>{ __( 'Posts and pages that are locked for logged-out users.', 'wp-edit-password-protected' ) }</p>

			{ posts.length === 0 ? (
				<p className="wpepp-empty-state">
					{ __( 'No locked posts yet. Use the Content Lock meta box in the post editor to lock individual posts.', 'wp-edit-password-protected' ) }
				</p>
			) : (
				<table className="wpepp-table widefat striped">
					<thead>
						<tr>
							<th>{ __( 'Title', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Type', 'wp-edit-password-protected' ) }</th>
							<th>{ __( 'Locked', 'wp-edit-password-protected' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ posts.map( ( post ) => (
							<tr key={ post.id }>
								<td>
									<a href={ post.edit_link } target="_blank" rel="noopener noreferrer">
										{ post.title }
									</a>
								</td>
								<td>{ post.post_type }</td>
								<td>
									<ToggleControl
										checked={ post.locked }
										onChange={ ( v ) => toggleLock( post.id, v ) }
										__nextHasNoMarginBottom
									/>
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }
		</div>
	);
};

export default ContentLock;
