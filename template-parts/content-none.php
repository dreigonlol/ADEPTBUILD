<?php
/**
 * Message shown when there are no posts to display.
 *
 * @package Adeptbuild
 */

?>
<section class="ab-card">
	<h2><?php esc_html_e( 'Nothing Found', 'adeptbuild' ); ?></h2>

	<?php if ( is_search() ) : ?>
		<p><?php esc_html_e( 'Try different keywords or double-check your spelling.', 'adeptbuild' ); ?></p>
		<?php get_search_form(); ?>
	<?php else : ?>
		<p><?php esc_html_e( "There's no published content in this section yet.", 'adeptbuild' ); ?></p>
		<a class="ast-button ab-btn--sm" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php esc_html_e( 'Back to Home', 'adeptbuild' ); ?>
		</a>
	<?php endif; ?>
</section>
