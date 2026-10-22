/**
 * Card — flat settings section (no accordion). Title + optional subtitle and
 * action on the header row, always-visible body below.
 */
import { __ } from '@wordpress/i18n';

const Card = ( { title, subtitle, action, children, className = '', padded = true } ) => (
	<section className={ `wpepp-card-sec ${ className }` }>
		{ ( title || action ) && (
			<header className="wpepp-card-sec__header">
				<div className="wpepp-card-sec__heading">
					{ title && <h4 className="wpepp-card-sec__title">{ title }</h4> }
					{ subtitle && <p className="wpepp-card-sec__subtitle">{ subtitle }</p> }
				</div>
				{ action && <div className="wpepp-card-sec__action">{ action }</div> }
			</header>
		) }
		<div className={ `wpepp-card-sec__body${ padded ? '' : ' is-flush' }` }>
			{ children }
		</div>
	</section>
);

export default Card;
