/**
 * ScoreRing — SVG donut showing the security score with letter grade.
 */
import { __ } from '@wordpress/i18n';

const RADIUS = 54;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

const GRADE_COLORS = {
	A: '#10B981',
	B: '#84CC16',
	C: '#F5A524',
	D: '#FB923C',
	F: '#FB4B6B',
};

const ScoreRing = ( { score = 0, grade = 'F', size = 150 } ) => {
	const clamped = Math.max( 0, Math.min( 100, Number( score ) || 0 ) );
	const offset = CIRCUMFERENCE * ( 1 - clamped / 100 );
	const color = GRADE_COLORS[ grade ] || GRADE_COLORS.F;

	return (
		<div className="wpepp-score-ring" role="img" aria-label={ `${ grade }: ${ clamped } / 100` }>
			<svg width={ size } height={ size } viewBox="0 0 140 140">
				<circle
					cx="70"
					cy="70"
					r={ RADIUS }
					fill="none"
					strokeWidth="12"
					style={ { stroke: 'var(--wpepp-ring-track, rgba(120,120,120,0.2))' } }
				/>
				<circle
					cx="70"
					cy="70"
					r={ RADIUS }
					fill="none"
					stroke={ color }
					strokeWidth="12"
					strokeLinecap="round"
					strokeDasharray={ CIRCUMFERENCE }
					strokeDashoffset={ offset }
					transform="rotate(-90 70 70)"
					style={ { transition: 'stroke-dashoffset .8s ease' } }
				/>
				<text x="70" y="64" textAnchor="middle" className="wpepp-score-ring__value">
					{ clamped }
				</text>
				<text x="70" y="86" textAnchor="middle" className="wpepp-score-ring__grade" fill={ color }>
					{ grade }
				</text>
			</svg>
			<span className="wpepp-score-ring__caption">{ __( 'Security Score', 'wp-edit-password-protected' ) }</span>
		</div>
	);
};

export default ScoreRing;
