/**
 * Templates page — gallery of preset styles with per-form tabs.
 */
import { __, sprintf } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { Button, Spinner, Card, CardBody } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { NavLink, Routes, Route, Navigate, useNavigate } from 'react-router-dom';
import apiFetch from '@wordpress/api-fetch';
import ProBadge from '../components/ProBadge';
import ProLock from '../components/ProLock';

/** Map tab key → section key sent to REST API. */
const TAB_SECTION_MAP = {
	login: 'login',
	register: 'register',
	'lost-password': 'lostpassword',
	password: 'password',
};

/** Map tab key → Form Style preview route. */
const TAB_PREVIEW_MAP = {
	login: '/form-style/login',
	register: '/form-style/register',
	'lost-password': '/form-style/lost-password',
	password: '/form-style/password',
};

const TemplateCard = ( { template, isPro, onApply, applyingId } ) => {
	const isLocked = ! template.isFree && ! isPro;
	const isBusy   = applyingId === template.id;

	return (
		<Card className={ `wpepp-template-card${ isLocked ? ' wpepp-template-locked' : '' }` }>
			<CardBody>
				<div className="wpepp-template-card__preview">
					{ template.preview && (
						<img src={ template.preview } alt={ template.name } loading="lazy" />
					) }
					{ isLocked && (
						<div className="wpepp-template-pro-overlay">
							<ProBadge />
						</div>
					) }
				</div>
				<h4>{ template.name }</h4>
				<Button
					variant="secondary"
					disabled={ isLocked || !! applyingId }
					onClick={ () => onApply( template.id ) }
					isBusy={ isBusy }
				>
					{ isLocked
						? __( 'Pro Required', 'wp-edit-password-protected' )
						: __( 'Apply Template', 'wp-edit-password-protected' )
					}
				</Button>
			</CardBody>
		</Card>
	);
};

/** Glassdoor loading overlay. */
const LoadingOverlay = () => (
	<div className="wpepp-template-overlay">
		<div className="wpepp-template-overlay__content">
			<Spinner />
			<p>{ __( 'Applying template…', 'wp-edit-password-protected' ) }</p>
		</div>
	</div>
);

/** Template grid for a specific form section tab. */
const TemplateGrid = ( { tabKey } ) => {
	const section  = TAB_SECTION_MAP[ tabKey ];
	const navigate = useNavigate();

	const { isPro, templates, isLoading } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			isPro: store.isPro(),
			templates: store.getTemplates(),
			isLoading: store.isLoading(),
		};
	} );

	const { applyTemplate } = useDispatch( 'wpepp/settings' );
	const [ applyingId, setApplyingId ] = useState( null );

	const handleApply = async ( templateId ) => {
		setApplyingId( templateId );
		try {
			await applyTemplate( templateId, section );
			// Small delay to ensure store subscribers are notified before navigating.
			await new Promise( ( r ) => setTimeout( r, 100 ) );
			navigate( TAB_PREVIEW_MAP[ tabKey ] );
		} catch ( e ) {
			// Error is already in the store via setError.
		}
		setApplyingId( null );
	};

	if ( isLoading || templates === null ) {
		return <Spinner />;
	}

	return (
		<>
			{ applyingId && <LoadingOverlay /> }
			<div className="wpepp-templates__grid">
				{ ( templates || [] ).map( ( template ) => (
					<TemplateCard
						key={ template.id }
						template={ template }
						isPro={ isPro }
						onApply={ handleApply }
						applyingId={ applyingId }
					/>
				) ) }
			</div>
		</>
	);
};

const Templates = () => {
	const isPro = useSelect( ( select ) => select( 'wpepp/settings' ).isPro() );

	const handleExport = async () => {
		try {
			const data = await apiFetch( { path: '/wpepp/v1/templates/export' } );
			const json = JSON.stringify( data, null, 2 );
			const blob = new Blob( [ json ], { type: 'application/json' } );
			const url  = URL.createObjectURL( blob );
			const a    = document.createElement( 'a' );
			a.href     = url;
			a.download = 'wpepp-settings-export.json';
			a.click();
			URL.revokeObjectURL( url );
		} catch ( err ) {
			// eslint-disable-next-line no-console
			console.error( 'Export failed:', err );
		}
	};

	const { importSettings } = useDispatch( 'wpepp/settings' );
	const handleImport = () => {
		const input  = document.createElement( 'input' );
		input.type   = 'file';
		input.accept = '.json';
		input.onchange = ( e ) => {
			const file = e.target.files[ 0 ];
			if ( ! file ) return;
			const reader = new FileReader();
			reader.onload = ( ev ) => {
				importSettings( ev.target.result );
			};
			reader.readAsText( file );
		};
		input.click();
	};

	return (
		<div className="wpepp-templates">
			<div className="wpepp-templates__header">
				<h2>{ __( 'Templates', 'wp-edit-password-protected' ) }</h2>
				<div className="wpepp-templates__actions">
					{ isPro ? (
						<>
							<Button variant="secondary" onClick={ handleExport }>
								{ __( 'Export', 'wp-edit-password-protected' ) }
							</Button>
							<Button variant="secondary" onClick={ handleImport }>
								{ __( 'Import', 'wp-edit-password-protected' ) }
							</Button>
						</>
					) : (
						<>
							<span className="wpepp-templates__pro-note">
								{ __( 'Import / Export is available in the Pro version.', 'wp-edit-password-protected' ) }
							</span>
							<a
								href={ window.wpeppData?.proUrl || '#' }
								className="components-button is-primary"
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'Upgrade to Pro', 'wp-edit-password-protected' ) }
							</a>
						</>
					) }
				</div>
			</div>

			<nav className="wpepp-inner-tabs">
				<NavLink to="/templates/login" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Login Form', 'wp-edit-password-protected' ) }
				</NavLink>
				<NavLink to="/templates/register" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Register Form', 'wp-edit-password-protected' ) }
					{ ! isPro && <ProBadge /> }
				</NavLink>
				<NavLink to="/templates/lost-password" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Lost Password', 'wp-edit-password-protected' ) }
					{ ! isPro && <ProBadge /> }
				</NavLink>
				<NavLink to="/templates/password" className={ ( { isActive } ) => isActive ? 'is-active' : '' }>
					{ __( 'Password Protected Form', 'wp-edit-password-protected' ) }
				</NavLink>
			</nav>

			<Routes>
				<Route index element={ <Navigate to="login" replace /> } />
				<Route path="login" element={ <TemplateGrid tabKey="login" /> } />
				<Route
					path="register"
					element={
						<ProLock isPro={ isPro } featureName={ __( 'Register Form Templates', 'wp-edit-password-protected' ) }>
							<TemplateGrid tabKey="register" />
						</ProLock>
					}
				/>
				<Route
					path="lost-password"
					element={
						<ProLock isPro={ isPro } featureName={ __( 'Lost Password Templates', 'wp-edit-password-protected' ) }>
							<TemplateGrid tabKey="lost-password" />
						</ProLock>
					}
				/>
				<Route path="password" element={ <TemplateGrid tabKey="password" /> } />
			</Routes>
		</div>
	);
};

export default Templates;
