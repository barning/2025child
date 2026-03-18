<?php
/**
 * Fediverse creator meta integration.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Sanitize fediverse creator handle.
 */
function child_sanitize_fediverse_handle( string $value ): string {
	return sanitize_text_field( $value );
}

/**
 * Register customizer controls for fediverse metadata.
 */
function child_register_fediverse_customizer_settings( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'fediverse_section',
		[
			'title'    => __( 'Fediverse Author', 'child' ),
			'priority' => 30,
		]
	);

	$wp_customize->add_setting(
		'fediverse_creator_handle',
		[
			'type'              => 'theme_mod',
			'sanitize_callback' => 'child_sanitize_fediverse_handle',
		]
	);

	$wp_customize->add_control(
		'fediverse_creator_handle',
		[
			'label'   => __( 'Fediverse Creator Handle (e.g. @yourname@mastodon.social)', 'child' ),
			'section' => 'fediverse_section',
			'type'    => 'text',
		]
	);
}
add_action( 'customize_register', 'child_register_fediverse_customizer_settings' );

/**
 * Print Fediverse creator meta tag in <head> when configured.
 */
function child_output_fediverse_meta_tag(): void {
	$handle = (string) get_theme_mod( 'fediverse_creator_handle' );

	if ( '' === $handle ) {
		return;
	}

	echo '<meta name="fediverse:creator" content="' . esc_attr( $handle ) . '" />' . "\n";
}
add_action( 'wp_head', 'child_output_fediverse_meta_tag' );
