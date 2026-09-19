<?php
/**
 * Adeptbuild Theme — setup and definitions.
 *
 * @package Adeptbuild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ADEPTBUILD_VERSION', '1.7.54' );

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

	// Montserrat for headings/CTAs, Geist for body text, Poppins for h1/h2
	// only (visual stand-in for "Posterama 2001", which is self-hosted via
	// @font-face in style.css — Poppins Bold is the fallback for as long
	// as that local file isn't reachable).
	wp_enqueue_style(
		'adeptbuild-fonts',
		'https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=Geist:wght@300;400;500;600;700&family=PT+Sans:ital,wght@0,400;0,700;1,400;1,700&family=Poppins:wght@400;600;700;800&display=swap',
		array(),
		null
	);

	wp_enqueue_style( 'adeptbuild-style', get_stylesheet_uri(), array( 'adeptbuild-fonts' ), ADEPTBUILD_VERSION );

	wp_enqueue_script(
		'adeptbuild-navigation',
		get_theme_file_uri( '/assets/js/navigation.js' ),
		array(),
		ADEPTBUILD_VERSION,
		true
	);

	wp_enqueue_script(
		'adeptbuild-lite-embed',
		get_theme_file_uri( '/assets/js/lite-embed.js' ),
		array(),
		ADEPTBUILD_VERSION,
		true
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'adeptbuild_scripts' );

/**
 * Preconnects to Google Fonts to speed up loading.
 */
function adeptbuild_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type && wp_style_is( 'adeptbuild-fonts', 'queue' ) ) {
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => '',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'adeptbuild_resource_hints', 10, 2 );

/**
 * Preloads the home hero video so the browser starts fetching it right
 * away, in parallel with CSS/fonts, instead of only discovering it once it
 * parses that far down the HTML.
 */
function adeptbuild_preload_hero_video() {
	if ( ! is_front_page() ) {
		return;
	}

	$video_path = get_theme_file_path( '/assets/img/videohome.mp4' );
	if ( ! file_exists( $video_path ) ) {
		return;
	}

	printf(
		'<link rel="preload" as="video" type="video/mp4" href="%s">' . "\n",
		esc_url( get_theme_file_uri( '/assets/img/videohome.mp4' ) )
	);
}
add_action( 'wp_head', 'adeptbuild_preload_hero_video', 1 );

/**
 * Positions the hero intro (title/button/Google review) over the tail of
 * the home video — same math as setHeroIntroOverlap() in navigation.js,
 * duplicated here as an inline script instead of relying on that external
 * file. navigation.js loads behind a long queue of third-party footer
 * scripts (CallRail, Fluent Forms, LiteSpeed's own delayed-JS handling…),
 * so by the time it finally runs, the intro has already been sitting in
 * its unpositioned spot (pushed below the hero) for a very visible moment.
 * Printed inline at the very front of wp_footer (priority 1, before any
 * plugin's own footer scripts) so it runs as early as the DOM allows,
 * with no external file to wait on. navigation.js still runs its own copy
 * afterwards — harmless (same numbers either way) — and that copy is the
 * one that keeps it correct on resize.
 */
function adeptbuild_inline_hero_intro_overlap() {
	if ( ! is_front_page() ) {
		return;
	}
	?>
	<script data-no-litespeed-delay="adeptbuild-hero-overlap">
	/* adeptbuild-hero-overlap: excluded from LiteSpeed's "JS Delayed"
	   optimization (Page Optimization → JS Settings → JS Delayed Excludes)
	   — this must run immediately, not after the visitor's first click/
	   scroll/mousemove, or the hero text sits unpositioned until then. */
	( function () {
		var intro = document.querySelector( '.home .ab-proto-intro' );
		var title = document.querySelector( '.ab-page-hero--video h1' );
		var hero  = document.querySelector( '.ab-page-hero--video' );
		if ( ! intro || ! title || ! hero ) {
			return;
		}
		var GAP = 4;
		intro.style.marginTop = '0px';
		intro.style.paddingBottom = '0px';
		var naturalTop = intro.getBoundingClientRect().top + window.scrollY;
		var titleBottom = title.getBoundingClientRect().bottom + window.scrollY;
		intro.style.marginTop = ( titleBottom + GAP - naturalTop ) + 'px';
		var heroBottom = hero.getBoundingClientRect().bottom + window.scrollY;
		var introBottom = intro.getBoundingClientRect().bottom + window.scrollY;
		if ( introBottom < heroBottom ) {
			intro.style.paddingBottom = ( heroBottom - introBottom ) + 'px';
		}
	} )();
	</script>
	<?php
}
add_action( 'wp_footer', 'adeptbuild_inline_hero_intro_overlap', 1 );

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
 * Appends a caret icon to primary menu items that have a submenu.
 */
function adeptbuild_menu_caret( $title, $item, $args ) {
	if ( isset( $args->theme_location ) && 'primary-menu' === $args->theme_location
		&& in_array( 'menu-item-has-children', (array) $item->classes, true ) ) {
		$title .= adeptbuild_get_icon( 'chevron-down', 14, 'ab-caret' );
	}
	return $title;
}
add_filter( 'nav_menu_item_title', 'adeptbuild_menu_caret', 10, 3 );

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
