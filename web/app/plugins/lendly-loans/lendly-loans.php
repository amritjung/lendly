<?php
/**
 * Plugin Name:       Lendly Loans
 * Description:       Loan repayment calculator, multi-step loan application and an admin area to review applications.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Your Company
 * Text Domain:       lendly
 *
 * Rename "Lendly" to your brand before going live.
 */

defined( 'ABSPATH' ) || exit;

define( 'LENDLY_VERSION', '1.0.0' );
define( 'LENDLY_FILE', __FILE__ );
define( 'LENDLY_DIR', plugin_dir_path( __FILE__ ) );
define( 'LENDLY_URL', plugin_dir_url( __FILE__ ) );
define( 'LENDLY_CAP', 'lendly_manage_applications' );

require_once LENDLY_DIR . 'includes/class-lendly-db.php';
require_once LENDLY_DIR . 'includes/class-lendly-crypto.php';
require_once LENDLY_DIR . 'includes/class-lendly-settings.php';
require_once LENDLY_DIR . 'includes/class-lendly-validator.php';
require_once LENDLY_DIR . 'includes/class-lendly-shortcodes.php';
require_once LENDLY_DIR . 'includes/class-lendly-rest.php';
require_once LENDLY_DIR . 'includes/class-lendly-mailer.php';

if ( is_admin() ) {
	require_once LENDLY_DIR . 'includes/class-lendly-list-table.php';
	require_once LENDLY_DIR . 'includes/class-lendly-admin.php';
}

register_activation_hook( __FILE__, [ 'Lendly_DB', 'activate' ] );

add_action( 'plugins_loaded', static function () {
	Lendly_DB::maybe_upgrade();
	Lendly_Settings::init();
	Lendly_Shortcodes::init();
	Lendly_REST::init();

	if ( is_admin() ) {
		Lendly_Admin::init();
	}
} );

/** Warn admins if the encryption key constant is missing. */
add_action( 'admin_notices', static function () {
	if ( ! current_user_can( LENDLY_CAP ) || Lendly_Crypto::has_dedicated_key() ) {
		return;
	}
	echo '<div class="notice notice-warning"><p><strong>Lendly Loans:</strong> ';
	echo esc_html__( 'Add a LENDLY_ENCRYPTION_KEY constant to wp-config.php before taking real applications. See the setup guide.', 'lendly' );
	echo '</p></div>';
} );
