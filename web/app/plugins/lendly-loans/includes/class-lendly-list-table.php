<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class Lendly_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( [ 'singular' => 'application', 'plural' => 'applications', 'ajax' => false ] );
	}

	public function get_columns(): array {
		return [
			'reference'   => __( 'Reference', 'lendly' ),
			'created_at'  => __( 'Submitted', 'lendly' ),
			'amount'      => __( 'Amount', 'lendly' ),
			'term_months' => __( 'Term', 'lendly' ),
			'purpose'     => __( 'Purpose', 'lendly' ),
			'status'      => __( 'Status', 'lendly' ),
		];
	}

	protected function get_sortable_columns(): array {
		return [
			'created_at' => [ 'created_at', true ],
			'amount'     => [ 'amount', false ],
			'status'     => [ 'status', false ],
		];
	}

	protected function get_views(): array {
		global $wpdb;
		$counts  = $wpdb->get_results( 'SELECT status, COUNT(*) n FROM ' . Lendly_DB::table() . ' GROUP BY status', OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL
		$current = sanitize_key( $_GET['status'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$base    = admin_url( 'admin.php?page=lendly-applications' );
		$total   = array_sum( array_map( static fn( $r ) => (int) $r->n, $counts ) );

		$views = [ 'all' => sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>', esc_url( $base ), '' === $current ? ' class="current"' : '', esc_html__( 'All', 'lendly' ), $total ) ];
		foreach ( Lendly_DB::STATUSES as $st ) {
			if ( empty( $counts[ $st ] ) ) {
				continue;
			}
			$views[ $st ] = sprintf(
				'<a href="%s"%s>%s <span class="count">(%d)</span></a>',
				esc_url( add_query_arg( 'status', $st, $base ) ),
				$current === $st ? ' class="current"' : '',
				esc_html( Lendly_DB::status_label( $st ) ),
				(int) $counts[ $st ]->n
			);
		}
		return $views;
	}

	public function prepare_items(): void {
		global $wpdb;
		$per_page = 20;
		$paged    = $this->get_pagenum();
		$table    = Lendly_DB::table();

		// phpcs:disable WordPress.Security.NonceVerification
		$status  = sanitize_key( $_GET['status'] ?? '' );
		$search  = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
		$orderby = in_array( $_GET['orderby'] ?? '', [ 'created_at', 'amount', 'status' ], true ) ? $_GET['orderby'] : 'created_at';
		$order   = 'asc' === strtolower( $_GET['order'] ?? '' ) ? 'ASC' : 'DESC';
		// phpcs:enable

		$where = [ '1=1' ];
		$args  = [];
		if ( in_array( $status, Lendly_DB::STATUSES, true ) ) {
			$where[] = 'status = %s';
			$args[]  = $status;
		}
		if ( '' !== $search ) {
			// Personal data is encrypted, so search is by reference only.
			$where[] = 'reference LIKE %s';
			$args[]  = '%' . $wpdb->esc_like( $search ) . '%';
		}
		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$total     = (int) ( $args ? $wpdb->get_var( $wpdb->prepare( $count_sql, $args ) ) : $wpdb->get_var( $count_sql ) ); // phpcs:ignore

		$sql         = "SELECT id, reference, status, amount, term_months, purpose, created_at FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$this->items = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $args, [ $per_page, ( $paged - 1 ) * $per_page ] ) ), ARRAY_A ); // phpcs:ignore

		$this->_column_headers = [ $this->get_columns(), [], $this->get_sortable_columns() ];
		$this->set_pagination_args( [ 'total_items' => $total, 'per_page' => $per_page ] );
	}

	protected function column_reference( array $item ): string {
		$url = admin_url( 'admin.php?page=lendly-applications&action=view&id=' . (int) $item['id'] );
		return sprintf( '<strong><a href="%s">%s</a></strong>', esc_url( $url ), esc_html( $item['reference'] ) );
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'created_at':
				return esc_html( get_date_from_gmt( $item['created_at'], 'j M Y, g:i a' ) );
			case 'amount':
				return '$' . esc_html( number_format( (float) $item['amount'] ) );
			case 'term_months':
				return esc_html( $item['term_months'] . ' mths' );
			case 'status':
				return sprintf( '<span class="lendly-status lendly-status--%1$s">%2$s</span>', esc_attr( $item['status'] ), esc_html( Lendly_DB::status_label( $item['status'] ) ) );
			default:
				return esc_html( $item[ $column_name ] ?? '' );
		}
	}

	public function no_items(): void {
		esc_html_e( 'No applications yet.', 'lendly' );
	}
}
