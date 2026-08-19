<?php
/**
 * Página de error 404.
 *
 * @package Adeptbuild
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="ast-container ast-container--narrow ab-404">

		<div class="ab-404__code">404</div>

		<h1><?php esc_html_e( 'Esta página se nos fue por el desagüe', 'adeptbuild' ); ?></h1>

		<p class="ab-lead">
			<?php esc_html_e( 'La dirección que buscas no existe o ha cambiado. Prueba con el buscador o vuelve al inicio.', 'adeptbuild' ); ?>
		</p>

		<div style="max-width:420px; margin:32px auto;">
			<?php get_search_form(); ?>
		</div>

		<div class="ab-btn-group" style="justify-content:center;">
			<a class="ast-button" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'Volver al inicio', 'adeptbuild' ); ?>
			</a>
			<a class="ast-button ab-btn--outline" href="<?php echo esc_url( home_url( '/#contacto' ) ); ?>">
				<?php esc_html_e( 'Contactar', 'adeptbuild' ); ?>
			</a>
		</div>

	</div>
</main>

<?php get_footer(); ?>
