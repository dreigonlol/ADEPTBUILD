/**
 * Adeptbuild Theme — YouTube lite-embed.
 *
 * WordPress's core embed block loads a full YouTube iframe for every video
 * the moment the page renders, even if nobody plays it. This finds every
 * YouTube embed on the page and swaps it for a click-to-play thumbnail —
 * the real iframe is only created once a visitor actually clicks play.
 *
 * Works on any page automatically; no changes to page content required.
 *
 * LiteSpeed Cache lazy-loads iframes: at load time the real embed URL sits
 * in "data-src" while "src" is a placeholder, and LiteSpeed only swaps it
 * in later (on scroll, or async after load). A MutationObserver below
 * catches that swap whenever it happens, instead of only checking once on
 * DOMContentLoaded.
 *
 * @package Adeptbuild
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', init );

	function init() {
		var frames = document.querySelectorAll(
			'.wp-block-embed-youtube iframe, .wp-block-embed.is-provider-youtube iframe'
		);

		Array.prototype.forEach.call( frames, watch );
	}

	/**
	 * Upgrades a frame immediately if its real YouTube URL is already
	 * resolved, otherwise watches it until a lazy-loader fills it in.
	 *
	 * @param {HTMLIFrameElement} frame The original embed iframe.
	 */
	function watch( frame ) {
		var id = getVideoId( frame );

		if ( id ) {
			upgrade( frame, id );
			return;
		}

		var observer = new MutationObserver( function () {
			var lazyId = getVideoId( frame );
			if ( lazyId ) {
				observer.disconnect();
				upgrade( frame, lazyId );
			}
		} );

		observer.observe( frame, { attributes: true, attributeFilter: [ 'src', 'data-src' ] } );
	}

	/**
	 * Replaces a YouTube iframe with a lite-embed thumbnail button.
	 *
	 * @param {HTMLIFrameElement} frame The original embed iframe.
	 * @param {string}            id    YouTube video id.
	 */
	function upgrade( frame, id ) {
		var wrapper = frame.closest( '.wp-block-embed__wrapper' ) || frame.parentNode;

		var button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'lite-video';
		button.style.backgroundImage = "url('https://img.youtube.com/vi/" + id + "/hqdefault.jpg')";
		button.setAttribute( 'aria-label', 'Play video' );
		button.setAttribute( 'data-yt-id', id );

		var playIcon = document.createElement( 'span' );
		playIcon.className = 'lite-video__play';
		playIcon.innerHTML =
			'<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' +
			'<path d="M8 5v14l11-7z"></path></svg>';
		button.appendChild( playIcon );

		var title = frame.title || 'Video';
		button.addEventListener( 'click', function () {
			playVideo( id, button, title );
		} );

		wrapper.replaceChild( button, frame );
	}

	/**
	 * Swaps a lite-embed button for a real, playing YouTube iframe.
	 *
	 * @param {string}            id    YouTube video id.
	 * @param {HTMLButtonElement} btn   The lite-embed button being replaced.
	 * @param {string}            title Accessible title for the new iframe.
	 */
	function playVideo( id, btn, title ) {
		var iframe = document.createElement( 'iframe' );

		// mute=1: mobile browsers block unmuted autoplay outright, and a
		// blocked autoplay can leave the player UI unresponsive to taps.
		// Muted autoplay is reliably allowed everywhere; viewers can unmute
		// from the player's own speaker icon.
		iframe.src = 'https://www.youtube.com/embed/' + id + '?autoplay=1&mute=1&playsinline=1&rel=0';
		iframe.title = title;
		iframe.allow =
			'accelerometer; autoplay; clipboard-write; encrypted-media; fullscreen; gyroscope; picture-in-picture; web-share';
		iframe.allowFullscreen = true;

		// Set sizing directly — the parent ".wp-block-embed__wrapper" is
		// already position:relative with a fixed aspect ratio (theme/core
		// embed styles), so an absolutely positioned frame fills it exactly.
		iframe.style.position = 'absolute';
		iframe.style.inset = '0';
		iframe.style.width = '100%';
		iframe.style.height = '100%';
		iframe.style.border = '0';

		btn.replaceWith( iframe );
	}

	/**
	 * Extracts the video id from a YouTube embed iframe, checking both the
	 * live "src" and LiteSpeed's lazy-load "data-src" attribute.
	 *
	 * @param {HTMLIFrameElement} frame
	 * @return {string|null}
	 */
	function getVideoId( frame ) {
		var src = frame.getAttribute( 'src' ) || '';
		var dataSrc = frame.getAttribute( 'data-src' ) || '';
		var match = src.match( /embed\/([a-zA-Z0-9_-]{6,})/ ) || dataSrc.match( /embed\/([a-zA-Z0-9_-]{6,})/ );
		return match ? match[ 1 ] : null;
	}
} )();
