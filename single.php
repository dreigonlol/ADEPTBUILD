<?php
/**
 * Single blog post.
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
			<div class="ab-layout<?php echo is_active_sidebar( 'sidebar-1' ) ? '' : ' ab-layout--full'; ?>">

				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

					<?php adeptbuild_post_meta(); ?>

					<?php if ( has_post_thumbnail() ) : ?>
						<figure style="margin:0 0 32px; border-radius:var(--ab-radius-lg); overflow:hidden;">
							<?php the_post_thumbnail( 'full' ); ?>
						</figure>
					<?php endif; ?>

					<div class="ab-entry-content">
						<?php
						the_content();

						wp_link_pages(
							array(
								'before' => '<div class="ab-pagination">' . esc_html__( 'Pages:', 'adeptbuild' ),
								'after'  => '</div>',
							)
						);
						?>
					</div>

					<?php if ( has_tag() ) : ?>
						<div class="ab-meta ab-mt-lg"><?php the_tags( '', ' ' ); ?></div>
					<?php endif; ?>

					<nav class="ab-meta ab-mt-lg" aria-label="<?php esc_attr_e( 'Posts', 'adeptbuild' ); ?>">
						<?php previous_post_link( '%link', '&larr; %title' ); ?>
						<?php next_post_link( '%link', '%title &rarr;' ); ?>
					</nav>

					<?php
					if ( comments_open() || get_comments_number() ) {
						comments_template();
					}
					?>

				</article>

				<?php get_sidebar(); ?>

			</div>
		</div>
	</main>

	<?php
endwhile;

get_footer();
