<?php
defined( 'ABSPATH' ) || exit;

class Lendly_Admin {

	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'menu' ] );
		add_action( 'admin_post_lendly_update', [ __CLASS__, 'handle_update' ] );
		add_action( 'admin_post_lendly_delete', [ __CLASS__, 'handle_delete' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'assets' ] );
	}

	public static function menu(): void {
		add_menu_page( __( 'Loan applications', 'lendly' ), __( 'Loans', 'lendly' ), LENDLY_CAP, 'lendly-applications', [ __CLASS__, 'router' ], 'dashicons-money-alt', 26 );
		add_submenu_page( 'lendly-applications', __( 'Applications', 'lendly' ), __( 'Applications', 'lendly' ), LENDLY_CAP, 'lendly-applications', [ __CLASS__, 'router' ] );
		add_submenu_page( 'lendly-applications', __( 'Loan settings', 'lendly' ), __( 'Settings', 'lendly' ), 'manage_options', 'lendly-settings', [ __CLASS__, 'settings_page' ] );
	}

	public static function assets( string $hook ): void {
		if ( str_contains( $hook, 'lendly' ) ) {
			wp_enqueue_style( 'lendly-admin', LENDLY_URL . 'assets/css/admin.css', [], LENDLY_VERSION );
		}
	}

	public static function router(): void {
		if ( ! current_user_can( LENDLY_CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'lendly' ) );
		}
		// phpcs:ignore WordPress.Security.NonceVerification -- read-only routing.
		if ( 'view' === ( $_GET['action'] ?? '' ) && ! empty( $_GET['id'] ) ) {
			self::detail( absint( $_GET['id'] ) );
			return;
		}
		$table = new Lendly_List_Table();
		$table->prepare_items();
		echo '<div class="wrap"><h1>' . esc_html__( 'Loan applications', 'lendly' ) . '</h1>';
		self::flash();
		$table->views();
		echo '<form method="get"><input type="hidden" name="page" value="lendly-applications">';
		$table->search_box( __( 'Search reference', 'lendly' ), 'lendly-search' );
		$table->display();
		echo '</form></div>';
	}

	private static function detail( int $id ): void {
		$app = Lendly_DB::find( $id );
		if ( ! $app ) {
			wp_die( esc_html__( 'Application not found.', 'lendly' ) );
		}
		include LENDLY_DIR . 'includes/views/admin-detail.php';
	}

	public static function handle_update(): void {
		$id = absint( $_POST['id'] ?? 0 );
		check_admin_referer( 'lendly_update_' . $id );
		if ( ! current_user_can( LENDLY_CAP ) ) {
			wp_die( esc_html__( 'Not allowed.', 'lendly' ) );
		}
		$status = sanitize_key( $_POST['status'] ?? '' );
		$notes  = sanitize_textarea_field( wp_unslash( $_POST['notes'] ?? '' ) );
		$ok     = Lendly_DB::update_review( $id, $status, $notes );

		/** Hook: send status emails, push to CRM, etc. */
		if ( $ok ) {
			do_action( 'lendly_status_changed', $id, $status );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=lendly-applications&action=view&id=' . $id . '&lendly_msg=' . ( $ok ? 'saved' : 'error' ) ) );
		exit;
	}

	public static function handle_delete(): void {
		$id = absint( $_POST['id'] ?? 0 );
		check_admin_referer( 'lendly_delete_' . $id );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Only administrators can delete applications.', 'lendly' ) );
		}
		Lendly_DB::delete( $id );
		wp_safe_redirect( admin_url( 'admin.php?page=lendly-applications&lendly_msg=deleted' ) );
		exit;
	}

	public static function flash(): void {
		$msg = sanitize_key( $_GET['lendly_msg'] ?? '' ); // phpcs:ignore WordPress.Security.NonceVerification
		$map = [
			'saved'   => [ 'success', __( 'Application updated.', 'lendly' ) ],
			'deleted' => [ 'success', __( 'Application deleted.', 'lendly' ) ],
			'error'   => [ 'error', __( 'Could not save changes.', 'lendly' ) ],
		];
		if ( isset( $map[ $msg ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $map[ $msg ][0] ), esc_html( $map[ $msg ][1] ) );
		}
	}

	public static function settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="wrap"><h1>' . esc_html__( 'Loan settings', 'lendly' ) . '</h1>';
		echo '<p>' . esc_html__( 'Every rate and fee on the website comes from here. Get compliance sign-off before changing advertised rates.', 'lendly' ) . '</p>';
		settings_errors( Lendly_Settings::OPTION );
		echo '<form method="post" action="options.php">';
		settings_fields( 'lendly_settings_group' );
		do_settings_sections( 'lendly-settings' );
		submit_button();
		echo '</form></div>';
	}
}
