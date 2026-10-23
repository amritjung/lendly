<?php
defined( 'ABSPATH' ) || exit;

/**
 * Loans > Settings. All rate/fee figures shown on the site come from here,
 * so marketing copy never drifts from the real product.
 */
class Lendly_Settings {

	const OPTION = 'lendly_settings';

	public static function defaults(): array {
		return [
			'amount_min'         => 2000,
			'amount_max'         => 50000,
			'amount_default'     => 15000,
			'amount_step'        => 500,
			'terms'              => '12,24,36,48,60,84',
			'term_default'       => 36,
			'rate_from'          => 8.95,
			'comparison_rate'    => 9.95,
			'comparison_example' => 'Comparison rate based on a $30,000 unsecured loan over 5 years.',
			'establishment_fee'  => 0,
			'monthly_fee'        => 0,
			'purposes'           => "Car\nDebt consolidation\nHome improvement\nTravel\nWedding\nMedical or dental\nOther",
			'notify_email'       => get_option( 'admin_email' ),
			'privacy_url'        => '/privacy-policy/',
			'credit_guide_url'   => '/credit-guide/',
		];
	}

	public static function get( ?string $key = null ) {
		$opts = wp_parse_args( get_option( self::OPTION, [] ), self::defaults() );
		return null === $key ? $opts : ( $opts[ $key ] ?? null );
	}

	public static function terms(): array {
		$terms = array_filter( array_map( 'absint', explode( ',', (string) self::get( 'terms' ) ) ) );
		sort( $terms );
		return array_values( array_unique( $terms ) );
	}

	public static function purposes(): array {
		return array_values( array_filter( array_map( 'trim', explode( "\n", (string) self::get( 'purposes' ) ) ) ) );
	}

	/** Public config for the JS calculator and form. */
	public static function public_config(): array {
		$o = self::get();
		return [
			'amountMin'        => (int) $o['amount_min'],
			'amountMax'        => (int) $o['amount_max'],
			'amountDefault'    => (int) $o['amount_default'],
			'amountStep'       => (int) $o['amount_step'],
			'terms'            => self::terms(),
			'termDefault'      => (int) $o['term_default'],
			'rateFrom'         => (float) $o['rate_from'],
			'comparisonRate'   => (float) $o['comparison_rate'],
			'establishmentFee' => (float) $o['establishment_fee'],
			'monthlyFee'       => (float) $o['monthly_fee'],
		];
	}

	public static function init(): void {
		add_action( 'admin_init', [ __CLASS__, 'register' ] );
	}

	public static function register(): void {
		register_setting( 'lendly_settings_group', self::OPTION, [
			'type'              => 'array',
			'sanitize_callback' => [ __CLASS__, 'sanitize' ],
		] );

		add_settings_section( 'lendly_product', __( 'Loan product', 'lendly' ), '__return_false', 'lendly-settings' );
		add_settings_section( 'lendly_ops', __( 'Operations', 'lendly' ), '__return_false', 'lendly-settings' );

		$fields = [
			'amount_min'         => [ 'Minimum amount ($)', 'number', 'lendly_product' ],
			'amount_max'         => [ 'Maximum amount ($)', 'number', 'lendly_product' ],
			'amount_default'     => [ 'Default amount on calculator ($)', 'number', 'lendly_product' ],
			'amount_step'        => [ 'Slider step ($)', 'number', 'lendly_product' ],
			'terms'              => [ 'Terms offered (months, comma separated)', 'text', 'lendly_product' ],
			'term_default'       => [ 'Default term (months)', 'number', 'lendly_product' ],
			'rate_from'          => [ 'Interest rate from (% p.a.)', 'number', 'lendly_product' ],
			'comparison_rate'    => [ 'Comparison rate (% p.a.)', 'number', 'lendly_product' ],
			'comparison_example' => [ 'Comparison rate example text', 'text', 'lendly_product' ],
			'establishment_fee'  => [ 'Establishment fee ($)', 'number', 'lendly_product' ],
			'monthly_fee'        => [ 'Monthly account fee ($)', 'number', 'lendly_product' ],
			'purposes'           => [ 'Loan purposes (one per line)', 'textarea', 'lendly_product' ],
			'notify_email'       => [ 'Notify this email of new applications', 'email', 'lendly_ops' ],
			'privacy_url'        => [ 'Privacy policy URL', 'text', 'lendly_ops' ],
			'credit_guide_url'   => [ 'Credit guide URL', 'text', 'lendly_ops' ],
		];

		foreach ( $fields as $key => [ $label, $type, $section ] ) {
			add_settings_field( $key, esc_html( $label ), [ __CLASS__, 'render_field' ], 'lendly-settings', $section, [
				'key'       => $key,
				'type'      => $type,
				'label_for' => 'lendly_' . $key,
			] );
		}
	}

	public static function render_field( array $args ): void {
		$key   = $args['key'];
		$value = self::get( $key );
		$name  = self::OPTION . '[' . $key . ']';
		$id    = 'lendly_' . $key;

		if ( 'textarea' === $args['type'] ) {
			printf( '<textarea id="%s" name="%s" rows="7" class="large-text">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
			return;
		}
		$step = 'number' === $args['type'] ? ' step="any" min="0"' : '';
		printf(
			'<input id="%s" type="%s" name="%s" value="%s" class="regular-text"%s>',
			esc_attr( $id ),
			esc_attr( $args['type'] ),
			esc_attr( $name ),
			esc_attr( $value ),
			$step // phpcs:ignore WordPress.Security.EscapeOutput -- static string.
		);
	}

	public static function sanitize( $input ): array {
		$d   = self::defaults();
		$in  = is_array( $input ) ? $input : [];
		$out = [];

		foreach ( [ 'amount_min', 'amount_max', 'amount_default', 'amount_step', 'term_default' ] as $k ) {
			$out[ $k ] = isset( $in[ $k ] ) ? absint( $in[ $k ] ) : $d[ $k ];
		}
		foreach ( [ 'rate_from', 'comparison_rate', 'establishment_fee', 'monthly_fee' ] as $k ) {
			$out[ $k ] = isset( $in[ $k ] ) ? round( max( 0, (float) $in[ $k ] ), 2 ) : $d[ $k ];
		}
		$out['terms']              = implode( ',', array_filter( array_map( 'absint', explode( ',', (string) ( $in['terms'] ?? $d['terms'] ) ) ) ) );
		$out['comparison_example'] = sanitize_text_field( $in['comparison_example'] ?? $d['comparison_example'] );
		$out['purposes']           = sanitize_textarea_field( $in['purposes'] ?? $d['purposes'] );
		$out['notify_email']       = sanitize_email( $in['notify_email'] ?? $d['notify_email'] );
		$out['privacy_url']        = esc_url_raw( $in['privacy_url'] ?? $d['privacy_url'] );
		$out['credit_guide_url']   = esc_url_raw( $in['credit_guide_url'] ?? $d['credit_guide_url'] );

		if ( $out['amount_min'] >= $out['amount_max'] ) {
			add_settings_error( self::OPTION, 'amount', __( 'Minimum amount must be below maximum.', 'lendly' ) );
			$out['amount_min'] = $d['amount_min'];
			$out['amount_max'] = $d['amount_max'];
		}
		$out['amount_default'] = min( max( $out['amount_default'], $out['amount_min'] ), $out['amount_max'] );
		$out['amount_step']    = max( 1, $out['amount_step'] );

		return $out;
	}
}
