<?php
/**
 * Home intro inside the video hero.
 *
 * The front page content starts with a Group block that carries the CSS
 * class "ab-proto-intro" (headline, copy, buttons, Google review badge).
 * Visually it has to sit right under the hero title, on top of the video.
 *
 * It used to be positioned with JavaScript (setHeroIntroOverlap()), which
 * measured the page after it was painted and then moved the block up. That
 * move was recorded as a large layout shift (CLS) and forced a synchronous
 * reflow. Instead, this file renders that block directly inside the hero,
 * on the server, and removes it from the regular page content, so the
 * browser lays it out correctly on the very first paint.
 *
 * The block is still edited normally in the block editor; only the place
 * where it is printed changes.
 *
 * @package Adeptbuild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CSS class that identifies the intro block in the front page content.
 */
const ADEPTBUILD_HOME_INTRO_CLASS = 'ab-proto-intro';

/**
 * Whether a parsed block carries the intro class.
 *
 * @param array $block Parsed block.
 * @return bool
 */
function adeptbuild_is_home_intro_block( $block ) {
	if ( empty( $block['attrs']['className'] ) ) {
		return false;
	}
	$classes = preg_split( '/\s+/', (string) $block['attrs']['className'] );
	return in_array( ADEPTBUILD_HOME_INTRO_CLASS, $classes, true );
}

/**
 * Finds the intro block in a list of parsed blocks, searching nested blocks too.
 *
 * @param array $blocks Parsed blocks.
 * @return array|null
 */
function adeptbuild_find_home_intro_block( $blocks ) {
	foreach ( $blocks as $block ) {
		if ( adeptbuild_is_home_intro_block( $block ) ) {
			return $block;
		}
		if ( ! empty( $block['innerBlocks'] ) ) {
			$found = adeptbuild_find_home_intro_block( $block['innerBlocks'] );
			if ( $found ) {
				return $found;
			}
		}
	}
	return null;
}

/**
 * Flag that lets the intro render once inside the hero while it is
 * removed everywhere else.
 *
 * @param bool|null $set Pass a boolean to change the flag.
 * @return bool
 */
function adeptbuild_home_intro_rendering( $set = null ) {
	static $rendering = false;
	if ( null !== $set ) {
		$rendering = (bool) $set;
	}
	return $rendering;
}

/**
 * Prints the intro block. Call it inside adeptbuild_page_hero(), right
 * after the <h1>, within the same container.
 */
function adeptbuild_home_intro() {
	if ( ! is_front_page() ) {
		return;
	}

	$post_id = (int) get_queried_object_id();
	if ( ! $post_id ) {
		return;
	}

	$block = adeptbuild_find_home_intro_block( parse_blocks( (string) get_post_field( 'post_content', $post_id ) ) );
	if ( ! $block ) {
		return;
	}

	adeptbuild_home_intro_rendering( true );
	// Same output the block would have in the page content (render_block runs
	// the block's own filters, so embeds, buttons, etc. render as usual).
	echo render_block( $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	adeptbuild_home_intro_rendering( false );
}

/**
 * Removes the intro block from the regular front page content, since it is
 * already printed inside the hero.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block.
 * @return string
 */
function adeptbuild_remove_home_intro_from_content( $block_content, $block ) {
	if ( adeptbuild_home_intro_rendering() || ! is_front_page() || is_admin() ) {
		return $block_content;
	}
	if ( adeptbuild_is_home_intro_block( $block ) ) {
		return '';
	}
	return $block_content;
}
add_filter( 'render_block', 'adeptbuild_remove_home_intro_from_content', 10, 2 );
