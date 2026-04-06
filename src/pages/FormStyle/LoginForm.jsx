/**
 * Login Form editor — live preview + style controls.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	PanelBody,
	PanelRow,
	SelectControl,
	RangeControl,
	ToggleControl,
	TextControl,
	TextareaControl,
	Button,
	GradientPicker,
} from '@wordpress/components';
import { useState, useCallback, useMemo } from '@wordpress/element';
import LivePreview from '../../components/LivePreview';
import ColorControl from '../../components/ColorControl';
import DimensionControl from '../../components/DimensionControl';
import ProBadge from '../../components/ProBadge';
import { useSaveBar } from '../../components/SaveBar';
import { generateLoginCss } from '../../utils/css-generator';
import { LOGIN_DEFAULTS, mergeDefaults } from '../../utils/defaults';
import { parseVideoUrl } from '../../utils/video';

const LoginForm = () => {
	const { settings, isLoading, isSaving, isPro } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'login' ),
			isLoading: store.isLoading(),
			isSaving: store.isSaving(),
			isPro: store.isPro(),
		};
	} );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );
	const [ previewUrl ] = useState( () => {
		const base = window.wpeppData?.adminUrl || '/wp-admin/';
		return `${ base }admin-ajax.php?action=wpepp_preview&type=login&_wpnonce=${ window.wpeppData?.previewNonce || '' }`;
	} );

	const s = useMemo( () => mergeDefaults( LOGIN_DEFAULTS, settings ), [ settings ] );

	const update = useCallback( ( key, value ) => {
		updateSetting( 'login', key, value );
	}, [ updateSetting ] );

	const css = useMemo( () => generateLoginCss( s ), [ s ] );

	const handleSave = useCallback( () => {
		saveSettings( 'login', s );
	}, [ saveSettings, s ] );

	const handleReset = useCallback( () => {
		resetSettings( 'login' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	// Video preview state — auto-parse when URL or background type changes.
	const bgTypeRef = s.page?.background_type || 'color';
	const videoUrl = s.page?.background_video || '';
	const activeVideo = useMemo( () => {
		if ( bgTypeRef !== 'video' || ! videoUrl ) {
			return null;
		}
		return parseVideoUrl( videoUrl );
	}, [ bgTypeRef, videoUrl ] );

	const openMediaUploader = ( settingKey ) => {
		const frame = wp.media( {
			title: __( 'Select Image', 'wp-edit-password-protected' ),
			button: { text: __( 'Use this image', 'wp-edit-password-protected' ) },
			multiple: false,
			library: { type: 'image' },
		} );
		frame.on( 'select', () => {
			const attachment = frame.state().get( 'selection' ).first().toJSON();
			update( settingKey, attachment.url );
		} );
		frame.open();
	};

	if ( isLoading ) {
		return <p>{ __( 'Loading…', 'wp-edit-password-protected' ) }</p>;
	}

	const bgType = s.page?.background_type || 'color';

	return (
		<div className="wpepp-editor-layout">
			<div className="wpepp-editor-controls">
				{ /* ── Page Background ── */ }
				<PanelBody title={ __( 'Page Background', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Type', 'wp-edit-password-protected' ) }
							value={ bgType }
							options={ [
								{ label: __( 'Color', 'wp-edit-password-protected' ), value: 'color' },
								{ label: __( 'Image', 'wp-edit-password-protected' ), value: 'image' },
								{ label: __( 'Gradient (Pro)', 'wp-edit-password-protected' ), value: 'gradient' },
								{ label: __( 'Video (Pro)', 'wp-edit-password-protected' ), value: 'video' },
							] }
							onChange={ ( v ) => {
								update( 'page.background_type', v );
							} }
						/>
					</PanelRow>

					{ bgType === 'color' && (
						<PanelRow>
							<ColorControl
								label={ __( 'Background Color', 'wp-edit-password-protected' ) }
								color={ s.page?.background_color || '#f0f0f1' }
								onChange={ ( v ) => update( 'page.background_color', v ) }
							/>
						</PanelRow>
					) }

					{ bgType === 'image' && (
						<>
							<PanelRow>
								<div className="wpepp-media-upload">
									{ s.page?.background_image && (
										<img src={ s.page.background_image } alt="" className="wpepp-media-upload__preview" />
									) }
									<div className="wpepp-media-upload__buttons">
										<Button variant="secondary" onClick={ () => openMediaUploader( 'page.background_image' ) }>
											{ s.page?.background_image ? __( 'Change Image', 'wp-edit-password-protected' ) : __( 'Upload Image', 'wp-edit-password-protected' ) }
										</Button>
										{ s.page?.background_image && (
											<Button variant="tertiary" isDestructive onClick={ () => update( 'page.background_image', '' ) }>
												{ __( 'Remove', 'wp-edit-password-protected' ) }
											</Button>
										) }
									</div>
								</div>
							</PanelRow>
							<PanelRow>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Position', 'wp-edit-password-protected' ) }
									value={ s.page?.background_position || 'center center' }
									options={ [
										{ label: __( 'Center', 'wp-edit-password-protected' ), value: 'center center' },
										{ label: __( 'Top', 'wp-edit-password-protected' ), value: 'center top' },
										{ label: __( 'Bottom', 'wp-edit-password-protected' ), value: 'center bottom' },
										{ label: __( 'Left', 'wp-edit-password-protected' ), value: 'left center' },
										{ label: __( 'Right', 'wp-edit-password-protected' ), value: 'right center' },
									] }
									onChange={ ( v ) => update( 'page.background_position', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Size', 'wp-edit-password-protected' ) }
									value={ s.page?.background_size || 'cover' }
									options={ [
										{ label: __( 'Cover', 'wp-edit-password-protected' ), value: 'cover' },
										{ label: __( 'Contain', 'wp-edit-password-protected' ), value: 'contain' },
										{ label: __( 'Auto', 'wp-edit-password-protected' ), value: 'auto' },
									] }
									onChange={ ( v ) => update( 'page.background_size', v ) }
								/>
							</PanelRow>
						</>
					) }

					{ bgType === 'gradient' && isPro && (
						<PanelRow>
							<div style={ { width: '100%' } }>
								<GradientPicker
									value={ s.page?.background_gradient || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }
									onChange={ ( v ) => update( 'page.background_gradient', v ) }
									clearable={ false }
									__experimentalIsRenderedInSidebar
								/>
							</div>
						</PanelRow>
					) }
					{ bgType === 'gradient' && ! isPro && (
						<PanelRow>
							<p className="wpepp-pro-notice">
								<ProBadge /> { __( 'Gradient background is a Pro feature.', 'wp-edit-password-protected' ) }
								<a href={ window.wpeppData?.proUrl || '#' } target="_blank" rel="noopener noreferrer">{ __( 'Upgrade', 'wp-edit-password-protected' ) }</a>
							</p>
						</PanelRow>
					) }

					{ bgType === 'video' && isPro && (
						<>
							<PanelRow>
								<TextControl
									label={ __( 'Video URL (MP4, YouTube, or Vimeo)', 'wp-edit-password-protected' ) }
									value={ s.page?.background_video || '' }
									onChange={ ( v ) => update( 'page.background_video', v ) }
									placeholder="https://www.youtube.com/watch?v=xxxxx"
									help={ __( 'Supports MP4, YouTube, and Vimeo URLs.', 'wp-edit-password-protected' ) }
								/>
							</PanelRow>
							<PanelRow>
								<ColorControl
									label={ __( 'Fallback Color', 'wp-edit-password-protected' ) }
									color={ s.page?.background_color || '#f0f0f1' }
									onChange={ ( v ) => update( 'page.background_color', v ) }
								/>
							</PanelRow>
						</>
					) }
					{ bgType === 'video' && ! isPro && (
						<PanelRow>
							<p className="wpepp-pro-notice">
								<ProBadge /> { __( 'Video background is a Pro feature.', 'wp-edit-password-protected' ) }
								<a href={ window.wpeppData?.proUrl || '#' } target="_blank" rel="noopener noreferrer">{ __( 'Upgrade', 'wp-edit-password-protected' ) }</a>
							</p>
						</PanelRow>
					) }
				</PanelBody>

				{ /* ── Logo ── */ }
				<PanelBody title={ __( 'Logo', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Logo Type', 'wp-edit-password-protected' ) }
							value={ s.logo?.type || 'default' }
							options={ [
								{ label: __( 'Default', 'wp-edit-password-protected' ), value: 'default' },
								{ label: __( 'Custom Image', 'wp-edit-password-protected' ), value: 'custom' },
								{ label: __( 'Text', 'wp-edit-password-protected' ), value: 'text' },
								{ label: __( 'Hide', 'wp-edit-password-protected' ), value: 'hide' },
							] }
							onChange={ ( v ) => update( 'logo.type', v ) }
						/>
					</PanelRow>
					{ s.logo?.type === 'custom' && (
						<>
							<PanelRow>
								<div className="wpepp-media-upload">
									{ s.logo?.image && (
										<img src={ s.logo.image } alt="" className="wpepp-media-upload__preview" />
									) }
									<div className="wpepp-media-upload__buttons">
										<Button variant="secondary" onClick={ () => openMediaUploader( 'logo.image' ) }>
											{ s.logo?.image ? __( 'Change Image', 'wp-edit-password-protected' ) : __( 'Upload Image', 'wp-edit-password-protected' ) }
										</Button>
										{ s.logo?.image && (
											<Button variant="tertiary" isDestructive onClick={ () => update( 'logo.image', '' ) }>
												{ __( 'Remove', 'wp-edit-password-protected' ) }
											</Button>
										) }
									</div>
								</div>
							</PanelRow>
							<PanelRow>
								<RangeControl
									label={ __( 'Width (px)', 'wp-edit-password-protected' ) }
									value={ s.logo?.width || 84 }
									onChange={ ( v ) => update( 'logo.width', v ) }
									min={ 20 }
									max={ 400 }
								/>
							</PanelRow>
							<PanelRow>
								<RangeControl
									label={ __( 'Height (px)', 'wp-edit-password-protected' ) }
									value={ s.logo?.height || 84 }
									onChange={ ( v ) => update( 'logo.height', v ) }
									min={ 20 }
									max={ 400 }
								/>
							</PanelRow>
						</>
					) }
					{ s.logo?.type === 'text' && (
						<>
							<PanelRow>
								<TextControl
									label={ __( 'Logo Text', 'wp-edit-password-protected' ) }
									value={ s.logo?.text || '' }
									onChange={ ( v ) => update( 'logo.text', v ) }
									placeholder={ __( 'Enter logo text', 'wp-edit-password-protected' ) }
								/>
							</PanelRow>
							<PanelRow>
								<RangeControl
									label={ __( 'Font Size (px)', 'wp-edit-password-protected' ) }
									value={ s.logo?.text_font_size || 24 }
									onChange={ ( v ) => update( 'logo.text_font_size', v ) }
									min={ 12 }
									max={ 72 }
								/>
							</PanelRow>
							<PanelRow>
								<ColorControl
									label={ __( 'Text Color', 'wp-edit-password-protected' ) }
									color={ s.logo?.text_color || '#333333' }
									onChange={ ( v ) => update( 'logo.text_color', v ) }
								/>
							</PanelRow>
						</>
					) }
					{ s.logo?.type !== 'hide' && (
						<>
							<PanelRow>
								<TextControl
									label={ __( 'Logo URL', 'wp-edit-password-protected' ) }
									value={ s.logo?.url || '' }
									onChange={ ( v ) => update( 'logo.url', v ) }
									placeholder={ window.wpeppData?.homeUrl || '' }
									help={ __( 'URL the logo links to. Leave empty to use your site home URL.', 'wp-edit-password-protected' ) }
									type="url"
								/>
							</PanelRow>
							<PanelRow>
								<ToggleControl
									label={ __( 'Background Overlay', 'wp-edit-password-protected' ) }
									checked={ !! s.logo?.show_background }
									onChange={ ( v ) => update( 'logo.show_background', v ) }
									help={ __( 'Adds a background behind the logo for better visibility on images/videos.', 'wp-edit-password-protected' ) }
								/>
							</PanelRow>
							{ s.logo?.show_background && (
								<>
									<PanelRow>
										<ColorControl
											label={ __( 'Background Color', 'wp-edit-password-protected' ) }
											color={ s.logo?.background_color || 'rgba(0,0,0,0.45)' }
											onChange={ ( v ) => update( 'logo.background_color', v ) }
										/>
									</PanelRow>
									<PanelRow>
										<DimensionControl
											label={ __( 'Padding', 'wp-edit-password-protected' ) }
											values={ s.logo?.padding }
											onChange={ ( v ) => update( 'logo.padding', v ) }
											min={ 0 }
											max={ 60 }
										/>
									</PanelRow>
									<PanelRow>
										<DimensionControl
											label={ __( 'Border Radius', 'wp-edit-password-protected' ) }
											values={ s.logo?.border_radius }
											onChange={ ( v ) => update( 'logo.border_radius', v ) }
											min={ 0 }
											max={ 50 }
										/>
									</PanelRow>
								</>
							) }
						</>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Form Container', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<RangeControl
							label={ __( 'Width (px)', 'wp-edit-password-protected' ) }
							value={ s.form?.width || 320 }
							onChange={ ( v ) => update( 'form.width', v ) }
							min={ 200 }
							max={ 600 }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Border Radius', 'wp-edit-password-protected' ) }
							values={ s.form?.border_radius }
							onChange={ ( v ) => update( 'form.border_radius', v ) }
							min={ 0 }
							max={ 50 }
						/>
					</PanelRow>
					<PanelRow>
						<ColorControl
							label={ __( 'Background Color', 'wp-edit-password-protected' ) }
							color={ s.form?.background_color || '#ffffff' }
							onChange={ ( v ) => update( 'form.background_color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Padding', 'wp-edit-password-protected' ) }
							values={ s.form?.padding }
							onChange={ ( v ) => update( 'form.padding', v ) }
							min={ 0 }
							max={ 60 }
						/>
					</PanelRow>
					<PanelRow>
						<ColorControl
							label={ __( 'Border Color', 'wp-edit-password-protected' ) }
							color={ s.form?.border_color || '#c3c4c7' }
							onChange={ ( v ) => update( 'form.border_color', v ) }
						/>
					</PanelRow>
				</PanelBody>

				{ /* ── Heading ── */ }
				<PanelBody title={ __( 'Heading', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Heading', 'wp-edit-password-protected' ) }
							checked={ !! s.heading?.show }
							onChange={ ( v ) => update( 'heading.show', v ) }
						/>
					</PanelRow>
					{ s.heading?.show && (
						<>
							<PanelRow>
								<TextControl
									label={ __( 'Heading Text', 'wp-edit-password-protected' ) }
									value={ s.heading?.text || '' }
									onChange={ ( v ) => update( 'heading.text', v ) }
									placeholder={ __( 'Enter heading text', 'wp-edit-password-protected' ) }
								/>
							</PanelRow>
							<PanelRow>
								<ColorControl
									label={ __( 'Heading Color', 'wp-edit-password-protected' ) }
									color={ s.heading?.color || '#333333' }
									onChange={ ( v ) => update( 'heading.color', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<RangeControl
									label={ __( 'Font Size (px)', 'wp-edit-password-protected' ) }
									value={ s.heading?.font_size || 20 }
									onChange={ ( v ) => update( 'heading.font_size', v ) }
									min={ 12 }
									max={ 48 }
								/>
							</PanelRow>
							<PanelRow>
								<ToggleControl
									label={ __( 'Background Overlay', 'wp-edit-password-protected' ) }
									checked={ !! s.heading?.show_background }
									onChange={ ( v ) => update( 'heading.show_background', v ) }
									help={ __( 'Adds a background behind the heading for better visibility.', 'wp-edit-password-protected' ) }
								/>
							</PanelRow>
							{ s.heading?.show_background && (
								<>
									<PanelRow>
										<ColorControl
											label={ __( 'Background Color', 'wp-edit-password-protected' ) }
											color={ s.heading?.background_color || 'rgba(0,0,0,0.45)' }
											onChange={ ( v ) => update( 'heading.background_color', v ) }
										/>
									</PanelRow>
									<PanelRow>
										<DimensionControl
											label={ __( 'Padding', 'wp-edit-password-protected' ) }
											values={ s.heading?.padding }
											onChange={ ( v ) => update( 'heading.padding', v ) }
											min={ 0 }
											max={ 60 }
										/>
									</PanelRow>
									<PanelRow>
										<DimensionControl
											label={ __( 'Border Radius', 'wp-edit-password-protected' ) }
											values={ s.heading?.border_radius }
											onChange={ ( v ) => update( 'heading.border_radius', v ) }
											min={ 0 }
											max={ 50 }
										/>
									</PanelRow>
								</>
							) }
						</>
					) }
				</PanelBody>

				{ /* ── Labels ── */ }
				<PanelBody title={ __( 'Labels', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ColorControl
							label={ __( 'Label Color', 'wp-edit-password-protected' ) }
							color={ s.labels?.color || '#1e1e1e' }
							onChange={ ( v ) => update( 'labels.color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<RangeControl
							label={ __( 'Font Size (px)', 'wp-edit-password-protected' ) }
							value={ s.labels?.font_size || 14 }
							onChange={ ( v ) => update( 'labels.font_size', v ) }
							min={ 10 }
							max={ 24 }
						/>
					</PanelRow>
				</PanelBody>

				{ /* ── Input Fields ── */ }
				<PanelBody title={ __( 'Input Fields', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ColorControl
							label={ __( 'Background Color', 'wp-edit-password-protected' ) }
							color={ s.fields?.background_color || '#ffffff' }
							onChange={ ( v ) => update( 'fields.background_color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<ColorControl
							label={ __( 'Text Color', 'wp-edit-password-protected' ) }
							color={ s.fields?.text_color || '#1e1e1e' }
							onChange={ ( v ) => update( 'fields.text_color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<ColorControl
							label={ __( 'Border Color', 'wp-edit-password-protected' ) }
							color={ s.fields?.border_color || '#8c8f94' }
							onChange={ ( v ) => update( 'fields.border_color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Border Radius', 'wp-edit-password-protected' ) }
							values={ s.fields?.border_radius }
							onChange={ ( v ) => update( 'fields.border_radius', v ) }
							min={ 0 }
							max={ 25 }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Padding', 'wp-edit-password-protected' ) }
							values={ s.fields?.padding }
							onChange={ ( v ) => update( 'fields.padding', v ) }
							min={ 0 }
							max={ 20 }
						/>
					</PanelRow>
				</PanelBody>

				{ /* ── Button ── */ }
				<PanelBody title={ __( 'Button', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ColorControl
							label={ __( 'Background Color', 'wp-edit-password-protected' ) }
							color={ s.button?.background_color || '#2271b1' }
							onChange={ ( v ) => update( 'button.background_color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<ColorControl
							label={ __( 'Text Color', 'wp-edit-password-protected' ) }
							color={ s.button?.text_color || '#ffffff' }
							onChange={ ( v ) => update( 'button.text_color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Border Radius', 'wp-edit-password-protected' ) }
							values={ s.button?.border_radius }
							onChange={ ( v ) => update( 'button.border_radius', v ) }
							min={ 0 }
							max={ 25 }
						/>
					</PanelRow>
					<PanelRow>
						<RangeControl
							label={ __( 'Font Size (px)', 'wp-edit-password-protected' ) }
							value={ s.button?.font_size || 14 }
							onChange={ ( v ) => update( 'button.font_size', v ) }
							min={ 10 }
							max={ 24 }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Padding', 'wp-edit-password-protected' ) }
							values={ s.button?.padding }
							onChange={ ( v ) => update( 'button.padding', v ) }
							min={ 2 }
							max={ 30 }
						/>
					</PanelRow>
				</PanelBody>

				{ /* ── Links ── */ }
				<PanelBody title={ __( 'Links', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ColorControl
							label={ __( 'Link Color', 'wp-edit-password-protected' ) }
							color={ s.links?.color || '#50575e' }
							onChange={ ( v ) => update( 'links.color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Lost Password Link', 'wp-edit-password-protected' ) }
							checked={ s.links?.show_lost_password !== false }
							onChange={ ( v ) => update( 'links.show_lost_password', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Back to Site Link', 'wp-edit-password-protected' ) }
							checked={ s.links?.show_back_to_site !== false }
							onChange={ ( v ) => update( 'links.show_back_to_site', v ) }
						/>
					</PanelRow>
				</PanelBody>

				{ /* ── Custom CSS ── */ }
				<PanelBody title={ __( 'Custom CSS', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<TextareaControl
							label={ __( 'Additional CSS', 'wp-edit-password-protected' ) }
							value={ s.custom_css || '' }
							onChange={ ( v ) => update( 'custom_css', v ) }
							placeholder=".login { /* your styles */ }"
							rows={ 8 }
							__nextHasNoMarginBottom
						/>
					</PanelRow>
				</PanelBody>

			</div>

			<LivePreview previewUrl={ previewUrl } css={ css } video={ activeVideo } />
		</div>
	);
};

export default LoginForm;
