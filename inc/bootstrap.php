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
 * Load modular includes from /inc.
 */
function child_load_modules(): void {
	$cache_key = 'child_inc_files';
	$inc_files = wp_cache_get( $cache_key, 'child_theme' );

	if ( false === $inc_files ) {
		$inc_files = glob( get_stylesheet_directory() . '/inc/*.php' );

		if ( is_array( $inc_files ) ) {
			sort( $inc_files );
			$inc_files = array_values(
				array_filter(
					$inc_files,
					static function( string $file ): bool {
						return basename( $file ) !== 'bootstrap.php';
					}
				)
			);

			wp_cache_set( $cache_key, $inc_files, 'child_theme', HOUR_IN_SECONDS );
		}
	}

	if ( ! is_array( $inc_files ) ) {
		return;
	}

	foreach ( $inc_files as $file ) {
		require_once $file;
	}
}
child_load_modules();
