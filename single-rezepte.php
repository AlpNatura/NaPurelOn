<?php
/**
 * Einzelansicht eines Rezepts.
 *
 * Aufbau: grosses Rezeptbild mit überlappender Infokarte (Titel, Kategorien,
 * Zeiten, Tags, Aktionsleiste), darunter zwei Spalten – links die Zutaten,
 * rechts die Zubereitung.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id     = get_the_ID();
	$kategorien  = get_the_term_list( $post_id, 'rezeptkategorie', '', '' );
	$tags        = get_the_terms( $post_id, 'rezepttag' );
	$aktiv       = napurelon_format_minuten( napurelon_rezept_meta( $post_id, 'napurelon_zubereitungszeit' ) );
	$kochzeit    = napurelon_format_minuten( napurelon_rezept_meta( $post_id, 'napurelon_kochzeit' ) );
	$portionen   = napurelon_rezept_meta( $post_id, 'napurelon_portionen' );
	$menge       = napurelon_rezept_meta( $post_id, 'napurelon_menge' );
	$haltbarkeit = napurelon_rezept_meta( $post_id, 'napurelon_haltbarkeit' );
	$untertitel  = napurelon_rezept_meta( $post_id, 'napurelon_untertitel' );
	$einleitung  = napurelon_rezept_meta( $post_id, 'napurelon_einleitung' );
	$zutaten     = napurelon_parse_zutaten( napurelon_rezept_meta( $post_id, 'napurelon_zutaten' ) );
	$ausbacken   = napurelon_rezept_meta( $post_id, 'napurelon_ausbacken' );
	$likes       = (int) get_post_meta( $post_id, NAPURELON_LIKES_META_KEY, true );
	?>

<div class="napurelon-rezept-titelbereich">
	<nav class="napurelon-brotkrumen" aria-label="Brotkrumen">
		<ol>
			<?php foreach ( napurelon_rezept_brotkrumen( $post_id ) as $krume ) : ?>
				<li>
					<?php if ( '' !== $krume['url'] ) : ?>
						<a href="<?php echo esc_url( $krume['url'] ); ?>"><?php echo esc_html( $krume['titel'] ); ?></a>
					<?php else : ?>
						<span><?php echo esc_html( $krume['titel'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
</div>

<article id="rezept-<?php echo esc_attr( $post_id ); ?>" <?php post_class( 'napurelon-rezept' ); ?>>

	<header class="napurelon-rezept__kopf">
		<?php
		// Galerie: Rezeptbild zuerst, danach die Bilder aus der Metabox.
		$bild_ids = napurelon_get_galerie_ids( $post_id );

		if ( has_post_thumbnail() ) {
			array_unshift( $bild_ids, (int) get_post_thumbnail_id( $post_id ) );
		}

		$bild_ids = array_values( array_unique( array_filter( $bild_ids ) ) );

		if ( ! empty( $bild_ids ) ) :
			?>
			<div class="napurelon-rezept__bild" data-napurelon-galerie>
				<figure class="napurelon-rezept__bild-haupt">
					<?php echo wp_get_attachment_image( $bild_ids[0], 'full', false, array( 'data-napurelon-galerie-haupt' => 'true' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ausgabe von wp_get_attachment_image(). ?>
				</figure>

				<?php if ( count( $bild_ids ) > 1 ) : ?>
					<ul class="napurelon-rezept__miniaturen">
						<?php foreach ( $bild_ids as $index => $bild_id ) : ?>
							<li>
								<button type="button"
									class="napurelon-miniatur<?php echo 0 === $index ? ' is-active' : ''; ?>"
									data-voll="<?php echo esc_url( (string) wp_get_attachment_image_url( $bild_id, 'full' ) ); ?>"
									data-srcset="<?php echo esc_attr( (string) wp_get_attachment_image_srcset( $bild_id, 'full' ) ); ?>"
									data-alt="<?php echo esc_attr( (string) get_post_meta( $bild_id, '_wp_attachment_image_alt', true ) ); ?>">
									<?php echo wp_get_attachment_image( $bild_id, 'thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Ausgabe von wp_get_attachment_image(). ?>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="napurelon-rezept__karte">
			<h1 class="napurelon-rezept__titel"><?php the_title(); ?></h1>

			<?php if ( '' !== $untertitel ) : ?>
				<p class="napurelon-rezept__untertitel"><?php echo esc_html( $untertitel ); ?></p>
			<?php endif; ?>

			<?php if ( $kategorien && ! is_wp_error( $kategorien ) ) : ?>
				<div class="napurelon-rezept__kategorien">
					<?php echo wp_kses_post( $kategorien ); ?>
				</div>
			<?php endif; ?>

			<?php
			// Zeiten: nur die Werte, ohne Feldbezeichnung.
			$zeiten = array_filter( array( $aktiv, $kochzeit ) );

			if ( ! empty( $zeiten ) ) :
				?>
				<p class="napurelon-rezept__zeile">
					<?php echo napurelon_rezept_icon( 'zeit' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Festes Inline-SVG. ?>
					<span><?php echo esc_html( implode( ' | ', $zeiten ) ); ?></span>
				</p>
			<?php endif; ?>

			<?php
			$angaben = array_filter(
				array(
					$portionen ? $portionen . ' Portionen' : '',
					$menge,
					$haltbarkeit,
				)
			);

			if ( ! empty( $angaben ) ) :
				?>
				<p class="napurelon-rezept__zeile">
					<?php echo napurelon_rezept_icon( 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Festes Inline-SVG. ?>
					<span><?php echo esc_html( implode( ' | ', $angaben ) ); ?></span>
				</p>
			<?php endif; ?>

			<?php if ( $tags && ! is_wp_error( $tags ) ) : ?>
				<ul class="napurelon-rezept__tags">
					<?php foreach ( $tags as $tag ) : ?>
						<li>
							<a href="<?php echo esc_url( (string) get_term_link( $tag ) ); ?>"><?php echo esc_html( $tag->name ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<div class="napurelon-rezept__aktionen">
				<button type="button" class="napurelon-aktion" data-napurelon-like>
					<?php echo napurelon_rezept_icon( 'herz' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Festes Inline-SVG. ?>
					<span><span data-napurelon-like-count><?php echo esc_html( (string) $likes ); ?></span> Likes</span>
				</button>

				<button type="button" class="napurelon-aktion" data-napurelon-merken>
					<?php echo napurelon_rezept_icon( 'merken' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Festes Inline-SVG. ?>
					<span data-napurelon-merken-label>Merken</span>
				</button>

				<button type="button" class="napurelon-aktion" data-napurelon-drucken>
					<?php echo napurelon_rezept_icon( 'drucken' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Festes Inline-SVG. ?>
					<span>Drucken</span>
				</button>

				<button type="button" class="napurelon-aktion" data-napurelon-teilen data-url="<?php the_permalink(); ?>" data-titel="<?php echo esc_attr( get_the_title() ); ?>">
					<?php echo napurelon_rezept_icon( 'teilen' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Festes Inline-SVG. ?>
					<span>Teilen</span>
				</button>
			</div>
		</div>
	</header>

	<?php if ( '' !== $einleitung ) : ?>
		<div class="napurelon-rezept__einleitung">
			<?php echo wp_kses_post( wpautop( $einleitung ) ); ?>
		</div>
	<?php endif; ?>

	<div class="napurelon-rezept__spalten">

		<aside class="napurelon-rezept__zutaten">
			<h2 class="napurelon-rezept__abschnitt">Das brauchst du</h2>

			<?php if ( $portionen ) : ?>
				<p class="napurelon-rezept__portionen"><?php echo esc_html( $portionen . ' Portionen' ); ?></p>
			<?php endif; ?>

			<?php foreach ( $zutaten as $gruppe ) : ?>
				<?php if ( '' !== $gruppe['title'] ) : ?>
					<h3 class="napurelon-rezept__gruppe"><?php echo esc_html( $gruppe['title'] ); ?></h3>
				<?php endif; ?>

				<ul class="napurelon-rezept__liste">
					<?php foreach ( $gruppe['items'] as $zutat ) : ?>
						<li><?php echo esc_html( $zutat ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endforeach; ?>

			<?php if ( '' !== $ausbacken ) : ?>
				<h3 class="napurelon-rezept__gruppe">Ausbacken</h3>
				<div class="napurelon-rezept__text"><?php echo wp_kses_post( wpautop( $ausbacken ) ); ?></div>
			<?php endif; ?>
		</aside>

		<div class="napurelon-rezept__zubereitung">
			<h2 class="napurelon-rezept__abschnitt">Schritt für Schritt</h2>
			<div class="napurelon-rezept__text"><?php the_content(); ?></div>

			<?php
			// Weitere Angaben stehen als eingerahmter Kasten unter der Zubereitung.
			$weitere = array(
				'Anwendung'            => napurelon_rezept_meta( $post_id, 'napurelon_anwendung' ),
				'Einsatzgebiete'       => napurelon_rezept_meta( $post_id, 'napurelon_einsatzgebiete' ),
				'Tipps & Empfehlungen' => napurelon_rezept_meta( $post_id, 'napurelon_tipps' ),
				'Vorteile'             => napurelon_rezept_meta( $post_id, 'napurelon_vorteile' ),
				'Nachteile'            => napurelon_rezept_meta( $post_id, 'napurelon_nachteile' ),
				'Warnhinweise'         => napurelon_rezept_meta( $post_id, 'napurelon_hinweise' ),
				'Quellen'              => napurelon_rezept_meta( $post_id, 'napurelon_quellen' ),
			);

			$weitere = array_filter( $weitere );
			$video   = napurelon_rezept_meta( $post_id, 'napurelon_video_url' );

			if ( ! empty( $weitere ) || '' !== $video ) :
				?>
				<section class="napurelon-rezept__wissen">
					<h2 class="napurelon-rezept__abschnitt">Gut zu wissen</h2>

					<?php foreach ( $weitere as $label => $wert ) : ?>
						<div class="napurelon-rezept__wissen-block">
							<h3 class="napurelon-rezept__gruppe"><?php echo esc_html( $label ); ?></h3>
							<div class="napurelon-rezept__text"><?php echo wp_kses_post( wpautop( $wert ) ); ?></div>
						</div>
					<?php endforeach; ?>

					<?php
					if ( '' !== $video ) :
						$einbettung = wp_oembed_get( $video );
						?>
						<div class="napurelon-rezept__video">
							<?php
							if ( $einbettung ) {
								echo wp_kses_post( $einbettung );
							} else {
								printf( '<a href="%1$s">%1$s</a>', esc_url( $video ) );
							}
							?>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		</div>

	</div>

	<?php
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
	?>

</article>

	<?php
endwhile;

get_footer();
