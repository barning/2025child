<?php
// Enqueue child theme stylesheet only (block theme inherits via theme.json)
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'child-style', get_stylesheet_uri(), [], wp_get_theme()->get('Version') );
});

// Auto-load all PHP files in inc/ for modular structure
foreach ( glob( get_stylesheet_directory() . '/inc/*.php' ) as $file ) {
	require_once $file;
}
