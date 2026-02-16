<?php
/**
 * Pixelfed Feed block registration.
 */

add_action( 'init', function() {
	register_block_type( get_stylesheet_directory() . '/build/pixelfed-feed', [
		'render_callback' => require get_stylesheet_directory() . '/blocks/pixelfed-feed/render.php',
	] );

	$css_path = get_stylesheet_directory() . '/build/pixelfed-feed/style-index.css';
	if ( file_exists( $css_path ) ) {
		wp_enqueue_block_style( 'child/pixelfed-feed', [
			'handle' => 'child-pixelfed-feed-style',
			'src'    => get_stylesheet_directory_uri() . '/build/pixelfed-feed/style-index.css',
			'path'   => $css_path,
		] );
	}
} );

add_action( 'wp_enqueue_scripts', function() {
	static $css_mtime = null;

	if ( null === $css_mtime ) {
		$css_path = get_stylesheet_directory() . '/build/pixelfed-feed/style-index.css';
		$css_mtime = file_exists( $css_path ) ? filemtime( $css_path ) : false;
	}

	if ( false !== $css_mtime ) {
		wp_enqueue_style( 'child-pixelfed-feed-style-global', get_stylesheet_directory_uri() . '/build/pixelfed-feed/style-index.css', [], $css_mtime );
	}
}, 20 );
