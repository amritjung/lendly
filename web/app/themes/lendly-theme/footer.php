</main>

<footer class="site-footer">
	<div class="container site-footer__grid">
		<div>
			<p class="site-footer__brand"><?php bloginfo( 'name' ); ?></p>
			<p><?php echo lendly_phone_link(); // phpcs:ignore WordPress.Security.EscapeOutput ?><br>
				<a href="mailto:<?php echo esc_attr( lendly_mod( 'email' ) ); ?>"><?php echo esc_html( lendly_mod( 'email' ) ); ?></a><br>
				<?php echo esc_html( lendly_mod( 'hours' ) ); ?></p>
		</div>
		<nav aria-label="<?php esc_attr_e( 'Company', 'lendly-theme' ); ?>">
			<h2 class="site-footer__heading"><?php esc_html_e( 'Company', 'lendly-theme' ); ?></h2>
			<?php wp_nav_menu( [ 'theme_location' => 'footer', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ] ); ?>
		</nav>
		<nav aria-label="<?php esc_attr_e( 'Legal documents', 'lendly-theme' ); ?>">
			<h2 class="site-footer__heading"><?php esc_html_e( 'Legal', 'lendly-theme' ); ?></h2>
			<?php wp_nav_menu( [ 'theme_location' => 'legal', 'container' => false, 'fallback_cb' => false, 'depth' => 1 ] ); ?>
		</nav>
	</div>

	<div class="container site-footer__legal">
		<p><?php echo esc_html( lendly_mod( 'footer_disclaimer' ) ); ?></p>
		<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( lendly_mod( 'company_name' ) ); ?>
			· ABN <?php echo esc_html( lendly_mod( 'abn' ) ); ?>
			· Australian Credit Licence <?php echo esc_html( lendly_mod( 'acl' ) ); ?></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
