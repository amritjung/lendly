<?php
defined( 'ABSPATH' ) || exit;

/**
 * Custom table for loan applications.
 * Personal details live in `payload`, encrypted. Only non-identifying
 * fields (amount, term, purpose, status) are stored in plain columns
 * so the admin list can sort and filter without decrypting.
 */
class Lendly_DB {

	const DB_VERSION = '1.0.0';
	const STATUSES   = [ 'new', 'reviewing', 'more_info', 'approved', 'declined', 'withdrawn' ];

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'lendly_applications';
	}

	public static function activate(): void {
		self::install();

		// Give administrators the capability; add it to other roles as needed.
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( LENDLY_CAP );
		}
	}

	public static function maybe_upgrade(): void {
		if ( get_option( 'lendly_db_version' ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	private static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		dbDelta( "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			reference varchar(20) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'new',
			amount decimal(10,2) NOT NULL,
			term_months smallint(5) unsigned NOT NULL,
			purpose varchar(50) NOT NULL,
			payload longtext NOT NULL,
			notes longtext NULL,
			ip_hash char(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY reference (reference),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset};" );

		update_option( 'lendly_db_version', self::DB_VERSION );
	}

	public static function insert( array $plain, array $personal ): ?array {
		global $wpdb;

		$now       = current_time( 'mysql', true );
		$reference = self::unique_reference();

		$ok = $wpdb->insert(
			self::table(),
			[
				'reference'   => $reference,
				'status'      => 'new',
				'amount'      => $plain['amount'],
				'term_months' => $plain['term_months'],
				'purpose'     => $plain['purpose'],
				'payload'     => Lendly_Crypto::encrypt( wp_json_encode( $personal ) ),
				'ip_hash'     => $plain['ip_hash'],
				'created_at'  => $now,
				'updated_at'  => $now,
			],
			[ '%s', '%s', '%f', '%d', '%s', '%s', '%s', '%s', '%s' ]
		);

		return $ok ? [ 'id' => (int) $wpdb->insert_id, 'reference' => $reference ] : null;
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', $id ),
			ARRAY_A
		);
		if ( ! $row ) {
			return null;
		}
		$json            = Lendly_Crypto::decrypt( $row['payload'] );
		$row['personal'] = $json ? json_decode( $json, true ) : null;
		unset( $row['payload'] );
		return $row;
	}

	public static function update_review( int $id, string $status, string $notes ): bool {
		global $wpdb;
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			return false;
		}
		return false !== $wpdb->update(
			self::table(),
			[ 'status' => $status, 'notes' => $notes, 'updated_at' => current_time( 'mysql', true ) ],
			[ 'id' => $id ],
			[ '%s', '%s', '%s' ],
			[ '%d' ]
		);
	}

	public static function delete( int $id ): bool {
		global $wpdb;
		return (bool) $wpdb->delete( self::table(), [ 'id' => $id ], [ '%d' ] );
	}

	public static function status_label( string $status ): string {
		$labels = [
			'new'       => __( 'New', 'lendly' ),
			'reviewing' => __( 'Reviewing', 'lendly' ),
			'more_info' => __( 'More info needed', 'lendly' ),
			'approved'  => __( 'Approved', 'lendly' ),
			'declined'  => __( 'Declined', 'lendly' ),
			'withdrawn' => __( 'Withdrawn', 'lendly' ),
		];
		return $labels[ $status ] ?? $status;
	}

	private static function unique_reference(): string {
		global $wpdb;
		do {
			$ref    = 'LND-' . strtoupper( wp_generate_password( 8, false, false ) );
			$exists = $wpdb->get_var(
				$wpdb->prepare( 'SELECT id FROM ' . self::table() . ' WHERE reference = %s', $ref )
			);
		} while ( $exists );
		return $ref;
	}
}
