<?php
/**
 * Page Header meta box.
 *
 * Lets editors set the large H1 shown at the top of a page separately from
 * the page's saved Title (which still drives the browser tab, the URL slug
 * and admin listings). Falls back to the page title when left empty.
 *
 * @package Adeptbuild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the meta box on the Page editor screen.
 */
function adeptbuild_register_meta_boxes() {
	add_meta_box(
		'adeptbuild_page_header',
		__( 'Page Header', 'adeptbuild' ),
		'adeptbuild_page_header_meta_box',
		'page',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'adeptbuild_register_meta_boxes' );

/**
 * Renders the meta box fields.
 *
 * @param WP_Post $post Current post object.
 */
function adeptbuild_page_header_meta_box( $post ) {

	wp_nonce_field( 'adeptbuild_save_page_header', 'adeptbuild_page_header_nonce' );

	$heading = get_post_meta( $post->ID, '_adeptbuild_hero_title', true );
	?>
	<p>
		<label for="adeptbuild_hero_title"><strong><?php esc_html_e( 'Heading', 'adeptbuild' ); ?></strong></label><br>
		<input
			type="text"
			id="adeptbuild_hero_title"
			name="adeptbuild_hero_title"
			value="<?php echo esc_attr( $heading ); ?>"
			class="large-text"
			placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>">
		<span class="description">
			<?php esc_html_e( 'The large title shown at the top of the page. Leave empty to use the page title above.', 'adeptbuild' ); ?>
		</span>
	</p>
	<?php
}

/**
 * Saves the meta box fields.
 *
 * @param int $post_id Post being saved.
 */
function adeptbuild_save_page_header_meta_box( $post_id ) {

	if ( ! isset( $_POST['adeptbuild_page_header_nonce'] )
		|| ! wp_verify_nonce( wp_unslash( $_POST['adeptbuild_page_header_nonce'] ), 'adeptbuild_save_page_header' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_page', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['adeptbuild_hero_title'] ) ) {
		$heading = sanitize_text_field( wp_unslash( $_POST['adeptbuild_hero_title'] ) );
		if ( $heading ) {
			update_post_meta( $post_id, '_adeptbuild_hero_title', $heading );
		} else {
			delete_post_meta( $post_id, '_adeptbuild_hero_title' );
		}
	}
}
add_action( 'save_post_page', 'adeptbuild_save_page_header_meta_box' );

/**
 * Returns the custom hero heading for a page or post.
 *
 * Priority: the "Heading" meta box field, then a heading block sitting
 * right at the start of the content — a lot of pages migrated from the old
 * Elementor site never got a real WordPress Title, just a heading typed
 * into the page itself — then the saved post title as a last resort.
 *
 * Only a heading that IS the first thing in the content counts: one
 * further down is real body copy, not a stand-in title, and is left alone.
 * Matches any level (H1–H6) since the block editor defaults to H2, not H1,
 * so most of these pages won't actually be using H1.
 *
 * @param int|WP_Post $post Post ID or object. Defaults to the current post.
 * @return string
 */
function adeptbuild_get_page_hero_title( $post = null ) {
	$post_id = $post ? get_post( $post )->ID : get_the_ID();

	$heading = get_post_meta( $post_id, '_adeptbuild_hero_title', true );
	if ( $heading ) {
		return $heading;
	}

	$leading = adeptbuild_get_leading_heading( get_post_field( 'post_content', $post_id ) );
	if ( $leading ) {
		return $leading;
	}

	return get_the_title( $post_id );
}

/**
 * Finds a heading tag sitting at the very start of a block of HTML (past
 * any leading whitespace or a single Gutenberg block comment) and returns
 * its plain-text content, or an empty string if the content doesn't open
 * with a heading.
 *
 * @param string $html Raw post content or already-rendered HTML.
 * @return string
 */
function adeptbuild_get_leading_heading( $html ) {
	$html = ltrim( (string) $html );

	// Raw post_content leads with the block's HTML comment
	// (e.g. "<!-- wp:heading {...} -->") before the actual tag; rendered
	// `the_content` output won't have one, so this is a no-op there.
	$html = preg_replace( '/^<!--.*?-->\s*/s', '', $html, 1 );

	if ( ! preg_match( '/^<h[1-6]\b[^>]*>(.*?)<\/h[1-6]>/is', $html, $matches ) ) {
		return '';
	}

	return trim( wp_strip_all_tags( $matches[1] ) );
}

/**
 * Strips a leading heading from the rendered content when it was reused as
 * the page hero heading, so the same title doesn't appear twice on the page.
 *
 * Hook this in right before a single `the_content()` call with:
 *     add_filter( 'the_content', 'adeptbuild_hide_content_h1_in_hero' );
 * It removes itself after running once.
 *
 * @param string $content Filtered post content.
 * @return string
 */
function adeptbuild_hide_content_h1_in_hero( $content ) {
	remove_filter( 'the_content', 'adeptbuild_hide_content_h1_in_hero' );

	if ( get_post_meta( get_the_ID(), '_adeptbuild_hero_title', true ) ) {
		return $content;
	}

	if ( ! adeptbuild_get_leading_heading( $content ) ) {
		return $content;
	}

	return preg_replace( '/^\s*<h[1-6]\b[^>]*>.*?<\/h[1-6]>/is', '', ltrim( $content ), 1 );
}
