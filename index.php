<?php
/**
 * Main template: blog, archives and search results.
 *
 * @package Adeptbuild
 */

get_header();

if ( is_search() ) {
	/* translators: %s: search query. */
	$ab_title = sprintf( __( 'Search Results for "%s"', 'adeptbuild' ), get_search_query() );
} elseif ( is_archive() ) {
	$ab_title = wp_strip_all_tags( get_the_archive_title() );
} else {
	$ab_title = get_theme_mod( 'adeptbuild_blog_title', __( 'Blog', 'adeptbuild' ) );
}

adeptbuild_page_hero( $ab_title );
?>

<main id="primary" class="site-main ab-content-area">
	<div class="ast-container">
		<div class="ab-layout<?php echo is_active_sidebar( 'sidebar-1' ) ? '' : ' ab-layout--full'; ?>">

			<div>
				<?php if ( have_posts() ) : ?>

					<div class="ab-posts">
						<?php
						while ( have_posts() ) :
							the_post();
							get_template_part( 'template-parts/content', 'card' );
						endwhile;
						?>
					</div>

					<div class="ab-pagination ab-text-center">
						<?php
						the_posts_pagination(
							array(
								'mid_size'  => 2,
								'prev_text' => esc_html__( 'Previous', 'adeptbuild' ),
								'next_text' => esc_html__( 'Next', 'adeptbuild' ),
							)
						);
						?>
					</div>

				<?php else : ?>
					<?php get_template_part( 'template-parts/content', 'none' ); ?>
				<?php endif; ?>
			</div>

			<?php get_sidebar(); ?>

		</div>
	</div>
</main>

<?php get_footer(); ?>
