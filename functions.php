<?php
// Cargar la hoja de estilos principal (style.css)
function adeptbuild_scripts() {
    wp_enqueue_style( 'adeptbuild-style', get_stylesheet_uri(), array(), '1.0.0' );
}
add_action( 'wp_enqueue_scripts', 'adeptbuild_scripts' );

// Configuración básica del tema y registro del menú
function adeptbuild_setup() {
    register_nav_menus( array(
        'primary-menu' => __( 'Menú Principal', 'adeptbuild' ),
    ) );
    add_theme_support( 'custom-logo' );
    add_theme_support( 'title-tag' );
}
add_action( 'after_setup_theme', 'adeptbuild_setup' );