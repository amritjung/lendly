<?php
/**
 * Lendly theme bootstrap.
 */
defined( 'ABSPATH' ) || exit;

define( 'LENDLY_THEME_VERSION', '1.0.0' );

require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/template-tags.php';

add_action( 'after_setup_theme', static function () {
	load_theme_textdomain( 'lendly-theme', get_template_directory() . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', [ 'height' => 64, 'width' => 220, 'flex-width' => true, 'flex-height' => true ] );
	add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ] );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/theme.css' );
	add_post_type_support( 'page', 'excerpt' ); // Loan pages use the excerpt as hero text + card summary.

	register_nav_menus( [
		'primary' => __( 'Primary menu', 'lendly-theme' ),
		'footer'  => __( 'Footer: company', 'lendly-theme' ),
		'legal'   => __( 'Footer: legal documents', 'lendly-theme' ),
	] );
} );

add_action( 'wp_enqueue_scripts', static function () {
	wp_enqueue_style( 'lendly-fonts', 'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap', [], null );
	wp_enqueue_style( 'lendly-theme', get_template_directory_uri() . '/assets/css/theme.css', [], LENDLY_THEME_VERSION );
	wp_add_inline_style( 'lendly-theme', lendly_brand_css() );
	wp_enqueue_script( 'lendly-theme', get_template_directory_uri() . '/assets/js/theme.js', [], LENDLY_THEME_VERSION, [ 'strategy' => 'defer' ] );
} );

add_action( 'widgets_init', static function () {
	register_sidebar( [
		'name'          => __( 'Blog sidebar', 'lendly-theme' ),
		'id'            => 'blog',
		'before_widget' => '<section class="widget %2$s">',
		'after_widget'  => '</section>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	] );
} );

/** Remove things a lender's site doesn't need. */
add_filter( 'xmlrpc_enabled', '__return_false' );
remove_action( 'wp_head', 'wp_generator' );

/** Is the loans plugin active? Theme degrades gracefully without it. */
function lendly_has_plugin(): bool {
	return shortcode_exists( 'lendly_calculator' );
}
