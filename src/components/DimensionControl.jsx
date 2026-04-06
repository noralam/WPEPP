/**
 * DimensionControl — Elementor-style linked/unlinked top/right/bottom/left inputs.
 */
import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { link as linkIcon, linkOff } from '@wordpress/icons';

const SIDES = [
	{ key: 'top', label: 'T' },
	{ key: 'right', label: 'R' },
	{ key: 'bottom', label: 'B' },
	{ key: 'left', label: 'L' },
];

const normalize = ( val ) => {
	if ( typeof val === 'object' && val !== null ) {
		return val;
	}
	const n = typeof val === 'number' ? val : 0;
	return { top: n, right: n, bottom: n, left: n };
};

const DimensionControl = ( { label, values, onChange, min = 0, max = 100, unit = 'px' } ) => {
	const norm = normalize( values );

	const [ linked, setLinked ] = useState( () => {
		return norm.top === norm.right && norm.right === norm.bottom && norm.bottom === norm.left;
	} );

	const current = {
		top: norm.top ?? 0,
		right: norm.right ?? 0,
		bottom: norm.bottom ?? 0,
		left: norm.left ?? 0,
	};

	const handleChange = ( side, raw ) => {
		const val = Math.max( min, Math.min( max, parseInt( raw, 10 ) || 0 ) );
		if ( linked ) {
			onChange( { top: val, right: val, bottom: val, left: val } );
		} else {
			onChange( { ...current, [ side ]: val } );
		}
	};

	return (
		<div className="wpepp-dimension-control">
			{ label && <span className="wpepp-dimension-control__label">{ label }</span> }
			<div className="wpepp-dimension-control__row">
				{ SIDES.map( ( { key, label: sideLabel } ) => (
					<div key={ key } className="wpepp-dimension-control__field">
						<input
							type="number"
							min={ min }
							max={ max }
							value={ current[ key ] }
							onChange={ ( e ) => handleChange( key, e.target.value ) }
						/>
						<span className="wpepp-dimension-control__side">{ sideLabel }</span>
					</div>
				) ) }
				<Button
					className={ `wpepp-dimension-control__link ${ linked ? 'is-linked' : '' }` }
					icon={ linked ? linkIcon : linkOff }
					label={ linked ? 'Unlink sides' : 'Link sides' }
					onClick={ () => {
						if ( ! linked ) {
							const val = current.top;
							onChange( { top: val, right: val, bottom: val, left: val } );
						}
						setLinked( ! linked );
					} }
					isSmall
				/>
			</div>
			<span className="wpepp-dimension-control__unit">{ unit }</span>
		</div>
	);
};

export default DimensionControl;
