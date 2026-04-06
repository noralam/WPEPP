/**
 * LivePreview — iframe-based live preview with postMessage CSS injection.
 */
import { useRef, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { useState } from '@wordpress/element';

const LivePreview = ( { previewUrl, css, video, settings, width = '100%' } ) => {
	const iframeRef = useRef( null );
	const [ isLoaded, setIsLoaded ] = useState( false );

	const sendCss = useCallback( () => {
		if ( iframeRef.current?.contentWindow && css !== undefined ) {
			iframeRef.current.contentWindow.postMessage(
				{ type: 'wpepp_preview_css', css },
				'*'
			);
		}
	}, [ css ] );

	const sendVideo = useCallback( () => {
		if ( iframeRef.current?.contentWindow && video ) {
			iframeRef.current.contentWindow.postMessage(
				{ type: 'wpepp_preview_video', video },
				'*'
			);
		} else if ( iframeRef.current?.contentWindow && ! video ) {
			iframeRef.current.contentWindow.postMessage(
				{ type: 'wpepp_preview_video', video: null },
				'*'
			);
		}
	}, [ video ] );

	const sendSettings = useCallback( () => {
		if ( iframeRef.current?.contentWindow && settings ) {
			iframeRef.current.contentWindow.postMessage(
				{ type: 'wpepp_preview_settings', settings },
				'*'
			);
		}
	}, [ settings ] );

	useEffect( () => {
		if ( isLoaded ) {
			sendCss();
		}
	}, [ css, isLoaded, sendCss ] );

	useEffect( () => {
		if ( isLoaded ) {
			sendVideo();
		}
	}, [ video, isLoaded, sendVideo ] );

	useEffect( () => {
		if ( isLoaded ) {
			sendSettings();
		}
	}, [ settings, isLoaded, sendSettings ] );

	const handleLoad = () => {
		setIsLoaded( true );
		sendCss();
		sendVideo();
		sendSettings();
	};

	if ( ! previewUrl ) {
		return null;
	}

	return (
		<div className="wpepp-live-preview" style={ { width } }>
			{ ! isLoaded && (
				<div className="wpepp-live-preview__loading">
					<Spinner />
				</div>
			) }
			<iframe
				ref={ iframeRef }
				src={ previewUrl }
				title={ __( 'Live Preview', 'wp-edit-password-protected' ) }
				className="wpepp-live-preview__iframe"
				onLoad={ handleLoad }
				sandbox="allow-same-origin allow-scripts"
				style={ { display: isLoaded ? 'block' : 'none' } }
			/>
		</div>
	);
};

export default LivePreview;
