/**
 * Loading & Skeleton Shimmer Screen
 * Modeled after the shimmer loading screen in AI Marketing Expert.
 *
 * Supports variants: 'table', 'cards', 'form', 'dashboard', 'lines'
 */

import React from 'react';
import { __ } from '@wordpress/i18n';

const LINE_PRESETS = [
	{ height: 14, width: '46%' },
	{ height: 16, width: '86%' },
	{ height: 12, width: '96%' },
	{ height: 12, width: '62%' },
];

const TABLE_PRESETS = [ '72%', '90%', '55%', '80%', '64%' ];

/**
 * Base atomic Skeleton bar.
 */
export const Skeleton = ( { height = 14, width = '100%', className = '', style = {} } ) => (
	<span
		className={ `wpepp-skeleton ${ className }`.trim() }
		style={ { height, width, ...style } }
		aria-hidden="true"
	/>
);

/**
 * Main Loading component with multiple layouts.
 */
export const Loading = ( {
	text,
	variant = 'table', // 'table' | 'cards' | 'form' | 'dashboard' | 'lines'
	count,
	className = '',
} ) => {
	const c = count || ( variant === 'table' ? 6 : 3 );
	let content;

	if ( variant === 'table' ) {
		// Table variant — matches the user's uploaded image exactly
		content = (
			<div className="wpepp-loading__table">
				<div className="wpepp-loading__tr wpepp-loading__tr--head">
					{ TABLE_PRESETS.map( ( w, idx ) => (
						<Skeleton key={ idx } height={ 12 } width={ w } className="wpepp-skeleton--pill" />
					) ) }
				</div>
				{ Array.from( { length: c } ).map( ( _, rIdx ) => (
					<div key={ rIdx } className="wpepp-loading__tr">
						{ TABLE_PRESETS.map( ( _, cIdx ) => (
							<Skeleton
								key={ cIdx }
								height={ 13 }
								width={ TABLE_PRESETS[ ( cIdx + rIdx ) % TABLE_PRESETS.length ] }
								className="wpepp-skeleton--pill"
							/>
						) ) }
					</div>
				) ) }
			</div>
		);
	} else if ( variant === 'form' ) {
		// Form settings variant
		content = (
			<div className="wpepp-loading__form">
				{ Array.from( { length: c } ).map( ( _, rIdx ) => (
					<div key={ rIdx } className="wpepp-loading__field">
						<Skeleton height={ 12 } width="28%" className="wpepp-skeleton--pill" />
						<Skeleton height={ 38 } width="100%" className="wpepp-skeleton--control" />
					</div>
				) ) }
			</div>
		);
	} else if ( variant === 'dashboard' ) {
		// Dashboard variant with stat cards and chart placeholders
		content = (
			<>
				<div className="wpepp-loading__stats">
					{ Array.from( { length: 4 } ).map( ( _, idx ) => (
						<div key={ idx } className="wpepp-loading__stat">
							<Skeleton height={ 28 } width="58%" className="wpepp-skeleton--pill" />
							<Skeleton height={ 12 } width="76%" className="wpepp-skeleton--pill" />
						</div>
					) ) }
				</div>
				<div className="wpepp-loading__panels">
					{ Array.from( { length: count || 2 } ).map( ( _, idx ) => (
						<div key={ idx } className="wpepp-loading__card">
							<Skeleton height={ 15 } width="34%" className="wpepp-skeleton--pill" />
							<Skeleton height={ 180 } width="100%" className="wpepp-skeleton--plot" />
						</div>
					) ) }
				</div>
			</>
		);
	} else if ( variant === 'lines' ) {
		// Simple text lines
		content = (
			<div className="wpepp-loading__lines">
				{ LINE_PRESETS.slice( 0, count || LINE_PRESETS.length ).map( ( item, idx ) => (
					<Skeleton key={ idx } { ...item } className="wpepp-skeleton--pill" />
				) ) }
			</div>
		);
	} else {
		// 'cards' variant (default)
		content = (
			<div className="wpepp-loading__grid">
				{ Array.from( { length: c } ).map( ( _, rIdx ) => (
					<div key={ rIdx } className="wpepp-loading__card">
						{ LINE_PRESETS.map( ( item, idx ) => (
							<Skeleton key={ idx } { ...item } className="wpepp-skeleton--pill" />
						) ) }
					</div>
				) ) }
			</div>
		);
	}

	return (
		<div
			className={ `wpepp-loading wpepp-loading--${ variant } ${ className }`.trim() }
			role="status"
			aria-busy="true"
			aria-live="polite"
		>
			<span className="screen-reader-text">{ text || __( 'Loading…', 'wp-edit-password-protected' ) }</span>
			{ content }
		</div>
	);
};

// Aliases for convenience
export const TableSkeleton = ( { rows = 6, count, className } ) => (
	<Loading variant="table" count={ count || rows } className={ className } />
);

export const CardSkeleton = ( { count = 3, className } ) => (
	<Loading variant="cards" count={ count } className={ className } />
);

export const FormSkeleton = ( { count = 4, className } ) => (
	<Loading variant="form" count={ count } className={ className } />
);

export const PageSkeleton = ( { variant = 'table', count } ) => (
	<div style={ { padding: '4px 0 20px' } }>
		<div style={ { display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 18 } }>
			<Skeleton height={ 22 } width="220px" className="wpepp-skeleton--pill" />
			<Skeleton height={ 34 } width="110px" className="wpepp-skeleton--pill" />
		</div>
		<Loading variant={ variant } count={ count } />
	</div>
);

export default Loading;
