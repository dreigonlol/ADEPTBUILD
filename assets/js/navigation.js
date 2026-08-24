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
		initReveal();
	} );

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
			document.documentElement.style.setProperty(
				'--ab-header-h',
				header.offsetHeight + 'px'
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

		var observer = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry, index ) {
					if ( ! entry.isIntersecting ) {
						return;
					}

					// Gentle stagger between sibling elements.
					entry.target.style.transitionDelay = index * 80 + 'ms';
					entry.target.classList.add( 'is-visible' );
					observer.unobserve( entry.target );
				} );
			},
			{ rootMargin: '0px 0px -12% 0px', threshold: 0.08 }
		);

		Array.prototype.forEach.call( items, function ( item ) {
			observer.observe( item );
		} );
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
