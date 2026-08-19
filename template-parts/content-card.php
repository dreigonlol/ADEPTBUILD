<?php
/**
 * Post card used in the blog listing.
 *
 * @package Adeptbuild
 */

?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'ab-post-card' ); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<a class="ab-post-card__thumb" href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
			<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
		</a>
	<?php else : ?>
		<div class="ab-post-card__thumb"></div>
	<?php endif; ?>

	<div class="ab-post-card__body">
		<?php adeptbuild_post_meta(); ?>

		<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>

		<p><?php echo esc_html( get_the_excerpt() ); ?></p>

		<a class="ab-link-arrow" href="<?php the_permalink(); ?>">
			<?php esc_html_e( 'Read More', 'adeptbuild' ); ?>
			<?php adeptbuild_icon( 'arrow-right', 16 ); ?>
		</a>
	</div>

</article>
