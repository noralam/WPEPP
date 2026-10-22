/**
 * Reusable, accessible Pagination component for WPEPP data tables.
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

const Pagination = ( {
	currentPage = 1,
	totalItems = 0,
	pageSize = 15,
	onPageChange,
	itemName = __( 'entries', 'wp-edit-password-protected' ),
} ) => {
	const totalPages = Math.max( 1, Math.ceil( totalItems / pageSize ) );

	if ( totalPages <= 1 && totalItems <= pageSize ) {
		return null;
	}

	const startItem = totalItems === 0 ? 0 : ( currentPage - 1 ) * pageSize + 1;
	const endItem = Math.min( totalItems, currentPage * pageSize );

	// Generate visible page numbers (smart truncation with ellipsis)
	const getPageNumbers = () => {
		const pages = [];
		const maxVisible = 5;

		if ( totalPages <= maxVisible ) {
			for ( let i = 1; i <= totalPages; i++ ) {
				pages.push( i );
			}
		} else {
			pages.push( 1 );
			if ( currentPage > 3 ) {
				pages.push( 'ellipsis_left' );
			}

			const start = Math.max( 2, currentPage - 1 );
			const end = Math.min( totalPages - 1, currentPage + 1 );

			for ( let i = start; i <= end; i++ ) {
				if ( ! pages.includes( i ) ) {
					pages.push( i );
				}
			}

			if ( currentPage < totalPages - 2 ) {
				pages.push( 'ellipsis_right' );
			}

			if ( ! pages.includes( totalPages ) ) {
				pages.push( totalPages );
			}
		}
		return pages;
	};

	const pages = getPageNumbers();

	return (
		<div
			className="wpepp-pagination"
			style={ {
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'space-between',
				flexWrap: 'wrap',
				gap: 12,
				padding: '12px 16px',
				borderTop: '1px solid var(--wpepp-border)',
				background: 'var(--wpepp-surface)',
				borderRadius: '0 0 8px 8px',
				fontSize: 13,
				color: 'var(--wpepp-text-2)',
			} }
		>
			<div className="wpepp-pagination__info">
				{ totalItems > 0 ? (
					sprintf(
						/* translators: 1: start item, 2: end item, 3: total items, 4: item name */
						__( 'Showing %1$d–%2$d of %3$d %4$s', 'wp-edit-password-protected' ),
						startItem,
						endItem,
						totalItems,
						itemName
					)
				) : (
					sprintf( __( 'No %s found', 'wp-edit-password-protected' ), itemName )
				) }
			</div>

			<div
				className="wpepp-pagination__controls"
				style={ {
					display: 'flex',
					alignItems: 'center',
					gap: 6,
				} }
			>
				<Button
					variant="secondary"
					size="compact"
					disabled={ currentPage <= 1 }
					onClick={ () => onPageChange( currentPage - 1 ) }
					style={ { minHeight: 30, fontSize: 12 } }
				>
					{ __( 'Previous', 'wp-edit-password-protected' ) }
				</Button>

				{ pages.map( ( p, idx ) => {
					if ( typeof p === 'string' && p.startsWith( 'ellipsis' ) ) {
						return (
							<span
								key={ p }
								style={ {
									padding: '0 4px',
									color: 'var(--wpepp-text-3)',
								} }
							>
								…
							</span>
						);
					}

					const isCurrent = p === currentPage;
					return (
						<Button
							key={ p }
							variant={ isCurrent ? 'primary' : 'tertiary' }
							size="compact"
							onClick={ () => onPageChange( p ) }
							style={ {
								minWidth: 30,
								minHeight: 30,
								fontSize: 12,
								padding: '0 8px',
								fontWeight: isCurrent ? 600 : 400,
							} }
						>
							{ p }
						</Button>
					);
				} ) }

				<Button
					variant="secondary"
					size="compact"
					disabled={ currentPage >= totalPages }
					onClick={ () => onPageChange( currentPage + 1 ) }
					style={ { minHeight: 30, fontSize: 12 } }
				>
					{ __( 'Next', 'wp-edit-password-protected' ) }
				</Button>
			</div>
		</div>
	);
};

export default Pagination;
