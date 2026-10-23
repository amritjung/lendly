<?php
defined( 'ABSPATH' ) || exit;

/**
 * Emails never contain personal or financial details — only the reference.
 * Staff open the application inside wp-admin (behind login + 2FA).
 */
class Lendly_Mailer {

	public static function new_application( int $id, string $reference, array $clean ): void {
		$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

		// Staff notification.
		$to = Lendly_Settings::get( 'notify_email' );
		if ( $to ) {
			$link = admin_url( 'admin.php?page=lendly-applications&action=view&id=' . $id );
			wp_mail(
				$to,
				sprintf( '[%s] New loan application %s', $site, $reference ),
				sprintf(
					"A new application was submitted.\n\nReference: %s\nAmount: $%s\nTerm: %d months\n\nReview it here (login required):\n%s\n",
					$reference,
					number_format( (float) $clean['amount'] ),
					$clean['term_months'],
					$link
				)
			);
		}

		// Applicant confirmation.
		wp_mail(
			$clean['email'],
			sprintf( '%s: we have your application (%s)', $site, $reference ),
			sprintf(
				"Hi %s,\n\nThanks for applying with %s. Your reference is %s.\n\nWe'll review your application and contact you within one business day. If we need more information, such as bank statements or ID, we'll ask for it securely.\n\nSubmitting an application is not an approval of credit.\n\n%s\n",
				$clean['first_name'],
				$site,
				$reference,
				$site
			)
		);
	}
}
