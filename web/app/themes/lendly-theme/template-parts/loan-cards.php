<?php
$loans = lendly_loan_pages();
if ( ! $loans ) {
	return;
}
?>
<section class="section section--alt">
	<div class="container">
		<h2 class="section__title"><?php esc_html_e( 'Loans for what matters to you', 'lendly-theme' ); ?></h2>
		<div class="card-grid">
			<?php foreach ( $loans as $loan ) : ?>
				<a class="card card--link" href="<?php echo esc_url( get_permalink( $loan ) ); ?>">
					<?php if ( has_post_thumbnail( $loan ) ) : ?>
						<?php echo get_the_post_thumbnail( $loan, 'thumbnail', [ 'class' => 'card__icon', 'alt' => '' ] ); ?>
					<?php endif; ?>
					<h3><?php echo esc_html( get_the_title( $loan ) ); ?></h3>
					<p><?php echo esc_html( wp_trim_words( $loan->post_excerpt ?: wp_strip_all_tags( $loan->post_content ), 22 ) ); ?></p>
					<span class="card__more"><?php esc_html_e( 'Learn more', 'lendly-theme' ); ?> &rarr;</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
