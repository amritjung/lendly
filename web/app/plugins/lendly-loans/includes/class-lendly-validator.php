<?php
defined( 'ABSPATH' ) || exit;

/**
 * Server-side validation for the application. Never trust the browser:
 * every field is re-checked here even though apply.js validates too.
 */
class Lendly_Validator {

	const STATES       = [ 'ACT', 'NSW', 'NT', 'QLD', 'SA', 'TAS', 'VIC', 'WA' ];
	const RESIDENTIAL  = [ 'renting', 'mortgage', 'own_outright', 'boarding', 'with_family', 'other' ];
	const EMPLOYMENT   = [ 'full_time', 'part_time', 'casual', 'self_employed', 'contract', 'retired', 'centrelink', 'other' ];
	const FREQUENCIES  = [ 'weekly' => 52, 'fortnightly' => 26, 'monthly' => 12, 'annually' => 1 ];

	private array $errors = [];
	private array $clean  = [];

	public function __construct( private array $in ) {}

	/** @return array{0: array, 1: array} [clean data, errors] */
	public function run(): array {
		$s = Lendly_Settings::get();

		// Loan.
		$amount = $this->num( 'amount' );
		if ( null === $amount || $amount < $s['amount_min'] || $amount > $s['amount_max'] ) {
			$this->err( 'amount', sprintf( 'Choose an amount between $%s and $%s.', number_format( $s['amount_min'] ), number_format( $s['amount_max'] ) ) );
		}
		$term = absint( $this->in['term_months'] ?? 0 );
		if ( ! in_array( $term, Lendly_Settings::terms(), true ) ) {
			$this->err( 'term_months', 'Choose a loan term.' );
		}
		$this->enum( 'purpose', Lendly_Settings::purposes(), 'Choose a loan purpose.' );
		$this->clean['amount']      = $amount;
		$this->clean['term_months'] = $term;

		// About you.
		$this->text( 'first_name', 'Enter your first name.', 50 );
		$this->text( 'last_name', 'Enter your last name.', 50 );

		$dob = sanitize_text_field( $this->in['dob'] ?? '' );
		$d   = DateTimeImmutable::createFromFormat( '!Y-m-d', $dob );
		if ( ! $d || $d->format( 'Y-m-d' ) !== $dob ) {
			$this->err( 'dob', 'Enter your date of birth.' );
		} elseif ( $d->diff( new DateTimeImmutable( 'today' ) )->y < 18 ) {
			$this->err( 'dob', 'You must be 18 or older to apply.' );
		}
		$this->clean['dob'] = $dob;

		$email = sanitize_email( $this->in['email'] ?? '' );
		if ( ! is_email( $email ) ) {
			$this->err( 'email', 'Enter a valid email address.' );
		}
		$this->clean['email'] = $email;

		$mobile = preg_replace( '/[\s()-]/', '', (string) ( $this->in['mobile'] ?? '' ) );
		if ( ! preg_match( '/^(\+?61|0)4\d{8}$/', $mobile ) ) {
			$this->err( 'mobile', 'Enter an Australian mobile number, e.g. 0412 345 678.' );
		}
		$this->clean['mobile'] = $mobile;

		// Address.
		$this->text( 'street', 'Enter your street address.', 120 );
		$this->text( 'suburb', 'Enter your suburb.', 60 );
		$this->enum( 'state', self::STATES, 'Choose your state.' );
		$postcode = (string) ( $this->in['postcode'] ?? '' );
		if ( ! preg_match( '/^\d{4}$/', $postcode ) ) {
			$this->err( 'postcode', 'Enter a 4-digit postcode.' );
		}
		$this->clean['postcode'] = $postcode;
		$this->enum( 'residential_status', self::RESIDENTIAL, 'Choose your living situation.' );
		$this->int( 'years_at_address', 0, 99, 'Enter years at this address.' );

		// Income.
		$this->enum( 'employment_status', self::EMPLOYMENT, 'Choose your employment status.' );
		$this->text( 'employer', '', 100, false );
		$this->money( 'income_amount', 'Enter your income after tax.' );
		$this->enum( 'income_frequency', array_keys( self::FREQUENCIES ), 'Choose how often you are paid.' );

		// Expenses (monthly).
		$this->money( 'housing_monthly', 'Enter your monthly rent or mortgage (0 if none).' );
		$this->money( 'living_monthly', 'Enter your monthly living expenses.' );
		$this->money( 'debts_monthly', 'Enter your monthly debt repayments (0 if none).' );
		$this->int( 'dependants', 0, 20, 'Enter number of dependants.' );

		// Consents.
		foreach ( [ 'consent_privacy', 'consent_credit_check', 'consent_electronic' ] as $c ) {
			if ( empty( $this->in[ $c ] ) ) {
				$this->err( $c, 'This consent is required to assess your application.' );
			}
			$this->clean[ $c ] = true;
		}
		$this->clean['consent_marketing'] = ! empty( $this->in['consent_marketing'] );

		return [ $this->clean, $this->errors ];
	}

	/** Monthly net income — used for a basic serviceability snapshot in admin. */
	public static function monthly_income( array $p ): float {
		$per_year = self::FREQUENCIES[ $p['income_frequency'] ?? 'monthly' ] ?? 12;
		return round( (float) ( $p['income_amount'] ?? 0 ) * $per_year / 12, 2 );
	}

	private function err( string $field, string $msg ): void {
		$this->errors[ $field ] = $msg;
	}

	private function text( string $k, string $msg, int $max, bool $required = true ): void {
		$v = sanitize_text_field( $this->in[ $k ] ?? '' );
		if ( $required && '' === $v ) {
			$this->err( $k, $msg );
		}
		$this->clean[ $k ] = mb_substr( $v, 0, $max );
	}

	private function enum( string $k, array $allowed, string $msg ): void {
		$v = sanitize_text_field( $this->in[ $k ] ?? '' );
		if ( ! in_array( $v, $allowed, true ) ) {
			$this->err( $k, $msg );
		}
		$this->clean[ $k ] = $v;
	}

	private function num( string $k ): ?float {
		$v = $this->in[ $k ] ?? null;
		return is_numeric( $v ) ? (float) $v : null;
	}

	private function money( string $k, string $msg ): void {
		$v = $this->num( $k );
		if ( null === $v || $v < 0 || $v > 10000000 ) {
			$this->err( $k, $msg );
		}
		$this->clean[ $k ] = round( (float) $v, 2 );
	}

	private function int( string $k, int $min, int $max, string $msg ): void {
		$raw = $this->in[ $k ] ?? '';
		if ( ! is_numeric( $raw ) || (int) $raw < $min || (int) $raw > $max ) {
			$this->err( $k, $msg );
		}
		$this->clean[ $k ] = (int) $raw;
	}
}
