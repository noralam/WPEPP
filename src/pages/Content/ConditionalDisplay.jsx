/**
 * Conditional Display dashboard — list of posts with conditional rules.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { Button, Spinner, ToggleControl, SelectControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';
import ProLock from '../../components/ProLock';

const ConditionalDisplay = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );
	const [ posts, setPosts ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ filter, setFilter ] = useState( 'all' );

	useEffect( () => {
		apiFetch( { path: '/wpepp/v1/conditional' } )
			.then( ( data ) => setPosts( Array.isArray( data ) ? data : [] ) )
			.catch( () => setPosts( [] ) )
			.finally( () => setLoading( false ) );
	}, [] );

	const toggleConditional = ( postId, enabled ) => {
		apiFetch( {
			path: `/wpepp/v1/conditional/${ postId }/toggle`,
			method: 'POST',
			data: { enabled },
		} ).then( () => {
			setPosts( ( prev ) =>
				prev.map( ( p ) =>
					p.id === postId ? { ...p, enabled } : p
				)
			);
		} );
	};

	const filteredPosts = filter === 'all'
		? posts
		: posts.filter( ( p ) => p.type === filter );

	if ( loading ) {
		return <Spinner />;
	}

	return (
		<div className="wpepp-conditional-display">
			<h3>{ __( 'Conditional Display', 'wp-edit-password-protected' ) }</h3>
			<p>{ __( 'Manage posts and pages with conditional display rules.', 'wp-edit-password-protected' ) }</p>

			<ProLock isPro={ isPro } featureName={ __( 'Conditional Display Dashboard', 'wp-edit-password-protected' ) }>
				<div className="wpepp-conditional-display__controls">
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Filter by type', 'wp-edit-password-protected' ) }
						value={ filter }
						options={ [
							{ label: __( 'All', 'wp-edit-password-protected' ), value: 'all' },
							{ label: __( 'Posts', 'wp-edit-password-protected' ), value: 'post' },
							{ label: __( 'Pages', 'wp-edit-password-protected' ), value: 'page' },
						] }
						onChange={ setFilter }
					/>
				</div>

				{ filteredPosts.length === 0 ? (
					<p className="wpepp-empty-state">
						{ __( 'No posts with conditional display rules. Use the Conditional Display meta box in the post editor.', 'wp-edit-password-protected' ) }
					</p>
				) : (
					<table className="wpepp-table widefat striped">
						<thead>
							<tr>
								<th>{ __( 'Title', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Type', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Condition', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Action', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Enabled', 'wp-edit-password-protected' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ filteredPosts.map( ( post ) => (
								<tr key={ post.id }>
									<td>
										<a href={ post.edit_link } target="_blank" rel="noopener noreferrer">
											{ post.title }
										</a>
									</td>
									<td>{ post.type }</td>
									<td>{ post.condition }</td>
									<td>{ post.action }</td>
									<td>
										<ToggleControl
											checked={ post.enabled }
											onChange={ ( v ) => toggleConditional( post.id, v ) }
											__nextHasNoMarginBottom
										/>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }
			</ProLock>
		</div>
	);
};

export default ConditionalDisplay;
