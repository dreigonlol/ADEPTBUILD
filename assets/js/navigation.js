/**
 * Adeptbuild Theme — interface interactions.
 *
 * - Accessible mobile menu (aria-expanded, close on Escape and outside click).
 * - Collapsible dropdown submenus on mobile, with synced aria-expanded state.
 * - Header shadow on scroll.
 * - Fade-in animation for revealed sections.
 *
 * @package Adeptbuild
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		setHeaderHeight();
		initMobileMenu();
		autoTagReveals();
		initReveal();
		initCounters();
	} );

	/**
	 * Tags content with .ab-reveal (and a directional variant where it
	 * reads better) so it fades/slides in as the visitor scrolls to it —
	 * no changes to the page content itself. Runs before initReveal(),
	 * which then picks up everything tagged here through its normal
	 * ".ab-reveal" query.
	 */
	function autoTagReveals() {
		var tag = function ( selector, variant ) {
			var found = document.querySelectorAll( selector );
			Array.prototype.forEach.call( found, function ( el ) {
				el.classList.add( 'ab-reveal' );
				if ( variant ) {
					el.classList.add( variant );
				}
			} );
		};

		if ( document.body.classList.contains( 'home' ) ) {
			// 1. Intro: copy slides in from the left, the video from the right.
			tag( '.ab-proto-intro-left', 'ab-reveal--left' );
			tag( '.ab-proto-intro .wp-block-embed', 'ab-reveal--right' );

			// 2. Our Services: each card fades up, staggered by initReveal().
			tag( '.ab-proto-services .wp-block-column' );

			// 3. Our Philosophy: heading from the left, copy from the right.
			tag( '.ab-proto-philosophy h2' );
			tag( '.ab-proto-philosophy-copy', 'ab-reveal--right' );

			// 4. How We Work: each step fades up in sequence.
			tag( '.ab-proto-process .wp-block-column' );

			// 5. Trust bar: the whole row scales in as one unit.
			tag( '.ab-proto-trust', 'ab-reveal--scale' );

			// 6. Watch Our Videos: each thumbnail fades up.
			tag( '.wp-block-columns.ab-proto-videos .wp-block-column' );

			// 7. From Our Blog: each post card fades up in sequence.
			tag( '.ab-proto-blog .wp-block-latest-posts li' );
			return;
		}

		// Every other page (services, project, etc.): Home's sections are
		// tagged by their known className above; a generic page has no such
		// hooks, so instead this walks whatever top-level blocks the editor
		// actually produced inside the article and reveals each one as it
		// scrolls in. Columns and post grids are unwrapped one level so
		// each column/card fades in on its own — the same staggering
		// initReveal() already gives Home's Services/Process/Blog rows —
		// instead of the whole row appearing as a single block.
		var entry = document.querySelector( '.ab-entry-content' );
		if ( ! entry ) {
			return;
		}

		Array.prototype.forEach.call( entry.children, function ( el ) {
			if ( el.classList.contains( 'wp-block-columns' ) ) {
				Array.prototype.forEach.call( el.children, function ( column ) {
					column.classList.add( 'ab-reveal' );
				} );
				return;
			}
			if ( el.classList.contains( 'wp-block-latest-posts' ) ) {
				Array.prototype.forEach.call( el.children, function ( post ) {
					post.classList.add( 'ab-reveal' );
				} );
				return;
			}
			el.classList.add( 'ab-reveal' );
		} );
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

		apply();
		window.addEventListener( 'resize', debounce( apply, 150 ) );
		window.addEventListener( 'scroll', apply, { passive: true } );
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
	 * Reveals .ab-reveal elements as they enter the viewport.
	 */
	function initReveal() {
		var items = document.querySelectorAll( '.ab-reveal' );

		if ( ! items.length ) {
			return;
		}

		// No IntersectionObserver, or reduced motion requested: show everything.
		if (
			! ( 'IntersectionObserver' in window ) ||
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		) {
			Array.prototype.forEach.call( items, function ( item ) {
				item.classList.add( 'is-visible' );
			} );
			return;
		}

		// Stable stagger, set once: each element's delay is its position
		// among its OWN siblings that are also .ab-reveal (so the 3 cards
		// in a row cascade 0/80/160ms relative to each other) rather than
		// its index within whatever batch of entries IntersectionObserver
		// happens to report in one callback — that batch depends on scroll
		// speed and used to make the cascade feel inconsistent.
		Array.prototype.forEach.call( items, function ( item ) {
			var siblings = item.parentElement
				? Array.prototype.filter.call( item.parentElement.children, function ( child ) {
					return child.classList.contains( 'ab-reveal' );
				} )
				: [ item ];
			var position = Math.max( siblings.indexOf( item ), 0 );
			item.style.transitionDelay = Math.min( position, 6 ) * 80 + 'ms';
		} );

		// No unobserve(): visibility stays bound to scroll position for as
		// long as the page is open, so scrolling back up un-reveals a
		// section exactly like scrolling down revealed it.
		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					entry.target.classList.toggle( 'is-visible', entry.isIntersecting );
				} );
			},
			{ rootMargin: '0px 0px -12% 0px', threshold: 0.08 }
		);

		Array.prototype.forEach.call( items, function ( item ) {
			observer.observe( item );
		} );
	}

	/**
	 * Animated stat counters. Give any text block (a Heading, for example)
	 * the "ab-counter-value" class via the block editor's Additional CSS
	 * Class(es) field — no custom HTML/data attributes needed. The script
	 * reads the number already sitting in the text ("250+", "15 years",
	 * "98%") and counts up to it from 0 once the element scrolls into
	 * view, keeping whatever prefix/suffix text surrounded the number.
	 */
	function initCounters() {
		var items = document.querySelectorAll( '.ab-counter-value' );

		if ( ! items.length ) {
			return;
		}

		var parsed = Array.prototype.map.call( items, parseCounter ).filter( Boolean );

		if (
			! ( 'IntersectionObserver' in window ) ||
			window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches
		) {
			return;
		}

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					if ( ! entry.isIntersecting ) {
						return;
					}
					var match = parsed.filter( function ( p ) {
						return p.el === entry.target;
					} )[ 0 ];
					if ( match ) {
						observer.unobserve( entry.target );
						runCount( match );
					}
				} );
			},
			{ threshold: 0.4 }
		);

		parsed.forEach( function ( p ) {
			p.el.textContent = p.prefix + ( 0 ).toFixed( p.decimals ) + p.suffix;
			observer.observe( p.el );
		} );

		/**
		 * Splits an element's text into a leading number plus whatever
		 * prefix/suffix text surrounds it (e.g. "15+ Years" → prefix "",
		 * number 15, suffix "+ Years").
		 */
		function parseCounter( el ) {
			var text = el.textContent.trim();
			var match = text.match( /^(\D*?)(\d[\d.,]*)(\D*)$/ );
			if ( ! match ) {
				return null;
			}
			var numberText = match[ 2 ].replace( /,/g, '' );
			return {
				el: el,
				prefix: match[ 1 ],
				suffix: match[ 3 ],
				target: parseFloat( numberText ),
				decimals: numberText.indexOf( '.' ) > -1 ? numberText.split( '.' )[ 1 ].length : 0,
			};
		}

		function runCount( p ) {
			var duration = 1600;
			var start = null;

			var step = function ( timestamp ) {
				if ( start === null ) {
					start = timestamp;
				}
				var progress = Math.min( ( timestamp - start ) / duration, 1 );
				// easeOutCubic — fast start, gentle settle.
				var eased = 1 - Math.pow( 1 - progress, 3 );
				p.el.textContent = p.prefix + ( p.target * eased ).toFixed( p.decimals ) + p.suffix;

				if ( progress < 1 ) {
					window.requestAnimationFrame( step );
				} else {
					p.el.textContent = p.prefix + p.target.toFixed( p.decimals ) + p.suffix;
				}
			};

			window.requestAnimationFrame( step );
		}
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
