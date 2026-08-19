<?php
/**
 * Mensaje cuando no hay entradas que mostrar.
 *
 * @package Adeptbuild
 */

?>
<section class="ab-card">
	<h2><?php esc_html_e( 'No hemos encontrado nada', 'adeptbuild' ); ?></h2>

	<?php if ( is_search() ) : ?>
		<p><?php esc_html_e( 'Prueba con otras palabras o revisa la ortografía de la búsqueda.', 'adeptbuild' ); ?></p>
		<?php get_search_form(); ?>
	<?php else : ?>
		<p><?php esc_html_e( 'Todavía no hay contenido publicado en esta sección.', 'adeptbuild' ); ?></p>
		<a class="ast-button ab-btn--sm" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php esc_html_e( 'Volver al inicio', 'adeptbuild' ); ?>
		</a>
	<?php endif; ?>
</section>
