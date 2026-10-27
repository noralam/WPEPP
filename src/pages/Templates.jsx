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
import Loading from '../components/Loading';

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

const TEMPLATE_PRESETS = {
	corporate: {
		bg: '#f1f5f9',
		cardBg: '#ffffff',
		cardBorder: '1px solid #e2e8f0',
		cardRadius: '6px',
		accent: '#0066cc',
		textColor: '#1e293b',
		inputBg: '#f8fafc',
		inputBorder: '1px solid #cbd5e1',
		btnBg: '#0066cc',
		btnTextColor: '#ffffff',
		btnRadius: '4px',
	},
	creative: {
		bg: 'linear-gradient(135deg, #ff6b6b 0%, #feca57 50%, #48dbfb 100%)',
		cardBg: 'rgba(255, 255, 255, 0.94)',
		cardBorder: '1px solid rgba(255, 255, 255, 0.6)',
		cardRadius: '16px',
		accent: '#ff6b6b',
		textColor: '#334155',
		inputBg: '#f8fafc',
		inputBorder: '1px solid #fecdd3',
		btnBg: '#ff6b6b',
		btnTextColor: '#ffffff',
		btnRadius: '12px',
	},
	elegant: {
		bg: '#141414',
		cardBg: '#222222',
		cardBorder: '1px solid #333333',
		cardRadius: '4px',
		accent: '#c9a96e',
		textColor: '#f5f5f5',
		inputBg: '#1a1a1a',
		inputBorder: '1px solid #3d3d3d',
		btnBg: '#c9a96e',
		btnTextColor: '#141414',
		btnRadius: '2px',
	},
	'gradient-wave': {
		bg: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
		cardBg: 'rgba(255, 255, 255, 0.95)',
		cardBorder: '1px solid rgba(255, 255, 255, 0.5)',
		cardRadius: '12px',
		accent: '#667eea',
		textColor: '#334155',
		inputBg: '#f8fafc',
		inputBorder: '1px solid #e2e8f0',
		btnBg: '#667eea',
		btnTextColor: '#ffffff',
		btnRadius: '8px',
	},
	minimal: {
		bg: '#fafafa',
		cardBg: '#ffffff',
		cardBorder: '1px solid #e5e7eb',
		cardRadius: '0px',
		accent: '#0f172a',
		textColor: '#0f172a',
		inputBg: '#ffffff',
		inputBorder: '1px solid #d1d5db',
		btnBg: '#0f172a',
		btnTextColor: '#ffffff',
		btnRadius: '0px',
	},
	'modern-dark': {
		bg: '#0f172a',
		cardBg: '#1e293b',
		cardBorder: '1px solid #334155',
		cardRadius: '10px',
		accent: '#38bdf8',
		textColor: '#f8fafc',
		inputBg: '#0f172a',
		inputBorder: '1px solid #334155',
		btnBg: '#2563eb',
		btnTextColor: '#ffffff',
		btnRadius: '6px',
	},
	nature: {
		bg: 'linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%)',
		cardBg: '#ffffff',
		cardBorder: '1px solid #c8e6c9',
		cardRadius: '16px',
		accent: '#2e7d32',
		textColor: '#1b5e20',
		inputBg: '#f1f8e9',
		inputBorder: '1px solid #c5e1a5',
		btnBg: '#43a047',
		btnTextColor: '#ffffff',
		btnRadius: '10px',
	},
	starter: {
		bg: '#f0f0f1',
		cardBg: '#ffffff',
		cardBorder: '1px solid #c3c4c7',
		cardRadius: '6px',
		accent: '#2271b1',
		textColor: '#2c3338',
		inputBg: '#ffffff',
		inputBorder: '1px solid #8c8f94',
		btnBg: '#2271b1',
		btnTextColor: '#ffffff',
		btnRadius: '4px',
	},
	sunset: {
		bg: 'linear-gradient(135deg, #ff9a56 0%, #ff6f61 50%, #d32f2f 100%)',
		cardBg: 'rgba(255, 255, 255, 0.95)',
		cardBorder: '1px solid rgba(255, 255, 255, 0.5)',
		cardRadius: '12px',
		accent: '#ff6f61',
		textColor: '#334155',
		inputBg: '#fff7ed',
		inputBorder: '1px solid #fed7aa',
		btnBg: '#e64a19',
		btnTextColor: '#ffffff',
		btnRadius: '8px',
	},
	'tech-blue': {
		bg: 'linear-gradient(135deg, #0284c7 0%, #0369a1 100%)',
		cardBg: '#ffffff',
		cardBorder: '1px solid #bae6fd',
		cardRadius: '8px',
		accent: '#0284c7',
		textColor: '#0f172a',
		inputBg: '#f0f9ff',
		inputBorder: '1px solid #7dd3fc',
		btnBg: '#0284c7',
		btnTextColor: '#ffffff',
		btnRadius: '6px',
	},
};

const TemplateMockup = ( { id } ) => {
	const preset = TEMPLATE_PRESETS[ id ] || TEMPLATE_PRESETS.starter;

	return (
		<div
			className="wpepp-template-mockup"
			style={ {
				width: '100%',
				height: '100%',
				background: preset.bg,
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				padding: '14px',
				boxSizing: 'border-box',
			} }
		>
			<div
				className="wpepp-template-mockup__form"
				style={ {
					width: '136px',
					background: preset.cardBg,
					border: preset.cardBorder,
					borderRadius: preset.cardRadius,
					boxShadow: '0 6px 18px rgba(0, 0, 0, 0.16)',
					padding: '12px 10px 10px',
					display: 'flex',
					flexDirection: 'column',
					alignItems: 'center',
					boxSizing: 'border-box',
					transition: 'transform 0.25s ease',
				} }
			>
				{ /* Brand / Logo icon */ }
				<div
					style={ {
						width: '18px',
						height: '18px',
						borderRadius: '50%',
						background: preset.accent,
						marginBottom: '6px',
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
					} }
				>
					<div style={ { width: '6px', height: '6px', borderRadius: '50%', background: preset.btnTextColor, opacity: 0.8 } } />
				</div>
				{ /* Title line */ }
				<div
					style={ {
						width: '46px',
						height: '4px',
						borderRadius: '2px',
						background: preset.textColor,
						marginBottom: '8px',
						opacity: 0.75,
					} }
				/>
				{ /* Username Input skeleton */ }
				<div
					style={ {
						width: '100%',
						height: '10px',
						borderRadius: '3px',
						background: preset.inputBg,
						border: preset.inputBorder,
						marginBottom: '5px',
						boxSizing: 'border-box',
					} }
				/>
				{ /* Password Input skeleton */ }
				<div
					style={ {
						width: '100%',
						height: '10px',
						borderRadius: '3px',
						background: preset.inputBg,
						border: preset.inputBorder,
						marginBottom: '8px',
						boxSizing: 'border-box',
					} }
				/>
				{ /* Action Button */ }
				<div
					style={ {
						width: '100%',
						height: '14px',
						borderRadius: preset.btnRadius,
						background: preset.btnBg,
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'center',
						boxSizing: 'border-box',
					} }
				>
					<div
						style={ {
							width: '32px',
							height: '3px',
							borderRadius: '1.5px',
							background: preset.btnTextColor,
							opacity: 0.9,
						} }
					/>
				</div>
			</div>
		</div>
	);
};

const TemplateCard = ( { template, isPro, onApply, applyingId } ) => {
	const isLocked = ! template.isFree && ! isPro;
	const isBusy   = applyingId === template.id;

	return (
		<Card className={ `wpepp-template-card${ isLocked ? ' wpepp-template-locked' : '' }` }>
			<CardBody>
				<div className="wpepp-template-card__preview">
					{ template.preview ? (
						<img src={ template.preview } alt={ template.name } loading="lazy" />
					) : (
						<TemplateMockup id={ template.id } />
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
		return <Loading variant="cards" count={ 6 } />;
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
