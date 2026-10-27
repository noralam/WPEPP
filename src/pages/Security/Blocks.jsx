/**
 * Block Center — manage manual/temporary IP & CIDR blocks.
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import {
	Button,
	TextControl,
	SelectControl,
	Notice,
	__experimentalConfirmDialog as ConfirmDialog,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import Card from '../../components/Card';
import Pagination from '../../components/Pagination';
import { TableSkeleton } from '../../components/Skeleton';

const TYPE_LABELS = {
	manual: __( 'Manual', 'wp-edit-password-protected' ),
	auto_404: __( 'Auto (404 flood)', 'wp-edit-password-protected' ),
	rate: __( 'Rate limit', 'wp-edit-password-protected' ),
	lockout: __( 'Login lockout', 'wp-edit-password-protected' ),
	country: __( 'Country', 'wp-edit-password-protected' ),
};

const Blocks = () => {
	const [ blocks, setBlocks ] = useState( null );
	const [ newIp, setNewIp ] = useState( '' );
	const [ reason, setReason ] = useState( '' );
	const [ duration, setDuration ] = useState( 'permanent' );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ confirmDelete, setConfirmDelete ] = useState( null );
	const [ page, setPage ] = useState( 1 );
	const pageSize = 15;

	const load = useCallback( () => {
		apiFetch( { path: '/wpepp/v1/blocks?per_page=100' } )
			.then( setBlocks )
			.catch( () => setBlocks( [] ) );
	}, [] );

	useEffect( load, [ load ] );

	const addBlock = useCallback( async () => {
		if ( ! newIp.trim() ) {
			return;
		}

		setSaving( true );
		setError( '' );

		try {
			await apiFetch( {
				path: '/wpepp/v1/blocks',
				method: 'POST',
				data: {
					ip: newIp.trim(),
					reason,
					duration,
				},
			} );
			setNewIp( '' );
			setReason( '' );
			setDuration( 'permanent' );
			load();
		} catch ( err ) {
			setError( err.message || __( 'Could not save the block.', 'wp-edit-password-protected' ) );
		}

		setSaving( false );
	}, [ newIp, reason, duration, load ] );

	const removeBlock = useCallback( async ( id ) => {
		setConfirmDelete( null );

		try {
			await apiFetch( { path: `/wpepp/v1/blocks/${ id }`, method: 'DELETE' } );
			load();
		} catch {
			// Ignore.
		}
	}, [ load ] );

	const purgeExpired = useCallback( async () => {
		try {
			await apiFetch( { path: '/wpepp/v1/blocks/purge-expired', method: 'POST' } );
			load();
		} catch {
			// Ignore.
		}
	}, [ load ] );

	return (
		<div className="wpepp-blocks">
			<h3>{ __( 'Block Center', 'wp-edit-password-protected' ) }</h3>

			{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }

			<Card
				title={ __( 'Add IP Block', 'wp-edit-password-protected' ) }
				subtitle={ __(
					'Blocked IPs cannot access the site at all. Supports single IPs and CIDR ranges (e.g. 203.0.113.0/24).',
					'wp-edit-password-protected'
				) }
			>
				<div className="wpepp-form-grid">
					<TextControl
						label={ __( 'IP or CIDR range', 'wp-edit-password-protected' ) }
						value={ newIp }
						onChange={ setNewIp }
						placeholder="192.0.2.55"
					/>
					<SelectControl
						label={ __( 'Duration', 'wp-edit-password-protected' ) }
						value={ duration }
						onChange={ setDuration }
						options={ [
							{ value: 'permanent', label: __( 'Permanent', 'wp-edit-password-protected' ) },
							{ value: '1h', label: __( '1 hour', 'wp-edit-password-protected' ) },
							{ value: '24h', label: __( '24 hours', 'wp-edit-password-protected' ) },
							{ value: '7d', label: __( '7 days', 'wp-edit-password-protected' ) },
						] }
					/>
					<div className="wpepp-form-grid__full">
						<TextControl
							label={ __( 'Reason (optional)', 'wp-edit-password-protected' ) }
							value={ reason }
							onChange={ setReason }
							placeholder={ __( 'e.g. Repeated failed login attempts', 'wp-edit-password-protected' ) }
						/>
					</div>
					<div className="wpepp-form-grid__full wpepp-form-actions">
						<Button variant="primary" onClick={ addBlock } isBusy={ saving } disabled={ ! newIp.trim() }>
							{ __( 'Add Block', 'wp-edit-password-protected' ) }
						</Button>
						<Button variant="secondary" onClick={ purgeExpired }>
							{ __( 'Purge expired blocks', 'wp-edit-password-protected' ) }
						</Button>
					</div>
				</div>
			</Card>

			<Card title={ __( 'Active Blocks', 'wp-edit-password-protected' ) } padded={ false }>
				{ blocks === null ? (
					<TableSkeleton rows={ 5 } columns={ 5 } />
				) : blocks.length === 0 ? (
					<div className="wpepp-empty-state">
						<div className="wpepp-empty-state__icon">🛡️</div>
						<h4 className="wpepp-empty-state__title">{ __( 'No Active IP Blocks', 'wp-edit-password-protected' ) }</h4>
						<p className="wpepp-empty-state__desc">
							{ __( 'All clear! There are currently no IP addresses or ranges blocked. When malicious visitors or rate limiters trigger a block, they will appear here.', 'wp-edit-password-protected' ) }
						</p>
					</div>
				) : (
					<>
						<table className="wpepp-blocks__table widefat">
							<thead>
								<tr>
									<th>{ __( 'IP / Range', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'Type', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'Reason', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'Expires', 'wp-edit-password-protected' ) }</th>
									<th>{ __( 'Hits', 'wp-edit-password-protected' ) }</th>
									<th aria-label={ __( 'Actions', 'wp-edit-password-protected' ) } />
								</tr>
							</thead>
							<tbody>
								{ blocks.slice( ( page - 1 ) * pageSize, page * pageSize ).map( ( b ) => (
									<tr key={ b.id }>
										<td><code>{ b.cidr }</code></td>
										<td>{ TYPE_LABELS[ b.type ] || b.type }</td>
										<td>{ b.reason || '—' }</td>
										<td>
											{ b.expires
												// eslint-disable-next-line @wordpress/no-global-date-now -- display only
												? new Date( b.expires.replace( ' ', 'T' ) + 'Z' ).toLocaleString()
												: __( 'Never', 'wp-edit-password-protected' ) }
										</td>
										<td>{ b.hit_count || 0 }</td>
										<td>
											<Button
												variant="tertiary"
												isDestructive
												onClick={ () => setConfirmDelete( b.id ) }
											>
												{ __( 'Remove', 'wp-edit-password-protected' ) }
											</Button>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
						<Pagination
							currentPage={ page }
							totalItems={ blocks.length }
							pageSize={ pageSize }
							onPageChange={ setPage }
							itemName={ __( 'blocks', 'wp-edit-password-protected' ) }
						/>
					</>
				) }
			</Card>

			{ confirmDelete !== null && (
				<ConfirmDialog
					isOpen
					onConfirm={ () => removeBlock( confirmDelete ) }
					onCancel={ () => setConfirmDelete( null ) }
				>
					{ __( 'Remove this block?', 'wp-edit-password-protected' ) }
				</ConfirmDialog>
			) }
		</div>
	);
};

export default Blocks;
