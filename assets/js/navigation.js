/**
 * Adeptbuild Theme — interacciones de la interfaz.
 *
 * - Menú móvil accesible (aria-expanded, cierre con Escape y clic fuera).
 * - Submenús desplegables en móvil.
 * - Sombra del encabezado al hacer scroll.
 * - Animación de aparición de secciones.
 *
 * @package Adeptbuild
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		setHeaderHeight();
		initMobileMenu();
		initStickyHeader();
		initReveal();
	} );

	/**
	 * Publica la altura real del encabezado como variable CSS, para que el
	 * scroll con anclas y el panel móvil se posicionen bien.
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
		};

		apply();
		window.addEventListener( 'resize', debounce( apply, 150 ) );
	}

	/**
	 * Menú de navegación en pantallas pequeñas.
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

		// Cerrar con Escape.
		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && nav.classList.contains( 'is-open' ) ) {
				close();
				toggle.focus();
			}
		} );

		// Cerrar al pulsar fuera del panel.
		document.addEventListener( 'click', function ( event ) {
			if (
				nav.classList.contains( 'is-open' ) &&
				! nav.contains( event.target ) &&
				! toggle.contains( event.target )
			) {
				close();
			}
		} );

		// Cerrar al navegar a un ancla de la misma página.
		nav.addEventListener( 'click', function ( event ) {
			var link = event.target.closest( 'a' );
			if ( link && link.getAttribute( 'href' ) && link.getAttribute( 'href' ).indexOf( '#' ) === 0 ) {
				close();
			}
		} );

		initSubmenus( nav );
	}

	/**
	 * Despliegue de submenús con el enlace padre en móvil.
	 *
	 * @param {HTMLElement} nav Contenedor de la navegación.
	 */
	function initSubmenus( nav ) {
		var parents = nav.querySelectorAll( '.menu-item-has-children > a' );

		Array.prototype.forEach.call( parents, function ( link ) {
			link.addEventListener( 'click', function ( event ) {
				// Solo interceptamos en la vista móvil.
				if ( window.innerWidth > 921 ) {
					return;
				}

				var item = link.parentNode;
				var isExpanded = item.classList.contains( 'is-expanded' );

				// El primer toque abre el submenú en lugar de navegar.
				if ( ! isExpanded ) {
					event.preventDefault();
				}

				item.classList.toggle( 'is-expanded', ! isExpanded );
			} );
		} );
	}

	/**
	 * Añade sombra al encabezado cuando la página se desplaza.
	 */
	function initStickyHeader() {
		var header = document.getElementById( 'masthead' );
		if ( ! header ) {
			return;
		}

		var onScroll = function () {
			header.classList.toggle( 'is-stuck', window.scrollY > 12 );
		};

		onScroll();
		window.addEventListener( 'scroll', onScroll, { passive: true } );
	}

	/**
	 * Revela los elementos .ab-reveal al entrar en pantalla.
	 */
	function initReveal() {
		var items = document.querySelectorAll( '.ab-reveal' );

		if ( ! items.length ) {
			return;
		}

		// Sin IntersectionObserver o con movimiento reducido: mostrar todo.
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

					// Escalonado suave entre elementos hermanos.
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
	 * Utilidad: retrasa la ejecución hasta que dejan de llegar eventos.
	 *
	 * @param {Function} fn    Función a ejecutar.
	 * @param {number}   delay Milisegundos de espera.
	 * @return {Function} Función retardada.
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
