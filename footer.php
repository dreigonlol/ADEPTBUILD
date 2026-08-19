<?php
/**
 * Pie de página del sitio.
 *
 * @package Adeptbuild
 */

$ab_phone   = adeptbuild_option( 'phone' );
$ab_email   = adeptbuild_option( 'email' );
$ab_address = adeptbuild_option( 'address' );
$ab_hours   = adeptbuild_option( 'hours' );
$ab_social  = adeptbuild_social_links();
?>

</div><!-- #content -->

<footer class="site-footer" role="contentinfo">
	<div class="ast-container">

		<div class="ab-footer-widgets">

			<div class="ab-footer-brand">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<strong><?php bloginfo( 'name' ); ?></strong>
				<?php endif; ?>

				<p><?php echo esc_html( adeptbuild_option( 'footer_about' ) ); ?></p>

				<?php if ( $ab_social ) : ?>
					<div class="ab-social" style="margin-top:20px;">
						<?php foreach ( $ab_social as $ab_link ) : ?>
							<a href="<?php echo esc_url( $ab_link['url'] ); ?>" target="_blank" rel="noopener noreferrer">
								<span class="screen-reader-text"><?php echo esc_html( $ab_link['label'] ); ?></span>
								<?php adeptbuild_icon( $ab_link['icon'], 16 ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>

			<div>
				<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
					<?php dynamic_sidebar( 'footer-1' ); ?>
				<?php else : ?>
					<h3><?php esc_html_e( 'Servicios', 'adeptbuild' ); ?></h3>
					<ul>
						<li><a href="#servicios"><?php esc_html_e( 'Construcción de piscinas', 'adeptbuild' ); ?></a></li>
						<li><a href="#servicios"><?php esc_html_e( 'Renovación y reformas', 'adeptbuild' ); ?></a></li>
						<li><a href="#servicios"><?php esc_html_e( 'Climatización y spa', 'adeptbuild' ); ?></a></li>
						<li><a href="#servicios"><?php esc_html_e( 'Mantenimiento', 'adeptbuild' ); ?></a></li>
					</ul>
				<?php endif; ?>
			</div>

			<div>
				<?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
					<?php dynamic_sidebar( 'footer-2' ); ?>
				<?php elseif ( has_nav_menu( 'footer-menu' ) ) : ?>
					<h3><?php esc_html_e( 'Enlaces', 'adeptbuild' ); ?></h3>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer-menu',
							'container'      => false,
							'depth'          => 1,
						)
					);
					?>
				<?php else : ?>
					<h3><?php esc_html_e( 'Empresa', 'adeptbuild' ); ?></h3>
					<ul>
						<li><a href="#nosotros"><?php esc_html_e( 'Sobre nosotros', 'adeptbuild' ); ?></a></li>
						<li><a href="#proceso"><?php esc_html_e( 'Cómo trabajamos', 'adeptbuild' ); ?></a></li>
						<li><a href="#proyectos"><?php esc_html_e( 'Proyectos', 'adeptbuild' ); ?></a></li>
						<li><a href="#contacto"><?php esc_html_e( 'Contacto', 'adeptbuild' ); ?></a></li>
					</ul>
				<?php endif; ?>
			</div>

			<div>
				<h3><?php esc_html_e( 'Contacto', 'adeptbuild' ); ?></h3>
				<ul class="ab-footer-contact">
					<?php if ( $ab_phone ) : ?>
						<li>
							<?php adeptbuild_icon( 'phone', 16 ); ?>
							<a href="<?php echo esc_url( adeptbuild_tel_href( $ab_phone ) ); ?>"><?php echo esc_html( $ab_phone ); ?></a>
						</li>
					<?php endif; ?>

					<?php if ( $ab_email ) : ?>
						<li>
							<?php adeptbuild_icon( 'mail', 16 ); ?>
							<a href="<?php echo esc_url( 'mailto:' . $ab_email ); ?>"><?php echo esc_html( $ab_email ); ?></a>
						</li>
					<?php endif; ?>

					<?php if ( $ab_address ) : ?>
						<li><?php adeptbuild_icon( 'map-pin', 16 ); ?><span><?php echo esc_html( $ab_address ); ?></span></li>
					<?php endif; ?>

					<?php if ( $ab_hours ) : ?>
						<li><?php adeptbuild_icon( 'clock', 16 ); ?><span><?php echo esc_html( $ab_hours ); ?></span></li>
					<?php endif; ?>
				</ul>
			</div>

		</div>

		<div class="ab-footer-bottom">
			<p style="margin:0;">
				<?php
				$ab_copy = adeptbuild_option( 'footer_copyright' );
				if ( $ab_copy ) {
					echo esc_html( $ab_copy );
				} else {
					printf(
						/* translators: 1: año actual, 2: nombre del sitio. */
						esc_html__( '© %1$s %2$s. Todos los derechos reservados.', 'adeptbuild' ),
						esc_html( gmdate( 'Y' ) ),
						esc_html( get_bloginfo( 'name' ) )
					);
				}
				?>
			</p>

			<?php if ( has_nav_menu( 'legal-menu' ) ) : ?>
				<?php
				wp_nav_menu(
					array(
						'theme_location' => 'legal-menu',
						'container'      => false,
						'depth'          => 1,
					)
				);
				?>
			<?php endif; ?>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
