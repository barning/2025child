<?php
/**
 * Minimal replacement for Integrate Umami plugin:
 * - Adds Customizer settings for Umami script URL, website ID, and data-domain.
 * - Outputs the Umami analytics script in the head or footer.
 */

add_action( 'customize_register', function( $wp_customize ) {
	$wp_customize->add_section( 'umami_section', [
		'title'    => __( 'Umami Analytics', 'child' ),
		'priority' => 31,
	] );
	$wp_customize->add_setting( 'umami_script_url', [
		'type' => 'theme_mod',
		'sanitize_callback' => 'esc_url_raw',
	] );
	$wp_customize->add_control( 'umami_script_url', [
		'label'   => __( 'Umami Script URL', 'child' ),
		'section' => 'umami_section',
		'type'    => 'text',
		'description' => __( 'Full URL to Umami script (e.g. https://analytics.example.com/script.js).', 'child' ),
	] );

	$wp_customize->add_setting( 'umami_website_id', [
		'type' => 'theme_mod',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	$wp_customize->add_control( 'umami_website_id', [
		'label'   => __( 'Website ID (data-website-id)', 'child' ),
		' section'=> 'umami_section',
		'type'    => 'text',
	] );

	$wp_customize->add_setting( 'umami_data_domain', [
		'type' => 'theme_mod',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	$wp_customize->add_control( 'umami_data_domain', [
		'label'   => __( 'Data Domain (data-domain)', 'child' ),
		' section'=> 'umami_section',
		'type'    => 'text',
	] );

	$wp_customize->add_setting( 'umami_in_head', [
		'default' => false,
		'type' => 'theme_mod',
		'sanitize_callback' => function( $v ){ return (bool) $v; },
	] );
	$wp_customize->add_control( 'umami_in_head', [
		'label'   => __( 'Load in <head> (otherwise footer)', 'child' ),
		'section' => 'umami_section',
		'type'    => 'checkbox',
	] );
});

function child_output_umami_script(){
	$url   = get_theme_mod( 'umami_script_url' );
	$wid   = get_theme_mod( 'umami_website_id' );
	$ddom  = get_theme_mod( 'umami_data_domain' );
	if ( $url && $wid ) {
		echo '<script async defer src="' . esc_url( $url ) . '" data-website-id="' . esc_attr( $wid ) . '"' . ( $ddom ? ' data-domain="' . esc_attr( $ddom ) . '"' : '' ) . '></script>' . "\n";
	}
}

add_action( 'wp_head', function(){ if ( get_theme_mod( 'umami_in_head' ) ) child_output_umami_script(); }, 99 );
add_action( 'wp_footer', function(){ if ( ! get_theme_mod( 'umami_in_head' ) ) child_output_umami_script(); }, 20 );
