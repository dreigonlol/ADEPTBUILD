/**
 * Vista previa en vivo del Personalizador.
 *
 * @package Adeptbuild
 */

( function ( $ ) {
	'use strict';

	wp.customize( 'blogname', function ( value ) {
		value.bind( function ( to ) {
			$( '.site-title a, .ab-footer-brand strong' ).text( to );
		} );
	} );

	wp.customize( 'blogdescription', function ( value ) {
		value.bind( function ( to ) {
			$( '.site-description' ).text( to );
		} );
	} );
} )( jQuery );
