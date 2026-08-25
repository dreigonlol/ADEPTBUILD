<?php
/**
 * Site footer.
 *
 * @package Adeptbuild
 */

$ab_phone   = adeptbuild_option( 'phone' );
$ab_email   = adeptbuild_option( 'email' );
$ab_address = adeptbuild_option( 'address' );
$ab_hours   = adeptbuild_option( 'hours' );
$ab_social  = adeptbuild_social_links();
$ab_about   = adeptbuild_option( 'footer_about' );
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

				<?php if ( $ab_about ) : ?>
					<p><?php echo esc_html( $ab_about ); ?></p>
				<?php endif; ?>

				<?php if ( $ab_social ) : ?>
					<div class="ab-footer-follow">
						<span class="ab-footer-follow__label"><?php esc_html_e( 'Follow us', 'adeptbuild' ); ?></span>
						<div class="ab-social ab-social--outline">
							<?php foreach ( $ab_social as $ab_link ) : ?>
								<a href="<?php echo esc_url( $ab_link['url'] ); ?>" target="_blank" rel="noopener noreferrer">
									<span class="screen-reader-text"><?php echo esc_html( $ab_link['label'] ); ?></span>
									<?php adeptbuild_icon( $ab_link['icon'], 17 ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<div>
				<?php if ( is_active_sidebar( 'footer-1' ) ) : ?>
					<?php dynamic_sidebar( 'footer-1' ); ?>
				<?php elseif ( current_user_can( 'edit_theme_options' ) ) : ?>
					<p class="ab-muted" style="font-size:.8125rem;">
						<?php
						printf(
							/* translators: %s: link to the widgets screen. */
							esc_html__( 'Admin only: add a widget to %s (Footer Column 1).', 'adeptbuild' ),
							'<a href="' . esc_url( admin_url( 'widgets.php' ) ) . '" style="color:inherit;text-decoration:underline;">' . esc_html__( 'Appearance → Widgets', 'adeptbuild' ) . '</a>'
						);
						?>
					</p>
				<?php endif; ?>
			</div>

			<div>
				<?php if ( is_active_sidebar( 'footer-2' ) ) : ?>
					<?php dynamic_sidebar( 'footer-2' ); ?>
				<?php elseif ( has_nav_menu( 'footer-menu' ) ) : ?>
					<h3><?php esc_html_e( 'Links', 'adeptbuild' ); ?></h3>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer-menu',
							'container'      => false,
							'depth'          => 1,
						)
					);
					?>
				<?php elseif ( current_user_can( 'edit_theme_options' ) ) : ?>
					<p class="ab-muted" style="font-size:.8125rem;">
						<?php
						printf(
							/* translators: %s: link to the menus screen. */
							esc_html__( 'Admin only: assign a menu to the Footer Menu location in %s.', 'adeptbuild' ),
							'<a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '" style="color:inherit;text-decoration:underline;">' . esc_html__( 'Appearance → Menus', 'adeptbuild' ) . '</a>'
						);
						?>
					</p>
				<?php endif; ?>
			</div>

			<div>
				<h3><?php esc_html_e( 'Contact', 'adeptbuild' ); ?></h3>
				<ul class="ab-footer-contact">
					<?php if ( $ab_phone ) : ?>
						<li class="ab-footer-contact__phone">
							<?php adeptbuild_icon( 'phone', 18 ); ?>
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
						/* translators: 1: current year, 2: site name. */
						esc_html__( '© %1$s %2$s. All rights reserved.', 'adeptbuild' ),
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
