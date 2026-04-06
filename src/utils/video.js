/**
 * Video URL helpers — parse YouTube, Vimeo, and MP4 URLs into embed info.
 */

/**
 * Parse a video URL into a type and embed URL.
 *
 * @param {string} url Raw video URL.
 * @return {{ type: string, embedUrl: string }|null}
 */
export function parseVideoUrl( url ) {
	if ( ! url || typeof url !== 'string' ) {
		return null;
	}

	url = url.trim();

	// YouTube.
	const ytMatch = url.match(
		/(?:youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/)([\w-]{11})/
	);
	if ( ytMatch ) {
		return {
			type: 'youtube',
			embedUrl: `https://www.youtube.com/embed/${ ytMatch[ 1 ] }?autoplay=1&mute=1&loop=1&playlist=${ ytMatch[ 1 ] }&controls=0&showinfo=0&rel=0`,
		};
	}

	// Vimeo.
	const vimeoMatch = url.match( /vimeo\.com\/(\d+)/ );
	if ( vimeoMatch ) {
		return {
			type: 'vimeo',
			embedUrl: `https://player.vimeo.com/video/${ vimeoMatch[ 1 ] }?autoplay=1&muted=1&loop=1&background=1`,
		};
	}

	// MP4 / direct video.
	if ( /\.(mp4|webm|ogg)(\?.*)?$/i.test( url ) || url.includes( '.mp4' ) || url.includes( '.webm' ) ) {
		return { type: 'mp4', embedUrl: url };
	}

	return null;
}
