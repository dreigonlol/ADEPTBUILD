<?php
/**
 * Custom nav walker for the primary menu.
 *
 * Turns each top-level dropdown ("Services", "Featured", ...) into an
 * image-card mega menu, matching submenu items to a thumbnail via
 * adeptbuild_menu_item_image(). Items without a matching image simply
 * render as a text-only card, so the menu keeps working as new items are
 * added in wp-admin.
 *
 * @package Adeptbuild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Adeptbuild_Mega_Menu_Walker extends Walker_Nav_Menu {

	/**
	 * Tags the immediate submenu of a top-level item as a mega menu, so it
	 * picks up the CSS grid layout instead of the plain narrow dropdown.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$class   = ( 0 === $depth ) ? 'sub-menu mega-menu' : 'sub-menu';
		$output .= '<ul class="' . esc_attr( $class ) . '">';
	}

	/**
	 * Renders mega-menu items (depth 1) as image cards. Everything else —
	 * top-level items and any deeper nesting — keeps the core markup.
	 */
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		if ( 1 !== $depth ) {
			parent::start_el( $output, $item, $depth, $args, $id );
			return;
		}

		$classes   = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;

		$image = adeptbuild_menu_item_image( $item->title );

		$output .= '<li class="' . esc_attr( implode( ' ', array_filter( $classes ) ) ) . '">';
		$output .= '<a href="' . esc_url( $item->url ) . '" class="mega-menu__link">';

		if ( $image ) {
			$output .= '<span class="mega-menu__thumb"><img src="' . esc_url( $image ) . '" alt="" loading="lazy"></span>';
		}

		$output .= '<span class="mega-menu__label">' . esc_html( $item->title ) . '</span>';
		$output .= '</a>';
	}

	/**
	 * Closes a mega-menu <li> opened above — no per-item description, so
	 * there's nothing else to close first.
	 */
	public function end_el( &$output, $item, $depth = 0, $args = null ) {
		if ( 1 !== $depth ) {
			parent::end_el( $output, $item, $depth, $args );
			return;
		}
		$output .= '</li>';
	}
}
