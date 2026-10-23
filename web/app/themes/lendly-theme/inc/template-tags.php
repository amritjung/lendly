<?php
/**
 * Small helpers used by templates.
 */
defined( 'ABSPATH' ) || exit;

function lendly_phone_link(): string {
	$phone = lendly_mod( 'phone' );
	return sprintf( '<a class="phone-link" href="tel:%s">%s</a>', esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ), esc_html( $phone ) );
}

function lendly_apply_url(): string {
	$url = lendly_mod( 'apply_url' );
	return str_starts_with( $url, 'http' ) ? $url : home_url( $url );
}

/** Calculator from the plugin, or a plain CTA if the plugin is off. */
function lendly_calculator(): void {
	if ( lendly_has_plugin() ) {
		echo do_shortcode( sprintf( '[lendly_calculator apply_url="%s"]', esc_url( lendly_apply_url() ) ) );
		return;
	}
	printf( '<div class="card"><p>%s</p><a class="btn" href="%s">%s</a></div>', esc_html__( 'Activate the Lendly Loans plugin to show the calculator.', 'lendly-theme' ), esc_url( lendly_apply_url() ), esc_html__( 'Apply now', 'lendly-theme' ) );
}

/** Pages built with the "Loan product" template, shown as cards. */
function lendly_loan_pages(): array {
	return get_pages( [
		'meta_key'    => '_wp_page_template',
		'meta_value'  => 'page-templates/template-loan.php',
		'sort_column' => 'menu_order',
	] );
}

/** Default FAQs — override with the `lendly_faqs` filter or edit here. */
function lendly_faqs(): array {
	return apply_filters( 'lendly_faqs', [
		[ 'How long does it take to apply?', 'Most people finish the online application in about 10 minutes. Have your ID, income details and a list of your regular expenses ready.' ],
		[ 'Will applying affect my credit score?', 'Submitting an application involves a credit check, which is recorded on your credit file. Use the calculator first to see if the loan suits your budget.' ],
		[ 'What do I need to be eligible?', 'You must be 18 or older, an Australian citizen or permanent resident, have a regular income, and be able to afford the repayments. All applications are subject to our credit assessment.' ],
		[ 'Can I repay my loan early?', 'Explain your early repayment terms and any fees here. Keep this answer consistent with your loan contract.' ],
		[ 'What if I have trouble making repayments?', 'Contact us as soon as possible. We have a financial hardship process and will work with you on options.' ],
	] );
}
