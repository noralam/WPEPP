/**
 * Password Form editor — single panel with Form Layout selector.
 */
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import {
	PanelBody,
	PanelRow,
	TextControl,
	TextareaControl,
	ToggleControl,
	SelectControl,
	RangeControl,
	Button,
	GradientPicker,
} from '@wordpress/components';
import { useMemo, useCallback } from '@wordpress/element';
import ProBadge from '../../components/ProBadge';
import LivePreview from '../../components/LivePreview';
import ColorControl from '../../components/ColorControl';
import DimensionControl from '../../components/DimensionControl';
import { useSaveBar } from '../../components/SaveBar';
import { generatePasswordCss } from '../../utils/css-generator';
import { PASSWORD_DEFAULTS, mergeDefaults } from '../../utils/defaults';

const LAYOUT_OPTIONS = [
	{ label: __( 'Horizontal', 'wp-edit-password-protected' ), value: 'one' },
	{ label: __( 'Inline', 'wp-edit-password-protected' ), value: 'two' },
	{ label: __( 'Vertical Card (Pro)', 'wp-edit-password-protected' ), value: 'three' },
	{ label: __( 'Full Width (Pro)', 'wp-edit-password-protected' ), value: 'four' },
];

const PasswordForm = () => {
	const { settings, isSaving, isPro } = useSelect( ( select ) => {
		const store = select( 'wpepp/settings' );
		return {
			settings: store.getSectionSettings( 'password' ),
			isSaving: store.isSaving(),
			isPro: store.isPro(),
		};
	} );

	const { updateSetting, saveSettings, resetSettings } = useDispatch( 'wpepp/settings' );

	const s = useMemo( () => mergeDefaults( PASSWORD_DEFAULTS, settings ), [ settings ] );
	const css = useMemo( () => generatePasswordCss( s ), [ s ] );

	const activeStyle = s.active_style || 'one';

	const previewUrl = useMemo( () => {
		const base = window.wpeppData?.adminUrl || '/wp-admin/';
		return `${ base }admin-ajax.php?action=wpepp_preview&type=password&_wpnonce=${ window.wpeppData?.previewNonce || '' }`;
	}, [] );

	const update = useCallback( ( key, value ) => {
		updateSetting( 'password', key, value );
	}, [ updateSetting ] );

	const handleSave = useCallback( () => {
		saveSettings( 'password', { ...s, active_style: activeStyle } );
	}, [ saveSettings, s, activeStyle ] );

	const handleReset = useCallback( () => {
		resetSettings( 'password' );
	}, [ resetSettings ] );

	useSaveBar( handleSave, handleReset );

	const bgType = s.page_background_type || 'color';

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

	return (
		<div className="wpepp-editor-layout">
			<div className="wpepp-editor-controls">
				<PanelBody title={ __( 'Form Layout', 'wp-edit-password-protected' ) } initialOpen>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Layout', 'wp-edit-password-protected' ) }
							value={ activeStyle }
							options={ LAYOUT_OPTIONS.map( ( opt ) => ( {
								...opt,
								disabled: ! isPro && ( opt.value === 'three' || opt.value === 'four' ),
							} ) ) }
							onChange={ ( v ) => {
								if ( ! isPro && ( v === 'three' || v === 'four' ) ) {
									return;
								}
								update( 'active_style', v );
							} }
							help={
								! isPro
									? __( 'Vertical Card and Full Width layouts require Pro.', 'wp-edit-password-protected' )
									: undefined
							}
						/>
					</PanelRow>
				</PanelBody>
				<PanelBody title={ __( 'Logo', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Logo Type', 'wp-edit-password-protected' ) }
							value={ s.logo_type || 'none' }
							options={ [
								{ label: __( 'None', 'wp-edit-password-protected' ), value: 'none' },
								{ label: __( 'Site Logo', 'wp-edit-password-protected' ), value: 'site' },
								{ label: __( 'Custom Image', 'wp-edit-password-protected' ), value: 'custom' },
								{ label: __( 'Text', 'wp-edit-password-protected' ), value: 'text' },
							] }
							onChange={ ( v ) => update( 'logo_type', v ) }
						/>
					</PanelRow>
					{ s.logo_type === 'custom' && (
						<>
							<PanelRow>
								<div className="wpepp-media-upload">
									{ s.logo_image && (
										<img src={ s.logo_image } alt="" className="wpepp-media-upload__preview" />
									) }
									<div className="wpepp-media-upload__buttons">
										<Button variant="secondary" onClick={ () => openMediaUploader( 'logo_image' ) }>
											{ s.logo_image ? __( 'Change Image', 'wp-edit-password-protected' ) : __( 'Upload Image', 'wp-edit-password-protected' ) }
										</Button>
										{ s.logo_image && (
											<Button variant="tertiary" isDestructive onClick={ () => update( 'logo_image', '' ) }>
												{ __( 'Remove', 'wp-edit-password-protected' ) }
											</Button>
										) }
									</div>
								</div>
							</PanelRow>
						</>
					) }
					{ ( s.logo_type === 'site' || s.logo_type === 'custom' ) && (
						<>
							<PanelRow>
								<RangeControl
									label={ __( 'Width (px)', 'wp-edit-password-protected' ) }
									value={ s.logo_width || 120 }
									onChange={ ( v ) => update( 'logo_width', v ) }
									min={ 20 }
									max={ 400 }
								/>
							</PanelRow>
							<PanelRow>
								<RangeControl
									label={ __( 'Height (px)', 'wp-edit-password-protected' ) }
									value={ s.logo_height || 60 }
									onChange={ ( v ) => update( 'logo_height', v ) }
									min={ 20 }
									max={ 400 }
								/>
							</PanelRow>
						</>
					) }
					{ s.logo_type === 'text' && (
						<>
							<PanelRow>
								<TextControl
									label={ __( 'Logo Text', 'wp-edit-password-protected' ) }
									value={ s.logo_text || '' }
									onChange={ ( v ) => update( 'logo_text', v ) }
									placeholder={ __( 'Enter logo text', 'wp-edit-password-protected' ) }
								/>
							</PanelRow>
							<PanelRow>
								<RangeControl
									label={ __( 'Font Size (px)', 'wp-edit-password-protected' ) }
									value={ s.logo_text_font_size || 24 }
									onChange={ ( v ) => update( 'logo_text_font_size', v ) }
									min={ 12 }
									max={ 72 }
								/>
							</PanelRow>
							<PanelRow>
								<ColorControl
									label={ __( 'Text Color', 'wp-edit-password-protected' ) }
									color={ s.logo_text_color || '#1e1e1e' }
									onChange={ ( v ) => update( 'logo_text_color', v ) }
								/>
							</PanelRow>
						</>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Top Text', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Top Text', 'wp-edit-password-protected' ) }
							checked={ s.show_top_text === 'on' }
							onChange={ ( v ) => update( 'show_top_text', v ? 'on' : 'off' ) }
						/>
					</PanelRow>
					{ s.show_top_text === 'on' && (
						<>
							<PanelRow>
								<TextControl
									label={ __( 'Heading', 'wp-edit-password-protected' ) }
									value={ s.top_header || '' }
									onChange={ ( v ) => update( 'top_header', v ) }
									placeholder={ __( 'Enter heading', 'wp-edit-password-protected' ) }
								/>
							</PanelRow>
							<PanelRow>
								<TextareaControl
									label={ __( 'Content', 'wp-edit-password-protected' ) }
									value={ s.top_content || '' }
									onChange={ ( v ) => update( 'top_content', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Alignment', 'wp-edit-password-protected' ) }
									value={ s.top_text_align || 'center' }
									options={ [
										{ label: __( 'Left', 'wp-edit-password-protected' ), value: 'left' },
										{ label: __( 'Center', 'wp-edit-password-protected' ), value: 'center' },
										{ label: __( 'Right', 'wp-edit-password-protected' ), value: 'right' },
									] }
									onChange={ ( v ) => update( 'top_text_align', v ) }
								/>
							</PanelRow>
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Form', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<TextControl
							label={ __( 'Label', 'wp-edit-password-protected' ) }
							value={ s.form_label || 'Password' }
							onChange={ ( v ) => update( 'form_label', v ) }
							placeholder={ __( 'Password', 'wp-edit-password-protected' ) }
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Label Display', 'wp-edit-password-protected' ) }
							value={ s.form_label_type || 'label' }
							options={ [
								{ label: __( 'Label', 'wp-edit-password-protected' ), value: 'label' },
								{ label: __( 'Placeholder', 'wp-edit-password-protected' ), value: 'placeholder' },
							] }
							onChange={ ( v ) => update( 'form_label_type', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Button Text', 'wp-edit-password-protected' ) }
							value={ s.form_btn_text || 'Submit' }
							onChange={ ( v ) => update( 'form_btn_text', v ) }
							placeholder={ __( 'Submit', 'wp-edit-password-protected' ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Error Message', 'wp-edit-password-protected' ) }
							value={ s.form_errortext || '' }
							onChange={ ( v ) => update( 'form_errortext', v ) }
							placeholder={ __( 'The password you have entered is invalid', 'wp-edit-password-protected' ) }
						/>
					</PanelRow>
					<PanelRow>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Error Text Position', 'wp-edit-password-protected' ) }
							value={ s.error_text_position || 'top' }
							options={ [
								{ label: __( 'Top', 'wp-edit-password-protected' ), value: 'top' },
								{ label: __( 'Bottom', 'wp-edit-password-protected' ), value: 'bottom' },
							] }
							onChange={ ( v ) => update( 'error_text_position', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<RangeControl
							label={ __( 'Label Font Size (px)', 'wp-edit-password-protected' ) }
							value={ s.label_font_size || 14 }
							onChange={ ( v ) => update( 'label_font_size', v ) }
							min={ 10 }
							max={ 24 }
						/>
					</PanelRow>
					<PanelRow>
						<ColorControl
							label={ __( 'Label Color', 'wp-edit-password-protected' ) }
							color={ s.label_color || '#1e1e1e' }
							onChange={ ( v ) => update( 'label_color', v ) }
						/>
					</PanelRow>
				</PanelBody>

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
							onChange={ ( v ) => update( 'page_background_type', v ) }
						/>
					</PanelRow>

					{ bgType === 'color' && (
						<PanelRow>
							<ColorControl
								label={ __( 'Background Color', 'wp-edit-password-protected' ) }
								color={ s.page_background_color || '#f0f0f1' }
								onChange={ ( v ) => update( 'page_background_color', v ) }
							/>
						</PanelRow>
					) }

					{ bgType === 'image' && (
						<>
							<PanelRow>
								<div className="wpepp-media-upload">
									{ s.page_background_image && (
										<img src={ s.page_background_image } alt="" className="wpepp-media-upload__preview" />
									) }
									<div className="wpepp-media-upload__buttons">
										<Button variant="secondary" onClick={ () => openMediaUploader( 'page_background_image' ) }>
											{ s.page_background_image ? __( 'Change Image', 'wp-edit-password-protected' ) : __( 'Upload Image', 'wp-edit-password-protected' ) }
										</Button>
										{ s.page_background_image && (
											<Button variant="tertiary" isDestructive onClick={ () => update( 'page_background_image', '' ) }>
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
									value={ s.page_background_position || 'center center' }
									options={ [
										{ label: __( 'Center', 'wp-edit-password-protected' ), value: 'center center' },
										{ label: __( 'Top', 'wp-edit-password-protected' ), value: 'center top' },
										{ label: __( 'Bottom', 'wp-edit-password-protected' ), value: 'center bottom' },
										{ label: __( 'Left', 'wp-edit-password-protected' ), value: 'left center' },
										{ label: __( 'Right', 'wp-edit-password-protected' ), value: 'right center' },
									] }
									onChange={ ( v ) => update( 'page_background_position', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Size', 'wp-edit-password-protected' ) }
									value={ s.page_background_size || 'cover' }
									options={ [
										{ label: __( 'Cover', 'wp-edit-password-protected' ), value: 'cover' },
										{ label: __( 'Contain', 'wp-edit-password-protected' ), value: 'contain' },
										{ label: __( 'Auto', 'wp-edit-password-protected' ), value: 'auto' },
									] }
									onChange={ ( v ) => update( 'page_background_size', v ) }
								/>
							</PanelRow>
						</>
					) }

					{ bgType === 'gradient' && isPro && (
						<PanelRow>
							<div style={ { width: '100%' } }>
								<GradientPicker
									value={ s.page_background_gradient || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }
									onChange={ ( v ) => update( 'page_background_gradient', v ) }
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
									value={ s.page_background_video || '' }
									onChange={ ( v ) => update( 'page_background_video', v ) }
									placeholder="https://www.youtube.com/watch?v=xxxxx"
									help={ __( 'Supports MP4, YouTube, and Vimeo URLs.', 'wp-edit-password-protected' ) }
								/>
							</PanelRow>
							<PanelRow>
								<ColorControl
									label={ __( 'Fallback Color', 'wp-edit-password-protected' ) }
									color={ s.page_background_color || '#f0f0f1' }
									onChange={ ( v ) => update( 'page_background_color', v ) }
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

				<PanelBody title={ __( 'Form Wrapper', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ColorControl
							label={ __( 'Background Color', 'wp-edit-password-protected' ) }
							color={ s.form_outer_background || '' }
							onChange={ ( v ) => update( 'form_outer_background', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Border Radius', 'wp-edit-password-protected' ) }
							values={ s.form_outer_border_radius }
							onChange={ ( v ) => update( 'form_outer_border_radius', v ) }
							min={ 0 }
							max={ 50 }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Padding', 'wp-edit-password-protected' ) }
							values={ s.form_outer_padding }
							onChange={ ( v ) => update( 'form_outer_padding', v ) }
							min={ 0 }
							max={ 60 }
						/>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ __( 'Form Container', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ColorControl
							label={ __( 'Background Color', 'wp-edit-password-protected' ) }
							color={ s.form_background || '#ffffff' }
							onChange={ ( v ) => update( 'form_background', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Border Radius', 'wp-edit-password-protected' ) }
							values={ s.form_border_radius }
							onChange={ ( v ) => update( 'form_border_radius', v ) }
							min={ 0 }
							max={ 50 }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Padding', 'wp-edit-password-protected' ) }
							values={ s.form_padding }
							onChange={ ( v ) => update( 'form_padding', v ) }
							min={ 0 }
							max={ 60 }
						/>
					</PanelRow>
					<PanelRow>
						<ColorControl
							label={ __( 'Text Color', 'wp-edit-password-protected' ) }
							color={ s.form_text_color || '#1e1e1e' }
							onChange={ ( v ) => update( 'form_text_color', v ) }
						/>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ __( 'Input Fields', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ColorControl
							label={ __( 'Background Color', 'wp-edit-password-protected' ) }
							color={ s.input_background || '#ffffff' }
							onChange={ ( v ) => update( 'input_background', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<ColorControl
							label={ __( 'Text Color', 'wp-edit-password-protected' ) }
							color={ s.input_text_color || '#1e1e1e' }
							onChange={ ( v ) => update( 'input_text_color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<ColorControl
							label={ __( 'Border Color', 'wp-edit-password-protected' ) }
							color={ s.input_border_color || '#8c8f94' }
							onChange={ ( v ) => update( 'input_border_color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Border Radius', 'wp-edit-password-protected' ) }
							values={ s.input_border_radius }
							onChange={ ( v ) => update( 'input_border_radius', v ) }
							min={ 0 }
							max={ 25 }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Padding', 'wp-edit-password-protected' ) }
							values={ s.input_padding }
							onChange={ ( v ) => update( 'input_padding', v ) }
							min={ 0 }
							max={ 20 }
						/>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ __( 'Button', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ColorControl
							label={ __( 'Background Color', 'wp-edit-password-protected' ) }
								color={ s.button_color || '#42276A' }
							onChange={ ( v ) => update( 'button_color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<ColorControl
							label={ __( 'Text Color', 'wp-edit-password-protected' ) }
							color={ s.button_text_color || '#ffffff' }
							onChange={ ( v ) => update( 'button_text_color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Border Radius', 'wp-edit-password-protected' ) }
							values={ s.button_border_radius }
							onChange={ ( v ) => update( 'button_border_radius', v ) }
							min={ 0 }
							max={ 25 }
						/>
					</PanelRow>
					<PanelRow>
						<RangeControl
							label={ __( 'Font Size (px)', 'wp-edit-password-protected' ) }
							value={ s.button_font_size || 14 }
							onChange={ ( v ) => update( 'button_font_size', v ) }
							min={ 10 }
							max={ 24 }
						/>
					</PanelRow>
					<PanelRow>
						<DimensionControl
							label={ __( 'Padding', 'wp-edit-password-protected' ) }
							values={ s.button_padding }
							onChange={ ( v ) => update( 'button_padding', v ) }
							min={ 2 }
							max={ 30 }
						/>
					</PanelRow>
				</PanelBody>

				<PanelBody title={ __( 'Heading Style', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ColorControl
							label={ __( 'Heading Color', 'wp-edit-password-protected' ) }
							color={ s.heading_color || '#1e1e1e' }
							onChange={ ( v ) => update( 'heading_color', v ) }
						/>
					</PanelRow>
					<PanelRow>
						<RangeControl
							label={ __( 'Font Size (px)', 'wp-edit-password-protected' ) }
							value={ s.heading_font_size || 20 }
							onChange={ ( v ) => update( 'heading_font_size', v ) }
							min={ 12 }
							max={ 48 }
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __( 'Background Overlay', 'wp-edit-password-protected' ) }
							checked={ !! s.heading_show_background }
							onChange={ ( v ) => update( 'heading_show_background', v ) }
							help={ __( 'Adds a background behind the heading for better visibility.', 'wp-edit-password-protected' ) }
						/>
					</PanelRow>
					{ s.heading_show_background && (
						<>
							<PanelRow>
								<ColorControl
									label={ __( 'Background Color', 'wp-edit-password-protected' ) }
									color={ s.heading_background_color || 'rgba(0,0,0,0.45)' }
									onChange={ ( v ) => update( 'heading_background_color', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<DimensionControl
									label={ __( 'Padding', 'wp-edit-password-protected' ) }
									values={ s.heading_padding }
									onChange={ ( v ) => update( 'heading_padding', v ) }
									min={ 0 }
									max={ 60 }
								/>
							</PanelRow>
							<PanelRow>
								<DimensionControl
									label={ __( 'Border Radius', 'wp-edit-password-protected' ) }
									values={ s.heading_border_radius }
									onChange={ ( v ) => update( 'heading_border_radius', v ) }
									min={ 0 }
									max={ 50 }
								/>
							</PanelRow>
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Social Icons', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Social Icons', 'wp-edit-password-protected' ) }
							checked={ s.show_social === 'on' }
							onChange={ ( v ) => update( 'show_social', v ? 'on' : 'off' ) }
						/>
					</PanelRow>
					{ s.show_social === 'on' && (
						<>
							<PanelRow>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Position', 'wp-edit-password-protected' ) }
									value={ s.icons_vposition || 'top' }
									options={ [
										{ label: __( 'Top', 'wp-edit-password-protected' ), value: 'top' },
										{ label: __( 'Middle', 'wp-edit-password-protected' ), value: 'middle' },
										{ label: __( 'Bottom', 'wp-edit-password-protected' ), value: 'bottom' },
									] }
									onChange={ ( v ) => update( 'icons_vposition', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Alignment', 'wp-edit-password-protected' ) }
									value={ s.icons_alignment || 'center' }
									options={ [
										{ label: __( 'Left', 'wp-edit-password-protected' ), value: 'left' },
										{ label: __( 'Center', 'wp-edit-password-protected' ), value: 'center' },
										{ label: __( 'Right', 'wp-edit-password-protected' ), value: 'right' },
									] }
									onChange={ ( v ) => update( 'icons_alignment', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Icon Style', 'wp-edit-password-protected' ) }
									value={ s.icons_style || 'square' }
									options={ [
										{ label: __( 'Square', 'wp-edit-password-protected' ), value: 'square' },
										{ label: __( 'Circle', 'wp-edit-password-protected' ), value: 'circle' },
										{ label: __( 'Rounded', 'wp-edit-password-protected' ), value: 'quarter' },
									] }
									onChange={ ( v ) => update( 'icons_style', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<ColorControl
									label={ __( 'Icon Background Color', 'wp-edit-password-protected' ) }
									value={ s.icons_color || '' }
									onChange={ ( v ) => update( 'icons_color', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<RangeControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Icon Size', 'wp-edit-password-protected' ) }
									value={ s.icons_size || 36 }
									onChange={ ( v ) => update( 'icons_size', v ) }
									min={ 20 }
									max={ 80 }
								/>
							</PanelRow>
							<PanelRow>
								<RangeControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Icon Gap', 'wp-edit-password-protected' ) }
									value={ s.icons_gap !== undefined ? s.icons_gap : 10 }
									onChange={ ( v ) => update( 'icons_gap', v ) }
									min={ 0 }
									max={ 40 }
								/>
							</PanelRow>
							<DimensionControl
								label={ __( 'Icons Padding', 'wp-edit-password-protected' ) }
								values={ s.icons_padding }
								onChange={ ( v ) => update( 'icons_padding', v ) }
							/>
							<PanelRow>
								<TextControl
									label={ __( 'Facebook URL', 'wp-edit-password-protected' ) }
									value={ s.link_facebook || '' }
									onChange={ ( v ) => update( 'link_facebook', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<TextControl
									label={ __( 'X (Twitter) URL', 'wp-edit-password-protected' ) }
									value={ s.link_twitter || '' }
									onChange={ ( v ) => update( 'link_twitter', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<TextControl
									label={ __( 'YouTube URL', 'wp-edit-password-protected' ) }
									value={ s.link_youtube || '' }
									onChange={ ( v ) => update( 'link_youtube', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<TextControl
									label={ __( 'Instagram URL', 'wp-edit-password-protected' ) }
									value={ s.link_instagram || '' }
									onChange={ ( v ) => update( 'link_instagram', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<TextControl
									label={ __( 'LinkedIn URL', 'wp-edit-password-protected' ) }
									value={ s.link_linkedin || '' }
									onChange={ ( v ) => update( 'link_linkedin', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<TextControl
									label={ __( 'Pinterest URL', 'wp-edit-password-protected' ) }
									value={ s.link_pinterest || '' }
									onChange={ ( v ) => update( 'link_pinterest', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<TextControl
									label={ __( 'Tumblr URL', 'wp-edit-password-protected' ) }
									value={ s.link_tumblr || '' }
									onChange={ ( v ) => update( 'link_tumblr', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<TextControl
									label={ __( 'Custom URL', 'wp-edit-password-protected' ) }
									value={ s.link_custom || '' }
									onChange={ ( v ) => update( 'link_custom', v ) }
								/>
							</PanelRow>
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Bottom Text', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Bottom Text', 'wp-edit-password-protected' ) }
							checked={ s.show_bottom_text === 'on' }
							onChange={ ( v ) => update( 'show_bottom_text', v ? 'on' : 'off' ) }
						/>
					</PanelRow>
					{ s.show_bottom_text === 'on' && (
						<>
							<PanelRow>
								<TextControl
									label={ __( 'Heading', 'wp-edit-password-protected' ) }
									value={ s.bottom_header || '' }
									onChange={ ( v ) => update( 'bottom_header', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<TextareaControl
									label={ __( 'Content', 'wp-edit-password-protected' ) }
									value={ s.bottom_content || '' }
									onChange={ ( v ) => update( 'bottom_content', v ) }
								/>
							</PanelRow>
							<PanelRow>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Text Alignment', 'wp-edit-password-protected' ) }
									value={ s.bottom_text_align || 'left' }
									options={ [
										{ label: __( 'Left', 'wp-edit-password-protected' ), value: 'left' },
										{ label: __( 'Center', 'wp-edit-password-protected' ), value: 'center' },
										{ label: __( 'Right', 'wp-edit-password-protected' ), value: 'right' },
									] }
									onChange={ ( v ) => update( 'bottom_text_align', v ) }
								/>
							</PanelRow>
						</>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Custom CSS', 'wp-edit-password-protected' ) } initialOpen={ false }>
					<PanelRow>
						<TextareaControl
							label={ __( 'Custom CSS', 'wp-edit-password-protected' ) }
							help={ __( 'Add your custom CSS for the password form page.', 'wp-edit-password-protected' ) }
							value={ s.custom_css || '' }
							onChange={ ( v ) => update( 'custom_css', v ) }
							rows={ 8 }
							placeholder=".wpepp-password-form { /* your styles */ }"
						/>
					</PanelRow>
				</PanelBody>


			</div>

			<LivePreview previewUrl={ previewUrl } css={ css } settings={ s } />
		</div>
	);
};

export default PasswordForm;
