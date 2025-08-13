<?php
/**
 * Dark Mode with system preference and toggle
 */

// Enqueue assets
add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_style( 'child-dark-mode', get_stylesheet_directory_uri() . '/dark-mode.css', [], '1.1' );
	wp_enqueue_script( 'child-dark-mode', get_stylesheet_directory_uri() . '/dark-mode.js', [], '1.1', true );
});

// Add color-scheme meta for better UI in browsers
add_action( 'wp_head', function() {
	echo '<meta name="color-scheme" content="light dark" />' . "\n";
}, 1 );

// Toggle button
add_action( 'wp_footer', function() {
	echo '<button id="dark-mode-toggle" aria-pressed="false" class="child-dark-toggle" title="Toggle dark mode">🌓</button>';
});
