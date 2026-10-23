<?php
/**
 * Single application screen. $app comes from Lendly_DB::find().
 */
defined( 'ABSPATH' ) || exit;

$p   = $app['personal'] ?? [];
$id  = (int) $app['id'];
$fmt = static fn( $n ) => '$' . number_format( (float) $n, 2 );
$nice = static fn( $v ) => ucfirst( str_replace( '_', ' ', (string) $v ) );

$income    = $p ? Lendly_Validator::monthly_income( $p ) : 0;
$expenses  = (float) ( $p['housing_monthly'] ?? 0 ) + (float) ( $p['living_monthly'] ?? 0 ) + (float) ( $p['debts_monthly'] ?? 0 );
$cfg       = Lendly_Settings::public_config();
$r         = $cfg['rateFrom'] / 100 / 12;
$n         = (int) $app['term_months'];
$principal = (float) $app['amount'] + $cfg['establishmentFee'];
$repay     = ( $r > 0 ? $principal * $r / ( 1 - ( 1 + $r ) ** -$n ) : $principal / max( 1, $n ) ) + $cfg['monthlyFee'];
$surplus   = $income - $expenses - $repay;

$sections = [
	__( 'Applicant', 'lendly' ) => [
		'Name'          => trim( ( $p['first_name'] ?? '' ) . ' ' . ( $p['last_name'] ?? '' ) ),
		'Date of birth' => $p['dob'] ?? '',
		'Email'         => $p['email'] ?? '',
		'Mobile'        => $p['mobile'] ?? '',
		'Address'       => trim( ( $p['street'] ?? '' ) . ', ' . ( $p['suburb'] ?? '' ) . ' ' . ( $p['state'] ?? '' ) . ' ' . ( $p['postcode'] ?? '' ) ),
		'Living'        => $nice( $p['residential_status'] ?? '' ) . ', ' . ( $p['years_at_address'] ?? '' ) . ' yrs',
		'Dependants'    => $p['dependants'] ?? '',
	],
	__( 'Income & expenses', 'lendly' ) => [
		'Employment'          => trim( $nice( $p['employment_status'] ?? '' ) . ' ' . ( ! empty( $p['employer'] ) ? '— ' . $p['employer'] : '' ) ),
		'Income after tax'    => $fmt( $p['income_amount'] ?? 0 ) . ' ' . ( $p['income_frequency'] ?? '' ),
		'Housing / month'     => $fmt( $p['housing_monthly'] ?? 0 ),
		'Living / month'      => $fmt( $p['living_monthly'] ?? 0 ),
		'Other debts / month' => $fmt( $p['debts_monthly'] ?? 0 ),
	],
	__( 'Consents', 'lendly' ) => [
		'Privacy & credit guide' => ! empty( $p['consent_privacy'] ) ? 'Yes' : 'No',
		'Credit check'           => ! empty( $p['consent_credit_check'] ) ? 'Yes' : 'No',
		'Electronic comms'       => ! empty( $p['consent_electronic'] ) ? 'Yes' : 'No',
		'Marketing'              => ! empty( $p['consent_marketing'] ) ? 'Yes' : 'No',
		'Consented at (UTC)'     => $p['consent_at'] ?? '',
	],
];
?>
<div class="wrap lendly-detail">
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=lendly-applications' ) ); ?>">&larr; <?php esc_html_e( 'All applications', 'lendly' ); ?></a></p>
	<h1><?php echo esc_html( $app['reference'] ); ?>
		<span class="lendly-status lendly-status--<?php echo esc_attr( $app['status'] ); ?>"><?php echo esc_html( Lendly_DB::status_label( $app['status'] ) ); ?></span>
	</h1>
	<?php Lendly_Admin::flash(); ?>

	<?php if ( ! $p ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Personal details could not be decrypted. Check LENDLY_ENCRYPTION_KEY in wp-config.php.', 'lendly' ); ?></p></div>
	<?php endif; ?>

	<div class="lendly-cards">
		<div class="lendly-card"><span><?php esc_html_e( 'Amount', 'lendly' ); ?></span><strong><?php echo esc_html( $fmt( $app['amount'] ) ); ?></strong></div>
		<div class="lendly-card"><span><?php esc_html_e( 'Term', 'lendly' ); ?></span><strong><?php echo esc_html( $app['term_months'] . ' months' ); ?></strong></div>
		<div class="lendly-card"><span><?php esc_html_e( 'Purpose', 'lendly' ); ?></span><strong><?php echo esc_html( $app['purpose'] ); ?></strong></div>
		<div class="lendly-card <?php echo $surplus < 0 ? 'is-bad' : 'is-good'; ?>">
			<span><?php esc_html_e( 'Est. monthly surplus*', 'lendly' ); ?></span><strong><?php echo esc_html( $fmt( $surplus ) ); ?></strong>
		</div>
	</div>
	<p class="description">* <?php
		printf(
			esc_html__( 'Declared income %1$s/month minus declared expenses %2$s minus estimated repayment %3$s at the lowest rate. A snapshot only — verify with bank statements and a credit report before any decision.', 'lendly' ),
			esc_html( $fmt( $income ) ),
			esc_html( $fmt( $expenses ) ),
			esc_html( $fmt( $repay ) )
		);
	?></p>

	<div class="lendly-columns">
		<div>
			<?php foreach ( $sections as $title => $rows ) : ?>
				<h2><?php echo esc_html( $title ); ?></h2>
				<table class="widefat striped"><tbody>
					<?php foreach ( $rows as $k => $v ) : ?>
						<tr><th scope="row"><?php echo esc_html( $k ); ?></th><td><?php echo esc_html( (string) $v ); ?></td></tr>
					<?php endforeach; ?>
				</tbody></table>
			<?php endforeach; ?>
		</div>

		<div>
			<h2><?php esc_html_e( 'Review', 'lendly' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="lendly_update">
				<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
				<?php wp_nonce_field( 'lendly_update_' . $id ); ?>
				<p><label for="lendly-status"><strong><?php esc_html_e( 'Status', 'lendly' ); ?></strong></label><br>
					<select id="lendly-status" name="status">
						<?php foreach ( Lendly_DB::STATUSES as $st ) : ?>
							<option value="<?php echo esc_attr( $st ); ?>" <?php selected( $app['status'], $st ); ?>><?php echo esc_html( Lendly_DB::status_label( $st ) ); ?></option>
						<?php endforeach; ?>
					</select></p>
				<p><label for="lendly-notes"><strong><?php esc_html_e( 'Internal notes', 'lendly' ); ?></strong></label><br>
					<textarea id="lendly-notes" name="notes" rows="8" class="large-text"><?php echo esc_textarea( $app['notes'] ?? '' ); ?></textarea></p>
				<?php submit_button( __( 'Save review', 'lendly' ), 'primary', 'submit', false ); ?>
			</form>

			<p class="description"><?php printf( esc_html__( 'Submitted %1$s · updated %2$s', 'lendly' ), esc_html( get_date_from_gmt( $app['created_at'], 'j M Y g:i a' ) ), esc_html( get_date_from_gmt( $app['updated_at'], 'j M Y g:i a' ) ) ); ?></p>

			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Permanently delete this application?');">
					<input type="hidden" name="action" value="lendly_delete">
					<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
					<?php wp_nonce_field( 'lendly_delete_' . $id ); ?>
					<button class="button button-link-delete"><?php esc_html_e( 'Delete application', 'lendly' ); ?></button>
				</form>
			<?php endif; ?>
		</div>
	</div>
</div>
