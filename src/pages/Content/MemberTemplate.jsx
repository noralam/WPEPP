/**
 * Member Template settings page.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	PanelBody,
	PanelRow,
	TextControl,
	TextareaControl,
	SelectControl,
	ToggleControl,
	Button,
} from '@wordpress/components';
import { useMemo, useCallback } from '@wordpress/element';
import { useSaveBar } from '../../components/SaveBar';
import { MEMBER_TEMPLATE_DEFAULTS } from '../../utils/defaults';
import ProBadge from '../../components/ProBadge';

const MemberTemplate = () => {
	const { settings, isSaving, isPro } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'member_template' ),
			isSaving: store.isSaving(),
			isPro: store.isPro(),
		};
	} );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );

	const s = useMemo( () => ( { ...MEMBER_TEMPLATE_DEFAULTS, ...settings } ), [ settings ] );

	const update = useCallback( ( key, value ) => {
		updateSetting( 'member_template', key, value );
	}, [ updateSetting ] );

	const handleSave = useCallback( () => {
		saveSettings( 'member_template', s );
	}, [ saveSettings, s ] );

	const handleReset = useCallback( () => {
		resetSettings( 'member_template' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	return (
		<div className="wpepp-member-template">
			<h3>{ __( 'Member-Only Template', 'wp-edit-password-protected' ) }</h3>
			<p>{ __( 'To use this template, edit any Page and select "Member Only (Login Required)" from the Template dropdown in the sidebar. Logged-out visitors will see the gate page below instead of the page content.', 'wp-edit-password-protected' ) }</p>

			<PanelBody title={ __( 'Gate Page Content', 'wp-edit-password-protected' ) } initialOpen>
				<PanelRow>
					<TextControl
						label={ __( 'Title', 'wp-edit-password-protected' ) }
						value={ s.infotitle || '' }
						onChange={ ( v ) => update( 'infotitle', v ) }
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Title Tag', 'wp-edit-password-protected' ) }
						value={ s.titletag || 'h2' }
						options={ [
							{ label: 'H1', value: 'h1' },
							{ label: 'H2', value: 'h2' },
							{ label: 'H3', value: 'h3' },
							{ label: 'H4', value: 'h4' },
						] }
						onChange={ ( v ) => update( 'titletag', v ) }
					/>
				</PanelRow>
				<PanelRow>
					<TextareaControl
						label={ __( 'Text', 'wp-edit-password-protected' ) }
						value={ s.text || '' }
						onChange={ ( v ) => update( 'text', v ) }
					/>
				</PanelRow>
				<PanelRow>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Text Alignment', 'wp-edit-password-protected' ) }
						value={ s.text_align || 'center' }
						options={ [
							{ label: __( 'Left', 'wp-edit-password-protected' ), value: 'left' },
							{ label: __( 'Center', 'wp-edit-password-protected' ), value: 'center' },
							{ label: __( 'Right', 'wp-edit-password-protected' ), value: 'right' },
						] }
						onChange={ ( v ) => update( 'text_align', v ) }
					/>
				</PanelRow>
			</PanelBody>

			<PanelBody title={ __( 'Login Options', 'wp-edit-password-protected' ) } initialOpen={ false }>
				<PanelRow>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={
							<>
								{ __( 'Mode', 'wp-edit-password-protected' ) }
								{ ! isPro && <ProBadge /> }
							</>
						}
						help={ ! isPro
							? __( 'The Popup (Glassdoor) option is available in the Pro version.', 'wp-edit-password-protected' )
							: undefined
						}
						value={ ! isPro && s.mode === 'popup' ? 'login' : ( s.mode || 'login' ) }
						options={ [
							{ label: __( 'Login', 'wp-edit-password-protected' ), value: 'login' },
							{ label: __( 'Popup (Glassdoor)', 'wp-edit-password-protected' ) + ( ! isPro ? ' (Pro)' : '' ), value: 'popup', disabled: ! isPro },
							{ label: __( 'Info Only', 'wp-edit-password-protected' ), value: 'info' },
						] }
						onChange={ ( v ) => {
							if ( ! isPro && v === 'popup' ) return;
							update( 'mode', v );
						} }
					/>
				</PanelRow>
				{ ( s.mode === 'login' || ( isPro && s.mode === 'popup' ) ) && (
					<>
						{ s.mode !== 'popup' && (
							<PanelRow>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Login Mode', 'wp-edit-password-protected' ) }
									value={ s.login_mode || 'form' }
									options={ [
										{ label: __( 'Login Form', 'wp-edit-password-protected' ), value: 'form' },
										{ label: __( 'Login Button', 'wp-edit-password-protected' ), value: 'button' },
									] }
									onChange={ ( v ) => update( 'login_mode', v ) }
								/>
							</PanelRow>
						) }
						{ s.login_mode === 'button' && s.mode !== 'popup' && (
							<PanelRow>
								<TextControl
									label={ __( 'Button Text', 'wp-edit-password-protected' ) }
									value={ s.btntext || 'Login' }
									onChange={ ( v ) => update( 'btntext', v ) }
								/>
							</PanelRow>
						) }
						{ ( ! s.login_mode || s.login_mode === 'form' || s.mode === 'popup' ) && (
							<>
								<PanelRow>
									<TextControl
										label={ __( 'Form Heading', 'wp-edit-password-protected' ) }
										value={ s.form_head || '' }
										onChange={ ( v ) => update( 'form_head', v ) }
									/>
								</PanelRow>
								<PanelRow>
									<TextControl
										label={ __( 'Username Label', 'wp-edit-password-protected' ) }
										value={ s.user_placeholder || '' }
										onChange={ ( v ) => update( 'user_placeholder', v ) }
									/>
								</PanelRow>
								<PanelRow>
									<TextControl
										label={ __( 'Password Label', 'wp-edit-password-protected' ) }
										value={ s.password_placeholder || '' }
										onChange={ ( v ) => update( 'password_placeholder', v ) }
									/>
								</PanelRow>
								<PanelRow>
									<ToggleControl
										__nextHasNoMarginBottom
										label={ __( 'Show Remember Me', 'wp-edit-password-protected' ) }
										checked={ s.form_remember !== 'off' }
										onChange={ ( v ) => update( 'form_remember', v ? 'on' : 'off' ) }
										__nextHasNoMarginBottom
									/>
								</PanelRow>
								<PanelRow>
									<TextControl
										label={ __( 'Submit Button Text', 'wp-edit-password-protected' ) }
										value={ s.formbtn_text || 'Login' }
										onChange={ ( v ) => update( 'formbtn_text', v ) }
									/>
								</PanelRow>
							</>
						) }
					</>
				) }
			</PanelBody>

		</div>
	);
};

export default MemberTemplate;
