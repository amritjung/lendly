<?php
/**
 * Multi-step application form. Variables from Lendly_Shortcodes::apply():
 * $s (settings), $amount, $term.
 */
defined( 'ABSPATH' ) || exit;

$S       = 'Lendly_Shortcodes';
$terms   = [];
foreach ( Lendly_Settings::terms() as $t ) {
	$terms[ $t ] = $t % 12 === 0 ? sprintf( _n( '%d year', '%d years', $t / 12, 'lendly' ), $t / 12 ) : sprintf( '%d months', $t );
}
$purposes = array_combine( Lendly_Settings::purposes(), Lendly_Settings::purposes() );
$states   = array_combine( Lendly_Validator::STATES, Lendly_Validator::STATES );
$steps    = [ __( 'Your loan', 'lendly' ), __( 'About you', 'lendly' ), __( 'Address', 'lendly' ), __( 'Income & expenses', 'lendly' ), __( 'Review', 'lendly' ) ];
?>
<div class="lendly-apply">
	<ol class="lendly-progress" aria-label="<?php esc_attr_e( 'Application progress', 'lendly' ); ?>">
		<?php foreach ( $steps as $i => $label ) : ?>
			<li data-progress="<?php echo (int) $i; ?>"><span><?php echo (int) $i + 1; ?></span><?php echo esc_html( $label ); ?></li>
		<?php endforeach; ?>
	</ol>

	<form class="lendly-form" novalidate>
		<!-- Honeypot: hidden from people, bots fill it. -->
		<div class="lendly-hp" aria-hidden="true">
			<label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
		</div>

		<fieldset class="lendly-step" data-step="0">
			<legend><?php esc_html_e( 'Tell us about your loan', 'lendly' ); ?></legend>
			<?php
			$S::input( 'amount', __( 'Loan amount ($)', 'lendly' ), 'number', [
				'min' => $s['amount_min'], 'max' => $s['amount_max'], 'step' => 100, 'value' => $amount, 'required' => 'required', 'inputmode' => 'numeric',
			] );
			$S::select( 'term_months', __( 'Loan term', 'lendly' ), $terms, (string) $term );
			$S::select( 'purpose', __( 'What is the loan for?', 'lendly' ), $purposes );
			?>
		</fieldset>

		<fieldset class="lendly-step" data-step="1" hidden>
			<legend><?php esc_html_e( 'About you', 'lendly' ); ?></legend>
			<div class="lendly-grid">
				<?php
				$S::input( 'first_name', __( 'First name', 'lendly' ), 'text', [ 'required' => 'required', 'autocomplete' => 'given-name', 'maxlength' => 50 ] );
				$S::input( 'last_name', __( 'Last name', 'lendly' ), 'text', [ 'required' => 'required', 'autocomplete' => 'family-name', 'maxlength' => 50 ] );
				?>
			</div>
			<?php
			$S::input( 'dob', __( 'Date of birth', 'lendly' ), 'date', [ 'required' => 'required', 'autocomplete' => 'bday', 'max' => gmdate( 'Y-m-d', strtotime( '-18 years' ) ) ] );
			$S::input( 'email', __( 'Email', 'lendly' ), 'email', [ 'required' => 'required', 'autocomplete' => 'email' ] );
			$S::input( 'mobile', __( 'Mobile', 'lendly' ), 'tel', [ 'required' => 'required', 'autocomplete' => 'tel', 'pattern' => '^(\+?61|0)4[\d\s]{8,12}$', 'placeholder' => '0412 345 678' ] );
			?>
		</fieldset>

		<fieldset class="lendly-step" data-step="2" hidden>
			<legend><?php esc_html_e( 'Where do you live?', 'lendly' ); ?></legend>
			<?php
			$S::input( 'street', __( 'Street address', 'lendly' ), 'text', [ 'required' => 'required', 'autocomplete' => 'address-line1' ] );
			?>
			<div class="lendly-grid lendly-grid--3">
				<?php
				$S::input( 'suburb', __( 'Suburb', 'lendly' ), 'text', [ 'required' => 'required', 'autocomplete' => 'address-level2' ] );
				$S::select( 'state', __( 'State', 'lendly' ), $states );
				$S::input( 'postcode', __( 'Postcode', 'lendly' ), 'text', [ 'required' => 'required', 'pattern' => '^\d{4}$', 'inputmode' => 'numeric', 'maxlength' => 4, 'autocomplete' => 'postal-code' ] );
				?>
			</div>
			<?php
			$S::select( 'residential_status', __( 'Living situation', 'lendly' ), [
				'renting'      => __( 'Renting', 'lendly' ),
				'mortgage'     => __( 'Own with a mortgage', 'lendly' ),
				'own_outright' => __( 'Own outright', 'lendly' ),
				'boarding'     => __( 'Boarding', 'lendly' ),
				'with_family'  => __( 'Living with family', 'lendly' ),
				'other'        => __( 'Other', 'lendly' ),
			] );
			$S::input( 'years_at_address', __( 'Years at this address', 'lendly' ), 'number', [ 'required' => 'required', 'min' => 0, 'max' => 99, 'inputmode' => 'numeric' ] );
			?>
		</fieldset>

		<fieldset class="lendly-step" data-step="3" hidden>
			<legend><?php esc_html_e( 'Income and expenses', 'lendly' ); ?></legend>
			<p class="lendly-help"><?php esc_html_e( 'We ask this so we only lend what you can comfortably repay.', 'lendly' ); ?></p>
			<?php
			$S::select( 'employment_status', __( 'Employment status', 'lendly' ), [
				'full_time'     => __( 'Full-time', 'lendly' ),
				'part_time'     => __( 'Part-time', 'lendly' ),
				'casual'        => __( 'Casual', 'lendly' ),
				'self_employed' => __( 'Self-employed', 'lendly' ),
				'contract'      => __( 'Contractor', 'lendly' ),
				'retired'       => __( 'Retired', 'lendly' ),
				'centrelink'    => __( 'Government benefits', 'lendly' ),
				'other'         => __( 'Other', 'lendly' ),
			] );
			$S::input( 'employer', __( 'Employer (optional)', 'lendly' ), 'text', [ 'autocomplete' => 'organization' ] );
			?>
			<div class="lendly-grid">
				<?php
				$S::input( 'income_amount', __( 'Income after tax ($)', 'lendly' ), 'number', [ 'required' => 'required', 'min' => 0, 'step' => 'any', 'inputmode' => 'decimal' ] );
				$S::select( 'income_frequency', __( 'Paid', 'lendly' ), [
					'weekly'      => __( 'Weekly', 'lendly' ),
					'fortnightly' => __( 'Fortnightly', 'lendly' ),
					'monthly'     => __( 'Monthly', 'lendly' ),
					'annually'    => __( 'Annually', 'lendly' ),
				] );
				?>
			</div>
			<div class="lendly-grid">
				<?php
				$S::input( 'housing_monthly', __( 'Rent or mortgage per month ($)', 'lendly' ), 'number', [ 'required' => 'required', 'min' => 0, 'step' => 'any', 'inputmode' => 'decimal' ] );
				$S::input( 'living_monthly', __( 'Other living expenses per month ($)', 'lendly' ), 'number', [ 'required' => 'required', 'min' => 0, 'step' => 'any', 'inputmode' => 'decimal' ] );
				$S::input( 'debts_monthly', __( 'Other loan & card repayments per month ($)', 'lendly' ), 'number', [ 'required' => 'required', 'min' => 0, 'step' => 'any', 'inputmode' => 'decimal' ] );
				$S::input( 'dependants', __( 'Number of dependants', 'lendly' ), 'number', [ 'required' => 'required', 'min' => 0, 'max' => 20, 'inputmode' => 'numeric', 'value' => 0 ] );
				?>
			</div>
		</fieldset>

		<fieldset class="lendly-step" data-step="4" hidden>
			<legend><?php esc_html_e( 'Review and submit', 'lendly' ); ?></legend>
			<dl class="lendly-review" data-review></dl>

			<label class="lendly-check"><input type="checkbox" name="consent_privacy" required>
				<?php
				printf(
					/* translators: 1: privacy URL, 2: credit guide URL */
					wp_kses( __( 'I have read the <a href="%1$s" target="_blank">Privacy Policy</a> and <a href="%2$s" target="_blank">Credit Guide</a>.', 'lendly' ), [ 'a' => [ 'href' => [], 'target' => [] ] ] ),
					esc_url( $s['privacy_url'] ),
					esc_url( $s['credit_guide_url'] )
				);
				?>
			</label>
			<span class="lendly-error" data-error-for="consent_privacy"></span>

			<label class="lendly-check"><input type="checkbox" name="consent_credit_check" required>
				<?php esc_html_e( 'I consent to a credit check and to my identity and income being verified.', 'lendly' ); ?>
			</label>
			<span class="lendly-error" data-error-for="consent_credit_check"></span>

			<label class="lendly-check"><input type="checkbox" name="consent_electronic" required>
				<?php esc_html_e( 'I agree to receive documents and notices electronically.', 'lendly' ); ?>
			</label>
			<span class="lendly-error" data-error-for="consent_electronic"></span>

			<label class="lendly-check"><input type="checkbox" name="consent_marketing">
				<?php esc_html_e( 'Send me occasional offers (optional).', 'lendly' ); ?>
			</label>
		</fieldset>

		<div class="lendly-form__alert" role="alert" hidden></div>

		<div class="lendly-form__nav">
			<button type="button" class="lendly-btn lendly-btn--ghost" data-nav="back" hidden><?php esc_html_e( 'Back', 'lendly' ); ?></button>
			<button type="button" class="lendly-btn" data-nav="next"><?php esc_html_e( 'Continue', 'lendly' ); ?></button>
			<button type="submit" class="lendly-btn" data-nav="submit" hidden><?php esc_html_e( 'Submit application', 'lendly' ); ?></button>
		</div>
	</form>

	<div class="lendly-success" hidden>
		<h3><?php esc_html_e( 'Application received', 'lendly' ); ?></h3>
		<p><?php esc_html_e( 'Your reference number is', 'lendly' ); ?> <strong data-reference></strong>.</p>
		<p><?php esc_html_e( "We've emailed you a copy and will be in touch within one business day.", 'lendly' ); ?></p>
	</div>
</div>
