<?php
/**
 * Adeptbuild Theme — funciones y definiciones.
 *
 * @package Adeptbuild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADEPTBUILD_VERSION', '1.1.0' );

/**
 * Configuración básica del tema.
 */
function adeptbuild_setup() {

	load_theme_textdomain( 'adeptbuild', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );

	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 70,
			'width'       => 260,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary-menu' => __( 'Menú Principal', 'adeptbuild' ),
			'footer-menu'  => __( 'Menú Pie de Página', 'adeptbuild' ),
			'legal-menu'   => __( 'Menú Legal (barra inferior)', 'adeptbuild' ),
		)
	);

	// Paleta disponible en el editor de bloques.
	add_theme_support(
		'editor-color-palette',
		array(
			array(
				'name'  => __( 'Azul piscina', 'adeptbuild' ),
				'slug'  => 'primary',
				'color' => '#0E7490',
			),
			array(
				'name'  => __( 'Agua', 'adeptbuild' ),
				'slug'  => 'aqua',
				'color' => '#22B8CF',
			),
			array(
				'name'  => __( 'Azul profundo', 'adeptbuild' ),
				'slug'  => 'deep',
				'color' => '#0B2F3E',
			),
			array(
				'name'  => __( 'Arena', 'adeptbuild' ),
				'slug'  => 'accent',
				'color' => '#D99A3E',
			),
			array(
				'name'  => __( 'Gris claro', 'adeptbuild' ),
				'slug'  => 'surface-alt',
				'color' => '#F6F9FA',
			),
			array(
				'name'  => __( 'Blanco', 'adeptbuild' ),
				'slug'  => 'white',
				'color' => '#FFFFFF',
			),
		)
	);
}
add_action( 'after_setup_theme', 'adeptbuild_setup' );

/**
 * Ancho del contenido (usado por WordPress para embeds e imágenes).
 */
function adeptbuild_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'adeptbuild_content_width', 1200 );
}
add_action( 'after_setup_theme', 'adeptbuild_content_width', 0 );

/**
 * Hojas de estilo y scripts.
 */
function adeptbuild_scripts() {

	// Tipografías (Outfit para títulos, Inter para texto).
	wp_enqueue_style(
		'adeptbuild-fonts',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_style( 'adeptbuild-style', get_stylesheet_uri(), array( 'adeptbuild-fonts' ), ADEPTBUILD_VERSION );

	wp_enqueue_script(
		'adeptbuild-navigation',
		get_theme_file_uri( '/assets/js/navigation.js' ),
		array(),
		ADEPTBUILD_VERSION,
		true
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'adeptbuild_scripts' );

/**
 * Preconexión a Google Fonts para acelerar la carga.
 */
function adeptbuild_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type && wp_style_is( 'adeptbuild-fonts', 'queue' ) ) {
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => '',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'adeptbuild_resource_hints', 10, 2 );

/**
 * Áreas de widgets.
 */
function adeptbuild_widgets_init() {

	register_sidebar(
		array(
			'name'          => __( 'Barra lateral del blog', 'adeptbuild' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Se muestra en entradas y archivos del blog.', 'adeptbuild' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);

	for ( $i = 1; $i <= 2; $i++ ) {
		register_sidebar(
			array(
				/* translators: %d: número de columna. */
				'name'          => sprintf( __( 'Pie de página — columna %d', 'adeptbuild' ), $i ),
				'id'            => 'footer-' . $i,
				'description'   => __( 'Columna central del pie de página.', 'adeptbuild' ),
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="widget-title">',
				'after_title'   => '</h3>',
			)
		);
	}
}
add_action( 'widgets_init', 'adeptbuild_widgets_init' );

/**
 * Longitud y terminación del extracto.
 */
function adeptbuild_excerpt_length() {
	return 24;
}
add_filter( 'excerpt_length', 'adeptbuild_excerpt_length' );

function adeptbuild_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'adeptbuild_excerpt_more' );

/**
 * Clases extra en el body para poder afinar estilos.
 */
function adeptbuild_body_classes( $classes ) {
	if ( ! is_active_sidebar( 'sidebar-1' ) || is_page() || is_front_page() ) {
		$classes[] = 'ab-no-sidebar';
	}
	if ( is_front_page() ) {
		$classes[] = 'ab-home';
	}
	return $classes;
}
add_filter( 'body_class', 'adeptbuild_body_classes' );

/**
 * Añade una flecha a los elementos de menú con submenú.
 */
function adeptbuild_menu_caret( $title, $item, $args ) {
	if ( isset( $args->theme_location ) && 'primary-menu' === $args->theme_location
		&& in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
		$title .= adeptbuild_get_icon( 'chevron-down', 14, 'ab-caret' );
	}
	return $title;
}
add_filter( 'nav_menu_item_title', 'adeptbuild_menu_caret', 10, 3 );

require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/customizer.php';
