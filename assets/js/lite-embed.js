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
 * @package Adeptbuild
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', init );

	function init() {
		var frames = document.querySelectorAll(
			'.wp-block-embed-youtube iframe, .wp-block-embed.is-provider-youtube iframe'
		);

		Array.prototype.forEach.call( frames, upgrade );
	}

	/**
	 * Replaces a loaded YouTube iframe with a lite-embed thumbnail button.
	 *
	 * @param {HTMLIFrameElement} frame The original embed iframe.
	 */
	function upgrade( frame ) {
		var id = getVideoId( frame.src );
		if ( ! id ) {
			return;
		}

		var wrapper = frame.closest( '.wp-block-embed__wrapper' ) || frame.parentNode;

		var button = document.createElement( 'button' );
		button.type = 'button';
		button.className = 'lite-video';
		button.style.backgroundImage = "url('https://img.youtube.com/vi/" + id + "/hqdefault.jpg')";
		button.setAttribute( 'aria-label', 'Play video' );
		button.setAttribute( 'data-yt-id', id );

		var play = document.createElement( 'span' );
		play.className = 'lite-video__play';
		play.innerHTML =
			'<svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' +
			'<path d="M8 5v14l11-7z"></path></svg>';
		button.appendChild( play );

		button.addEventListener( 'click', function () {
			playVideo( id, button, frame.title || 'Video' );
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
	 * Extracts the video id from a YouTube embed src.
	 *
	 * @param {string} src e.g. "https://www.youtube.com/embed/QgnzUcLLAyU?...".
	 * @return {string|null}
	 */
	function getVideoId( src ) {
		var match = src && src.match( /embed\/([a-zA-Z0-9_-]{6,})/ );
		return match ? match[ 1 ] : null;
	}
} )();
