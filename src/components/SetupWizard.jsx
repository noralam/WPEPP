/**
 * SetupWizard — guided 5-step security setup applying safe server presets.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect, useCallback } from '@wordpress/element';
import { Modal, Button, Notice } from '@wordpress/components';
import { useNavigate } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import ScoreRing from './ScoreRing';

const STEP_ICONS = {
	logins: '🔐',
	shield: '🛡️',
	harden: '🧱',
	monitor: '📡',
	alerts: '🔔',
};

const STEP_DETAILS = {
	logins: __( 'Enables the login attempt limiter (5 tries / 15 min lockout), login + registration honeypots and registration rate limiting.', 'wp-edit-password-protected' ),
	shield: __( 'Blocks username discovery via ?author=N scans, oEmbed leaks and REST user enumeration; masks login errors.', 'wp-edit-password-protected' ),
	harden: __( 'Disables XML-RPC, hides the WordPress version and hides the login page from logged-out visitors.', 'wp-edit-password-protected' ),
	monitor: __( 'Turns on the AI crawler blocker and sets a 30-day login-log retention window.', 'wp-edit-password-protected' ),
	alerts: __( 'Sends security alerts (lockouts, critical findings) to your admin email address.', 'wp-edit-password-protected' ),
};

const SetupWizard = ( { onClose, onComplete } ) => {
	const navigate = useNavigate();
	const [ steps, setSteps ] = useState( [] );
	const [ current, setCurrent ] = useState( 0 );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ result, setResult ] = useState( null );

	useEffect( () => {
		apiFetch( { path: '/wpepp/v1/security/wizard-steps' } )
			.then( ( data ) => setSteps( Array.isArray( data ) ? data : [] ) )
			.catch( () => setSteps( [] ) );
	}, [] );

	const step = steps[ current ];
	const isLast = current >= steps.length - 1;

	const applyStep = useCallback( async () => {
		if ( ! step?.step ) {
			return;
		}

		setBusy( true );
		setError( '' );

		try {
			const res = await apiFetch( {
				path: '/wpepp/v1/security/apply-preset',
				method: 'POST',
				data: { step: step.step },
			} );

			setResult( res.summary ?? null );

			if ( isLast ) {
				onComplete?.( res.summary ?? null );
			} else {
				setCurrent( ( c ) => c + 1 );
			}
		} catch ( err ) {
			setError( err.message || __( 'Could not apply this step. Please try again.', 'wp-edit-password-protected' ) );
		}

		setBusy( false );
	}, [ step, isLast, onComplete ] );

	const skip = useCallback( () => {
		if ( isLast ) {
			onComplete?.( null );
		} else {
			setCurrent( ( c ) => c + 1 );
		}
	}, [ isLast, onComplete ] );

	return (
		<Modal
			title={ __( 'Security Setup Wizard', 'wp-edit-password-protected' ) }
			onRequestClose={ onClose }
			className="wpepp-wizard"
		>
			{ steps.length === 0 ? (
				<p>{ __( 'Loading…', 'wp-edit-password-protected' ) }</p>
			) : (
				<>
					<div className="wpepp-wizard__progress" aria-hidden="true">
						{ steps.map( ( s, i ) => (
							<span
								key={ s.step }
								className={ `wpepp-wizard__dot${ i < current ? ' is-done' : '' }${ i === current ? ' is-current' : '' }` }
							/>
						) ) }
					</div>

					{ ! result && (
						<>
							<div className="wpepp-wizard__step-head">
								<span className="wpepp-wizard__icon">{ STEP_ICONS[ step.step ] || '✨' }</span>
								<h3>{ step.label }</h3>
							</div>

							<p className="wpepp-wizard__desc">
								{ STEP_DETAILS[ step.step ] || '' }
							</p>

							<p className="wpepp-wizard__counter">
								{
									sprintf(
										/* translators: 1: current step number, 2: total steps */
										__( 'Step %1$d of %2$d', 'wp-edit-password-protected' ),
										current + 1,
										steps.length
									)
								}
							</p>

							{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }

							<div className="wpepp-wizard__actions">
								<Button variant="secondary" onClick={ skip }>
									{ __( 'Skip', 'wp-edit-password-protected' ) }
								</Button>
								<Button variant="primary" onClick={ applyStep } isBusy={ busy }>
									{ isLast
										? __( 'Apply & Finish', 'wp-edit-password-protected' )
										: __( 'Apply & Continue', 'wp-edit-password-protected' ) }
								</Button>
							</div>
						</>
					) }

					{ result && (
						<div className="wpepp-wizard__done">
							<h3>🎉 { __( 'Security Setup Complete!', 'wp-edit-password-protected' ) }</h3>
							<ScoreRing score={ result.score } grade={ result.grade } />
							<p className="wpepp-wizard__done-stat">
								{ result.passed }/{ result.total } { __( 'security checks passing', 'wp-edit-password-protected' ) }
							</p>
							<div className="wpepp-wizard__done-links">
								<p className="wpepp-wizard__done-links-label">
									{ __( 'Where would you like to go next?', 'wp-edit-password-protected' ) }
								</p>
								<div className="wpepp-wizard__done-buttons">
									<Button
										variant="secondary"
										onClick={ () => {
											onClose();
											navigate( '/' );
										} }
									>
										📊 { __( 'Dashboard', 'wp-edit-password-protected' ) }
									</Button>
									<Button
										variant="secondary"
										onClick={ () => {
											onClose();
											navigate( '/firewall' );
										} }
									>
										🛡️ { __( 'Firewall', 'wp-edit-password-protected' ) }
									</Button>
									<Button
										variant="secondary"
										onClick={ () => {
											onClose();
											navigate( '/activity' );
										} }
									>
										📋 { __( 'Activity Log', 'wp-edit-password-protected' ) }
									</Button>
								</div>
							</div>
							<div className="wpepp-wizard__done-footer">
								<Button variant="primary" onClick={ onClose }>
									{ __( 'Close & Finish', 'wp-edit-password-protected' ) }
								</Button>
							</div>
						</div>
					) }
				</>
			) }
		</Modal>
	);
};

export default SetupWizard;
