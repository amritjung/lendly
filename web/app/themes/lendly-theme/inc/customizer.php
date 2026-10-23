<?php
/**
 * Appearance > Customize > Lendly: brand, contact and licence details.
 */
defined( 'ABSPATH' ) || exit;

function lendly_mod_defaults(): array {
	return [
		'brand_color'      => '#5b3fd6',
		'accent_color'     => '#f2b134',
		'phone'            => '1300 000 000',
		'email'            => 'hello@example.com.au',
		'hours'            => 'Mon–Fri 8:30am–5:30pm AEST',
		'abn'              => '00 000 000 000',
		'acl'              => '000000',
		'company_name'     => 'Your Company Pty Ltd',
		'hero_eyebrow'     => 'Personal loans from $2,000 to $50,000',
		'hero_title'       => 'A personal loan that fits your life',
		'hero_text'        => 'Apply online in about 10 minutes. Get a decision fast, with no hidden fees.',
		'apply_url'        => '/apply/',
		'footer_disclaimer'=> 'All applications are subject to our credit assessment and lending criteria. Terms, conditions, fees and charges apply. Information on this website is general in nature and does not take into account your objectives, financial situation or needs.',
	];
}

function lendly_mod( string $key ): string {
	$d = lendly_mod_defaults();
	return (string) get_theme_mod( 'lendly_' . $key, $d[ $key ] ?? '' );
}

add_action( 'customize_register', static function ( WP_Customize_Manager $wp_customize ) {
	$d = lendly_mod_defaults();

	$wp_customize->add_panel( 'lendly', [ 'title' => __( 'Lendly', 'lendly-theme' ), 'priority' => 30 ] );

	$sections = [
		'lendly_brand'   => __( 'Brand colours', 'lendly-theme' ),
		'lendly_hero'    => __( 'Home page hero', 'lendly-theme' ),
		'lendly_company' => __( 'Company & licence', 'lendly-theme' ),
	];
	foreach ( $sections as $id => $title ) {
		$wp_customize->add_section( $id, [ 'title' => $title, 'panel' => 'lendly' ] );
	}

	foreach ( [ 'brand_color' => 'Brand colour', 'accent_color' => 'Accent colour' ] as $key => $label ) {
		$wp_customize->add_setting( 'lendly_' . $key, [ 'default' => $d[ $key ], 'sanitize_callback' => 'sanitize_hex_color', 'transport' => 'refresh' ] );
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'lendly_' . $key, [ 'label' => $label, 'section' => 'lendly_brand' ] ) );
	}

	$text = [
		'hero_eyebrow'      => [ 'lendly_hero', 'Small line above heading', 'text' ],
		'hero_title'        => [ 'lendly_hero', 'Heading', 'text' ],
		'hero_text'         => [ 'lendly_hero', 'Intro text', 'textarea' ],
		'apply_url'         => [ 'lendly_hero', 'Apply page URL', 'text' ],
		'company_name'      => [ 'lendly_company', 'Legal company name', 'text' ],
		'abn'               => [ 'lendly_company', 'ABN', 'text' ],
		'acl'               => [ 'lendly_company', 'Australian Credit Licence number', 'text' ],
		'phone'             => [ 'lendly_company', 'Phone', 'text' ],
		'email'             => [ 'lendly_company', 'Email', 'text' ],
		'hours'             => [ 'lendly_company', 'Opening hours', 'text' ],
		'footer_disclaimer' => [ 'lendly_company', 'Footer disclaimer', 'textarea' ],
	];
	foreach ( $text as $key => [ $section, $label, $type ] ) {
		$wp_customize->add_setting( 'lendly_' . $key, [
			'default'           => $d[ $key ],
			'sanitize_callback' => 'textarea' === $type ? 'sanitize_textarea_field' : 'sanitize_text_field',
		] );
		$wp_customize->add_control( 'lendly_' . $key, [ 'label' => $label, 'section' => $section, 'type' => $type ] );
	}
} );

/** Brand colours as CSS variables, consumed by theme.css and the plugin's loans.css. */
function lendly_brand_css(): string {
	$brand  = sanitize_hex_color( lendly_mod( 'brand_color' ) ) ?: '#5b3fd6';
	$accent = sanitize_hex_color( lendly_mod( 'accent_color' ) ) ?: '#f2b134';
	return ":root{--brand:{$brand};--accent:{$accent};}";
}
