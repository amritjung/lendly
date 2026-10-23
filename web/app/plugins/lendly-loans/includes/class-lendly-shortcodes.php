<?php
defined( 'ABSPATH' ) || exit;

/**
 * [lendly_calculator apply_url="/apply/"]
 * [lendly_apply]
 */
class Lendly_Shortcodes {

	public static function init(): void {
		add_shortcode( 'lendly_calculator', [ __CLASS__, 'calculator' ] );
		add_shortcode( 'lendly_apply', [ __CLASS__, 'apply' ] );
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ] );
	}

	public static function register_assets(): void {
		wp_register_style( 'lendly', LENDLY_URL . 'assets/css/loans.css', [], LENDLY_VERSION );
		wp_register_script( 'lendly-calculator', LENDLY_URL . 'assets/js/calculator.js', [], LENDLY_VERSION, [ 'strategy' => 'defer' ] );
		wp_register_script( 'lendly-apply', LENDLY_URL . 'assets/js/apply.js', [ 'lendly-calculator' ], LENDLY_VERSION, [ 'strategy' => 'defer' ] );

		wp_add_inline_script(
			'lendly-calculator',
			'window.LENDLY = ' . wp_json_encode( Lendly_Settings::public_config() ) . ';',
			'before'
		);
	}

	public static function calculator( $atts ): string {
		$atts = shortcode_atts( [ 'apply_url' => home_url( '/apply/' ), 'title' => __( 'How much would you like to borrow?', 'lendly' ) ], $atts );
		wp_enqueue_style( 'lendly' );
		wp_enqueue_script( 'lendly-calculator' );

		$s = Lendly_Settings::get();
		ob_start();
		?>
		<div class="lendly-calc" data-apply-url="<?php echo esc_url( $atts['apply_url'] ); ?>">
			<h3 class="lendly-calc__title"><?php echo esc_html( $atts['title'] ); ?></h3>

			<label class="lendly-calc__label" for="lendly-amount"><?php esc_html_e( 'Loan amount', 'lendly' ); ?>
				<output class="lendly-calc__value" data-out="amount"></output>
			</label>
			<input id="lendly-amount" type="range" data-in="amount"
				min="<?php echo esc_attr( $s['amount_min'] ); ?>" max="<?php echo esc_attr( $s['amount_max'] ); ?>"
				step="<?php echo esc_attr( $s['amount_step'] ); ?>" value="<?php echo esc_attr( $s['amount_default'] ); ?>">

			<label class="lendly-calc__label" for="lendly-term"><?php esc_html_e( 'Loan term', 'lendly' ); ?></label>
			<div class="lendly-calc__terms" role="radiogroup" aria-label="<?php esc_attr_e( 'Loan term', 'lendly' ); ?>">
				<?php foreach ( Lendly_Settings::terms() as $t ) : ?>
					<label class="lendly-chip">
						<input type="radio" name="lendly-term" data-in="term" value="<?php echo esc_attr( $t ); ?>" <?php checked( (int) $s['term_default'], $t ); ?>>
						<span><?php echo esc_html( $t % 12 === 0 ? sprintf( _n( '%d year', '%d years', $t / 12, 'lendly' ), $t / 12 ) : sprintf( '%d mths', $t ) ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>

			<label class="lendly-calc__label" for="lendly-frequency"><?php esc_html_e( 'Repayments', 'lendly' ); ?></label>
			<select id="lendly-frequency" data-in="frequency">
				<option value="weekly"><?php esc_html_e( 'Weekly', 'lendly' ); ?></option>
				<option value="fortnightly"><?php esc_html_e( 'Fortnightly', 'lendly' ); ?></option>
				<option value="monthly" selected><?php esc_html_e( 'Monthly', 'lendly' ); ?></option>
			</select>

			<div class="lendly-calc__result" aria-live="polite">
				<span><?php esc_html_e( 'Estimated repayment', 'lendly' ); ?></span>
				<strong data-out="repayment"></strong>
				<small data-out="total"></small>
			</div>

			<a class="lendly-btn" data-out="apply-link" href="<?php echo esc_url( $atts['apply_url'] ); ?>"><?php esc_html_e( 'Get started', 'lendly' ); ?></a>

			<p class="lendly-calc__disclaimer">
				<?php
				printf(
					/* translators: 1: rate, 2: comparison rate */
					esc_html__( 'Rates from %1$s%% p.a. (comparison rate %2$s%% p.a.*). Estimate only, based on the lowest rate; your actual rate depends on your circumstances and credit assessment.', 'lendly' ),
					esc_html( number_format( (float) $s['rate_from'], 2 ) ),
					esc_html( number_format( (float) $s['comparison_rate'], 2 ) )
				);
				?>
				<br>* <?php echo esc_html( $s['comparison_example'] ); ?>
				<?php esc_html_e( 'WARNING: This comparison rate is true only for the examples given and may not include all fees and charges. Different terms, fees or other loan amounts might result in a different comparison rate.', 'lendly' ); ?>
			</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	public static function apply(): string {
		wp_enqueue_style( 'lendly' );
		wp_enqueue_script( 'lendly-apply' );
		wp_add_inline_script(
			'lendly-apply',
			'window.LENDLY_APPLY = ' . wp_json_encode( [
				'endpoint' => esc_url_raw( rest_url( 'lendly/v1/applications' ) ),
				'nonce'    => wp_create_nonce( 'lendly_apply' ),
				'restNonce' => wp_create_nonce( 'wp_rest' ), // keeps logged-in users authenticated on the REST call.
			] ) . ';',
			'before'
		);

		$s        = Lendly_Settings::get();
		$amount   = isset( $_GET['amount'] ) ? absint( $_GET['amount'] ) : (int) $s['amount_default']; // phpcs:ignore WordPress.Security.NonceVerification
		$term     = isset( $_GET['term'] ) ? absint( $_GET['term'] ) : (int) $s['term_default']; // phpcs:ignore WordPress.Security.NonceVerification
		$amount   = min( max( $amount, (int) $s['amount_min'] ), (int) $s['amount_max'] );

		ob_start();
		include LENDLY_DIR . 'includes/views/apply-form.php';
		return (string) ob_get_clean();
	}

	/** Small helper used by the view to print a labelled select. */
	public static function select( string $name, string $label, array $options, string $selected = '' ): void {
		printf( '<div class="lendly-field"><label for="lf-%1$s">%2$s</label><select id="lf-%1$s" name="%1$s" required><option value="">%3$s</option>', esc_attr( $name ), esc_html( $label ), esc_html__( 'Select…', 'lendly' ) );
		foreach ( $options as $value => $text ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( $selected, $value, false ), esc_html( $text ) );
		}
		echo '</select><span class="lendly-error" data-error-for="' . esc_attr( $name ) . '"></span></div>';
	}

	public static function input( string $name, string $label, string $type = 'text', array $attrs = [] ): void {
		$extra = '';
		foreach ( $attrs as $k => $v ) {
			$extra .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( $v ) );
		}
		printf(
			'<div class="lendly-field"><label for="lf-%1$s">%2$s</label><input id="lf-%1$s" name="%1$s" type="%3$s"%4$s><span class="lendly-error" data-error-for="%1$s"></span></div>',
			esc_attr( $name ),
			esc_html( $label ),
			esc_attr( $type ),
			$extra // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
		);
	}
}
