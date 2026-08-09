<?php
/**
 * Theme bootstrap and module loading.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Enqueue child theme stylesheet.
 */
function child_enqueue_theme_styles(): void {
	wp_enqueue_style( 'child-style', get_stylesheet_uri(), [], CHILD_THEME_VERSION );
}
add_action( 'wp_enqueue_scripts', 'child_enqueue_theme_styles' );

/**
 * Load runtime modules in deterministic dependency order.
 *
 * Keep this manifest explicit: loading every PHP file found in /inc makes a
 * partially deployed or temporary file executable and hides module ordering.
 */
function child_load_modules(): void {
	$module_names = [
		'provider-http.php',
		'book-rating.php',
		'head-footer-injections.php',
		'human-json.php',
		'media-cover-grid-normalizers.php',
		'media-cover-grid-dedupe.php',
		'media-cover-grid.php',
		'media-recommendation.php',
		'music-recommendation.php',
		'notes.php',
		'post-likes.php',
		'rss-block-fallbacks.php',
		'rss-feed-footer.php',
		'videogame-recommendation.php',
		'visual-link-preview-async.php',
		'blocks.php',
	];
	$inc_dir      = get_stylesheet_directory() . '/inc/';

	foreach ( $module_names as $module_name ) {
		$file = $inc_dir . $module_name;

		// provider-http.php is an optional shared transport module.
		if ( 'provider-http.php' === $module_name && ! is_readable( $file ) ) {
			continue;
		}

		if ( ! is_readable( $file ) ) {
			continue;
		}

		require_once $file;
	}
}
child_load_modules();
