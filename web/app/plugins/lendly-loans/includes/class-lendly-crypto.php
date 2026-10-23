<?php
defined( 'ABSPATH' ) || exit;

/**
 * Authenticated encryption for applicant data (libsodium secretbox).
 *
 * Put a dedicated key in wp-config.php (generate with:
 *   php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
 * ):
 *   define( 'LENDLY_ENCRYPTION_KEY', 'base64-string-here' );
 *
 * Back the key up separately from the database: lose it and stored
 * applications can't be decrypted.
 */
class Lendly_Crypto {

	public static function has_dedicated_key(): bool {
		return defined( 'LENDLY_ENCRYPTION_KEY' )
			&& SODIUM_CRYPTO_SECRETBOX_KEYBYTES === strlen( (string) base64_decode( LENDLY_ENCRYPTION_KEY, true ) );
	}

	private static function key(): string {
		if ( self::has_dedicated_key() ) {
			return base64_decode( LENDLY_ENCRYPTION_KEY, true );
		}
		// Development fallback only: derived from WordPress salts.
		return sodium_crypto_generichash( wp_salt( 'auth' ) . 'lendly', '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES );
	}

	public static function encrypt( string $plaintext ): string {
		$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cipher = sodium_crypto_secretbox( $plaintext, $nonce, self::key() );
		return 'v1:' . base64_encode( $nonce . $cipher );
	}

	public static function decrypt( string $encoded ): ?string {
		if ( ! str_starts_with( $encoded, 'v1:' ) ) {
			return null;
		}
		$raw = base64_decode( substr( $encoded, 3 ), true );
		if ( false === $raw || strlen( $raw ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return null;
		}
		$nonce  = substr( $raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cipher = substr( $raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$plain  = sodium_crypto_secretbox_open( $cipher, $nonce, self::key() );
		return false === $plain ? null : $plain;
	}
}
