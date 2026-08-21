<?php
/**
 * 404 error page.
 *
 * @package Adeptbuild
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="ast-container ast-container--narrow ab-404">

		<div class="ab-404__code">404</div>

		<h1><?php esc_html_e( 'This page went down the drain', 'adeptbuild' ); ?></h1>

		<p class="ab-lead">
			<?php esc_html_e( "The page you're looking for doesn't exist or has moved. Try the search below or head back home.", 'adeptbuild' ); ?>
		</p>

		<div style="max-width:420px; margin:32px auto;">
			<?php get_search_form(); ?>
		</div>

		<div class="ab-btn-group" style="justify-content:center;">
			<a class="ast-button" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Back to Home', 'adeptbuild' ); ?>
			</a>
			<a class="ast-button ab-btn--outline" href="<?php echo esc_url( home_url( '/contact-us/' ) ); ?>">
				<?php esc_html_e( 'Contact Us', 'adeptbuild' ); ?>
			</a>
		</div>

	</div>
</main>

<?php get_footer(); ?>
