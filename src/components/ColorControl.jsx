/**
 * ColorControl — a small color swatch that opens a ColorPicker in a Popover on click.
 */
import { useState, useRef } from '@wordpress/element';
import { ColorPicker, Popover } from '@wordpress/components';

const ColorControl = ( { label, color, onChange, enableAlpha = true } ) => {
	const [ isOpen, setIsOpen ] = useState( false );
	const buttonRef = useRef( null );

	return (
		<div className="wpepp-color-control">
			{ label && (
				<span className="wpepp-color-control__label">{ label }</span>
			) }
			<button
				ref={ buttonRef }
				type="button"
				className="wpepp-color-control__swatch"
				style={ { backgroundColor: color || '#000000' } }
				onClick={ () => setIsOpen( ! isOpen ) }
				aria-label={ label || 'Select color' }
			/>
			{ isOpen && (
				<Popover
					anchor={ buttonRef.current }
					onClose={ () => setIsOpen( false ) }
					placement="bottom-start"
				>
					<div className="wpepp-color-control__popover">
						<ColorPicker
							color={ color }
							onChange={ onChange }
							enableAlpha={ enableAlpha }
						/>
					</div>
				</Popover>
			) }
		</div>
	);
};

export default ColorControl;
