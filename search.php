<?php
/**
 * Suchergebnisse: Rezepte als Karten, darunter Seiten & Beiträge.
 *
 * @package NaPurelOn
 */

defined( 'ABSPATH' ) || exit;

get_header();

$napurelon_begriff = napurelon_suchseite_begriff();
$napurelon_seite   = max( 1, (int) get_query_var( 'paged' ) );
$napurelon_rezepte = ( 1 === $napurelon_seite && mb_strlen( $napurelon_begriff ) >= 2 ) ? napurelon_rezept_such_ids( $napurelon_begriff, NAPURELON_SUCHSEITE_REZEPTE ) : array();
$napurelon_seiten  = (int) $GLOBALS['wp_query']->found_posts;
?>

<div id="primary" class="content-area primary npo-suche">
	<main id="main" class="site-main">
		<header class="npo-suche__kopf">
			<h1 class="npo-suche__titel">
				<?php
				if ( '' !== $napurelon_begriff ) {
					echo esc_html( sprintf( napurelon_text( 'Suchergebnisse für „%s“' ), $napurelon_begriff ) );
				} else {
					echo esc_html( napurelon_text( 'Suche' ) );
				}
				?>
			</h1>
			<?php echo napurelon_suchseite_formular( $napurelon_begriff ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intern escapt. ?>
		</header>

		<?php if ( mb_strlen( $napurelon_begriff ) < 2 ) : ?>
			<p class="npo-suche__hinweis"><?php echo esc_html( napurelon_text( 'Bitte gib mindestens zwei Zeichen ein.' ) ); ?></p>
		<?php elseif ( empty( $napurelon_rezepte ) && 0 === $napurelon_seiten ) : ?>
			<p class="npo-suche__hinweis"><?php echo esc_html( sprintf( napurelon_text( 'Leider haben wir zu „%s“ nichts gefunden. Versuche es mit einem anderen Begriff, zum Beispiel einer Zutat.' ), $napurelon_begriff ) ); ?></p>
		<?php else : ?>

			<?php if ( 1 === $napurelon_seite ) : ?>
				<section class="npo-suche__abschnitt" aria-labelledby="npo-suche-rezepte">
					<h2 id="npo-suche-rezepte" class="npo-suche__ueberschrift">
						<?php echo esc_html( napurelon_text( 'Rezepte' ) ); ?>
						<span class="npo-suche__anzahl"><?php echo esc_html( (string) count( $napurelon_rezepte ) ); ?></span>
					</h2>
					<?php if ( empty( $napurelon_rezepte ) ) : ?>
						<p class="npo-suche__leer"><?php echo esc_html( napurelon_text( 'Keine Rezepte gefunden.' ) ); ?></p>
					<?php else : ?>
						<?php echo napurelon_rezept_karten( $napurelon_rezepte ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Karten sind intern escapt. ?>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<section class="npo-suche__abschnitt" aria-labelledby="npo-suche-seiten">
				<h2 id="npo-suche-seiten" class="npo-suche__ueberschrift">
					<?php echo esc_html( napurelon_text( 'Seiten & Beiträge' ) ); ?>
					<span class="npo-suche__anzahl"><?php echo esc_html( (string) $napurelon_seiten ); ?></span>
				</h2>
				<?php if ( have_posts() ) : ?>
					<ul class="npo-suche__liste">
						<?php
						while ( have_posts() ) :
							the_post();
							$napurelon_auszug = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 18 );
							?>
							<li class="npo-suche__eintrag">
								<a class="npo-suche__link" href="<?php the_permalink(); ?>">
									<span class="npo-suche__typ"><?php echo esc_html( napurelon_text( 'page' === get_post_type() ? 'Seite' : 'Beitrag' ) ); ?></span>
									<span class="npo-suche__eintragstitel"><?php the_title(); ?></span>
									<?php if ( '' !== $napurelon_auszug ) : ?>
										<span class="npo-suche__auszug"><?php echo esc_html( $napurelon_auszug ); ?></span>
									<?php endif; ?>
								</a>
							</li>
						<?php endwhile; ?>
					</ul>
					<?php
					$napurelon_seitenlinks = paginate_links(
						array(
							'total'     => (int) $GLOBALS['wp_query']->max_num_pages,
							'current'   => $napurelon_seite,
							'prev_text' => '‹',
							'next_text' => '›',
							'mid_size'  => 1,
						)
					);
					if ( $napurelon_seitenlinks ) {
						echo '<nav class="npo-suche__seiten">' . wp_kses_post( $napurelon_seitenlinks ) . '</nav>';
					}
					?>
				<?php else : ?>
					<p class="npo-suche__leer"><?php echo esc_html( napurelon_text( 'Keine Seiten oder Beiträge gefunden.' ) ); ?></p>
				<?php endif; ?>
			</section>

		<?php endif; ?>
	</main>
</div>

<?php
get_footer();
