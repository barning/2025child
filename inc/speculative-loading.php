<?php
/**
 * Speculative Loading (resource hints) via Customizer
 * Format per line: type|url|as|crossorigin
 * type: preload, prefetch, preconnect, dns-prefetch, prerender
 */

add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'speculative_loading_section', [
		'title'    => __( 'Speculative Loading', 'child' ),
		'priority' => 32,
	] );
	$wp_customize->add_setting( 'speculative_loading_resources', [
		'type' => 'theme_mod',
		'sanitize_callback' => 'sanitize_textarea_field',
	] );
	$wp_customize->add_control( 'speculative_loading_resources', [
		'label'   => __( 'Resource Hints', 'child' ),
		'section' => 'speculative_loading_section',
		'type'    => 'textarea',
		'description' => __( 'One per line: type|url|as|crossorigin. type in {preload,prefetch,preconnect,dns-prefetch,prerender}', 'child' ),
	] );
});

add_action( 'wp_head', function() {
	$resources = get_theme_mod( 'speculative_loading_resources' );
	if ( ! $resources ) return;
	$types = [ 'preload', 'prefetch', 'preconnect', 'dns-prefetch', 'prerender' ];
	foreach ( explode( "\n", $resources ) as $line ) {
		$line = trim( $line );
		if ( ! $line ) continue;
		list( $type, $url, $as, $cross ) = array_pad( explode( '|', $line ), 4, '' );
		if ( ! in_array( $type, $types, true ) || ! $url ) continue;
		$attrs = 'rel="' . esc_attr( $type ) . '" href="' . esc_url( $url ) . '"';
		if ( $as && in_array( $type, [ 'preload', 'prefetch' ], true ) ) {
			$attrs .= ' as="' . esc_attr( $as ) . '"';
		}
		if ( $cross && in_array( $type, [ 'preload', 'preconnect' ], true ) ) {
			$attrs .= ' crossorigin="' . esc_attr( $cross ) . '"';
		}
		echo '<link ' . $attrs . ">\n";
	}
}, 2 );
