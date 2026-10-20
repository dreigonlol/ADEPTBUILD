<?php

/**
 * Adds srcset/sizes to hand-written content images that point to the
 * media library but lack the wp-image-{ID} class, so WordPress can't
 * add them by itself. Without srcset, phones download the original
 * file (often 1600px+) for a ~300px gallery card.
 *
 * - URLs are compared without their scheme, so an http/https mismatch
 *   between the uploads URL and the image src doesn't break the match.
 * - The URL → attachment ID lookup is cached in a transient (a month when
 *   found, a day when not), so it only hits the database once per image.
 *
 * @param string $img           Full <img> tag.
 * @param string $context       Where the tag comes from.
 * @param int    $attachment_id Attachment ID if WordPress already knows it.
 * @return string
 */
function adeptbuild_content_img_srcset( $img, $context, $attachment_id ) {
	if ( 'the_content' !== $context || $attachment_id || false !== strpos( $img, ' srcset=' ) ) {
		return $img;
	}
	if ( ! preg_match( '/ src="([^"]+)"/', $img, $m ) ) {
		return $img;
	}

	$src      = $m[1];
	$uploads  = wp_get_upload_dir();
	$strip    = function ( $url ) {
		return preg_replace( '#^https?:#', '', $url );
	};

	if ( 0 !== strpos( $strip( $src ), $strip( $uploads['baseurl'] ) ) ) {
		return $img;
	}

	$key = 'ab_att2_' . md5( $src );
	$id  = get_transient( $key );
	if ( false === $id ) {
		$id = (int) attachment_url_to_postid( $src );
		set_transient( $key, $id, $id ? MONTH_IN_SECONDS : DAY_IN_SECONDS );
	}
	if ( ! $id ) {
		return $img;
	}

	$meta = wp_get_attachment_metadata( $id );
	if ( empty( $meta['width'] ) ) {
		return $img;
	}

	$img = wp_image_add_srcset_and_sizes( $img, $meta, $id );

	// Lazy images can use sizes="auto": the browser picks the file by the
	// image's real rendered width (gallery cards, bands) instead of 100vw.
	if ( function_exists( 'wp_img_tag_add_auto_sizes' ) ) {
		$img = wp_img_tag_add_auto_sizes( $img );
	}

	return $img;
}
add_filter( 'wp_content_img_tag', 'adeptbuild_content_img_srcset', 9, 3 );

/**
 * Loads stylesheets that aren't needed for the first screen without
 * blocking rendering. The Fluent Forms styles only apply to the
 * consultation modal, which is hidden on load, so the page can paint
 * before they arrive. Uses the media="print" swap: the browser fetches
 * the file at low priority and applies it once loaded.
 *
 * @param string $html   The <link> tag.
 * @param string $handle Stylesheet handle.
 * @return string
 */
function adeptbuild_async_styles( $html, $handle ) {
	$async = array(
		'fluent-form-styles',
		'fluentform-public-default',
	);

	if ( is_admin() || ! in_array( $handle, $async, true ) ) {
		return $html;
	}

	$async_html = preg_replace(
		'/media=([\'"])all\1/',
		'media="print" onload="this.media=\'all\'"',
		$html
	);

	return $async_html . '<noscript>' . $html . '</noscript>';
}
add_filter( 'style_loader_tag', 'adeptbuild_async_styles', 10, 2 );

/**
 * Preloads the home hero poster. With a poster on the video, it becomes the
 * largest element on screen (the LCP element), so the browser should start
 * fetching it right away instead of waiting until it parses the <video>.
 * Phones only ever see the poster (they don't download the video at all).
 */
function adeptbuild_preload_hero_poster() {
	if ( ! is_front_page() || ! file_exists( get_theme_file_path( '/assets/img/hero-poster.webp' ) ) ) {
		return;
	}
	printf(
		'<link rel="preload" as="image" href="%s" imagesrcset="%s" imagesizes="100vw" fetchpriority="high">' . "\n",
		'https://adeptbuild.com/wp-content/uploads/2026/10/adept-cheviot-hills-backyard-cabana-pool-night-2560.webp', 'https://adeptbuild.com/wp-content/uploads/2026/10/adept-cheviot-hills-backyard-cabana-pool-night-1280.webp 1280w, https://adeptbuild.com/wp-content/uploads/2026/10/adept-cheviot-hills-backyard-cabana-pool-night-2560.webp 2560w'
	);
}
add_action( 'wp_head', 'adeptbuild_preload_hero_poster', 1 );

/**
 * Removes stylesheets that plugins load on the public site even though
 * visitors never use them. Each one is render-blocking, which hurts
 * mobile FCP/LCP the most (slow 4G makes every extra request expensive).
 *
 * - dashicons: the WordPress admin icon font.
 * - wp-components: the block editor's UI styles (panels, modals, sidebar
 *   controls). Not needed to display blocks; front-end block styles live
 *   in wp-block-library and the wp-block-* inline styles.
 *
 * Logged-in users keep both, since the admin bar and any front-end
 * editing tools may rely on them.
 *
 * If a stylesheet keeps showing up after this, another stylesheet lists
 * it as a dependency and WordPress re-adds it; find that plugin before
 * removing anything else.
 */
function adeptbuild_dequeue_unused_styles() {
	if ( is_user_logged_in() ) {
		return;
	}

	$handles = array(
		'dashicons',
		'wp-components',
		'godaddy-styles',
		'reviewstream',
	);

	foreach ( $handles as $handle ) {
		wp_dequeue_style( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'adeptbuild_dequeue_unused_styles', 100 );

/**
 * Lazy-loads images in the page content across the whole site.
 *
 * WordPress only adds loading="lazy" automatically to images that have
 * width and height attributes. Most of this site's images live in custom
 * HTML blocks and galleries without them, so every image on a page was
 * downloaded on load (60+ full-size photos on Full Home Renovation alone,
 * enough to make PageSpeed time out).
 *
 * - Front page: every content image is lazy, since all of them sit below
 *   the video hero (the intro is printed inside the hero, not the content).
 * - Other pages: the first content image is left alone, because it may be
 *   visible on load (and even be the LCP element); lazy-loading it would
 *   delay it. Everything after it is lazy.
 *
 * @param string $img     Full <img> tag.
 * @param string $context Where the tag comes from.
 * @return string
 */
function adeptbuild_lazy_content_images( $img, $context ) {
	static $count = 0;

	if ( 'the_content' !== $context || is_admin() ) {
		return $img;
	}

	$count++;

	if ( ! is_front_page() && 1 === $count ) {
		return $img;
	}

	if ( false === strpos( $img, ' loading=' ) ) {
		$img = str_replace( '<img ', '<img loading="lazy" ', $img );
	}

	return $img;
}
add_filter( 'wp_content_img_tag', 'adeptbuild_lazy_content_images', 10, 2 );

/**
 * LocalBusiness structured data for Adept Builders & Design.
 *
 * Instead of printing a separate JSON-LD block (which would create a second
 * organization entity competing with Yoast's), this extends the Organization
 * node Yoast already outputs and also types it as HomeAndConstructionBusiness.
 * Everything stays in a single graph under the same @id.
 *
 * Requirement: Yoast SEO → Settings → Site representation must be set to
 * "Organization", with a name and logo. If it is set to "Person", this
 * filter never runs.
 *
 * @package Adeptbuild
 */
 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
 
/**
 * Adds local business details to Yoast's Organization schema node.
 *
 * @param array $data Organization schema data generated by Yoast.
 * @return array
 */
function adeptbuild_local_business_schema( $data ) {
 
	$data['@type'] = array( 'Organization', 'HomeAndConstructionBusiness' );
 
	// Core business details.
	$data['name']      = 'Adept Builders & Design';
	$data['url']       = home_url( '/' );
	$data['telephone'] = '+1-800-611-1776';
	$data['email']     = 'office@adeptbuild.com';
	$data['priceRange'] = '$$$$';
 
	// Reuse the logo Yoast already registers instead of duplicating it.
	$data['image'] = array( '@id' => home_url( '/#/schema/logo/image/' ) );
 
	// Office address. Must match Google Business Profile, Yelp, Houzz and the site footer exactly.
	$data['address'] = array(
		'@type'           => 'PostalAddress',
		'streetAddress'   => '14431 Ventura Blvd. #440',
		'addressLocality' => 'Sherman Oaks',
		'addressRegion'   => 'CA',
		'postalCode'      => '91423',
		'addressCountry'  => 'US',
	);
 
	$data['geo'] = array(
		'@type'     => 'GeoCoordinates',
		'latitude'  => 34.152454925373576,
		'longitude' => -118.44706016932098,
	);
 
	$data['hasMap'] = 'https://maps.app.goo.gl/sjiqM4j4wsw2fgWx5';
 
	// Business hours. Assumes Monday to Friday; add 'Saturday' / 'Sunday' if needed.
	$data['openingHoursSpecification'] = array(
		array(
			'@type'     => 'OpeningHoursSpecification',
			'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
			'opens'     => '09:00',
			'closes'    => '17:00',
		),
	);
 
	// Contact points: main phone line and the text-message line.
	$data['contactPoint'] = array(
		array(
			'@type'             => 'ContactPoint',
			'telephone'         => '+1-800-611-1776',
			'email'             => 'office@adeptbuild.com',
			'contactType'       => 'customer service',
			'areaServed'        => 'US',
			'availableLanguage' => 'English',
		),
		array(
			'@type'       => 'ContactPoint',
			'telephone'   => '+1-818-579-2578',
			'contactType' => 'sales',
			'description' => 'Text messages',
		),
	);
 
	// Service area. Cities mentioned across the site; edit as needed.
	$cities = array(
		'Los Angeles',
		'Sherman Oaks',
		'Santa Monica',
		'West Hollywood',
		'Hollywood',
		'Bel Air',
		'Burbank',
		'Pasadena',
		'Calabasas',
		'Agoura Hills',
		'Malibu',
		'Torrance',
	);
	$data['areaServed'] = array_map(
		function ( $city ) {
			return array(
				'@type' => 'City',
				'name'  => $city . ', CA',
			);
		},
		$cities
	);
 
	// Services, each linked to its landing page. Update the paths if any URL changes.
	$services = array(
		'Full Home Renovation'    => '/full-home-renovations/',
		'ADUs & Home Additions'   => '/adus/',
		'Deck Construction'       => '/decking/',
		'Custom Pools'            => '/custom-pools/',
		'Pergolas & Patio Covers' => '/pergola/',
		'Landscape Design'        => '/landscape-design/',
	);
	$offers = array();
	foreach ( $services as $name => $path ) {
		$offers[] = array(
			'@type'       => 'Offer',
			'itemOffered' => array(
				'@type' => 'Service',
				'name'  => $name,
				'url'   => home_url( $path ),
			),
		);
	}
	$data['hasOfferCatalog'] = array(
		'@type'           => 'OfferCatalog',
		'name'            => 'Design-Build Services',
		'itemListElement' => $offers,
	);
 
	$data['founder'] = array(
		'@type' => 'Person',
		'name'  => 'Lior Ohayon',
	);
 
	// Official profiles, merged with any already set in Yoast → Site representation.
	$profiles = array(
		'https://www.facebook.com/adeptbuild',
		'https://www.instagram.com/adept_builders/',
		'https://www.yelp.com/biz/adept-builders-and-design-sherman-oaks',
		'https://www.houzz.com/professionals/design-build-firms/adept-builders-and-design-pfvwus-pf~29209487',
	);
	$existing       = isset( $data['sameAs'] ) ? (array) $data['sameAs'] : array();
	$data['sameAs'] = array_values( array_unique( array_merge( $existing, $profiles ) ) );
 
	/*
	 * Optional fields, not set yet:
	 * - 'priceRange'   => '$$$$'
	 * - 'foundingDate' => 'YYYY'
	 * - 'identifier'   => CSLB license number, if one is ever provided.
	 */
 
	return $data;
}
add_filter( 'wpseo_schema_organization', 'adeptbuild_local_business_schema' );

define( 'ADEPTBUILD_VERSION', '1.8.5' );

/**
 * Cache-busting version for theme assets: the file's last-modified time,
 * so browsers fetch a fresh copy every time the file is updated.
 */
function adeptbuild_asset_ver( $relative_path ) {
	$path = get_theme_file_path( $relative_path );
	return file_exists( $path ) ? (string) filemtime( $path ) : ADEPTBUILD_VERSION;
}

/**
 * Basic theme setup.
 */
function adeptbuild_setup() {

	load_theme_textdomain( 'adeptbuild', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );

	add_theme_support(
		'html5',
		array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' )
	);

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 70,
			'width'       => 260,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	register_nav_menus(
		array(
			'primary-menu' => __( 'Primary Menu', 'adeptbuild' ),
			'footer-menu'  => __( 'Footer Menu', 'adeptbuild' ),
			'legal-menu'   => __( 'Legal Menu (bottom bar)', 'adeptbuild' ),
		)
	);

	// Color palette available in the block editor.
	add_theme_support(
		'editor-color-palette',
		array(
			array(
				'name'  => __( 'Signal Blue', 'adeptbuild' ),
				'slug'  => 'primary',
				'color' => '#1046BB',
			),
			array(
				'name'  => __( 'Cyan', 'adeptbuild' ),
				'slug'  => 'aqua',
				'color' => '#57E1F3',
			),
			array(
				'name'  => __( 'Charcoal', 'adeptbuild' ),
				'slug'  => 'deep',
				'color' => '#1A1A1A',
			),
			array(
				'name'  => __( 'Cyan Accent', 'adeptbuild' ),
				'slug'  => 'accent',
				'color' => '#57E1F3',
			),
			array(
				'name'  => __( 'Light Gray', 'adeptbuild' ),
				'slug'  => 'surface-alt',
				'color' => '#F6F9FA',
			),
			array(
				'name'  => __( 'White', 'adeptbuild' ),
				'slug'  => 'white',
				'color' => '#FFFFFF',
			),
		)
	);
}
add_action( 'after_setup_theme', 'adeptbuild_setup' );

/**
 * Tells the browser the real rendered width of the header logo.
 *
 * WordPress outputs sizes="(max-width: 2558px) 100vw, 2558px" for the custom
 * logo, so the browser downloads the full-size file even though the logo is
 * displayed at roughly 260px. The logo is the LCP element on the home page,
 * so this directly affects LCP. Adjust the values if the logo width changes.
 *
 * @param array $attr Custom logo image attributes.
 * @return array
 */
function adeptbuild_custom_logo_sizes( $attr ) {
	$attr['sizes']         = '(max-width: 768px) 180px, 260px';
	$attr['fetchpriority'] = 'high';
	unset( $attr['loading'] );
	return $attr;
}
add_filter( 'get_custom_logo_image_attributes', 'adeptbuild_custom_logo_sizes' );

/**
 * Content width (used by WordPress for embeds and images).
 */
function adeptbuild_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'adeptbuild_content_width', 1200 );
}
add_action( 'after_setup_theme', 'adeptbuild_content_width', 0 );

/**
 * Styles and scripts.
 */
function adeptbuild_scripts() {

	// The only web font is Quicksand, self-hosted via @font-face in style.css.
	wp_enqueue_style( 'adeptbuild-style', get_stylesheet_uri(), array(), adeptbuild_asset_ver( '/style.css' ) );

	wp_enqueue_script(
		'adeptbuild-navigation',
		get_theme_file_uri( '/assets/js/navigation.js' ),
		array(),
		adeptbuild_asset_ver( '/assets/js/navigation.js' ),
		true
	);

	// Only where the content actually has a YouTube embed (none of the
	// static pages do — mostly blog posts).
	if ( is_singular() && false !== stripos( (string) get_post_field( 'post_content', get_queried_object_id() ), 'youtu' ) ) {
		wp_enqueue_script(
			'adeptbuild-lite-embed',
			get_theme_file_uri( '/assets/js/lite-embed.js' ),
			array(),
			adeptbuild_asset_ver( '/assets/js/lite-embed.js' ),
			true
		);
	}

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'adeptbuild_scripts' );

/**
 * Preloads Quicksand — every element on the site uses it, so the browser
 * shouldn't wait until it parses style.css to discover it.
 */
function adeptbuild_preload_font() {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( get_theme_file_uri( '/assets/fonts/Quicksand.woff2' ) )
	);
}
add_action( 'wp_head', 'adeptbuild_preload_font', 1 );

/**
 * Widget areas.
 */
function adeptbuild_widgets_init() {

	register_sidebar(
		array(
			'name'          => __( 'Blog Sidebar', 'adeptbuild' ),
			'id'            => 'sidebar-1',
			'description'   => __( 'Shown on blog posts and archives.', 'adeptbuild' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="widget-title">',
			'after_title'   => '</h3>',
		)
	);

	for ( $i = 1; $i <= 2; $i++ ) {
		register_sidebar(
			array(
				/* translators: %d: column number. */
				'name'          => sprintf( __( 'Footer Column %d', 'adeptbuild' ), $i ),
				'id'            => 'footer-' . $i,
				'description'   => __( 'Middle columns of the footer.', 'adeptbuild' ),
				'before_widget' => '<section id="%1$s" class="widget %2$s">',
				'after_widget'  => '</section>',
				'before_title'  => '<h3 class="widget-title">',
				'after_title'   => '</h3>',
			)
		);
	}
}
add_action( 'widgets_init', 'adeptbuild_widgets_init' );

/**
 * Excerpt length and "read more" marker.
 */
function adeptbuild_excerpt_length() {
	return 24;
}
add_filter( 'excerpt_length', 'adeptbuild_excerpt_length' );

function adeptbuild_excerpt_more() {
	return '&hellip;';
}
add_filter( 'excerpt_more', 'adeptbuild_excerpt_more' );

/**
 * Extra body classes so styles can be fine-tuned.
 */
function adeptbuild_body_classes( $classes ) {
	if ( ! is_active_sidebar( 'sidebar-1' ) || is_page() || is_front_page() ) {
		$classes[] = 'ab-no-sidebar';
	}
	if ( is_front_page() ) {
		$classes[] = 'ab-home';
	}
	return $classes;
}
add_filter( 'body_class', 'adeptbuild_body_classes' );

/**
 * Adds ARIA attributes to primary menu links that open a dropdown.
 */
function adeptbuild_menu_link_attributes( $atts, $item, $args ) {
	if ( isset( $args->theme_location ) && 'primary-menu' === $args->theme_location
		&& in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
		$atts['aria-haspopup'] = 'true';
		$atts['aria-expanded'] = 'false';
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'adeptbuild_menu_link_attributes', 10, 3 );

require_once get_template_directory() . '/inc/template-tags.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/meta-boxes.php';
require_once get_template_directory() . '/inc/class-mega-menu-walker.php';
require_once get_template_directory() . '/inc/home-intro.php';

/* Security header: HSTS (Magi, Oct 10 2026) */
add_action( 'send_headers', function () {
	if ( is_ssl() ) {
		header( 'Strict-Transport-Security: max-age=31536000' );
	}
} );


/**
 * Adept Sections – block pattern category + 10 patterns (real block markup from page 5047).
 * Code Snippets plugin: paste WITHOUT the opening <?php line, set 'Run snippet everywhere'.
 * Content uses nowdoc (<<<'HTML') so $, quotes and backslashes are kept verbatim.
 */
add_action( 'init', function () {
	if ( ! function_exists( 'register_block_pattern' ) ) { return; }
	register_block_pattern_category( 'adept-sections', array( 'label' => __( 'Adept Sections' ) ) );

	register_block_pattern( 'adept-sections/hero', array(
		'title'      => 'Adept – Home Hero (intro)',
		'categories' => array( 'adept-sections' ),
		'content'    => <<<'HTML'
<!-- wp:group {"className":"ab-proto-intro"} -->
<div class="wp-block-group ab-proto-intro"><!-- wp:group {"className":"ab-proto-intro-left"} -->
<div class="wp-block-group ab-proto-intro-left"><!-- wp:heading {"animation":"slideInLeft"} -->
<h2 class="wp-block-heading coblocks-animate" data-coblocks-animation="slideInLeft">Your Home, Beautifully Built</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Full-service renovations and outdoor living spaces across Los Angeles - from concept to completion.</p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<div class="wp-block-buttons">
<button onclick="document.getElementById('adept-modal-landscaping').style.display='flex'" class="ab-btn" style="font-size:clamp(0.8rem,1.6vw,1rem); padding:clamp(12px,1.6vw,18px) clamp(20px,3vw,32px); box-shadow:0 8px 20px rgba(0,0,0,0.15);">REQUEST A PRIVATE CONSULTATION</button>
</div>
<a class="ab-google-rating" href="https://maps.app.goo.gl/Xrm4UWWqjy95AXHt7" target="_blank" rel="noopener noreferrer" aria-label="Read our reviews on Google"> <span class="ab-google-rating__stars"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279L12 19.771l-7.416 3.642 1.48-8.279L.001 9.306l8.332-1.151z"></path></svg><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279L12 19.771l-7.416 3.642 1.48-8.279L.001 9.306l8.332-1.151z"></path></svg><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279L12 19.771l-7.416 3.642 1.48-8.279L.001 9.306l8.332-1.151z"></path></svg><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279L12 19.771l-7.416 3.642 1.48-8.279L.001 9.306l8.332-1.151z"></path></svg><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279L12 19.771l-7.416 3.642 1.48-8.279L.001 9.306l8.332-1.151z"></path></svg></span> <span class="ab-google-rating__text">5.0 on Google</span> </a> <span class="ab-google-rating__extra">+10 years building in LA | Licensed, bonded &amp; insured</span> <style>
.ab-google-rating{display:inline-flex;align-items:center;gap:8px;margin-top:14px;text-decoration:none;transition:opacity .2s ease;}
.ab-google-rating:hover{opacity:.75;}
.ab-google-rating__logo{flex-shrink:0;}
.ab-google-rating__stars{display:inline-flex;align-items:center;gap:2px;color:#F5B400;}
.ab-google-rating__stars svg{flex-shrink:0;}
.ab-google-rating__text{font-family:var(--ab-font-heading);font-weight:300;font-size:0.9375rem;color:#fff;}
.ab-google-rating__extra{display:inline-block;margin-top:14px;margin-left:14px;padding-left:14px;border-left:1px solid rgba(255,255,255,0.35);font-family:var(--ab-font-heading);font-weight:300;font-size:0.875rem;color:rgba(255,255,255,0.8);}
@media (max-width:600px){
  .ab-google-rating__extra{display:block;margin-left:0;padding-left:0;border-left:0;margin-top:8px;}
}
</style>
<!-- /wp:html --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
HTML
	) );

	register_block_pattern( 'adept-sections/stats', array(
		'title'      => 'Adept – Stats Band',
		'categories' => array( 'adept-sections' ),
		'content'    => <<<'HTML'
<!-- wp:html -->
<div class="ab-stats">
<div class="ab-stats__inner">
<div class="ab-stats__rule"></div>
<h2 class="ab-stats__title">Built on Trust. Backed by the Numbers.</h2>
<div class="ab-stats__grid">
<div class="ab-stat">
<p class="ab-stat__num">10<span class="ab-stat__suffix">+</span></p>
<div class="ab-stat__divider"></div>
<div class="ab-stat__label">years in business</div>
</div>
<div class="ab-stat">
<p class="ab-stat__num">15<span class="ab-stat__suffix">+</span></p>
<div class="ab-stat__divider"></div>
<div class="ab-stat__label">LA communities served</div>
</div>
<div class="ab-stat">
<p class="ab-stat__num">200<span class="ab-stat__suffix">+</span></p>
<div class="ab-stat__divider"></div>
<div class="ab-stat__label">projects completed</div>
</div>
<div class="ab-stat">
<p class="ab-stat__num">5.0<span class="ab-stat__suffix ab-stat__suffix--star">&#9733;</span></p>
<div class="ab-stat__divider"></div>
<div class="ab-stat__label">average client rating</div>
</div>
</div>
</div>
<style>
  .ab-stats-section{background:transparent !important;margin:0 !important;}
  .ab-stats{
    --ab-bg:#E3DDD1;      /* fondo beige */
    --ab-ink:#1E1E1E;     /* texto */
    --ab-gold:#C28A35;    /* detalles dorados */
    --ab-font:'Quicksand', -apple-system, BlinkMacSystemFont, sans-serif;
    position:relative;
    width:100vw;
    left:50%;
    right:50%;
    margin-left:-50vw;
    margin-right:-50vw;
    background:var(--ab-bg);
    padding:40px 0 60px !important;
    text-align:center;
    box-sizing:border-box;
  }
  .ab-stats *,.ab-stats *::before,.ab-stats *::after{box-sizing:border-box;}
  .ab-stats__inner{max-width:1320px;margin:0 auto;padding:0 24px;}

  /* Linea dorada arriba del titulo */
  .ab-stats__rule{width:36px;height:1px;background:var(--ab-gold);margin:0 auto 18px;}

  /* Titulo: mayusculas, letras espaciadas, sin negrita */
  .ab-stats__title{
    margin:0 0 36px !important;
    color:var(--ab-ink) !important;
    font-family:var(--ab-font) !important;
    font-weight:400 !important;
    font-size:clamp(1.2rem,1.9vw,1.65rem) !important;
    letter-spacing:.12em !important;
    line-height:1.3 !important;
    text-transform:uppercase;
  }

  .ab-stats__grid{display:grid;grid-template-columns:repeat(4,1fr);gap:32px;}
  .ab-stat{text-align:center;min-width:0;display:flex;flex-direction:column;align-items:center;}

  /* Numeros grandes, delgados */
  .ab-stat__num{
    margin:0;
    color:var(--ab-ink);
    font-family:var(--ab-font);
    font-weight:300;
    font-size:clamp(2.8rem,4.8vw,4.2rem);
    line-height:1;
    letter-spacing:0;
    white-space:nowrap;
  }
  .ab-stat__suffix{font-weight:300;}
  .ab-stat__suffix--star{color:var(--ab-gold);}

  /* Linea dorada entre numero y texto */
  .ab-stat__divider{width:26px;height:1px;background:var(--ab-gold);margin:12px auto 10px;}

  /* Textos de abajo */
  .ab-stat__label{
    color:var(--ab-ink);
    font-family:var(--ab-font);
    font-weight:400;
    font-size:clamp(.95rem,1.15vw,1.05rem);
    letter-spacing:0;
    line-height:1.5;
    max-width:24ch;
    margin:0 auto;
  }

  @media (max-width:820px){
    .ab-stats__grid{grid-template-columns:repeat(2,1fr);gap:36px 24px;}
    .ab-stats{padding:36px 0 48px !important;}
  }
</style>
</div>
<!-- /wp:html -->
HTML
	) );

	register_block_pattern( 'adept-sections/story', array(
		'title'      => 'Adept – We Build Story (word rotator)',
		'categories' => array( 'adept-sections' ),
		'content'    => <<<'HTML'
<!-- wp:group {"className":"ab-story"} -->
<div class="wp-block-group ab-story" data-rotate-hover="we-build"><!-- wp:html -->
<div class="ab-story__bg ab-word-rotate" data-rotate-sync="we-build"><a class="ab-word-rotate__item" href="https://adeptbuild.com/full-home-renovations/#kitchens" tabindex="-1"><img src="https://adeptbuild.com/wp-content/uploads/2026/09/kitchen-remodeling-los-angeles.webp" alt="Kitchen remodeling in Los Angeles"></a> <a class="ab-word-rotate__item" href="https://adeptbuild.com/full-home-renovations/#bathrooms" tabindex="-1"><img src="https://adeptbuild.com/wp-content/uploads/2026/09/bathroom-remodeling-los-angeles.webp" alt="Luxury bathroom remodeling in Los Angeles"></a> <a class="ab-word-rotate__item" href="https://adeptbuild.com/pergola/" tabindex="-1"><img src="https://adeptbuild.com/wp-content/uploads/2026/09/custom-wood-pergola-los-angeles.webp" alt="Custom wood pergola in Los Angeles"></a> <a class="ab-word-rotate__item" href="https://adeptbuild.com/decking/" tabindex="-1"><img src="https://adeptbuild.com/wp-content/uploads/2026/09/deck-construction-los-angeles.webp" alt="Custom deck construction in Los Angeles"></a> <a class="ab-word-rotate__item" href="https://adeptbuild.com/custom-pools/" tabindex="-1"><img src="https://adeptbuild.com/wp-content/uploads/2026/10/custom-pool-los-angeles-freeform-pool-outdoor-kitchen-paver-patio-dusk.webp" alt="Freeform pool with a raised spa at dusk in Los Angeles, facing an outdoor kitchen under a louvered pergola, a lit paver patio, and a modern gabled home"></a> <a class="ab-word-rotate__item" href="https://adeptbuild.com/landscape-design/" tabindex="-1"><img src="https://adeptbuild.com/wp-content/uploads/2026/09/landscape-design-outdoor-living-los-angeles.webp" alt="Backyard landscape design in Los Angeles"></a> <a class="ab-word-rotate__item" href="https://adeptbuild.com/adus/" tabindex="-1"><img src="https://adeptbuild.com/wp-content/uploads/2026/Index/ADUS.webp" alt="Backyard ADU in Los Angeles"></a></div>
<style>
.ab-story__bg .ab-word-rotate__item img{
  filter:brightness(.55);
}
</style>
<!-- /wp:html -->

<!-- wp:group {"className":"ab-story__body"} -->
<div class="wp-block-group ab-story__body"><!-- wp:heading {"className":"ab-story__heading","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white"} -->
<h2 class="wp-block-heading ab-story__heading has-white-color has-text-color has-link-color"><span class="ab-story__heading-label">WE BUILD</span><span class="ab-word-rotate" data-rotate-sync="we-build"><a class="ab-word-rotate__item" href="https://adeptbuild.com/full-home-renovations/#kitchens" data-text="Custom kitchen remodeling in Los Angeles, designed around the way you cook, gather and entertain. Bespoke cabinetry, natural stone and refined lighting come together in a space that feels unmistakably yours.">Kitchens</a><a class="ab-word-rotate__item" href="https://adeptbuild.com/full-home-renovations/#bathrooms" data-text="Luxury bathroom renovations that turn everyday routines into a private retreat. Spa-inspired showers, custom vanities and impeccable tile work, crafted with uncompromising attention to detail.">Bathrooms</a><a class="ab-word-rotate__item" href="https://adeptbuild.com/pergola/" data-text="Custom pergolas that frame your outdoor living with architectural presence. From modern louvered roofs to timeless wood designs, each is built to bring shade, style and comfort to your Southern California home.">Pergolas</a><a class="ab-word-rotate__item" href="https://adeptbuild.com/decking/" data-text="Custom decks built with premium hardwoods and composite materials to last for decades. Thoughtfully designed to extend your living space and capture the views your home deserves.">Decks</a><a class="ab-word-rotate__item" href="https://adeptbuild.com/custom-pools/" data-text="Custom pool design and construction that becomes the centerpiece of your backyard. Elegant lines, refined finishes and seamless integration with your landscape create a resort experience at home.">Pools</a><a class="ab-word-rotate__item" href="https://adeptbuild.com/landscape-design/" data-text="Complete backyard transformations, from landscape design and hardscape to outdoor kitchens and fire features. One cohesive vision for outdoor spaces made for living well.">Backyards</a><a class="ab-word-rotate__item" href="https://adeptbuild.com/adus/" data-text="Accessory dwelling units designed and built with the same craftsmanship as a custom home. A refined guest house, rental or private studio that adds lasting value to your property.">ADUs</a></span></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"ab-story__text"} -->
<p class="ab-story__text">Custom kitchen remodeling in Los Angeles, designed around the way you cook, gather and entertain. Bespoke cabinetry, natural stone and refined lighting come together in a space that feels unmistakably yours.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
HTML
	) );

	register_block_pattern( 'adept-sections/timeline', array(
		'title'      => 'Adept – How We Work Timeline',
		'categories' => array( 'adept-sections' ),
		'content'    => <<<'HTML'
<!-- wp:html -->
<div class="ab-timeline">
  <div class="ab-timeline__bg" aria-hidden="true"></div>
  <div class="ab-timeline__scrim" aria-hidden="true"></div>

  <div class="ab-timeline__inner">
    <h2 class="ab-timeline__title">How We Work</h2>

    <div class="ab-timeline__track">
      <div class="ab-timeline__line" aria-hidden="true"></div>
      <div class="ab-timeline__fill" aria-hidden="true"></div>

      <div class="ab-tl-step ab-tl-step--left">
        <div class="ab-tl-step__side">
          <div class="ab-tl-card">
            <div class="ab-tl-card__head"><span class="ab-tl-card__num">01</span><h3 class="ab-tl-card__title">Discover</h3></div>
            <p class="ab-tl-card__text">We learn how you live, what you want to change, and what the home needs to become.</p>
          </div>
        </div>
        <div class="ab-tl-step__mid"><span class="ab-tl-dot"><i></i></span></div>
        <div class="ab-tl-step__side ab-tl-step__side--empty"></div>
      </div>

      <div class="ab-tl-step ab-tl-step--right">
        <div class="ab-tl-step__side ab-tl-step__side--empty"></div>
        <div class="ab-tl-step__mid"><span class="ab-tl-dot"><i></i></span></div>
        <div class="ab-tl-step__side">
          <div class="ab-tl-card">
            <div class="ab-tl-card__head"><span class="ab-tl-card__num">02</span><h3 class="ab-tl-card__title">Envision</h3></div>
            <p class="ab-tl-card__text">Our team develops the design direction, materials, functionality and project scope.</p>
          </div>
        </div>
      </div>

      <div class="ab-tl-step ab-tl-step--left">
        <div class="ab-tl-step__side">
          <div class="ab-tl-card">
            <div class="ab-tl-card__head"><span class="ab-tl-card__num">03</span><h3 class="ab-tl-card__title">Design</h3></div>
            <p class="ab-tl-card__text">Plans, selections, specifications and project details are developed.</p>
          </div>
        </div>
        <div class="ab-tl-step__mid"><span class="ab-tl-dot"><i></i></span></div>
        <div class="ab-tl-step__side ab-tl-step__side--empty"></div>
      </div>

      <div class="ab-tl-step ab-tl-step--right">
        <div class="ab-tl-step__side ab-tl-step__side--empty"></div>
        <div class="ab-tl-step__mid"><span class="ab-tl-dot"><i></i></span></div>
        <div class="ab-tl-step__side">
          <div class="ab-tl-card">
            <div class="ab-tl-card__head"><span class="ab-tl-card__num">04</span><h3 class="ab-tl-card__title">Build</h3></div>
            <p class="ab-tl-card__text">Our project team manages construction, trades and execution.</p>
          </div>
        </div>
      </div>

      <div class="ab-tl-step ab-tl-step--left">
        <div class="ab-tl-step__side">
          <div class="ab-tl-card">
            <div class="ab-tl-card__head"><span class="ab-tl-card__num">05</span><h3 class="ab-tl-card__title">Reveal</h3></div>
            <p class="ab-tl-card__text">The finished home is delivered with the details reviewed and completed.</p>
          </div>
        </div>
        <div class="ab-tl-step__mid"><span class="ab-tl-dot"><i></i></span></div>
        <div class="ab-tl-step__side ab-tl-step__side--empty"></div>
      </div>
    </div>
  </div>

  <style>
  .ab-timeline{position:relative;overflow:hidden;background:#16181a;padding:clamp(56px,7vw,110px) clamp(16px,4vw,48px) clamp(70px,8vw,130px);width:100vw;margin-left:calc(50% - 50vw);margin-right:calc(50% - 50vw);box-sizing:border-box;}
  .ab-timeline *,.ab-timeline *::before,.ab-timeline *::after{box-sizing:border-box;}
  .ab-timeline__bg{position:absolute;inset:0;background-image:url('https://adeptbuild.com/wp-content/uploads/2023/07/Louise_Ave.webp');background-size:cover;background-position:center;opacity:.38;}
  .ab-timeline__scrim{position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.80) 0%,rgba(0,0,0,.62) 45%,rgba(0,0,0,.82) 100%);}
  .ab-timeline__inner{position:relative;max-width:1240px;margin:0 auto;}
  .ab-timeline__title{margin:0 0 clamp(48px,6vw,88px) !important;text-align:center;color:#fff !important;font-weight:300;text-transform:uppercase;letter-spacing:.04em;line-height:1.2;font-size:clamp(1.5rem,3.2vw,2.5rem);text-wrap:pretty;}
  .ab-timeline__track{position:relative;}
  .ab-timeline__line,.ab-timeline__fill{position:absolute;left:50%;width:2px;margin-left:-1px;}
  .ab-timeline__line{top:0;bottom:0;background:rgba(217,154,62,.28);}
  .ab-timeline__fill{top:0;height:0;background:linear-gradient(180deg,rgba(217,154,62,.55),#D99A3E);box-shadow:0 0 14px rgba(217,154,62,.45);}
  .ab-tl-step{position:relative;display:flex;align-items:center;padding:clamp(14px,2vw,26px) 0;}
  .ab-tl-step__side{flex:1 1 0;min-width:0;}
  .ab-tl-step--left>.ab-tl-step__side:first-child{display:flex;justify-content:flex-end;padding-right:38px;}
  .ab-tl-step--left>.ab-tl-step__side:last-child{padding-left:38px;}
  .ab-tl-step--right>.ab-tl-step__side:first-child{padding-right:38px;}
  .ab-tl-step--right>.ab-tl-step__side:last-child{display:flex;justify-content:flex-start;padding-left:38px;}
  .ab-tl-step__mid{flex:0 0 76px;display:flex;justify-content:center;}
  .ab-tl-dot{position:relative;width:24px;height:24px;border-radius:50%;background:#16181a;border:2px solid rgba(217,154,62,.55);display:flex;align-items:center;justify-content:center;transition:border-color .4s ease,box-shadow .4s ease;}
  .ab-tl-dot>i{width:9px;height:9px;border-radius:50%;background:rgba(217,154,62,.3);transition:background .4s ease;}
  .ab-tl-dot.is-on{border-color:#D99A3E;box-shadow:0 0 0 5px rgba(217,154,62,.18);}
  .ab-tl-dot.is-on>i{background:#D99A3E;}
  .ab-tl-card{position:relative;width:min(100%,520px);padding:clamp(22px,2.4vw,32px);background:rgba(255,255,255,.07);border:1px solid rgba(217,154,62,.55);-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);opacity:0;transform:translateY(26px);transition:opacity .7s ease,transform .7s cubic-bezier(.22,1,.36,1);}
  .ab-tl-card.is-in{opacity:1;transform:none;}
  .ab-tl-card::after{content:"";position:absolute;top:50%;width:12px;height:12px;margin-top:-6px;background:rgba(255,255,255,.07);transform:rotate(45deg);}
  .ab-tl-step--left .ab-tl-card::after{right:-7px;border-top:1px solid rgba(217,154,62,.55);border-right:1px solid rgba(217,154,62,.55);}
  .ab-tl-step--right .ab-tl-card::after{left:-7px;border-bottom:1px solid rgba(217,154,62,.55);border-left:1px solid rgba(217,154,62,.55);}
  .ab-tl-card__head{display:flex;align-items:baseline;gap:12px;margin-bottom:12px;}
  .ab-tl-card__num{font-size:.8125rem;font-weight:400;letter-spacing:.12em;color:#D99A3E;}
  .ab-tl-card__title{margin:0 !important;color:#fff !important;font-size:clamp(1.0625rem,1.6vw,1.375rem);font-weight:300;letter-spacing:.05em;text-transform:uppercase;line-height:1.25;}
  .ab-tl-card__text{margin:0 !important;color:rgba(255,255,255,.88) !important;font-size:clamp(.9375rem,1.1vw,1.0625rem);line-height:1.65;text-wrap:pretty;}
  @media (max-width:780px){
    .ab-timeline__line,.ab-timeline__fill{left:24px;}
    .ab-tl-step{align-items:flex-start;}
    .ab-tl-step__mid{order:-1;flex:0 0 48px;justify-content:flex-start;}
    .ab-tl-step__side{display:block !important;padding:0 !important;}
    .ab-tl-step__side--empty{display:none !important;}
    .ab-tl-card::after{display:none;}
  }
  @media (prefers-reduced-motion:reduce){
    .ab-tl-card{opacity:1;transform:none;transition:none;}
  }
  </style>

  <script>
  (function(){
    var init=function(){
      document.querySelectorAll('.ab-timeline').forEach(function(sec){
        if(sec.dataset.abTlInit)return;
        sec.dataset.abTlInit='1';
        var track=sec.querySelector('.ab-timeline__track');
        var fill=sec.querySelector('.ab-timeline__fill');
        var steps=[].slice.call(sec.querySelectorAll('.ab-tl-step'));
        if(!track||!fill)return;
        var update=function(){
          var vh=window.innerHeight||document.documentElement.clientHeight;
          var r=track.getBoundingClientRect();
          var top=window.pageYOffset||document.documentElement.scrollTop||0;
          var max=(document.documentElement.scrollHeight||document.body.scrollHeight)-vh;
          var remaining=Math.max(0,max-top);
          var anchor=Math.min(vh,vh*0.62+Math.max(0,vh*0.38-remaining));
          var p=Math.max(0,Math.min(1,(anchor-r.top)/Math.max(r.height,1)));
          fill.style.height=(p*r.height)+'px';
          var fillY=r.top+p*r.height;
          steps.forEach(function(step){
            var dot=step.querySelector('.ab-tl-dot'),card=step.querySelector('.ab-tl-card');
            if(!dot)return;
            var d=dot.getBoundingClientRect();
            var on=(d.top+d.height/2)<=fillY+2;
            dot.classList.toggle('is-on',on);
            if(on){if(card){card.classList.add('is-in');}}
          });
        };
        var queued=false;
        var onScroll=function(){
          if(queued)return;queued=true;
          requestAnimationFrame(function(){queued=false;update();});
        };
        window.addEventListener('scroll',onScroll,{passive:true});
        window.addEventListener('resize',onScroll);
        window.addEventListener('load',onScroll);
        update();
      });
    };
    if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',init);}else{init();}
  })();
  </script>
</div>
<!-- /wp:html -->
HTML
	) );

	register_block_pattern( 'adept-sections/service-strips', array(
		'title'      => 'Adept – Service Strips',
		'categories' => array( 'adept-sections' ),
		'content'    => <<<'HTML'
<!-- wp:group {"className":"ab-proto-services"} -->
<div class="wp-block-group ab-proto-services"><!-- wp:group {"className":"ab-service-strips"} -->
<div class="wp-block-group ab-service-strips"><!-- wp:group {"className":"ab-proto-inner"} -->
<div class="wp-block-group ab-proto-inner"><!-- wp:heading {"style":{"elements":{"link":{"color":{"text":"#C28A35"}}},"color":{"text":"#C28A35"}},"animation":"slideInRight"} -->
<h2 class="wp-block-heading has-text-color has-link-color coblocks-animate" id="our-services" style="color:#C28A35" data-coblocks-animation="slideInRight">Our Services</h2>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ab-service-strip"} -->
<div class="wp-block-group ab-service-strip"><!-- wp:image {"id":8428,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="https://adeptbuild.com/wp-content/uploads/2026/10/full-home-interior-renovation-los-angeles-800.webp" alt="Full home interior renovation in Los Angeles" class="wp-image-8428"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"ab-service-strip__content","style":{"elements":{"link":{"color":{"text":"var:preset|color|deep"}}}},"textColor":"deep"} -->
<p class="ab-service-strip__content has-deep-color has-text-color has-link-color"><span class="ab-service-strip__num">01</span><span class="ab-service-strip__label">FULL HOME RENOVATION</span><span class="ab-service-strip__desc">A home renewed, top to bottom.</span></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"ab-service-strip__link"} -->
<div class="wp-block-button ab-service-strip__link"><a class="wp-block-button__link wp-element-button" href="https://adeptbuild.com/full-home-renovations/"><span class="uppercase">Full Home Renovation</span></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ab-service-strip"} -->
<div class="wp-block-group ab-service-strip"><!-- wp:image {"id":8424,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="https://adeptbuild.com/wp-content/uploads/2026/10/ADUS-800.webp" alt="ADU addition built in Los Angeles" class="wp-image-8424"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"ab-service-strip__content"} -->
<p class="ab-service-strip__content"><span class="ab-service-strip__num">02</span><span class="ab-service-strip__label">ADUS</span><span class="ab-service-strip__desc">Extra space, seamlessly added.</span></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"ab-service-strip__link"} -->
<div class="wp-block-button ab-service-strip__link"><a class="wp-block-button__link wp-element-button" href="https://adeptbuild.com/adus/"><span class="uppercase">ADUs</span></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ab-service-strip"} -->
<div class="wp-block-group ab-service-strip"><!-- wp:image {"id":8427,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="https://adeptbuild.com/wp-content/uploads/2026/10/deck-construction-raised-composite-deck-white-pergola-lounge-800.webp" alt="Raised composite deck with a white pergola and dark metal baluster railing, overlooking a lower lounge deck surrounded by trees" class="wp-image-8427"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"ab-service-strip__content"} -->
<p class="ab-service-strip__content"><span class="ab-service-strip__num">03</span><span class="ab-service-strip__label">DECKING</span><span class="ab-service-strip__desc">Outdoor living, built to last.</span></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"ab-service-strip__link"} -->
<div class="wp-block-button ab-service-strip__link"><a class="wp-block-button__link wp-element-button" href="https://adeptbuild.com/decking/"><span class="uppercase">Decking</span></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ab-service-strip"} -->
<div class="wp-block-group ab-service-strip"><!-- wp:image {"id":8426,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="https://adeptbuild.com/wp-content/uploads/2026/10/custom-pool-rectangular-pool-round-spa-turf-pavers-dusk-800.webp" alt="Rectangular pool with a round spa and blue tile waterline at dusk, beside concrete pavers set in turf, a curtained pergola, and a modern single-story home" class="wp-image-8426"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"ab-service-strip__content"} -->
<p class="ab-service-strip__content"><span class="ab-service-strip__num">04</span><span class="ab-service-strip__label">POOLS</span><span class="ab-service-strip__desc">Your own backyard escape.</span></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"ab-service-strip__link"} -->
<div class="wp-block-button ab-service-strip__link"><a class="wp-block-button__link wp-element-button" href="https://adeptbuild.com/custom-pools/"><span class="uppercase">Pools</span></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ab-service-strip"} -->
<div class="wp-block-group ab-service-strip"><!-- wp:image {"id":8431,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="https://adeptbuild.com/wp-content/uploads/2026/10/patio-cover-solid-roof-attached-curtains-outdoor-lounge-pool-800.webp" alt="Attached solid roof patio cover with striped outdoor curtains and a pendant light over a lounge area, beside a small backyard pool at dusk" class="wp-image-8431"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"ab-service-strip__content"} -->
<p class="ab-service-strip__content"><span class="ab-service-strip__num">05</span><span class="ab-service-strip__label">PERGOLAS</span><span class="ab-service-strip__desc">Shade and style, custom-built.</span></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"ab-service-strip__link"} -->
<div class="wp-block-button ab-service-strip__link"><a class="wp-block-button__link wp-element-button" href="https://adeptbuild.com/pergola/"><span class="uppercase">Pergolas</span></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"ab-service-strip"} -->
<div class="wp-block-group ab-service-strip"><!-- wp:image {"id":8429,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="https://adeptbuild.com/wp-content/uploads/2026/10/landscape-design-los-angeles-drought-tolerant-succulents-drip-irrigation-800.webp" alt="Drought-tolerant succulent bed with drip irrigation beside an artificial turf lawn and a metal fence on a Los Angeles hillside" class="wp-image-8429"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"ab-service-strip__content"} -->
<p class="ab-service-strip__content"><span class="ab-service-strip__num">06</span><span class="ab-service-strip__label">BACKYARD BEAUTIFICATION</span><span class="ab-service-strip__desc">Grounds as beautiful as your home.</span></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"ab-service-strip__link"} -->
<div class="wp-block-button ab-service-strip__link"><a class="wp-block-button__link wp-element-button" href="https://adeptbuild.com/landscape-design/"><span class="uppercase">Landscaping</span></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
HTML
	) );

	register_block_pattern( 'adept-sections/service-strip', array(
		'title'      => 'Adept – Service Strip (single)',
		'categories' => array( 'adept-sections' ),
		'content'    => <<<'HTML'
<!-- wp:group {"className":"ab-service-strip"} -->
<div class="wp-block-group ab-service-strip"><!-- wp:image {"id":8428,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="https://adeptbuild.com/wp-content/uploads/2026/10/full-home-interior-renovation-los-angeles-800.webp" alt="Full home interior renovation in Los Angeles" class="wp-image-8428"/></figure>
<!-- /wp:image -->

<!-- wp:paragraph {"className":"ab-service-strip__content","style":{"elements":{"link":{"color":{"text":"var:preset|color|deep"}}}},"textColor":"deep"} -->
<p class="ab-service-strip__content has-deep-color has-text-color has-link-color"><span class="ab-service-strip__num">01</span><span class="ab-service-strip__label">FULL HOME RENOVATION</span><span class="ab-service-strip__desc">A home renewed, top to bottom.</span></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"ab-service-strip__link"} -->
<div class="wp-block-button ab-service-strip__link"><a class="wp-block-button__link wp-element-button" href="https://adeptbuild.com/full-home-renovations/"><span class="uppercase">Full Home Renovation</span></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
HTML
	) );

	register_block_pattern( 'adept-sections/proto-trust', array(
		'title'      => 'Adept – Trust Bar',
		'categories' => array( 'adept-sections' ),
		'content'    => <<<'HTML'
<!-- wp:group {"className":"ab-proto-trust","layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
<div class="wp-block-group ab-proto-trust"><!-- wp:image {"width":"64px","linkDestination":"custom"} -->
<figure class="wp-block-image is-resized"><a href="https://www.yelp.com/biz/adept-builders-and-design-sherman-oaks"><img src="https://adeptbuild.com/wp-content/uploads/2021/05/yelp.webp" alt="Yelp reviews" style="width:64px"/></a></figure>
<!-- /wp:image -->

<!-- wp:image {"width":"64px","linkDestination":"custom"} -->
<figure class="wp-block-image is-resized"><a href="https://www.homeadvisor.com/rated.AdeptBuildersDesign.95969369.html"><img src="https://adeptbuild.com/wp-content/uploads/2021/05/award.webp" alt="HomeAdvisor rating" style="width:64px"/></a></figure>
<!-- /wp:image -->

<!-- wp:image {"width":"64px","linkDestination":"custom"} -->
<figure class="wp-block-image is-resized"><a href="https://www.facebook.com/adeptbuild"><img src="https://adeptbuild.com/wp-content/uploads/2021/05/facebook.webp" alt="Facebook" style="width:64px"/></a></figure>
<!-- /wp:image -->

<!-- wp:image {"width":"64px","linkDestination":"custom"} -->
<figure class="wp-block-image is-resized"><a href="https://www.instagram.com/adept_builders/"><img src="https://adeptbuild.com/wp-content/uploads/2026/09/instagram_icon.webp" alt="Instagram" style="width:64px"/></a></figure>
<!-- /wp:image -->

<!-- wp:image {"width":"64px","linkDestination":"custom"} -->
<figure class="wp-block-image is-resized"><a href="https://www.google.com/maps/place//data=!4m2!3m1!1s0x80c2972b3f0e4793:0x739de5432c732ebb?source=g.page.share"><img src="https://adeptbuild.com/wp-content/uploads/2021/05/google.webp" alt="Google reviews" style="width:64px"/></a></figure>
<!-- /wp:image -->

<!-- wp:image {"width":"64px","linkDestination":"custom"} -->
<figure class="wp-block-image is-resized"><a href="https://www.houzz.com/pro/adeptbuild/adept-builders-and-design"><img src="https://adeptbuild.com/wp-content/uploads/2021/05/houzz.webp" alt="Houzz" style="width:64px"/></a></figure>
<!-- /wp:image -->

<!-- wp:image {"width":"64px","linkDestination":"custom"} -->
<figure class="wp-block-image is-resized"><a href="https://www.thumbtack.com/ca/sherman-oaks/general-contractors/adept-builders-design/service/406909116153552909"><img src="https://adeptbuild.com/wp-content/uploads/2021/05/thumbtack.webp" alt="Thumbtack" style="width:64px"/></a></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->
HTML
	) );

	register_block_pattern( 'adept-sections/cta-photo', array(
		'title'      => 'Adept – Photo CTA Banner',
		'categories' => array( 'adept-sections' ),
		'content'    => <<<'HTML'
<!-- wp:html -->
<style>
.ab-cta-photo{
  position:relative;
  width:100vw;
  left:50%;
  right:50%;
  margin-left:-50vw;
  margin-right:-50vw;
  margin-top:-40px !important;
  margin-bottom:-40px !important;
  height:clamp(380px,42vw,520px);
  overflow:hidden;
  z-index:2;
}
.ab-cta-photo__bg{position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;z-index:1;}
.ab-cta-photo__overlay{position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(10,14,17,0.25);z-index:2;}
.ab-cta-photo__content{position:relative;z-index:3;height:100%;display:flex;align-items:center;justify-content:center;padding:0 24px;box-sizing:border-box;}
.ab-cta-photo__card{background:rgba(15,20,23,0.3);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,0.14);padding:clamp(36px,5vw,52px) clamp(28px,5vw,60px);text-align:center;max-width:540px;border-radius:4px;}
.ab-cta-photo__rule{width:32px;height:1px;background:#D99A3E;margin:0 auto 22px;}
.ab-cta-photo__card h2{margin:0 0 16px;font-size:clamp(1.5rem,3vw,1.9rem);font-weight:300;color:#ffffff;font-family:'Quicksand',sans-serif;}
.ab-cta-photo__card p{margin:0 0 28px;font-size:14.5px;color:rgba(255,255,255,0.8);font-family:'Quicksand',sans-serif;}
</style>

<div class="ab-cta-photo ab-reveal">
  <img class="ab-cta-photo__bg" src="https://adeptbuild.com/wp-content/uploads/2026/Index/ADUS.webp" alt="ADUs Los Angeles" />
  <div class="ab-cta-photo__overlay"></div>
  <div class="ab-cta-photo__content">
    <div class="ab-cta-photo__card">
      <div class="ab-cta-photo__rule"></div>
      <h2>Have a project in mind?</h2>
      <p>Tell us about your space and we'll set up a consultation to talk through the possibilities.</p>
      <button onclick="document.getElementById('adept-modal-landscaping').style.display='flex'" class="ab-btn" style="font-size:clamp(0.8rem,1.6vw,1rem); padding:clamp(12px,1.6vw,18px) clamp(20px,3vw,32px);">REQUEST A PRIVATE CONSULTATION</button>
    </div>
  </div>
</div>
<!-- /wp:html -->
HTML
	) );

	register_block_pattern( 'adept-sections/modal-landscaping', array(
		'title'      => 'Adept – Consultation Modal (Fluent Form 3)',
		'categories' => array( 'adept-sections' ),
		'content'    => <<<'HTML'
<!-- wp:html -->
<div id="adept-modal-landscaping" style="display:none; position:fixed; inset:0; background:rgba(11,47,62,0.65); z-index:99999; align-items:center; justify-content:center; padding:20px; box-sizing:border-box;">
  <div style="background:#fff; border-radius:14px; padding:28px 30px; max-width:440px; width:100%; max-height:88vh; overflow-y:auto; position:relative; box-shadow:0 24px 60px rgba(0,0,0,0.35);">
    <button type="button" class="adept-modal__close" aria-label="Cerrar" style="position:absolute; top:14px; right:16px; background:none; border:none; font-size:1.6rem; line-height:1; color:#7a8a93; cursor:pointer;">-</button>
    <h3 style="margin:0 0 16px 0; text-align:center; font-size:1.15rem; color:#0f2a35;">REQUEST A PRIVATE CONSULTATION</h3>
    [fluentform id="3"]
  </div>
</div>

<script>
(function(){
  var init = function(){
    var modal = document.getElementById('adept-modal-landscaping');
    if(!modal || modal.dataset.abModalInit) return;
    modal.dataset.abModalInit = '1';

    var open = function(e){ if(e) e.preventDefault(); modal.style.display='flex'; document.body.style.overflow='hidden'; };
    var close = function(){ modal.style.display='none'; document.body.style.overflow=''; };

    document.querySelectorAll('a[href="#adept-modal-landscaping"]').forEach(function(link){
      link.addEventListener('click', open);
    });

    var closeBtn = modal.querySelector('.adept-modal__close');
    if(closeBtn) closeBtn.addEventListener('click', close);

    modal.addEventListener('click', function(e){ if(e.target === modal) close(); });
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape' && modal.style.display === 'flex') close(); });
  };
  if(document.readyState === 'loading'){ document.addEventListener('DOMContentLoaded', init); }
  else { init(); }
})();
</script>
<!-- /wp:html -->
HTML
	) );

	register_block_pattern( 'adept-sections/google-rating', array(
		'title'      => 'Adept – Google Rating Pill',
		'categories' => array( 'adept-sections' ),
		'content'    => <<<'HTML'
<!-- wp:html -->
<a class="ab-google-rating" href="https://maps.app.goo.gl/Xrm4UWWqjy95AXHt7" target="_blank" rel="noopener noreferrer" aria-label="Read our reviews on Google"> <span class="ab-google-rating__stars"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279L12 19.771l-7.416 3.642 1.48-8.279L.001 9.306l8.332-1.151z"></path></svg><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279L12 19.771l-7.416 3.642 1.48-8.279L.001 9.306l8.332-1.151z"></path></svg><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279L12 19.771l-7.416 3.642 1.48-8.279L.001 9.306l8.332-1.151z"></path></svg><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279L12 19.771l-7.416 3.642 1.48-8.279L.001 9.306l8.332-1.151z"></path></svg><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.828 1.48 8.279L12 19.771l-7.416 3.642 1.48-8.279L.001 9.306l8.332-1.151z"></path></svg></span> <span class="ab-google-rating__text">5.0 on Google</span> </a> <span class="ab-google-rating__extra">+10 years building in LA | Licensed, bonded &amp; insured</span> <style>
.ab-google-rating{display:inline-flex;align-items:center;gap:8px;margin-top:14px;text-decoration:none;transition:opacity .2s ease;}
.ab-google-rating:hover{opacity:.75;}
.ab-google-rating__logo{flex-shrink:0;}
.ab-google-rating__stars{display:inline-flex;align-items:center;gap:2px;color:#F5B400;}
.ab-google-rating__stars svg{flex-shrink:0;}
.ab-google-rating__text{font-family:var(--ab-font-heading);font-weight:300;font-size:0.9375rem;color:#fff;}
.ab-google-rating__extra{display:inline-block;margin-top:14px;margin-left:14px;padding-left:14px;border-left:1px solid rgba(255,255,255,0.35);font-family:var(--ab-font-heading);font-weight:300;font-size:0.875rem;color:rgba(255,255,255,0.8);}
@media (max-width:600px){
  .ab-google-rating__extra{display:block;margin-left:0;padding-left:0;border-left:0;margin-top:8px;}
}
</style>
<!-- /wp:html -->
HTML
	) );

} );
