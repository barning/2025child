<?php
/**
 * Frontend performance optimizations for the child theme.
 */

// Self-hosted font-face overrides for Fira Sans with metric tuning that respect the parent theme's font sources.
add_action( 'wp_enqueue_scripts', function() {
    $fonts_settings = wp_get_global_settings( [ 'typography', 'fontFamilies' ] );
    $fira_faces     = [];

    foreach ( [ 'theme', 'custom' ] as $scope ) {
        foreach ( $fonts_settings[ $scope ] ?? [] as $family ) {
            if ( empty( $family['fontFamily'] ) || false === stripos( $family['fontFamily'], 'Fira Sans' ) ) {
                continue;
            }

            if ( empty( $family['fontFace'] ) || ! is_array( $family['fontFace'] ) ) {
                continue;
            }

            $fira_faces = array_merge( $fira_faces, $family['fontFace'] );
        }
    }

    if ( ! $fira_faces ) {
        return;
    }

    $font_css = '';

    foreach ( $fira_faces as $face ) {
        if ( empty( $face['src'] ) ) {
            continue;
        }

        $sources = [];

        foreach ( (array) $face['src'] as $src ) {
            if ( str_starts_with( $src, 'file:./' ) ) {
                $relative = ltrim( substr( $src, strlen( 'file:./' ) ), '/' );

                // Prefer a child override of the font file when it exists, fall back to the parent asset otherwise.
                $child_file  = get_theme_file_path( $relative );
                $child_uri   = get_theme_file_uri( $relative );
                $parent_uri  = get_parent_theme_file_uri( $relative );
                $src         = ( $child_file && file_exists( $child_file ) ) ? $child_uri : $parent_uri;
            }

            $format = pathinfo( wp_parse_url( $src, PHP_URL_PATH ), PATHINFO_EXTENSION );
            $format = $format ? sprintf( " format('%s')", esc_attr( $format ) ) : '';

            $sources[] = sprintf( "url('%s')%s", esc_url_raw( $src ), $format );
        }

        $font_css .= sprintf(
            "@font-face{font-family:'%s';font-style:%s;font-weight:%s;font-display:swap;src:%s;ascent-override:92%%;descent-override:26%%;line-gap-override:0%%;}\n",
            esc_attr( $face['fontFamily'] ?? 'Fira Sans' ),
            esc_attr( $face['fontStyle'] ?? 'normal' ),
            esc_attr( $face['fontWeight'] ?? '400' ),
            implode( ',', $sources )
        );
    }

    if ( $font_css ) {
        wp_register_style( 'child-fira-sans', false );
        wp_enqueue_style( 'child-fira-sans' );
        wp_add_inline_style( 'child-fira-sans', $font_css );
    }
}, 5 );

// Promote the first hero/avatar image on the front page to LCP-friendly markup.
add_action( 'wp', function() {
    if ( ! is_front_page() ) {
        return;
    }

    // Enforce eager loading + fetchpriority on the first attachment-backed image.
    add_filter( 'render_block', function( $block_content, $block ) {
        static $lcp_enhanced = false;

        if ( $lcp_enhanced ) {
            return $block_content;
        }

        if ( 'core/image' === ( $block['blockName'] ?? '' ) && ! empty( $block['attrs']['id'] ) ) {
            $attachment_id = absint( $block['attrs']['id'] );
            $existing_img  = null;
            if ( preg_match( '/<img[^>]+>/i', $block_content, $match ) ) {
                $existing_img = $match[0];
            }

            $img = wp_get_attachment_image(
                $attachment_id,
                $block['attrs']['sizeSlug'] ?? 'full',
                false,
                [
                    'class'         => trim( ( $block['attrs']['className'] ?? '' ) . ' wp-image-' . $attachment_id ),
                    'loading'       => 'eager',
                    'fetchpriority' => 'high',
                    'decoding'      => 'async',
                ]
            );

            if ( $img && $existing_img ) {
                $lcp_enhanced = true;
                return str_replace( $existing_img, $img, $block_content );
            }
        }

        return $block_content;
    }, 10, 2 );
});

// Defer the WordPress Popular Posts frontend script without re-registering it.
add_action( 'wp_enqueue_scripts', function() {
    foreach ( [ 'wpp-js', 'wpp', 'wpp-frontend' ] as $handle ) {
        if ( wp_script_is( $handle, 'registered' ) ) {
            // WordPress 6.3+: set a defer strategy directly on the registered handle.
            wp_script_add_data( $handle, 'strategy', 'defer' );
        }
    }
}, 20 );

// Fallback for older WordPress versions: inject defer into the script tag if the strategy API is unavailable.
add_filter( 'script_loader_tag', function( $tag, $handle ) {
    if ( ! in_array( $handle, [ 'wpp-js', 'wpp', 'wpp-frontend' ], true ) ) {
        return $tag;
    }

    // Avoid duplicate defer/async attributes.
    if ( preg_match( '/\s(defer|async)\b/', $tag ) ) {
        return $tag;
    }

    return str_replace( '<script ', '<script defer ', $tag );
}, 10, 2 );

// Add preconnect hints for external origins used on the site.
add_action( 'wp_head', function() {
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />' . "\n";
    echo '<link rel="preconnect" href="https://niklasbarning.de" />' . "\n";
}, 1 );
