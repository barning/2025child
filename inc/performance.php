<?php
/**
 * Performance-centric adjustments that make sense to ship with the child theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Force all WordPress-managed webfonts to use font-display: swap.
 *
 * The Webfonts API (WP 6.5+) exposes the wp_webfonts_font_face_declarations filter,
 * allowing themes to tweak descriptors without touching the generated CSS file.
 * We only touch fonts registered through that API so editors can keep using the
 * Font Library UI without losing this optimization.
 */
add_filter( 'wp_webfonts_font_face_declarations', function( $collections ) {
	if ( empty( $collections ) || ! is_array( $collections ) ) {
		return $collections;
	}

	foreach ( $collections as $collection_key => $collection ) {
		if ( ! is_array( $collection ) ) {
			continue;
		}

		foreach ( $collection as $font_index => $font_face ) {
			if ( ! is_array( $font_face ) ) {
				continue;
			}

			$font_face['font-display'] = 'swap';
			$collection[ $font_index ] = $font_face;
		}

		$collections[ $collection_key ] = $collection;
	}

	return $collections;
} );

/**
 * Defer the legacy WordPress Popular Posts script if it is still registered.
 *
 * The child theme now provides a native Popular Posts block, so the plugin's
 * synchronous script is redundant and only serves to block rendering. When the
 * plugin is still active (for residual dashboards/widgets), we at least mark it
 * as deferred and move it to the footer so it no longer delays LCP.
 */
add_action( 'wp_enqueue_scripts', function() {
	if ( wp_script_is( 'wpp', 'registered' ) || wp_script_is( 'wpp', 'enqueued' ) ) {
		// Load late + defer to keep it off the critical path.
		wp_script_add_data( 'wpp', 'strategy', 'defer' );
		wp_script_add_data( 'wpp', 'group', 1 );
	}
}, 20 );

/**
 * Ensure the hero teaser block exposes its image to the preload scanner.
 *
 * Lighthouse flagged the hero art as the LCP element, so we inject eager loading
 * and fetchpriority=high directly on the server-rendered markup.
 */
add_filter( 'render_block_child/hero-teaser', function( $block_content, $block ) {
	if ( empty( $block_content ) || false === strpos( $block_content, '<img' ) ) {
		return $block_content;
	}

	// Only touch the first <img> inside the block, that is the hero visual.
	$block_content = preg_replace_callback(
		'/<img\b[^>]*>/i',
		function( $matches ) {
			$img_tag = $matches[0];

			$img_tag = childtheme_replace_or_inject_attr( $img_tag, 'loading', 'eager' );
			$img_tag = childtheme_replace_or_inject_attr( $img_tag, 'fetchpriority', 'high' );
			$img_tag = childtheme_replace_or_inject_attr( $img_tag, 'decoding', 'async' );

			return $img_tag;
		},
		$block_content,
		1
	);

	return $block_content;
}, 10, 2 );

/**
 * Helper to inject/update an attribute on an image tag string.
 */
if ( ! function_exists( 'childtheme_replace_or_inject_attr' ) ) {
	function childtheme_replace_or_inject_attr( $tag, $attr, $value ) {
		$pattern = sprintf( '/%s\s*=\s*"[^"]*"/i', preg_quote( $attr, '/' ) );

		if ( preg_match( $pattern, $tag ) ) {
			return preg_replace( $pattern, sprintf( '%s="%s"', $attr, esc_attr( $value ) ), $tag, 1 );
		}

		return str_replace( '<img', sprintf( '<img %s="%s"', $attr, esc_attr( $value ) ), $tag );
	}
}
