<?php
get_header();
?>
<section class="page-header">
	<div class="container">
		<h1><?php esc_html_e( 'Page not found', 'lendly-theme' ); ?></h1>
		<p class="lead"><?php esc_html_e( "The page you're looking for has moved or doesn't exist.", 'lendly-theme' ); ?></p>
		<p><a class="btn" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'lendly-theme' ); ?></a></p>
	</div>
</section>
<?php
get_footer();
