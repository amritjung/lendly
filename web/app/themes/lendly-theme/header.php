<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'lendly-theme' ); ?></a>

<header class="site-header">
	<div class="container site-header__inner">
		<div class="site-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="site-brand__name" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>

		<button class="nav-toggle" aria-expanded="false" aria-controls="primary-nav">
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'lendly-theme' ); ?></span>
			<span class="nav-toggle__bar"></span>
		</button>

		<nav id="primary-nav" class="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'lendly-theme' ); ?>">
			<?php
			wp_nav_menu( [
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'menu',
				'fallback_cb'    => false,
				'depth'          => 2,
			] );
			?>
			<div class="primary-nav__actions">
				<?php echo lendly_phone_link(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in helper. ?>
				<a class="btn btn--small" href="<?php echo esc_url( lendly_apply_url() ); ?>"><?php esc_html_e( 'Apply now', 'lendly-theme' ); ?></a>
			</div>
		</nav>
	</div>
</header>

<main id="main" class="site-main">
