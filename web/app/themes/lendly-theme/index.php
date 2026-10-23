<?php
/**
 * Blog index / archive fallback.
 */
get_header();
?>
<section class="page-header">
	<div class="container"><h1><?php echo is_home() ? esc_html( get_the_title( (int) get_option( 'page_for_posts' ) ) ?: __( 'Blog', 'lendly-theme' ) ) : wp_kses_post( get_the_archive_title() ); ?></h1></div>
</section>

<section class="section section--tight">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<div class="post-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article <?php post_class( 'post-card' ); ?>>
						<?php if ( has_post_thumbnail() ) : ?>
							<a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true"><?php the_post_thumbnail( 'medium_large' ); ?></a>
						<?php endif; ?>
						<div class="post-card__body">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php the_excerpt(); ?>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing here yet.', 'lendly-theme' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
