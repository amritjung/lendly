<?php
get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class(); ?>>
		<section class="page-header">
			<div class="container">
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<h1><?php the_title(); ?></h1>
			</div>
		</section>
		<section class="section section--tight">
			<div class="container prose">
				<?php the_content(); ?>
				<p class="disclaimer"><?php esc_html_e( 'This article is general information only and is not financial advice.', 'lendly-theme' ); ?></p>
			</div>
		</section>
	</article>
	<?php
endwhile;

get_template_part( 'template-parts/cta' );
get_footer();
