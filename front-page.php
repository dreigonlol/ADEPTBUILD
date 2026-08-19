<?php
/**
 * Portada del sitio.
 *
 * Se usa automáticamente cuando la página de inicio está configurada como
 * "Tu últimas entradas" o como página estática. El contenido editable vive en
 * Apariencia → Personalizar → Adept Build.
 *
 * @package Adeptbuild
 */

get_header();

$ab_hero_image = adeptbuild_option( 'hero_image' );
$ab_hero_style = $ab_hero_image
	? sprintf(
		'background-image: linear-gradient(115deg, rgba(11,47,62,.90) 0%%, rgba(14,116,144,.72) 55%%, rgba(34,184,207,.42) 100%%), url(%s);',
		esc_url( $ab_hero_image )
	)
	: '';
?>

<main id="primary" class="site-main">

	<!-- ============================= HERO ============================= -->
	<section class="ab-hero" <?php echo $ab_hero_style ? 'style="' . esc_attr( $ab_hero_style ) . '"' : ''; ?>>
		<div class="ast-container">
			<div class="ab-hero__content">

				<?php $ab_badge = adeptbuild_option( 'hero_badge' ); ?>
				<?php if ( $ab_badge ) : ?>
					<span class="ab-hero__badge">
						<?php adeptbuild_icon( 'award', 16 ); ?><?php echo esc_html( $ab_badge ); ?>
					</span>
				<?php endif; ?>

				<h1>
					<?php echo esc_html( adeptbuild_option( 'hero_title', 'Diseñamos y construimos' ) ); ?>
					<em><?php echo esc_html( adeptbuild_option( 'hero_title_highlight', 'la piscina de tus sueños' ) ); ?></em>
				</h1>

				<p class="ab-hero__text">
					<?php echo esc_html( adeptbuild_option( 'hero_text', 'Proyectos llave en mano: diseño 3D, obra civil, climatización y mantenimiento.' ) ); ?>
				</p>

				<div class="ab-btn-group">
					<?php if ( adeptbuild_option( 'hero_btn1_text' ) ) : ?>
						<a class="ast-button ab-btn--accent ab-btn--lg" href="<?php echo esc_url( adeptbuild_option( 'hero_btn1_url', '#contacto' ) ); ?>">
							<?php echo esc_html( adeptbuild_option( 'hero_btn1_text' ) ); ?>
							<?php adeptbuild_icon( 'arrow-right', 18 ); ?>
						</a>
					<?php endif; ?>

					<?php if ( adeptbuild_option( 'hero_btn2_text' ) ) : ?>
						<a class="ast-button ab-btn--ghost ab-btn--lg" href="<?php echo esc_url( adeptbuild_option( 'hero_btn2_url', '#proyectos' ) ); ?>">
							<?php echo esc_html( adeptbuild_option( 'hero_btn2_text' ) ); ?>
						</a>
					<?php endif; ?>
				</div>

				<div class="ab-hero__stats">
					<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
						<?php
						$ab_value = adeptbuild_option( "hero_stat{$i}_value" );
						$ab_label = adeptbuild_option( "hero_stat{$i}_label" );
						if ( ! $ab_value ) {
							continue;
						}
						?>
						<div class="ab-hero__stat">
							<strong><?php echo esc_html( $ab_value ); ?></strong>
							<span><?php echo esc_html( $ab_label ); ?></span>
						</div>
					<?php endfor; ?>
				</div>

			</div>
		</div>
		<?php adeptbuild_wave(); ?>
	</section>

	<!-- =========================== GARANTÍAS =========================== -->
	<section class="ab-section ab-section--tight">
		<div class="ast-container">
			<div class="ab-grid ab-grid--4">
				<?php
				$ab_promises = array(
					array( 'ruler', __( 'Diseño 3D previo', 'adeptbuild' ), __( 'Verás tu piscina antes de excavar el primer metro.', 'adeptbuild' ) ),
					array( 'shield', __( 'Obra garantizada', 'adeptbuild' ), __( 'Estructura con 10 años de garantía por escrito.', 'adeptbuild' ) ),
					array( 'calendar', __( 'Plazos que se cumplen', 'adeptbuild' ), __( 'Cronograma cerrado y penalización por retraso.', 'adeptbuild' ) ),
					array( 'headset', __( 'Un solo interlocutor', 'adeptbuild' ), __( 'Tu jefe de obra te acompaña de principio a fin.', 'adeptbuild' ) ),
				);
				foreach ( $ab_promises as $ab_promise ) :
					?>
					<div class="ab-reveal">
						<span class="ab-card__icon"><?php adeptbuild_icon( $ab_promise[0], 26 ); ?></span>
						<h3><?php echo esc_html( $ab_promise[1] ); ?></h3>
						<p><?php echo esc_html( $ab_promise[2] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<!-- =========================== SERVICIOS =========================== -->
	<section id="servicios" class="ab-section ab-section--alt">
		<div class="ast-container">

			<div class="ab-section-head ab-section-head--center">
				<span class="ab-eyebrow"><?php esc_html_e( 'Qué hacemos', 'adeptbuild' ); ?></span>
				<h2><?php esc_html_e( 'Servicios de principio a fin', 'adeptbuild' ); ?></h2>
				<p><?php esc_html_e( 'Cubrimos todo el ciclo de vida de la piscina: proyecto, construcción, equipamiento y cuidado posterior.', 'adeptbuild' ); ?></p>
			</div>

			<div class="ab-grid ab-grid--3">
				<?php
				$ab_services = array(
					array(
						'droplet',
						__( 'Construcción de piscinas', 'adeptbuild' ),
						__( 'Hormigón proyectado (gunita), skimmer o desbordante. Estructura calculada para tu terreno y acabados a medida.', 'adeptbuild' ),
					),
					array(
						'hammer',
						__( 'Renovación y reformas', 'adeptbuild' ),
						__( 'Cambio de revestimiento, ampliación de vaso, nueva iluminación LED y actualización completa de la sala de máquinas.', 'adeptbuild' ),
					),
					array(
						'sun',
						__( 'Climatización y spa', 'adeptbuild' ),
						__( 'Bombas de calor, cubiertas térmicas, hidromasaje y cascadas para disfrutar la piscina los doce meses del año.', 'adeptbuild' ),
					),
					array(
						'leaf',
						__( 'Paisajismo y entorno', 'adeptbuild' ),
						__( 'Solárium, pérgolas, zonas de sombra, iluminación exterior y jardín integrado con el conjunto de la vivienda.', 'adeptbuild' ),
					),
					array(
						'wrench',
						__( 'Mantenimiento', 'adeptbuild' ),
						__( 'Planes mensuales de limpieza, control del agua, revisión de equipos y puesta a punto de temporada.', 'adeptbuild' ),
					),
					array(
						'sparkles',
						__( 'Tratamiento del agua', 'adeptbuild' ),
						__( 'Cloración salina, ultravioleta y automatización del pH: agua cristalina con menos productos químicos.', 'adeptbuild' ),
					),
				);

				foreach ( $ab_services as $ab_service ) :
					?>
					<article class="ab-card ab-reveal">
						<span class="ab-card__icon"><?php adeptbuild_icon( $ab_service[0], 28 ); ?></span>
						<h3><?php echo esc_html( $ab_service[1] ); ?></h3>
						<p><?php echo esc_html( $ab_service[2] ); ?></p>
						<a class="ab-link-arrow" href="#contacto">
							<?php esc_html_e( 'Pedir información', 'adeptbuild' ); ?>
							<?php adeptbuild_icon( 'arrow-right', 16 ); ?>
						</a>
					</article>
				<?php endforeach; ?>
			</div>

		</div>
	</section>

	<!-- =========================== NOSOTROS =========================== -->
	<section id="nosotros" class="ab-section">
		<div class="ast-container">
			<div class="ab-media">

				<div class="ab-media__visual ab-reveal">
					<?php
					// Sustituye esta imagen desde el editor o deja el degradado del tema.
					$ab_about_image = adeptbuild_option( 'about_image' );
					if ( $ab_about_image ) :
						?>
						<img src="<?php echo esc_url( $ab_about_image ); ?>" alt="<?php esc_attr_e( 'Obra de Adept Builders & Design', 'adeptbuild' ); ?>" loading="lazy">
					<?php endif; ?>

					<div class="ab-media__badge">
						<strong><?php echo esc_html( adeptbuild_option( 'hero_stat2_value', '15' ) ); ?></strong>
						<span><?php esc_html_e( 'años de obra', 'adeptbuild' ); ?></span>
					</div>
				</div>

				<div class="ab-reveal">
					<div class="ab-section-head ab-section-head--left" style="margin-bottom:24px;">
						<span class="ab-eyebrow"><?php esc_html_e( 'Sobre nosotros', 'adeptbuild' ); ?></span>
						<h2><?php esc_html_e( 'Constructores, no intermediarios', 'adeptbuild' ); ?></h2>
					</div>

					<p class="ab-lead">
						<?php esc_html_e( 'Ejecutamos con equipo propio. Eso significa un único responsable de la obra, control real de los plazos y una garantía que respondemos nosotros, no un subcontratista.', 'adeptbuild' ); ?>
					</p>

					<ul class="ab-checklist">
						<li><?php esc_html_e( 'Estudio de terreno y viabilidad sin coste', 'adeptbuild' ); ?></li>
						<li><?php esc_html_e( 'Presupuesto cerrado, sin sorpresas a mitad de obra', 'adeptbuild' ); ?></li>
						<li><?php esc_html_e( 'Gestión completa de licencias y permisos', 'adeptbuild' ); ?></li>
						<li><?php esc_html_e( 'Materiales de primeras marcas con certificado', 'adeptbuild' ); ?></li>
					</ul>

					<a class="ast-button" href="#contacto"><?php esc_html_e( 'Hablemos de tu proyecto', 'adeptbuild' ); ?></a>
				</div>

			</div>
		</div>
	</section>

	<!-- ============================ PROCESO ============================ -->
	<section id="proceso" class="ab-section ab-section--alt">
		<div class="ast-container">

			<div class="ab-section-head ab-section-head--center">
				<span class="ab-eyebrow"><?php esc_html_e( 'Cómo trabajamos', 'adeptbuild' ); ?></span>
				<h2><?php esc_html_e( 'Cuatro pasos hasta el primer baño', 'adeptbuild' ); ?></h2>
			</div>

			<div class="ab-grid ab-grid--4 ab-steps">
				<?php
				$ab_steps = array(
					array( __( 'Visita y estudio', 'adeptbuild' ), __( 'Analizamos el terreno, los accesos y el uso que le vas a dar.', 'adeptbuild' ) ),
					array( __( 'Diseño y presupuesto', 'adeptbuild' ), __( 'Recibes el render 3D y un presupuesto cerrado por partidas.', 'adeptbuild' ) ),
					array( __( 'Construcción', 'adeptbuild' ), __( 'Excavación, estructura, instalaciones y acabados con seguimiento semanal.', 'adeptbuild' ) ),
					array( __( 'Entrega y cuidado', 'adeptbuild' ), __( 'Puesta en marcha, formación de uso y plan de mantenimiento.', 'adeptbuild' ) ),
				);

				foreach ( $ab_steps as $ab_index => $ab_step ) :
					?>
					<div class="ab-step ab-reveal">
						<span class="ab-step__num"><?php echo esc_html( sprintf( '%02d', $ab_index + 1 ) ); ?></span>
						<h3><?php echo esc_html( $ab_step[0] ); ?></h3>
						<p><?php echo esc_html( $ab_step[1] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>

		</div>
	</section>

	<!-- =========================== PROYECTOS =========================== -->
	<section id="proyectos" class="ab-section">
		<div class="ast-container">

			<div class="ab-section-head ab-section-head--center">
				<span class="ab-eyebrow"><?php esc_html_e( 'Portfolio', 'adeptbuild' ); ?></span>
				<h2><?php esc_html_e( 'Proyectos recientes', 'adeptbuild' ); ?></h2>
				<p><?php esc_html_e( 'Cada piscina se diseña para su casa y su familia. Estas son algunas de las últimas entregas.', 'adeptbuild' ); ?></p>
			</div>

			<div class="ab-gallery">
				<?php
				$ab_projects = array(
					array( __( 'Piscina desbordante', 'adeptbuild' ), __( 'Chalet · 12 × 5 m', 'adeptbuild' ), 'ab-gallery__item--wide' ),
					array( __( 'Spa exterior climatizado', 'adeptbuild' ), __( 'Vivienda unifamiliar', 'adeptbuild' ), '' ),
					array( __( 'Renovación integral', 'adeptbuild' ), __( 'Comunidad de vecinos', 'adeptbuild' ), '' ),
					array( __( 'Piscina de arena', 'adeptbuild' ), __( 'Casa de campo', 'adeptbuild' ), '' ),
					array( __( 'Lámina de agua y solárium', 'adeptbuild' ), __( 'Hotel boutique', 'adeptbuild' ), '' ),
				);

				foreach ( $ab_projects as $ab_project ) :
					?>
					<a class="ab-gallery__item <?php echo esc_attr( $ab_project[2] ); ?> ab-reveal" href="#contacto">
						<div class="ab-gallery__overlay">
							<span><?php echo esc_html( $ab_project[1] ); ?></span>
							<strong><?php echo esc_html( $ab_project[0] ); ?></strong>
						</div>
					</a>
				<?php endforeach; ?>
			</div>

		</div>
	</section>

	<!-- ========================== TESTIMONIOS ========================== -->
	<section class="ab-section ab-section--alt">
		<div class="ast-container">

			<div class="ab-section-head ab-section-head--center">
				<span class="ab-eyebrow"><?php esc_html_e( 'Opiniones', 'adeptbuild' ); ?></span>
				<h2><?php esc_html_e( 'Lo que dicen nuestros clientes', 'adeptbuild' ); ?></h2>
			</div>

			<div class="ab-grid ab-grid--3">
				<?php
				$ab_reviews = array(
					array(
						__( 'Cumplieron el plazo al día. El render 3D era idéntico al resultado final y el presupuesto no se movió ni un euro.', 'adeptbuild' ),
						'María L.',
						__( 'Piscina desbordante', 'adeptbuild' ),
					),
					array(
						__( 'Renovaron una piscina de treinta años y parece nueva. El equipo fue puntual, limpio y muy claro explicando cada fase.', 'adeptbuild' ),
						'Javier R.',
						__( 'Reforma integral', 'adeptbuild' ),
					),
					array(
						__( 'Llevan el mantenimiento desde hace dos años. El agua siempre perfecta y responden el mismo día ante cualquier incidencia.', 'adeptbuild' ),
						'Carmen P.',
						__( 'Plan de mantenimiento', 'adeptbuild' ),
					),
				);

				foreach ( $ab_reviews as $ab_review ) :
					?>
					<article class="ab-quote ab-reveal">
						<div class="ab-quote__stars" aria-label="<?php esc_attr_e( '5 de 5 estrellas', 'adeptbuild' ); ?>">
							<?php for ( $ab_s = 0; $ab_s < 5; $ab_s++ ) : ?>
								<?php adeptbuild_icon( 'star', 15 ); ?>
							<?php endfor; ?>
						</div>
						<p>“<?php echo esc_html( $ab_review[0] ); ?>”</p>
						<div class="ab-quote__author">
							<span class="ab-quote__avatar" aria-hidden="true">
								<?php echo esc_html( function_exists( 'mb_substr' ) ? mb_substr( $ab_review[1], 0, 1 ) : substr( $ab_review[1], 0, 1 ) ); ?>
							</span>
							<span>
								<strong><?php echo esc_html( $ab_review[1] ); ?></strong>
								<span><?php echo esc_html( $ab_review[2] ); ?></span>
							</span>
						</div>
					</article>
				<?php endforeach; ?>
			</div>

		</div>
	</section>

	<!-- ============================ CONTACTO ============================ -->
	<section id="contacto" class="ab-section">
		<div class="ast-container">
			<div class="ab-media">

				<div class="ab-reveal">
					<div class="ab-section-head ab-section-head--left" style="margin-bottom:24px;">
						<span class="ab-eyebrow"><?php esc_html_e( 'Contacto', 'adeptbuild' ); ?></span>
						<h2><?php esc_html_e( 'Cuéntanos qué piscina imaginas', 'adeptbuild' ); ?></h2>
					</div>

					<p><?php esc_html_e( 'Te respondemos en menos de 24 horas laborables con una primera estimación y una fecha de visita.', 'adeptbuild' ); ?></p>

					<ul class="ab-checklist ab-mt-lg">
						<?php if ( adeptbuild_option( 'phone' ) ) : ?>
							<li><?php echo esc_html( adeptbuild_option( 'phone' ) ); ?></li>
						<?php endif; ?>
						<?php if ( adeptbuild_option( 'email' ) ) : ?>
							<li><?php echo esc_html( adeptbuild_option( 'email' ) ); ?></li>
						<?php endif; ?>
						<?php if ( adeptbuild_option( 'address' ) ) : ?>
							<li><?php echo esc_html( adeptbuild_option( 'address' ) ); ?></li>
						<?php endif; ?>
					</ul>
				</div>

				<div class="ab-reveal">
					<?php
					$ab_form_shortcode = adeptbuild_option( 'contact_shortcode' );

					if ( $ab_form_shortcode ) {
						echo '<div class="ab-form">' . do_shortcode( $ab_form_shortcode ) . '</div>';
					} else {
						?>
						<form class="ab-form" method="post" action="">
							<div class="ab-form__row">
								<div>
									<label for="ab-name"><?php esc_html_e( 'Nombre', 'adeptbuild' ); ?></label>
									<input type="text" id="ab-name" name="ab-name" required>
								</div>
								<div>
									<label for="ab-phone"><?php esc_html_e( 'Teléfono', 'adeptbuild' ); ?></label>
									<input type="tel" id="ab-phone" name="ab-phone" required>
								</div>
							</div>
							<div>
								<label for="ab-email"><?php esc_html_e( 'Correo electrónico', 'adeptbuild' ); ?></label>
								<input type="email" id="ab-email" name="ab-email" required>
							</div>
							<div>
								<label for="ab-message"><?php esc_html_e( 'Tu proyecto', 'adeptbuild' ); ?></label>
								<textarea id="ab-message" name="ab-message" placeholder="<?php esc_attr_e( 'Tamaño aproximado, ubicación, plazos…', 'adeptbuild' ); ?>"></textarea>
							</div>
							<button type="submit" class="ast-button ab-btn--accent"><?php esc_html_e( 'Solicitar presupuesto', 'adeptbuild' ); ?></button>

							<?php if ( current_user_can( 'edit_theme_options' ) ) : ?>
								<p class="ab-muted" style="font-size:.8125rem;">
									<?php esc_html_e( 'Solo visible para administradores: este formulario es maquetación. Pega el shortcode de tu plugin de formularios en Personalizar → Adept Build → Datos de contacto para que envíe correos.', 'adeptbuild' ); ?>
								</p>
							<?php endif; ?>
						</form>
						<?php
					}
					?>
				</div>

			</div>
		</div>
	</section>

	<!-- ============================== CTA ============================== -->
	<section class="ab-section ab-section--tight">
		<div class="ast-container">
			<div class="ab-cta ab-reveal">
				<div>
					<h2><?php esc_html_e( '¿Empezamos este verano?', 'adeptbuild' ); ?></h2>
					<p><?php esc_html_e( 'Las agendas de obra se cierran con meses de antelación. Reserva ahora tu visita técnica gratuita.', 'adeptbuild' ); ?></p>
				</div>
				<?php if ( adeptbuild_option( 'phone' ) ) : ?>
					<a class="ast-button ab-btn--accent ab-btn--lg" href="<?php echo esc_url( adeptbuild_tel_href( adeptbuild_option( 'phone' ) ) ); ?>">
						<?php adeptbuild_icon( 'phone', 18 ); ?>
						<?php echo esc_html( adeptbuild_option( 'phone' ) ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php
	// Si la portada es una página estática con contenido, se muestra debajo.
	if ( have_posts() ) :
		while ( have_posts() ) :
			the_post();
			$ab_content = get_the_content();
			if ( trim( $ab_content ) ) :
				?>
				<section class="ab-section">
					<div class="ast-container ast-container--narrow ab-entry-content">
						<?php the_content(); ?>
					</div>
				</section>
				<?php
			endif;
		endwhile;
	endif;
	?>

</main>

<?php get_footer(); ?>
