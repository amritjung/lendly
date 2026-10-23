<?php
/**
 * Home page. Set Settings > Reading > "A static page" for this to load.
 * Any content you write on the Home page in the editor appears below the hero.
 */
get_header();
?>

<section class="hero">
	<div class="container hero__grid">
		<div class="hero__copy">
			<p class="eyebrow"><?php echo esc_html( lendly_mod( 'hero_eyebrow' ) ); ?></p>
			<h1><?php echo esc_html( lendly_mod( 'hero_title' ) ); ?></h1>
			<p class="lead"><?php echo esc_html( lendly_mod( 'hero_text' ) ); ?></p>
			<ul class="ticks">
				<li><?php esc_html_e( 'Apply online in minutes', 'lendly-theme' ); ?></li>
				<li><?php esc_html_e( 'Fixed rate, fixed repayments', 'lendly-theme' ); ?></li>
				<li><?php esc_html_e( 'Australian-based support', 'lendly-theme' ); ?></li>
			</ul>
		</div>
		<div class="hero__calc">
			<?php lendly_calculator(); ?>
		</div>
	</div>
</section>

<?php get_template_part( 'template-parts/how-it-works' ); ?>
<?php get_template_part( 'template-parts/loan-cards' ); ?>
<?php get_template_part( 'template-parts/features' ); ?>

<?php
while ( have_posts() ) :
	the_post();
	if ( get_the_content() ) :
		?>
		<section class="section"><div class="container prose"><?php the_content(); ?></div></section>
		<?php
	endif;
endwhile;
?>

<?php get_template_part( 'template-parts/faq' ); ?>
<?php get_template_part( 'template-parts/cta' ); ?>

<?php
get_footer();
