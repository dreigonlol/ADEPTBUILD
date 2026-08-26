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
 * Priority: the "Heading" meta box field, then the first <h1> found in the
 * content itself, then the saved post title as a last resort.
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

	$content = get_post_field( 'post_content', $post_id );
	if ( $content && preg_match( '/<h1\b[^>]*>(.*?)<\/h1>/is', $content, $matches ) ) {
		return wp_strip_all_tags( $matches[1] );
	}

	return get_the_title( $post_id );
}

/**
 * Strips the first <h1> from the rendered content when it was reused as the
 * page hero heading, so the same heading doesn't appear twice on the page.
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

	return preg_replace( '/<h1\b[^>]*>.*?<\/h1>/is', '', $content, 1 );
}
