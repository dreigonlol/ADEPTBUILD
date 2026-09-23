<?php
/**
 * Reusable template tags and helpers.
 *
 * @package Adeptbuild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns an inline SVG icon from the theme's icon set.
 *
 * @param string $name  Icon name.
 * @param int    $size  Size in pixels.
 * @param string $class Extra CSS classes.
 * @return string SVG markup.
 */
function adeptbuild_get_icon( $name, $size = 20, $class = '' ) {

	$paths = array(
		'phone'         => '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>',
		'mail'          => '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>',
		'message'       => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="13.5" x2="13" y2="13.5"/>',
		'map-pin'       => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
		'clock'         => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
		'chevron-down'  => '<polyline points="6 9 12 15 18 9"/>',
		'arrow-right'   => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
		'check'         => '<polyline points="20 6 9 17 4 12"/>',
		'star'          => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
		'award'         => '<circle cx="12" cy="8" r="6"/><polyline points="15.477 12.89 17 22 12 19 7 22 8.523 12.89"/>',
		'shield'        => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
		'droplet'       => '<path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>',
		'waves'         => '<path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>',
		'ruler'         => '<path d="M16 3l5 5L8 21l-5-5L16 3z"/><path d="M14.5 5.5l2 2"/><path d="M11.5 8.5l2 2"/><path d="M8.5 11.5l2 2"/><path d="M5.5 14.5l2 2"/>',
		'hammer'        => '<path d="M15 12l-8.5 8.5a2.12 2.12 0 0 1-3-3L12 9"/><path d="M17.64 15L22 10.64"/><path d="M20.91 11.7l-1.25-1.25a2 2 0 0 1 0-2.83l.82-.82-3.53-3.53-2.83 2.83 3.54 3.53"/>',
		'wrench'        => '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>',
		'sun'           => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>',
		'leaf'          => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6"/>',
		'sparkles'      => '<path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M19 15l.9 2.1L22 18l-2.1.9L19 21l-.9-2.1L16 18l2.1-.9L19 15z"/>',
		'headset'       => '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>',
		'calendar'      => '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
		'user'          => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
		'facebook'      => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
		'instagram'     => '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>',
		'whatsapp'      => '<path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>',
		'close'         => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	$fill = in_array( $name, array( 'facebook', 'star' ), true ) ? 'currentColor' : 'none';

	return sprintf(
		'<svg class="ab-icon%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="%3$s" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%4$s</svg>',
		$class ? ' ' . esc_attr( $class ) : '',
		(int) $size,
		esc_attr( $fill ),
		$paths[ $name ]
	);
}

/**
 * Prints an icon from the theme's icon set.
 */
function adeptbuild_icon( $name, $size = 20, $class = '' ) {
	echo adeptbuild_get_icon( $name, $size, $class ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped -- theme's own SVG.
}

/**
 * Reads a Customizer option with a fallback default.
 */
function adeptbuild_option( $key, $default = '' ) {
	return get_theme_mod( 'adeptbuild_' . $key, $default );
}

/**
 * Normalized "tel:" link from a human-readable phone number.
 */
function adeptbuild_tel_href( $phone ) {
	return 'tel:' . preg_replace( '/[^0-9+]/', '', $phone );
}

/**
 * Normalized "sms:" link from a human-readable phone number.
 */
function adeptbuild_sms_href( $phone ) {
	return 'sms:' . preg_replace( '/[^0-9+]/', '', $phone );
}

/**
 * Google Tag Manager — container script, injected as high in <head> as
 * possible per Google's own install instructions. The GTM ID is the only
 * thing configured here; Google Ads conversion/remarketing tags and any
 * click or form-submit triggers are set up entirely inside the GTM
 * container itself (tagmanager.google.com), no further theme code needed.
 */
function adeptbuild_gtm_head() {
	$gtm_id = adeptbuild_option( 'gtm_id' );
	if ( ! $gtm_id ) {
		return;
	}
	?>
	<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','<?php echo esc_js( $gtm_id ); ?>');</script>
	<?php
}
add_action( 'wp_head', 'adeptbuild_gtm_head', 1 );

/**
 * Google Tag Manager — noscript fallback, required immediately after the
 * opening <body> tag per Google's install instructions.
 */
function adeptbuild_gtm_body() {
	$gtm_id = adeptbuild_option( 'gtm_id' );
	if ( ! $gtm_id ) {
		return;
	}
	?>
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<?php
}
add_action( 'wp_body_open', 'adeptbuild_gtm_body', 1 );

/**
 * Social network links configured in the Customizer.
 *
 * @return array List of arrays with `icon`, `url` and `label` keys.
 */
function adeptbuild_social_links() {

	$networks = array(
		'facebook'  => __( 'Facebook', 'adeptbuild' ),
		'instagram' => __( 'Instagram', 'adeptbuild' ),
		'whatsapp'  => __( 'WhatsApp', 'adeptbuild' ),
	);

	$links = array();

	foreach ( $networks as $key => $label ) {
		$url = adeptbuild_option( $key . '_url' );
		if ( $url ) {
			$links[] = array(
				'icon'  => $key,
				'url'   => $url,
				'label' => $label,
			);
		}
	}

	return $links;
}

/**
 * Post meta row (date, author, category).
 */
function adeptbuild_post_meta() {
	?>
	<div class="ab-meta">
		<span><?php adeptbuild_icon( 'calendar', 14 ); ?><?php echo esc_html( get_the_date() ); ?></span>
		<span><?php adeptbuild_icon( 'user', 14 ); ?><?php the_author(); ?></span>
		<?php
		$category = get_the_category();
		if ( ! empty( $category ) ) :
			?>
			<span><?php echo esc_html( $category[0]->name ); ?></span>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Simple inner-page header with just the title.
 */
function adeptbuild_page_hero( $title = '' ) {
	$title      = $title ? $title : get_the_title();
	$is_home    = is_front_page();
	$video_path = get_theme_file_path( '/assets/img/videohome.mp4' );
	$has_video  = $is_home && file_exists( $video_path );
	?>
	<section class="ab-page-hero<?php echo $has_video ? ' ab-page-hero--video' : ''; ?>">
		<?php if ( $has_video ) : ?>
			<video
				class="ab-page-hero__video"
				src="<?php echo esc_url( get_theme_file_uri( '/assets/img/videohome.mp4' ) ); ?>"
				autoplay
				loop
				muted
				playsinline
				preload="auto"
				fetchpriority="high"
				aria-hidden="true"></video>
			<div class="ab-page-hero__overlay" aria-hidden="true"></div>
		<?php endif; ?>
		<div class="ast-container">
			<h1 class="ab-reveal"><?php echo esc_html( $title ); ?></h1>
		</div>
	</section>
	<?php
}

/**
 * Maps a primary-menu submenu item's title to its mega-menu thumbnail in
 * assets/img/. Add an entry here when a new submenu item should show an
 * image; anything without a match just renders without one.
 *
 * @param string $title Menu item title, as typed in wp-admin.
 * @return string Image URL, or '' if there's no match.
 */
function adeptbuild_menu_item_image( $title ) {

	static $map = array(
		'full home renovation' => 'Full Home Renovation.jpg',
		'adus'                  => 'ADUS.jpg',
		'decking'               => 'DECKING.jpg',
		'pools'                 => 'POOLS.jpg',
		'pergolas'              => 'PERGOLAS.jpeg',
		'landscaping'           => 'LADNSCAPING.jpeg',
		'agnew project'         => 'AGNEW.jpeg',
		'ensley project'        => 'ENSLEY.jpeg',
		'santa monica project'  => 'SANTA MONICA.jpg',
		'sheens project'        => 'SHEENS.jpg',
	);

	$key = strtolower( trim( $title ) );

	if ( ! isset( $map[ $key ] ) ) {
		return '';
	}

	return get_theme_file_uri( '/assets/img/' ) . rawurlencode( $map[ $key ] );
}

/**
 * Fallback menu shown when no menu has been assigned yet.
 * Only visible to administrators, as a shortcut to the menus screen.
 */
function adeptbuild_menu_fallback() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	echo '<ul class="main-header-menu"><li><a href="' . esc_url( admin_url( 'nav-menus.php' ) ) . '">'
		. esc_html__( 'Create a menu', 'adeptbuild' ) . '</a></li></ul>';
}

/**
 * Decorative wave that separates a dark section from the next one.
 *
 * @param string $modifier Extra class, e.g. 'ab-wave--alt'.
 */
function adeptbuild_wave( $modifier = '' ) {
	?>
	<svg class="ab-wave <?php echo esc_attr( $modifier ); ?>" viewBox="0 0 1440 110" preserveAspectRatio="none" aria-hidden="true" focusable="false">
		<path fill="currentColor" d="M0,64 C240,110 480,110 720,80 C960,50 1200,20 1440,48 L1440,110 L0,110 Z"></path>
	</svg>
	<?php
}
