<?php
defined( 'ABSPATH' ) || exit;

/**
 * POST /wp-json/lendly/v1/applications
 */
class Lendly_REST {

	const RATE_LIMIT = 5; // submissions per IP per hour.

	public static function init(): void {
		add_action( 'rest_api_init', [ __CLASS__, 'routes' ] );
	}

	public static function routes(): void {
		register_rest_route( 'lendly/v1', '/applications', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ __CLASS__, 'create' ],
			'permission_callback' => [ __CLASS__, 'check_nonce' ],
		] );
	}

	/** Public endpoint, but only callable from our own form. */
	public static function check_nonce( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'x_lendly_nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'lendly_apply' ) ) {
			return new WP_Error( 'lendly_bad_nonce', __( 'Your session expired. Refresh the page and try again.', 'lendly' ), [ 'status' => 403 ] );
		}
		return true;
	}

	public static function create( WP_REST_Request $request ) {
		$data = (array) $request->get_json_params();

		// Honeypot: bots fill hidden fields. Pretend success, store nothing.
		if ( ! empty( $data['website'] ) ) {
			return rest_ensure_response( [ 'ok' => true, 'reference' => 'LND-' . strtoupper( wp_generate_password( 8, false ) ) ] );
		}

		$ip_hash = hash_hmac( 'sha256', self::client_ip(), wp_salt( 'nonce' ) );
		$key     = 'lendly_rl_' . substr( $ip_hash, 0, 32 );
		$count   = (int) get_transient( $key );
		if ( $count >= self::RATE_LIMIT ) {
			return new WP_Error( 'lendly_rate_limited', __( 'Too many applications from this connection. Please call us instead.', 'lendly' ), [ 'status' => 429 ] );
		}

		[ $clean, $errors ] = ( new Lendly_Validator( $data ) )->run();
		if ( $errors ) {
			return new WP_Error( 'lendly_invalid', __( 'Please fix the highlighted fields.', 'lendly' ), [ 'status' => 422, 'fields' => $errors ] );
		}

		$plain = [
			'amount'      => $clean['amount'],
			'term_months' => $clean['term_months'],
			'purpose'     => $clean['purpose'],
			'ip_hash'     => $ip_hash,
		];
		$personal                 = $clean;
		$personal['submitted_ua'] = substr( sanitize_text_field( $request->get_header( 'user_agent' ) ?? '' ), 0, 255 );
		$personal['consent_at']   = gmdate( 'c' );

		$saved = Lendly_DB::insert( $plain, $personal );
		if ( ! $saved ) {
			return new WP_Error( 'lendly_db', __( 'We could not save your application. Please try again or call us.', 'lendly' ), [ 'status' => 500 ] );
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		Lendly_Mailer::new_application( $saved['id'], $saved['reference'], $clean );

		/** Hook for CRM / credit-check integrations. */
		do_action( 'lendly_application_created', $saved['id'], $saved['reference'] );

		return rest_ensure_response( [ 'ok' => true, 'reference' => $saved['reference'] ] );
	}

	/** Uses REMOTE_ADDR only; if you're behind Cloudflare/a proxy, trust its header explicitly. */
	private static function client_ip(): string {
		return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0' ) );
	}
}
