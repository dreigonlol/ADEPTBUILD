/**
 * Adeptbuild Theme — interface interactions.
 *
 * - Accessible mobile menu (aria-expanded, close on Escape and outside click).
 * - Collapsible dropdown submenus on mobile, with synced aria-expanded state.
 * - Header shadow on scroll.
 *
 * @package Adeptbuild
 */

( function () {
	'use strict';

	function init() {
		splitMenuAroundLogo();
		setHeaderHeight();
		initMobileMenu();
		initFloatingContact();
		initWordRotators();
		initHeroVideo();
	}

	// On the live site this script tag often ends up loading near the end of
	// <body>, after third-party scripts — by then "DOMContentLoaded" has
	// usually already fired, and a listener attached after the fact never
	// runs. Falling back to an immediate call when the DOM is already ready
	// avoids silently losing every one of the functions above in production.
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	/**
	 * Home video hero: makes sure the right file is loaded and playing.
	 *
	 * The <video> picks its file through <source media="…"> (full video on
	 * desktop, light portrait clip on phones). iOS Safari wasn't starting it
	 * on its own even though the file plays fine when opened directly, so
	 * this does the selection explicitly with matchMedia when the browser
	 * hasn't picked a source, then calls play(). The video is muted and
	 * inline, which is what iOS requires to allow autoplay. If playback is
	 * still refused (e.g. Low Power Mode), the poster image stays visible.
	 */
	function initHeroVideo() {
		var video = document.querySelector( '.ab-page-hero__video' );
		if ( ! video ) {
			return;
		}

		video.muted = true;
		video.playsInline = true;
		video.setAttribute( 'playsinline', '' );

		if ( ! video.currentSrc ) {
			var sources = video.querySelectorAll( 'source' );
			for ( var i = 0; i < sources.length; i++ ) {
				var media = sources[ i ].getAttribute( 'media' );
				if ( ! media || window.matchMedia( media ).matches ) {
					video.src = sources[ i ].src;
					video.load();
					break;
				}
			}
		}

		var play = function () {
			var attempt = video.play();
			if ( attempt && attempt.catch ) {
				attempt.catch( function () {} );
			}
		};

		play();
		video.addEventListener( 'canplay', play, { once: true } );
		// Last resort: start on the first touch if autoplay was refused.
		document.addEventListener( 'touchstart', play, { once: true, passive: true } );
	}

	/**
	 * ".ab-word-rotate" — the single word under a heading like "WE BUILD"
	 * that cycles through a list on an interval. All items are stacked via
	 * CSS (each absolutely positioned, centered); this just walks the
	 * cycle, toggling which one carries ".is-active" (visible, in place)
	 * and, briefly, ".is-leaving" (sliding out) on the item stepping aside
	 * for it — see ".ab-word-rotate__item" in style.css for the actual
	 * slide/fade transition. Respects reduced-motion by just leaving the
	 * first word showing instead of cycling.
	 */
	function initWordRotators() {
		var rotators = document.querySelectorAll( '.ab-word-rotate' );
		var INTERVAL = 2600; // was 4500, before that 2400 — how long each word/photo lingers before advancing.

		var advance = function ( items, index ) {
			var current = items[ index ];
			var nextIndex = ( index + 1 ) % items.length;
			var next = items[ nextIndex ];

			current.classList.remove( 'is-active' );
			current.classList.add( 'is-leaving' );
			next.classList.add( 'is-active' );

			setTimeout( function () {
				current.classList.remove( 'is-leaving' );
			}, 650 );

			return nextIndex;
		};

		var setUp = function ( rotator ) {
			var items = rotator.querySelectorAll( '.ab-word-rotate__item' );
			if ( items.length < 2 ) {
				return null;
			}
			rotator.classList.add( 'ab-word-rotate--js' );
			items[ 0 ].classList.add( 'is-active' );
			return items;
		};

		var reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		// Rotators sharing a "data-rotate-sync" value (e.g. the "WE BUILD"
		// word and its background photo) advance together on one shared
		// interval/index instead of each running its own independent timer,
		// so the photo shown always matches the word currently on screen.
		var synced = {};
		var standalone = [];

		Array.prototype.forEach.call( rotators, function ( rotator ) {
			var key = rotator.getAttribute( 'data-rotate-sync' );
			if ( key ) {
				( synced[ key ] = synced[ key ] || [] ).push( rotator );
			} else {
				standalone.push( rotator );
			}
		} );

		standalone.forEach( function ( rotator ) {
			var items = setUp( rotator );
			if ( ! items || reduceMotion ) {
				return;
			}
			var index = 0;
			setInterval( function () {
				index = advance( items, index );
			}, INTERVAL );
		} );

		// A synced group can be gated behind mouse hover instead of running
		// on its own timer from page load — used by "WE BUILD" so the photo
		// sits still (and fully lit) until a visitor's cursor is actually on
		// it. Opt in by adding data-rotate-hover="<the shared sync key>" to
		// whatever element should act as the hover zone (see ".ab-story" in
		// home-content.html). Desktop-only by nature (mouseenter/mouseleave
		// don't fire meaningfully on touch), so touch devices — and anyone
		// with reduced motion, same as every other rotator — just keep the
		// original always-cycling behavior instead of sitting frozen with
		// no way to trigger it.
		var hoverScopes = {};
		Array.prototype.forEach.call( document.querySelectorAll( '[data-rotate-hover]' ), function ( el ) {
			hoverScopes[ el.getAttribute( 'data-rotate-hover' ) ] = el;
		} );
		var canHover = window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches;

		Object.keys( synced ).forEach( function ( key ) {
			var group = synced[ key ]
				.map( setUp )
				.filter( function ( items ) {
					return !! items;
				} );
			if ( ! group.length || reduceMotion ) {
				return;
			}
			var index = 0;
			var tick = function () {
				group.forEach( function ( items ) {
					// Each member's own item count may differ, so it keeps
					// its own effective index even on a shared clock tick.
					advance( items, index % items.length );
				} );
				index = ( index + 1 ) % Math.max.apply( null, group.map( function ( items ) {
					return items.length;
				} ) );
			};

			var hoverEl = hoverScopes[ key ];
			if ( hoverEl && canHover ) {
				var timer = null;
				hoverEl.addEventListener( 'mouseenter', function () {
					if ( timer ) {
						return;
					}
					hoverEl.classList.add( 'is-rotate-hover' );
					timer = setInterval( tick, INTERVAL );
				} );
				hoverEl.addEventListener( 'mouseleave', function () {
					clearInterval( timer );
					timer = null;
					hoverEl.classList.remove( 'is-rotate-hover' );
					// Just stops in place — whichever photo/word was showing
					// when the cursor left stays put (no snap back to #1).
				} );
			} else {
				setInterval( tick, INTERVAL );
			}
		} );
	}

	/**
	 * Splits the primary menu into two halves flanking the centered logo on
	 * desktop, matching the template's [nav] [logo] [nav] header layout — a
	 * single full-width menu list with the logo just overlaid on top of it
	 * would otherwise run right through the logo instead of sitting beside
	 * it. Reverts to the original single-list-plus-separate-logo markup
	 * below the mobile breakpoint, where the logo has to stay visible in the
	 * closed header bar instead of living inside the off-canvas panel.
	 */
	function splitMenuAroundLogo() {
		var nav = document.getElementById( 'site-navigation' );
		var menu = document.getElementById( 'primary-menu' );
		var branding = document.querySelector( '.site-branding' );

		if ( ! nav || ! menu || ! branding ) {
			return;
		}

		var brandingHome = branding.parentNode;
		var brandingNext = branding.nextSibling;
		var items = Array.prototype.slice.call( menu.children );
		var secondHalf = items.slice( Math.ceil( items.length / 2 ) );

		if ( ! secondHalf.length ) {
			return;
		}

		var rightMenu = document.createElement( 'ul' );
		rightMenu.className = menu.className;

		var isSplit = false;

		var applySplit = function () {
			if ( isSplit ) {
				return;
			}
			secondHalf.forEach( function ( li ) {
				rightMenu.appendChild( li );
			} );
			menu.classList.add( 'main-header-menu--left' );
			rightMenu.classList.add( 'main-header-menu--right' );
			nav.appendChild( branding );
			nav.appendChild( rightMenu );
			nav.classList.add( 'main-navigation--split' );
			isSplit = true;
		};

		var undoSplit = function () {
			if ( ! isSplit ) {
				return;
			}
			secondHalf.forEach( function ( li ) {
				menu.appendChild( li );
			} );
			menu.classList.remove( 'main-header-menu--left' );
			nav.classList.remove( 'main-navigation--split' );
			nav.removeChild( rightMenu );
			brandingHome.insertBefore( branding, brandingNext );
			isSplit = false;
		};

		var sync = function () {
			if ( window.matchMedia( '(min-width: 922px)' ).matches ) {
				applySplit();
			} else {
				undoSplit();
			}
		};

		sync();
		window.addEventListener( 'resize', debounce( sync, 150 ) );
	}

	/**
	 * Publishes the real header height as a CSS variable, so anchor-link
	 * scrolling and the mobile panel position correctly under it.
	 */
	function setHeaderHeight() {
		var header = document.getElementById( 'masthead' );
		if ( ! header ) {
			return;
		}

		var apply = function () {
			// getBoundingClientRect().bottom (viewport-relative), not
			// offsetHeight (the element's own box height): the WP admin
			// bar pushes the whole page down when logged in, so the
			// header's own height alone would leave the mobile menu
			// panel starting above where the header actually ends.
			document.documentElement.style.setProperty(
				'--ab-header-h',
				header.getBoundingClientRect().bottom + 'px'
			);
			header.classList.toggle( 'is-stuck', window.scrollY > 12 );
		};

		// Scroll fires far more often than the screen repaints; measuring
		// the header on every event forces a layout each time.
		var queued = false;
		var onScroll = function () {
			if ( queued ) {
				return;
			}
			queued = true;
			window.requestAnimationFrame( function () {
				queued = false;
				apply();
			} );
		};

		apply();
		window.addEventListener( 'resize', debounce( apply, 150 ) );
		window.addEventListener( 'scroll', onScroll, { passive: true } );
	}

	/**
	 * Small-screen navigation panel.
	 */
	function initMobileMenu() {
		var toggle = document.querySelector( '.menu-toggle' );
		var nav = document.getElementById( 'site-navigation' );

		if ( ! toggle || ! nav ) {
			return;
		}

		var close = function () {
			toggle.setAttribute( 'aria-expanded', 'false' );
			nav.classList.remove( 'is-open' );
			document.body.classList.remove( 'ab-menu-open' );
		};

		toggle.addEventListener( 'click', function () {
			var isOpen = toggle.getAttribute( 'aria-expanded' ) === 'true';

			toggle.setAttribute( 'aria-expanded', isOpen ? 'false' : 'true' );
			nav.classList.toggle( 'is-open', ! isOpen );
			document.body.classList.toggle( 'ab-menu-open', ! isOpen );
		} );

		// Close on Escape.
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && nav.classList.contains( 'is-open' ) ) {
				close();
				toggle.focus();
			}
		} );

		// Close when tapping outside the panel.
		document.addEventListener( 'click', function ( event ) {
			if (
				nav.classList.contains( 'is-open' ) &&
				! nav.contains( event.target ) &&
				! toggle.contains( event.target )
			) {
				close();
			}
		} );

		// Close after navigating to an on-page anchor. Skip clicks the
		// submenu toggle below already handled (event.preventDefault()) —
		// otherwise tapping a dropdown parent like "Services" (href="#")
		// opens its submenu and closes the whole panel in the same tap.
		nav.addEventListener( 'click', function ( event ) {
			if ( event.defaultPrevented ) {
				return;
			}
			var link = event.target.closest( 'a' );
			if ( link && link.getAttribute( 'href' ) && link.getAttribute( 'href' ).indexOf( '#' ) === 0 ) {
				close();
			}
		} );

		initSubmenus( nav );
	}

	/**
	 * Floating Call/Text widget, bottom-left. Desktop just relies on the
	 * ":hover"/":focus-visible" CSS to expand the pills — this only handles
	 * the mobile tab-opens-a-panel interaction (tap the tab, tap the scrim/
	 * close button/Escape to close). No-ops entirely above the 768px
	 * breakpoint where the tab is hidden and the panel is always shown.
	 */
	function initFloatingContact() {
		var widget = document.getElementById( 'ab-floating-contact' );
		if ( ! widget ) {
			return;
		}

		var tab = widget.querySelector( '.ab-floating-contact__tab' );
		if ( ! tab ) {
			return;
		}

		var setOpen = function ( open ) {
			widget.classList.toggle( 'is-open', open );
			tab.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		};

		tab.addEventListener( 'click', function () {
			setOpen( true );
		} );

		Array.prototype.forEach.call( widget.querySelectorAll( '[data-ab-fc-close]' ), function ( el ) {
			el.addEventListener( 'click', function () {
				setOpen( false );
			} );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && widget.classList.contains( 'is-open' ) ) {
				setOpen( false );
				tab.focus();
			}
		} );
	}

	/**
	 * Dropdown submenus: tap-to-expand on mobile, synced aria-expanded on
	 * hover/focus at every breakpoint (used for the caret rotation and for
	 * assistive tech).
	 *
	 * @param {HTMLElement} nav Navigation container.
	 */
	function initSubmenus( nav ) {
		var isMobile = function () {
			return window.matchMedia( '(max-width: 921px)' ).matches;
		};

		var parentItems = nav.querySelectorAll( '.menu-item-has-children' );

		Array.prototype.forEach.call( parentItems, function ( item ) {
			var link = item.querySelector( ':scope > a' );
			if ( ! link ) {
				return;
			}

			var setExpanded = function ( expanded ) {
				link.setAttribute( 'aria-expanded', expanded ? 'true' : 'false' );
				item.classList.toggle( 'is-expanded', expanded );
			};

			// Mobile: first tap opens the submenu instead of following the link.
			// Placeholder links (href="#", used by dropdown-only parents like
			// "Services") never navigate anywhere, so always prevent default
			// for those — otherwise collapsing them re-triggers the browser's
			// same-page "#" jump and the close-on-anchor handler above.
			link.addEventListener( 'click', function ( event ) {
				if ( ! isMobile() ) {
					return;
				}

				var isExpanded = item.classList.contains( 'is-expanded' );
				var href = link.getAttribute( 'href' );
				var isPlaceholder = ! href || '#' === href;

				if ( ! isExpanded || isPlaceholder ) {
					event.preventDefault();
				}
				setExpanded( ! isExpanded );
			} );

			// Desktop: keep aria-expanded in sync with the CSS hover/focus reveal.
			item.addEventListener( 'mouseenter', function () {
				if ( ! isMobile() ) {
					positionMegaMenu( item );
					setExpanded( true );
				}
			} );
			item.addEventListener( 'mouseleave', function () {
				if ( ! isMobile() ) {
					setExpanded( false );
				}
			} );
			item.addEventListener( 'focusin', function () {
				if ( ! isMobile() ) {
					positionMegaMenu( item );
					setExpanded( true );
				}
			} );
			item.addEventListener( 'focusout', function ( event ) {
				if ( ! isMobile() && ! item.contains( event.relatedTarget ) ) {
					setExpanded( false );
				}
			} );
		} );
	}

	/**
	 * Centers a mega-menu panel under its trigger link, then nudges it
	 * back inside the viewport if that would run it off either edge.
	 * CSS alone can't do this: it has no way to know where a given
	 * trigger actually sits on screen, only where the panel is relative
	 * to it. Skipped for a plain (non-mega) submenu — those are narrow
	 * enough that the CSS-only left:0 placement never overflows.
	 *
	 * @param {HTMLElement} item A ".menu-item-has-children" <li>.
	 */
	function positionMegaMenu( item ) {
		var panel = item.querySelector( ':scope > .sub-menu.mega-menu' );
		if ( ! panel ) {
			return;
		}

		// Reset to the CSS default (left:0, i.e. flush with the trigger)
		// before measuring, so a stale offset from a previous open/resize
		// can't throw off this calculation.
		panel.style.left = '';

		var margin = 16;
		var itemRect = item.getBoundingClientRect();
		var panelRect = panel.getBoundingClientRect();
		var idealLeft = itemRect.left + itemRect.width / 2 - panelRect.width / 2;
		var maxLeft = window.innerWidth - panelRect.width - margin;
		var clampedLeft = Math.max( margin, Math.min( idealLeft, maxLeft ) );

		// `left` on the panel is relative to its trigger <li> (its
		// positioned ancestor), not the viewport, so convert back.
		panel.style.left = ( clampedLeft - itemRect.left ) + 'px';
	}

	/**
	 * Utility: delays execution until events stop firing.
	 *
	 * @param {Function} fn    Function to run.
	 * @param {number}   delay Milliseconds to wait.
	 * @return {Function} Debounced function.
	 */
	function debounce( fn, delay ) {
		var timer;
		return function () {
			var args = arguments;
			clearTimeout( timer );
			timer = setTimeout( function () {
				fn.apply( null, args );
			}, delay );
		};
	}
} )();
