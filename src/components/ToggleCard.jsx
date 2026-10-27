/**
 * ToggleCard — bordered card with title + description on the left and a
 * toggle switch on the right (mockup grammar). Used inside .wpepp-toggle-grid.
 */
import { ToggleControl } from '@wordpress/components';

const ToggleCard = ( { title, description, checked, onChange, disabled, help, badge } ) => (
	<div className={ `wpepp-toggle-card${ disabled ? ' is-disabled' : '' }` }>
		<div className="wpepp-toggle-card__text">
			<div style={ { display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' } }>
				<span className="wpepp-toggle-card__title">{ title }</span>
				{ badge }
			</div>
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
