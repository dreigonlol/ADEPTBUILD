<?php
/**
 * Customizer settings (Appearance → Customize → Adept Build).
 *
 * Only header/footer and site-wide content lives here — contact details,
 * social links and the header CTA button — so the client can edit them
 * without touching code.
 *
 * @package Adeptbuild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers a text-type setting/control in a single call.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 * @param string               $id           Setting id (without prefix).
 * @param string               $section      Target section.
 * @param string               $label        Visible label.
 * @param string               $default      Default value.
 * @param string               $type         text | textarea | url | image.
 */
function adeptbuild_add_field( $wp_customize, $id, $section, $label, $default = '', $type = 'text' ) {

	$sanitize = 'sanitize_text_field';
	if ( 'url' === $type ) {
		$sanitize = 'esc_url_raw';
	} elseif ( 'textarea' === $type ) {
		$sanitize = 'wp_kses_post';
	} elseif ( 'image' === $type ) {
		$sanitize = 'esc_url_raw';
	}

	$wp_customize->add_setting(
		'adeptbuild_' . $id,
		array(
			'default'           => $default,
			'sanitize_callback' => $sanitize,
			'transport'         => 'refresh',
		)
	);

	if ( 'image' === $type ) {
		$wp_customize->add_control(
			new WP_Customize_Image_Control(
				$wp_customize,
				'adeptbuild_' . $id,
				array(
					'label'   => $label,
					'section' => $section,
				)
			)
		);
		return;
	}

	$wp_customize->add_control(
		'adeptbuild_' . $id,
		array(
			'label'   => $label,
			'section' => $section,
			'type'    => 'textarea' === $type ? 'textarea' : ( 'url' === $type ? 'url' : 'text' ),
		)
	);
}

/**
 * Registers the full panel.
 */
function adeptbuild_customize_register( $wp_customize ) {

	$wp_customize->add_panel(
		'adeptbuild_panel',
		array(
			'title'       => __( 'Adept Build', 'adeptbuild' ),
			'description' => __( 'Header, footer and contact settings.', 'adeptbuild' ),
			'priority'    => 20,
		)
	);

	/* ---------------------------------------------------------------------
	 * Contact details
	 * ------------------------------------------------------------------- */
	$wp_customize->add_section(
		'adeptbuild_contact',
		array(
			'title' => __( 'Contact Details', 'adeptbuild' ),
			'panel' => 'adeptbuild_panel',
		)
	);

	adeptbuild_add_field( $wp_customize, 'phone', 'adeptbuild_contact', __( 'Phone (Call)', 'adeptbuild' ), '+1 (555) 123-4567' );
	adeptbuild_add_field( $wp_customize, 'phone_text', 'adeptbuild_contact', __( 'Phone (Text/SMS) — leave empty to hide', 'adeptbuild' ), '' );
	adeptbuild_add_field( $wp_customize, 'email', 'adeptbuild_contact', __( 'Email', 'adeptbuild' ), 'info@adeptbuild.com' );
	adeptbuild_add_field( $wp_customize, 'address', 'adeptbuild_contact', __( 'Address', 'adeptbuild' ), '' );
	adeptbuild_add_field( $wp_customize, 'hours', 'adeptbuild_contact', __( 'Business Hours', 'adeptbuild' ), __( 'Mon - Fri · 8:00 AM - 6:00 PM', 'adeptbuild' ) );
	adeptbuild_add_field(
		$wp_customize,
		'contact_shortcode',
		'adeptbuild_contact',
		__( 'Contact form shortcode (Contact Form 7, WPForms…)', 'adeptbuild' ),
		''
	);

	/* ---------------------------------------------------------------------
	 * Social links
	 * ------------------------------------------------------------------- */
	$wp_customize->add_section(
		'adeptbuild_social',
		array(
			'title' => __( 'Social Links', 'adeptbuild' ),
			'panel' => 'adeptbuild_panel',
		)
	);

	adeptbuild_add_field( $wp_customize, 'facebook_url', 'adeptbuild_social', 'Facebook', '', 'url' );
	adeptbuild_add_field( $wp_customize, 'instagram_url', 'adeptbuild_social', 'Instagram', '', 'url' );
	adeptbuild_add_field( $wp_customize, 'whatsapp_url', 'adeptbuild_social', 'WhatsApp', '', 'url' );
	adeptbuild_add_field( $wp_customize, 'google_maps_url', 'adeptbuild_social', __( 'Google Maps (shown in the header instead of WhatsApp)', 'adeptbuild' ), 'https://maps.app.goo.gl/Xrm4UWWqjy95AXHt7', 'url' );

	/* ---------------------------------------------------------------------
	 * Header
	 * ------------------------------------------------------------------- */
	$wp_customize->add_section(
		'adeptbuild_header',
		array(
			'title' => __( 'Header', 'adeptbuild' ),
			'panel' => 'adeptbuild_panel',
		)
	);

	adeptbuild_add_field( $wp_customize, 'cta_text', 'adeptbuild_header', __( 'Button text', 'adeptbuild' ), __( 'Free Estimate', 'adeptbuild' ) );
	adeptbuild_add_field( $wp_customize, 'cta_url', 'adeptbuild_header', __( 'Button link', 'adeptbuild' ), '/contact-us/', 'url' );

	/* ---------------------------------------------------------------------
	 * Footer
	 * ------------------------------------------------------------------- */
	$wp_customize->add_section(
		'adeptbuild_footer',
		array(
			'title' => __( 'Footer', 'adeptbuild' ),
			'panel' => 'adeptbuild_panel',
		)
	);

	adeptbuild_add_field( $wp_customize, 'footer_about', 'adeptbuild_footer', __( 'About text', 'adeptbuild' ), '', 'textarea' );
	adeptbuild_add_field( $wp_customize, 'footer_copyright', 'adeptbuild_footer', __( 'Copyright notice (leave empty for the default)', 'adeptbuild' ), '' );

	/* ---------------------------------------------------------------------
	 * Analytics
	 * ------------------------------------------------------------------- */
	$wp_customize->add_section(
		'adeptbuild_analytics',
		array(
			'title'       => __( 'Analytics', 'adeptbuild' ),
			'panel'       => 'adeptbuild_panel',
			'description' => __( 'Google Ads conversion tags, remarketing and event tracking are configured inside the GTM container itself (tagmanager.google.com) — pasting the ID here is the only code-side step needed.', 'adeptbuild' ),
		)
	);

	adeptbuild_add_field( $wp_customize, 'gtm_id', 'adeptbuild_analytics', __( 'Google Tag Manager container ID (e.g. GTM-XXXXXXX)', 'adeptbuild' ), '' );

	// Live-refresh the site title and tagline in the preview.
	$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';
}
add_action( 'customize_register', 'adeptbuild_customize_register' );

/**
 * Script that live-refreshes the Customizer preview without reloading.
 */
function adeptbuild_customize_preview_js() {
	wp_enqueue_script(
		'adeptbuild-customizer-preview',
		get_theme_file_uri( '/assets/js/customizer-preview.js' ),
		array( 'customize-preview', 'jquery' ),
		ADEPTBUILD_VERSION,
		true
	);
}
add_action( 'customize_preview_init', 'adeptbuild_customize_preview_js' );
