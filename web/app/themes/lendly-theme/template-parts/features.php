<?php
/**
 * "Why choose us". Only claim what is true for your product — these are placeholders.
 */
$features = [
	[ __( 'Clear pricing', 'lendly-theme' ), __( 'Your rate, fees and repayments are set out before you sign.', 'lendly-theme' ) ],
	[ __( 'Fast decisions', 'lendly-theme' ), __( 'Describe your real turnaround time here, e.g. a decision within one business day.', 'lendly-theme' ) ],
	[ __( 'Responsible lending', 'lendly-theme' ), __( "We only lend what you can afford, based on your income and expenses.", 'lendly-theme' ) ],
	[ __( 'Your data, protected', 'lendly-theme' ), __( 'Your application is encrypted and only seen by our assessment team.', 'lendly-theme' ) ],
];
?>
<section class="section">
	<div class="container">
		<h2 class="section__title"><?php esc_html_e( 'Why borrow with us', 'lendly-theme' ); ?></h2>
		<div class="feature-grid">
			<?php foreach ( $features as [ $title, $text ] ) : ?>
				<div class="feature">
					<h3><?php echo esc_html( $title ); ?></h3>
					<p><?php echo esc_html( $text ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
