<?php
$faqs = lendly_faqs();
if ( ! $faqs ) {
	return;
}

// FAQPage structured data for search engines.
$schema = [
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => array_map( static fn( $f ) => [
		'@type'          => 'Question',
		'name'           => $f[0],
		'acceptedAnswer' => [ '@type' => 'Answer', 'text' => $f[1] ],
	], $faqs ),
];
?>
<section class="section section--alt" id="faq">
	<div class="container container--narrow">
		<h2 class="section__title"><?php esc_html_e( 'Frequently asked questions', 'lendly-theme' ); ?></h2>
		<div class="faq">
			<?php foreach ( $faqs as [ $q, $a ] ) : ?>
				<details class="faq__item">
					<summary><?php echo esc_html( $q ); ?></summary>
					<p><?php echo esc_html( $a ); ?></p>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
	<script type="application/ld+json"><?php echo wp_json_encode( $schema ); ?></script>
</section>
