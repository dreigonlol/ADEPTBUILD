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
		splitMenuAroundLogo();
		setHeaderHeight();
		setHeroIntroOverlap();
		initMobileMenu();
		autoTagReveals();
		initReveal();
		initCounters();
		initSliders();
		initWordRotators();
		initCarousels();
		initPortfolioSliders();
	} );

	/**
	 * Prev/next buttons for any ".ab-slider" (a row of video cards, ...) —
	 * the track itself already scrolls and snaps with no JS at all (CSS
	 * scroll-snap), so this only adds the optional buttons on top: scroll
	 * by one card's width, and disable whichever end is already fully
	 * scrolled to. The buttons stay hidden (see the CSS) until
	 * ".ab-slider--js" is added here, so a slider never shows controls
	 * that don't work. For a single-slide-at-a-time carousel (the
	 * portfolio one), see ".ab-carousel"/initCarousels() below instead —
	 * different enough (auto-advance, always exactly one active slide)
	 * that it isn't just another ".ab-slider".
	 */
	function initSliders() {
		var sliders = document.querySelectorAll( '.ab-slider' );

		Array.prototype.forEach.call( sliders, function ( slider ) {
			var track = slider.querySelector( '.ab-slider__track' );
			var prev = slider.querySelector( '.ab-slider__prev' );
			var next = slider.querySelector( '.ab-slider__next' );

			if ( ! track || ! prev || ! next ) {
				return;
			}

			var step = function () {
				var item = track.querySelector( '.ab-slider__item' );
				var gap = parseFloat( getComputedStyle( track ).columnGap || 20 );
				return item ? item.getBoundingClientRect().width + gap : track.clientWidth;
			};

			var updateDisabled = function () {
				var max = track.scrollWidth - track.clientWidth;
				prev.disabled = track.scrollLeft <= 1;
				next.disabled = track.scrollLeft >= max - 1;
			};

			prev.addEventListener( 'click', function () {
				track.scrollBy( { left: -step(), behavior: 'smooth' } );
			} );
			next.addEventListener( 'click', function () {
				track.scrollBy( { left: step(), behavior: 'smooth' } );
			} );

			track.addEventListener( 'scroll', debounce( updateDisabled, 100 ), { passive: true } );
			window.addEventListener( 'resize', debounce( updateDisabled, 150 ) );

			updateDisabled();
			slider.classList.add( 'ab-slider--js' );
		} );
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

		Array.prototype.forEach.call( rotators, function ( rotator ) {
			var items = rotator.querySelectorAll( '.ab-word-rotate__item' );

			if ( items.length < 2 ) {
				return;
			}

			rotator.classList.add( 'ab-word-rotate--js' );
			items[ 0 ].classList.add( 'is-active' );

			if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
				return;
			}

			var index = 0;

			setInterval( function () {
				var current = items[ index ];
				index = ( index + 1 ) % items.length;
				var next = items[ index ];

				current.classList.remove( 'is-active' );
				current.classList.add( 'is-leaving' );
				next.classList.add( 'is-active' );

				setTimeout( function () {
					current.classList.remove( 'is-leaving' );
				}, 650 );
			}, 2400 );
		} );
	}

	/**
	 * Single-slide-at-a-time carousel (".ab-carousel", currently the
	 * portfolio one) — advances on its own every few seconds and via the
	 * prev/next arrows, pausing the auto-advance while a visitor's pointer
	 * or keyboard focus is on it and skipping it entirely under
	 * reduced-motion (the arrows/dots still work either way). The track is
	 * translated by whole slide-widths — see ".ab-carousel__track" in
	 * style.css. Dots are built here, one per slide, so the markup only
	 * has to list the slides themselves.
	 */
	function initCarousels() {
		var carousels = document.querySelectorAll( '.ab-carousel' );

		Array.prototype.forEach.call( carousels, function ( carousel ) {
			var track = carousel.querySelector( '.ab-carousel__track' );
			var slides = track ? track.querySelectorAll( '.ab-carousel__slide' ) : [];
			var prev = carousel.querySelector( '.ab-carousel__prev' );
			var next = carousel.querySelector( '.ab-carousel__next' );
			var dotsWrap = carousel.querySelector( '.ab-carousel__dots' );

			if ( ! track || slides.length < 2 ) {
				return;
			}

			var index = 0;
			var timer = null;
			var dots = [];

			var goTo = function ( i ) {
				index = ( i + slides.length ) % slides.length;
				track.style.transform = 'translateX(-' + ( index * 100 ) + '%)';
				dots.forEach( function ( dot, dotIndex ) {
					dot.classList.toggle( 'is-active', dotIndex === index );
				} );
			};

			if ( dotsWrap ) {
				Array.prototype.forEach.call( slides, function ( slide, i ) {
					var dot = document.createElement( 'button' );
					dot.type = 'button';
					dot.className = 'ab-carousel__dot';
					dot.setAttribute( 'aria-label', 'Go to slide ' + ( i + 1 ) );
					dot.addEventListener( 'click', function () {
						goTo( i );
						restart();
					} );
					dotsWrap.appendChild( dot );
					dots.push( dot );
				} );
			}

			var stop = function () {
				if ( timer ) {
					clearInterval( timer );
					timer = null;
				}
			};

			var start = function () {
				if ( window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
					return;
				}
				stop();
				timer = setInterval( function () {
					goTo( index + 1 );
				}, 5000 );
			};

			var restart = function () {
				stop();
				start();
			};

			if ( prev ) {
				prev.addEventListener( 'click', function () {
					goTo( index - 1 );
					restart();
				} );
			}
			if ( next ) {
				next.addEventListener( 'click', function () {
					goTo( index + 1 );
					restart();
				} );
			}

			carousel.addEventListener( 'mouseenter', stop );
			carousel.addEventListener( 'mouseleave', start );
			carousel.addEventListener( 'focusin', stop );
			carousel.addEventListener( 'focusout', function ( event ) {
				if ( ! carousel.contains( event.relatedTarget ) ) {
					start();
				}
			} );

			goTo( 0 );
			start();
		} );
	}

	/**
	 * PORTFOLIO SLIDER (".ab-pfslider") — a single fading image with prev/
	 * next arrows on either side, a counter, and a row of thin indicators
	 * below it. Unlike ".ab-carousel" above, the whole slide is a link
	 * (see ".ab-pfslider__link" in style.css) and the image itself can be
	 * dragged left/right (mouse, touch, pen — Pointer Events) to change
	 * project, with an elastic follow while dragging and a real click
	 * cancelled afterwards so a drag-release over the link doesn't
	 * navigate. Auto-play pauses on hover, on keyboard focus, and whenever
	 * the section scrolls out of view (IntersectionObserver), and is
	 * skipped entirely under reduced-motion (controls still work).
	 */
	function initPortfolioSliders() {
		var sliders = document.querySelectorAll( '.ab-pfslider' );

		Array.prototype.forEach.call( sliders, function ( slider ) {
			var slides = slider.querySelectorAll( '.ab-pfslider__slide' );
			if ( slides.length < 1 ) {
				return;
			}

			var prev = slider.querySelector( '.ab-pfslider__prev' );
			var next = slider.querySelector( '.ab-pfslider__next' );
			var dotsWrap = slider.querySelector( '.ab-pfslider__dots' );
			var counter = slider.querySelector( '.ab-pfslider__counter' );
			var interval = parseInt( slider.getAttribute( 'data-interval' ), 10 ) || 5500;
			var reduced = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

			var index = 0;
			var timer = null;
			var visible = true;
			var dots = [];

			var pad = function ( n ) {
				return n < 10 ? '0' + n : String( n );
			};

			var goTo = function ( i ) {
				index = ( i + slides.length ) % slides.length;

				Array.prototype.forEach.call( slides, function ( slide, n ) {
					slide.classList.toggle( 'is-active', n === index );
					slide.style.transform = '';
				} );
				dots.forEach( function ( dot, n ) {
					dot.classList.toggle( 'is-active', n === index );
					dot.setAttribute( 'aria-current', n === index ? 'true' : 'false' );
				} );
				if ( counter ) {
					counter.textContent = pad( index + 1 ) + ' / ' + pad( slides.length );
				}
			};

			var stop = function () {
				if ( timer ) {
					clearInterval( timer );
					timer = null;
				}
			};

			var start = function () {
				stop();
				if ( reduced || ! visible || slides.length < 2 ) {
					return;
				}
				timer = setInterval( function () {
					goTo( index + 1 );
				}, interval );
			};

			var restart = function () {
				stop();
				start();
			};

			// Indicators: one per slide, built here so the editor's markup
			// only has to list the projects.
			if ( dotsWrap && slides.length > 1 ) {
				Array.prototype.forEach.call( slides, function ( slide, i ) {
					var dot = document.createElement( 'button' );
					dot.type = 'button';
					dot.className = 'ab-pfslider__dot';
					dot.setAttribute( 'aria-label', 'Go to project ' + ( i + 1 ) );
					dot.addEventListener( 'click', function () {
						goTo( i );
						restart();
					} );
					dotsWrap.appendChild( dot );
					dots.push( dot );
				} );
			}

			if ( prev ) {
				prev.addEventListener( 'click', function () {
					goTo( index - 1 );
					restart();
				} );
			}
			if ( next ) {
				next.addEventListener( 'click', function () {
					goTo( index + 1 );
					restart();
				} );
			}

			// Pause while the visitor is looking at or navigating the slider.
			slider.addEventListener( 'mouseenter', stop );
			slider.addEventListener( 'mouseleave', start );
			slider.addEventListener( 'focusin', stop );
			slider.addEventListener( 'focusout', function ( event ) {
				if ( ! slider.contains( event.relatedTarget ) ) {
					start();
				}
			} );

			// Keyboard: ← → arrows while focus is inside the slider.
			slider.addEventListener( 'keydown', function ( event ) {
				if ( 'ArrowLeft' === event.key ) {
					goTo( index - 1 );
					restart();
				} else if ( 'ArrowRight' === event.key ) {
					goTo( index + 1 );
					restart();
				}
			} );

			// --- Image drag -------------------------------------------------
			// One path for mouse/touch/pen via Pointer Events. The active
			// slide follows the finger damped (0.32) for tactile response;
			// past the threshold it changes project, and if there was real
			// movement the following click is cancelled in the capture phase
			// — without that, releasing over the link covering the slide
			// would navigate to the project.
			var viewport = slider.querySelector( '.ab-pfslider__viewport' );

			if ( viewport && window.PointerEvent && slides.length > 1 ) {
				var THRESHOLD = 60;
				var startX = 0;
				var delta = 0;
				var dragging = false;
				var moved = false;

				viewport.addEventListener( 'pointerdown', function ( event ) {
					if ( 0 !== event.button || event.target.closest( '.ab-pfslider__btn' ) ) {
						return;
					}
					dragging = true;
					moved = false;
					delta = 0;
					startX = event.clientX;
					stop();
					viewport.classList.add( 'is-dragging' );
					slides[ index ].style.transition = 'none';
					if ( viewport.setPointerCapture ) {
						viewport.setPointerCapture( event.pointerId );
					}
				} );

				viewport.addEventListener( 'pointermove', function ( event ) {
					if ( ! dragging ) {
						return;
					}
					delta = event.clientX - startX;
					if ( Math.abs( delta ) > 6 ) {
						moved = true;
					}
					slides[ index ].style.transform = 'translateX(' + ( delta * 0.32 ) + 'px)';
				} );

				var release = function () {
					if ( ! dragging ) {
						return;
					}
					dragging = false;
					viewport.classList.remove( 'is-dragging' );
					slides[ index ].style.transition = '';
					slides[ index ].style.transform = '';

					if ( Math.abs( delta ) > THRESHOLD ) {
						goTo( delta < 0 ? index + 1 : index - 1 );
					}
					start();
					setTimeout( function () {
						moved = false;
					}, 60 );
				};

				viewport.addEventListener( 'pointerup', release );
				viewport.addEventListener( 'pointercancel', release );
				viewport.addEventListener( 'dragstart', function ( event ) {
					event.preventDefault();
				} );
				viewport.addEventListener( 'click', function ( event ) {
					if ( moved ) {
						event.preventDefault();
						event.stopPropagation();
					}
				}, true );
			}

			// No timers running while the section is off-screen: the visitor
			// would otherwise come back to a slider advanced 8 positions.
			if ( 'IntersectionObserver' in window ) {
				new IntersectionObserver( function ( entries ) {
					visible = entries[ 0 ].isIntersecting;
					if ( visible ) {
						start();
					} else {
						stop();
					}
				}, { threshold: 0.25 } ).observe( slider );
			}

			slider.classList.add( 'ab-pfslider--js' );
			goTo( 0 );
			start();
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
	 * Pulls ".home .ab-proto-intro" up to sit right under the front-page
	 * video hero's title — close enough to read as one continuous block
	 * (like a single line break between them), not a fixed vh-based
	 * guess or a calc() built on 100vh, both of which drift out of sync
	 * with what's actually on screen (scrollbar width, mobile browser
	 * chrome collapsing/expanding, etc. all make "100vh" an unreliable
	 * stand-in for the hero's real rendered height).
	 *
	 * Measures both ends directly instead: temporarily zero the intro's
	 * margin to find where it would naturally land, compare that to
	 * where the title's own bottom edge actually is, and the difference
	 * is exactly the (negative) margin needed — no assumptions, no unit
	 * mixing, just two real measurements.
	 *
	 * Also guarantees whatever comes after the intro (".ab-proto-services")
	 * never starts before the hero's own video box actually ends: on a
	 * screen/zoom combination where the intro's own text is short, pulling
	 * it up to sit under the title could otherwise leave its bottom edge
	 * short of the hero's bottom edge — meaning the next section would
	 * start while the hero's video is technically still going, instead of
	 * cleanly after it. Padding the intro out (never trimming — only ever
	 * adding) to reach that point keeps the boundary exactly at the
	 * video's real end on every screen, regardless of how tall the
	 * intro's own content happens to be there.
	 */
	function setHeroIntroOverlap() {
		var intro = document.querySelector( '.home .ab-proto-intro' );
		var title = document.querySelector( '.ab-page-hero--video h1' );
		var hero = document.querySelector( '.ab-page-hero--video' );
		if ( ! intro || ! title || ! hero ) {
			return;
		}

		var GAP = 4; // Breathing room below the title, roughly one line break.

		var apply = function () {
			intro.style.marginTop = '0px';
			intro.style.paddingBottom = '0px';

			var naturalTop = intro.getBoundingClientRect().top + window.scrollY;
			var titleBottom = title.getBoundingClientRect().bottom + window.scrollY;
			intro.style.marginTop = ( titleBottom + GAP - naturalTop ) + 'px';

			var heroBottom = hero.getBoundingClientRect().bottom + window.scrollY;
			var introBottom = intro.getBoundingClientRect().bottom + window.scrollY;
			if ( introBottom < heroBottom ) {
				intro.style.paddingBottom = ( heroBottom - introBottom ) + 'px';
			}
		};

		apply();
		window.addEventListener( 'resize', debounce( apply, 150 ) );
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
