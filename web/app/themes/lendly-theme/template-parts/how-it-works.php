<?php
$steps = [
	[ __( 'Check your repayments', 'lendly-theme' ), __( 'Use the calculator to pick an amount and term that suit your budget.', 'lendly-theme' ) ],
	[ __( 'Apply online', 'lendly-theme' ), __( 'Tell us about yourself, your income and expenses. It takes about 10 minutes.', 'lendly-theme' ) ],
	[ __( 'We assess and verify', 'lendly-theme' ), __( 'We check your details, credit history and bank statements to make sure the loan is right for you.', 'lendly-theme' ) ],
	[ __( 'Sign and get paid', 'lendly-theme' ), __( 'If approved, sign your contract online and the money is sent to your account.', 'lendly-theme' ) ],
];
?>
<section class="section" id="how-it-works">
	<div class="container">
		<h2 class="section__title"><?php esc_html_e( 'How it works', 'lendly-theme' ); ?></h2>
		<ol class="steps">
			<?php foreach ( $steps as [ $title, $text ] ) : ?>
				<li class="steps__item">
					<h3><?php echo esc_html( $title ); ?></h3>
					<p><?php echo esc_html( $text ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
