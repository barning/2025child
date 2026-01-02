<?php
// Enqueue child theme stylesheet only (block theme inherits via theme.json)
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'child-style', get_stylesheet_uri(), [], wp_get_theme()->get('Version') );
});

// Auto-load all PHP files in inc/ for modular structure
// Cache the glob result to avoid filesystem operations on every request
$inc_files = wp_cache_get( 'child_inc_files', 'child_theme' );
if ( false === $inc_files ) {
	$inc_files = glob( get_stylesheet_directory() . '/inc/*.php' );
	if ( is_array( $inc_files ) ) {
		wp_cache_set( 'child_inc_files', $inc_files, 'child_theme', HOUR_IN_SECONDS );
	}
}
if ( is_array( $inc_files ) ) {
	foreach ( $inc_files as $file ) {
		require_once $file;
	}
}
