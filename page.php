<?php
/**
 * Static page.
 *
 * @package Adeptbuild
 */

get_header();

while ( have_posts() ) :
	the_post();
	adeptbuild_page_hero( get_the_title() );
	?>

	<main id="primary" class="site-main ab-content-area">
		<div class="ast-container">
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'ab-entry-content' ); ?>>
				<?php
				the_content();

				wp_link_pages(
					array(
						'before' => '<div class="ab-pagination">' . esc_html__( 'Pages:', 'adeptbuild' ),
						'after'  => '</div>',
					)
				);
				?>
			</article>

			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div>
	</main>

	<?php
endwhile;

get_footer();
