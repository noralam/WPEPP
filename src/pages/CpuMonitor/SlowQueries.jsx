/**
 * CPU Monitor — Slow Queries tab.
 *
 * Lists slow DB queries with pagination and diagnostic controls.
 */
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button, Spinner, Notice, ToggleControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import ProBadge from '../../components/ProBadge';
import Pagination from '../../components/Pagination';

const SlowQueries = () => {
	const [ queries, setQueries ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ expanded, setExpanded ] = useState( null );
	const [ saveQueries, setSaveQueries ] = useState( false );
	const [ configWritable, setConfigWritable ] = useState( true );
	const [ toggling, setToggling ] = useState( false );
	const [ page, setPage ] = useState( 1 );
	const pageSize = 10;

	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	const fetchQueries = useCallback( () => {
		setLoading( true );
		apiFetch( { path: '/wpepp/v1/cpu/slow-queries' } )
			.then( ( data ) => setQueries( Array.isArray( data ) ? data : [] ) )
			.catch( () => setQueries( [] ) )
			.finally( () => setLoading( false ) );
	}, [] );

	const fetchConfigStatus = useCallback( () => {
		apiFetch( { path: '/wpepp/v1/cpu/wp-config/status' } )
			.then( ( data ) => {
				setSaveQueries( !! data?.SAVEQUERIES );
				setConfigWritable( !! data?.writable );
			} )
			.catch( () => {} );
	}, [] );

	useEffect( () => {
		fetchQueries();
		fetchConfigStatus();
	}, [] );

	const handleToggleSaveQueries = ( value ) => {
		setToggling( true );
		apiFetch( {
			path: '/wpepp/v1/cpu/wp-config/toggle',
			method: 'POST',
			data: { constant: 'SAVEQUERIES', value },
		} )
			.then( ( res ) => {
				setSaveQueries( !! res?.status?.SAVEQUERIES );
			} )
			.catch( () => {} )
			.finally( () => setToggling( false ) );
	};

	if ( loading ) {
		return <Spinner />;
	}

	const paginatedQueries = queries.slice( ( page - 1 ) * pageSize, page * pageSize );

	return (
		<div className="wpepp-slow-queries">
			<div className="wpepp-slow-queries__header">
				<h3>{ __( 'Slow Database Queries', 'wp-edit-password-protected' ) }</h3>
				<Button variant="secondary" onClick={ fetchQueries }>
					{ __( 'Refresh', 'wp-edit-password-protected' ) }
				</Button>
			</div>

			{ ! isPro && (
				<Notice status="info" isDismissible={ false } style={ { marginBottom: 12 } }>
					{ __( 'Free version shows the last 10 slow queries without call stacks.', 'wp-edit-password-protected' ) }
					<ProBadge />
				</Notice>
			) }

			<div className="wpepp-config-toggle">
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Enable Query Logging (SAVEQUERIES)', 'wp-edit-password-protected' ) }
					help={
						! configWritable
							? __( 'wp-config.php is not writable. Please check file permissions.', 'wp-edit-password-protected' )
							: saveQueries
								? __( 'Slow queries are currently being tracked. Recommended: Disable when finished troubleshooting to eliminate PHP memory overhead.', 'wp-edit-password-protected' )
								: __( 'Enable temporarily to monitor slow database queries during performance investigation.', 'wp-edit-password-protected' )
					}
					checked={ saveQueries }
					onChange={ handleToggleSaveQueries }
					disabled={ toggling || ! configWritable }
				/>
			</div>

			{ queries.length === 0 ? (
				<p className="wpepp-empty-state">
					{ saveQueries
						? __( 'No slow queries logged yet. Queries will appear after page loads.', 'wp-edit-password-protected' )
						: __( 'Enable Query Logging above to start tracking slow database queries.', 'wp-edit-password-protected' )
					}
				</p>
			) : (
				<>
					<table className="wpepp-table widefat striped">
						<thead>
							<tr>
								<th>{ __( 'Query', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Time (s)', 'wp-edit-password-protected' ) }</th>
								<th>{ __( 'Recorded', 'wp-edit-password-protected' ) }</th>
								{ isPro && <th>{ __( 'Stack', 'wp-edit-password-protected' ) }</th> }
							</tr>
						</thead>
						<tbody>
							{ paginatedQueries.map( ( q ) => (
								<tr key={ q.id }>
									<td className="wpepp-slow-queries__sql">
										<code>{ q.query_sql }</code>
									</td>
									<td>
										<span className={ q.exec_time >= 1 ? 'wpepp-status wpepp-status--failed' : '' }>
											{ parseFloat( q.exec_time ).toFixed( 3 ) }
										</span>
									</td>
									<td style={ { fontVariantNumeric: 'tabular-nums', whiteSpace: 'nowrap' } }>{ q.recorded_at }</td>
									{ isPro && (
										<td>
											{ q.call_stack && (
												<>
													<Button
														variant="link"
														onClick={ () => setExpanded( expanded === q.id ? null : q.id ) }
													>
														{ expanded === q.id ? __( 'Hide', 'wp-edit-password-protected' ) : __( 'View', 'wp-edit-password-protected' ) }
													</Button>
													{ expanded === q.id && (
														<pre className="wpepp-slow-queries__stack">{ q.call_stack }</pre>
													) }
												</>
											) }
										</td>
									) }
								</tr>
							) ) }
						</tbody>
					</table>
					<Pagination
						currentPage={ page }
						totalItems={ queries.length }
						pageSize={ pageSize }
						onPageChange={ setPage }
						itemName={ __( 'slow queries', 'wp-edit-password-protected' ) }
					/>
				</>
			) }
		</div>
	);
};

export default SlowQueries;
