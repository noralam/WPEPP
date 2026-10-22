/**
 * ToggleCard — bordered card with title + description on the left and a
 * toggle switch on the right (mockup grammar). Used inside .wpepp-toggle-grid.
 */
import { ToggleControl } from '@wordpress/components';

const ToggleCard = ( { title, description, checked, onChange, disabled, help } ) => (
	<div className={ `wpepp-toggle-card${ disabled ? ' is-disabled' : '' }` }>
		<div className="wpepp-toggle-card__text">
			<span className="wpepp-toggle-card__title">{ title }</span>
			{ description && <span className="wpepp-toggle-card__desc">{ description }</span> }
			{ help && <span className="wpepp-toggle-card__help">{ help }</span> }
		</div>
		<ToggleControl
			__nextHasNoMarginBottom
			checked={ !! checked }
			disabled={ !! disabled }
			onChange={ onChange }
			label=""
		/>
	</div>
);

export default ToggleCard;
