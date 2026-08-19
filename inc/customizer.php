<?php
/**
 * Opciones del Personalizador (Apariencia → Personalizar → Adept Build).
 *
 * Todo el contenido editable de la portada, los datos de contacto y las redes
 * sociales viven aquí, para que el cliente no tenga que tocar código.
 *
 * @package Adeptbuild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registra un ajuste de texto en una sola llamada.
 *
 * @param WP_Customize_Manager $wp_customize Instancia del personalizador.
 * @param string               $id           Identificador (sin prefijo).
 * @param string               $section      Sección destino.
 * @param string               $label        Etiqueta visible.
 * @param string               $default      Valor por defecto.
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
 * Registra el panel completo.
 */
function adeptbuild_customize_register( $wp_customize ) {

	$wp_customize->add_panel(
		'adeptbuild_panel',
		array(
			'title'       => __( 'Adept Build', 'adeptbuild' ),
			'description' => __( 'Contenido y datos de contacto del tema.', 'adeptbuild' ),
			'priority'    => 20,
		)
	);

	/* ---------------------------------------------------------------------
	 * Contacto
	 * ------------------------------------------------------------------- */
	$wp_customize->add_section(
		'adeptbuild_contact',
		array(
			'title' => __( 'Datos de contacto', 'adeptbuild' ),
			'panel' => 'adeptbuild_panel',
		)
	);

	adeptbuild_add_field( $wp_customize, 'phone', 'adeptbuild_contact', __( 'Teléfono', 'adeptbuild' ), '+1 (555) 123-4567' );
	adeptbuild_add_field( $wp_customize, 'email', 'adeptbuild_contact', __( 'Correo electrónico', 'adeptbuild' ), 'info@adeptbuild.com' );
	adeptbuild_add_field( $wp_customize, 'address', 'adeptbuild_contact', __( 'Dirección', 'adeptbuild' ), '' );
	adeptbuild_add_field( $wp_customize, 'hours', 'adeptbuild_contact', __( 'Horario', 'adeptbuild' ), __( 'Lun a Vie · 8:00 - 18:00', 'adeptbuild' ) );
	adeptbuild_add_field(
		$wp_customize,
		'contact_shortcode',
		'adeptbuild_contact',
		__( 'Shortcode del formulario (Contact Form 7, WPForms…)', 'adeptbuild' ),
		''
	);

	/* ---------------------------------------------------------------------
	 * Redes sociales
	 * ------------------------------------------------------------------- */
	$wp_customize->add_section(
		'adeptbuild_social',
		array(
			'title' => __( 'Redes sociales', 'adeptbuild' ),
			'panel' => 'adeptbuild_panel',
		)
	);

	adeptbuild_add_field( $wp_customize, 'facebook_url', 'adeptbuild_social', 'Facebook', '', 'url' );
	adeptbuild_add_field( $wp_customize, 'instagram_url', 'adeptbuild_social', 'Instagram', '', 'url' );
	adeptbuild_add_field( $wp_customize, 'whatsapp_url', 'adeptbuild_social', 'WhatsApp', '', 'url' );

	/* ---------------------------------------------------------------------
	 * Botón del encabezado
	 * ------------------------------------------------------------------- */
	$wp_customize->add_section(
		'adeptbuild_header',
		array(
			'title' => __( 'Encabezado', 'adeptbuild' ),
			'panel' => 'adeptbuild_panel',
		)
	);

	adeptbuild_add_field( $wp_customize, 'cta_text', 'adeptbuild_header', __( 'Texto del botón', 'adeptbuild' ), __( 'Presupuesto gratis', 'adeptbuild' ) );
	adeptbuild_add_field( $wp_customize, 'cta_url', 'adeptbuild_header', __( 'Enlace del botón', 'adeptbuild' ), '#contacto', 'url' );

	/* ---------------------------------------------------------------------
	 * Portada — Hero
	 * ------------------------------------------------------------------- */
	$wp_customize->add_section(
		'adeptbuild_hero',
		array(
			'title'       => __( 'Portada · Sección principal', 'adeptbuild' ),
			'description' => __( 'Se muestra en la página de inicio.', 'adeptbuild' ),
			'panel'       => 'adeptbuild_panel',
		)
	);

	adeptbuild_add_field( $wp_customize, 'hero_image', 'adeptbuild_hero', __( 'Imagen de fondo', 'adeptbuild' ), '', 'image' );
	adeptbuild_add_field( $wp_customize, 'hero_badge', 'adeptbuild_hero', __( 'Distintivo superior', 'adeptbuild' ), __( 'Más de 15 años construyendo piscinas', 'adeptbuild' ) );
	adeptbuild_add_field( $wp_customize, 'hero_title', 'adeptbuild_hero', __( 'Título', 'adeptbuild' ), __( 'Diseñamos y construimos', 'adeptbuild' ) );
	adeptbuild_add_field( $wp_customize, 'hero_title_highlight', 'adeptbuild_hero', __( 'Título resaltado', 'adeptbuild' ), __( 'la piscina de tus sueños', 'adeptbuild' ) );
	adeptbuild_add_field( $wp_customize, 'hero_text', 'adeptbuild_hero', __( 'Texto', 'adeptbuild' ), __( 'Proyectos llave en mano: diseño 3D, obra civil, climatización y mantenimiento. Un solo equipo desde la primera idea hasta el primer baño.', 'adeptbuild' ), 'textarea' );
	adeptbuild_add_field( $wp_customize, 'hero_btn1_text', 'adeptbuild_hero', __( 'Botón principal · texto', 'adeptbuild' ), __( 'Solicitar presupuesto', 'adeptbuild' ) );
	adeptbuild_add_field( $wp_customize, 'hero_btn1_url', 'adeptbuild_hero', __( 'Botón principal · enlace', 'adeptbuild' ), '#contacto', 'url' );
	adeptbuild_add_field( $wp_customize, 'hero_btn2_text', 'adeptbuild_hero', __( 'Botón secundario · texto', 'adeptbuild' ), __( 'Ver proyectos', 'adeptbuild' ) );
	adeptbuild_add_field( $wp_customize, 'hero_btn2_url', 'adeptbuild_hero', __( 'Botón secundario · enlace', 'adeptbuild' ), '#proyectos', 'url' );

	for ( $i = 1; $i <= 3; $i++ ) {
		$defaults = array(
			1 => array( '250+', __( 'Piscinas entregadas', 'adeptbuild' ) ),
			2 => array( '15', __( 'Años de experiencia', 'adeptbuild' ) ),
			3 => array( '10', __( 'Años de garantía', 'adeptbuild' ) ),
		);

		/* translators: %d: número de dato. */
		adeptbuild_add_field( $wp_customize, "hero_stat{$i}_value", 'adeptbuild_hero', sprintf( __( 'Dato %d · cifra', 'adeptbuild' ), $i ), $defaults[ $i ][0] );
		/* translators: %d: número de dato. */
		adeptbuild_add_field( $wp_customize, "hero_stat{$i}_label", 'adeptbuild_hero', sprintf( __( 'Dato %d · etiqueta', 'adeptbuild' ), $i ), $defaults[ $i ][1] );
	}

	/* ---------------------------------------------------------------------
	 * Portada — Sobre nosotros
	 * ------------------------------------------------------------------- */
	$wp_customize->add_section(
		'adeptbuild_about',
		array(
			'title' => __( 'Portada · Sobre nosotros', 'adeptbuild' ),
			'panel' => 'adeptbuild_panel',
		)
	);

	adeptbuild_add_field( $wp_customize, 'about_image', 'adeptbuild_about', __( 'Imagen de la sección', 'adeptbuild' ), '', 'image' );

	/* ---------------------------------------------------------------------
	 * Pie de página
	 * ------------------------------------------------------------------- */
	$wp_customize->add_section(
		'adeptbuild_footer',
		array(
			'title' => __( 'Pie de página', 'adeptbuild' ),
			'panel' => 'adeptbuild_panel',
		)
	);

	adeptbuild_add_field( $wp_customize, 'footer_about', 'adeptbuild_footer', __( 'Texto de presentación', 'adeptbuild' ), __( 'Construcción, renovación y mantenimiento de piscinas residenciales y comerciales. Diseño propio, obra garantizada.', 'adeptbuild' ), 'textarea' );
	adeptbuild_add_field( $wp_customize, 'footer_copyright', 'adeptbuild_footer', __( 'Aviso de copyright', 'adeptbuild' ), '' );

	// Refresco en vivo del título y la descripción del sitio.
	$wp_customize->get_setting( 'blogname' )->transport        = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';
}
add_action( 'customize_register', 'adeptbuild_customize_register' );

/**
 * Script que refresca la vista previa sin recargar.
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
