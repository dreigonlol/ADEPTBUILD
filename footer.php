<?php
/**
 * Site footer — three columns (contact info / large logo / sub-pages)
 * plus a bottom bar with copyright, legal links and social links.
 *
 * @package Adeptbuild
 */

$ab_phone      = adeptbuild_option( 'phone' );
$ab_phone_text = adeptbuild_option( 'phone_text' );
$ab_email      = adeptbuild_option( 'email' );
$ab_address    = adeptbuild_option( 'address', '14431 Ventura Blvd. #440, Sherman Oaks, CA 91423' );
$ab_address    = $ab_address ? $ab_address : '14431 Ventura Blvd. #440, Sherman Oaks, CA 91423';
$ab_social     = adeptbuild_social_links();
?>

</div><!-- #content -->

<footer class="site-footer" role="contentinfo">
	<div class="ast-container">

		<div class="ab-footer-main">

			<div class="ab-footer-info">
				<strong><?php esc_html_e( 'Office', 'adeptbuild' ); ?></strong>
				<?php if ( $ab_address ) : ?>
					<a href="https://maps.app.goo.gl/GAXy3HYj2YE4cmbT7" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $ab_address ); ?></a>
				<?php endif; ?>
				<?php if ( $ab_phone ) : ?>
					<a href="<?php echo esc_url( adeptbuild_tel_href( $ab_phone ) ); ?>"><?php echo esc_html( $ab_phone ); ?></a>
				<?php endif; ?>
				<?php if ( $ab_phone_text ) : ?>
					<a href="<?php echo esc_url( adeptbuild_sms_href( $ab_phone_text ) ); ?>"><?php echo esc_html( __( 'Text:', 'adeptbuild' ) . ' ' . $ab_phone_text ); ?></a>
				<?php endif; ?>
				<?php if ( $ab_email ) : ?>
					<a href="<?php echo esc_url( 'mailto:' . $ab_email ); ?>"><?php echo esc_html( $ab_email ); ?></a>
				<?php endif; ?>
			</div>

			<div class="ab-footer-logo">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
				<?php endif; ?>
			</div>

			<nav class="ab-footer-nav" aria-label="<?php esc_attr_e( 'Footer Navigation', 'adeptbuild' ); ?>">
				<?php
				wp_nav_menu(
					array(
						'theme_location' => has_nav_menu( 'footer-menu' ) ? 'footer-menu' : 'primary-menu',
						'container'      => false,
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
				?>
			</nav>

		</div>

		<div class="ab-footer-bottom">
			<p>
				<?php
				$ab_copy = adeptbuild_option( 'footer_copyright' );
				if ( $ab_copy ) {
					echo esc_html( $ab_copy );
				} else {
					printf(
						/* translators: 1: current year, 2: site name. */
						esc_html__( '© %1$s %2$s', 'adeptbuild' ),
						esc_html( gmdate( 'Y' ) ),
						esc_html( get_bloginfo( 'name' ) )
					);
				}
				?>
			</p>

			<ul class="ab-footer-legal">
				<li><a href="<?php echo esc_url( home_url( '/privacy-policy/' ) ); ?>"><?php esc_html_e( 'Privacy Policy', 'adeptbuild' ); ?></a></li>
				<li><a href="<?php echo esc_url( home_url( '/terms-of-service/' ) ); ?>"><?php esc_html_e( 'Terms of Service', 'adeptbuild' ); ?></a></li>
			</ul>

			<?php if ( $ab_social ) : ?>
				<ul class="ab-footer-social">
					<?php foreach ( $ab_social as $ab_link ) : ?>
						<li><a href="<?php echo esc_url( $ab_link['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $ab_link['label'] ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
