<?php
/**
 * Minimal replacement for Head, Footer and Post Injections plugin:
 * - Adds a Customizer setting for the fediverse:creator handle.
 * - Outputs the <meta name="fediverse:creator"> tag in the <head> if set.
 * - Implements https://blog.joinmastodon.org/2024/07/highlighting-journalism-on-mastodon/
 */

// Add Customizer setting
add_action( 'customize_register', function( $wp_customize ) {
    $wp_customize->add_section( 'fediverse_section', [
        'title'    => __( 'Fediverse Author', 'child' ),
        'priority' => 30,
    ] );
    $wp_customize->add_setting( 'fediverse_creator_handle', [
        'type' => 'theme_mod',
        'sanitize_callback' => function( $value ) {
            return sanitize_text_field( $value );
        },
    ] );
    $wp_customize->add_control( 'fediverse_creator_handle', [
        'label'   => __( 'Fediverse Creator Handle (e.g. @yourname@mastodon.social)', 'child' ),
        'section' => 'fediverse_section',
        'type'    => 'text',
    ] );
});

// Output meta tag in <head>
add_action( 'wp_head', function() {
    $handle = get_theme_mod( 'fediverse_creator_handle' );
    if ( $handle ) {
        echo '<meta name="fediverse:creator" content="' . esc_attr( $handle ) . '" />' . "\n";
    }
});
