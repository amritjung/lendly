<?php
/**
 * Template Name: Loan product
 *
 * Use for Personal Loans, Car Loans, Debt Consolidation, etc.
 * The page excerpt becomes the hero intro and the summary on home-page cards.
 */
get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="hero hero--page">
		<div class="container hero__grid">
			<div class="hero__copy">
				<p class="eyebrow"><?php esc_html_e( 'Personal finance', 'lendly-theme' ); ?></p>
				<h1><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
			</div>
			<div class="hero__calc"><?php lendly_calculator(); ?></div>
		</div>
	</section>

	<section class="section">
		<div class="container prose"><?php the_content(); ?></div>
	</section>
	<?php
endwhile;

get_template_part( 'template-parts/how-it-works' );
get_template_part( 'template-parts/faq' );
get_template_part( 'template-parts/cta' );

get_footer();
