<?php
/**
 * Default page: About, Contact, Apply, legal documents.
 */
get_header();

while ( have_posts() ) :
	the_post();
	?>
	<section class="page-header">
		<div class="container"><h1><?php the_title(); ?></h1></div>
	</section>
	<section class="section section--tight">
		<div class="container prose"><?php the_content(); ?></div>
	</section>
	<?php
endwhile;

get_footer();
