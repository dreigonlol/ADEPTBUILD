<?php
/**
 * Site header.
 *
 * @package Adeptbuild
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'adeptbuild' ); ?></a>

<?php
$ab_phone      = adeptbuild_option( 'phone' );
$ab_phone_text = adeptbuild_option( 'phone_text' );
$ab_email      = adeptbuild_option( 'email' );
$ab_hours      = adeptbuild_option( 'hours' );
$ab_social     = adeptbuild_social_links();
?>

<?php if ( $ab_email || $ab_hours ) : ?>
	<div class="ab-topbar">
		<div class="ast-container">
			<div class="ab-topbar__info">

				<?php if ( $ab_email ) : ?>
					<a class="ab-topbar__item" href="<?php echo esc_url( 'mailto:' . $ab_email ); ?>">
						<?php adeptbuild_icon( 'mail', 14 ); ?><?php echo esc_html( $ab_email ); ?>
					</a>
				<?php endif; ?>

				<?php if ( $ab_hours ) : ?>
					<span class="ab-topbar__item">
						<?php adeptbuild_icon( 'clock', 14 ); ?><?php echo esc_html( $ab_hours ); ?>
					</span>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<header id="masthead" class="site-header ast-primary-header-bar">
	<div class="ast-container">

		<div class="site-branding">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<div>
					<p class="site-title">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a>
					</p>
					<?php $ab_description = get_bloginfo( 'description', 'display' ); ?>
					<?php if ( $ab_description ) : ?>
						<p class="site-description"><?php echo esc_html( $ab_description ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary Navigation', 'adeptbuild' ); ?>">
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary-menu',
					'menu_class'     => 'main-header-menu',
					'menu_id'        => 'primary-menu',
					'container'      => false,
					'depth'          => 3,
					'fallback_cb'    => 'adeptbuild_menu_fallback',
					'walker'         => new Adeptbuild_Mega_Menu_Walker(),
				)
			);
			?>
		</nav>

		<div class="ab-header-actions">
			<?php
			$ab_cta_text     = adeptbuild_option( 'cta_text' );
			$ab_cta_url      = adeptbuild_option( 'cta_url', '/contact-us/' );
			$ab_maps_url     = adeptbuild_option( 'google_maps_url' );
			$ab_header_social = array_filter(
				$ab_social,
				function ( $ab_link ) {
					return 'whatsapp' !== $ab_link['icon'];
				}
			);
			?>
			<?php if ( $ab_cta_text ) : ?>
				<a class="ast-button ab-btn--accent ab-btn--sm" href="<?php echo esc_url( $ab_cta_url ); ?>">
					<?php echo esc_html( $ab_cta_text ); ?>
				</a>
			<?php endif; ?>

			<?php if ( $ab_header_social || $ab_maps_url ) : ?>
				<div class="ab-header-social">
					<?php foreach ( $ab_header_social as $ab_link ) : ?>
						<a href="<?php echo esc_url( $ab_link['url'] ); ?>" target="_blank" rel="noopener noreferrer">
							<span class="screen-reader-text"><?php echo esc_html( $ab_link['label'] ); ?></span>
							<?php adeptbuild_icon( $ab_link['icon'], 16 ); ?>
						</a>
					<?php endforeach; ?>

					<?php if ( $ab_maps_url ) : ?>
						<a href="<?php echo esc_url( $ab_maps_url ); ?>" target="_blank" rel="noopener noreferrer" class="ab-header-social__maps">
							<span class="screen-reader-text"><?php esc_html_e( 'Find us on Google Maps', 'adeptbuild' ); ?></span>
							<?php adeptbuild_icon( 'map-pin', 16 ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<button
				class="menu-toggle"
				aria-controls="site-navigation"
				aria-expanded="false"
				aria-label="<?php esc_attr_e( 'Open menu', 'adeptbuild' ); ?>">
				<span class="menu-toggle__bars" aria-hidden="true">
					<span></span><span></span><span></span>
				</span>
			</button>
		</div>

	</div>
</header>

<?php
$ab_whatsapp = adeptbuild_option( 'whatsapp_url' );
?>
<div class="ab-floating-ctas">
	<?php if ( $ab_cta_text ) : ?>
		<a class="ab-floating-cta" href="<?php echo esc_url( $ab_cta_url ); ?>">
			<span><?php echo esc_html( $ab_cta_text ); ?></span>
		</a>
	<?php endif; ?>

	<?php if ( $ab_phone ) : ?>
		<a class="ab-floating-pill ab-floating-pill--call" href="<?php echo esc_url( adeptbuild_tel_href( $ab_phone ) ); ?>">
			<span class="ab-floating-pill__icon"><?php adeptbuild_icon( 'phone', 18 ); ?></span>
			<span class="ab-floating-pill__text"><?php echo esc_html( $ab_phone ); ?></span>
		</a>
	<?php endif; ?>

	<?php if ( $ab_phone_text ) : ?>
		<a class="ab-floating-pill ab-floating-pill--text" href="<?php echo esc_url( adeptbuild_sms_href( $ab_phone_text ) ); ?>">
			<span class="ab-floating-pill__icon"><?php adeptbuild_icon( 'message', 18 ); ?></span>
			<span class="ab-floating-pill__text"><?php echo esc_html( $ab_phone_text ); ?></span>
		</a>
	<?php elseif ( $ab_whatsapp ) : ?>
		<a class="ab-floating-pill ab-floating-pill--text" href="<?php echo esc_url( $ab_whatsapp ); ?>" target="_blank" rel="noopener noreferrer">
			<span class="ab-floating-pill__icon"><?php adeptbuild_icon( 'whatsapp', 18 ); ?></span>
			<span class="ab-floating-pill__text"><?php esc_html_e( 'WhatsApp', 'adeptbuild' ); ?></span>
		</a>
	<?php endif; ?>
</div>

<div id="content" class="site-content">
