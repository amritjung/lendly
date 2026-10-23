<section class="cta">
	<div class="container cta__inner">
		<div>
			<h2><?php esc_html_e( 'Ready when you are', 'lendly-theme' ); ?></h2>
			<p><?php esc_html_e( 'Apply online in about 10 minutes, or call us to talk it through.', 'lendly-theme' ); ?></p>
		</div>
		<div class="cta__actions">
			<a class="btn btn--light" href="<?php echo esc_url( lendly_apply_url() ); ?>"><?php esc_html_e( 'Apply now', 'lendly-theme' ); ?></a>
			<?php echo lendly_phone_link(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</div>
</section>
