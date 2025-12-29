<?php
/**
 * Background fetch handler for Visual Link Preview
 */

add_action( 'admin_post_child_vlp_fetch', function() {
    $url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
    if ( ! $url ) {
        wp_die( 'no-url' );
    }

    $cache_key = 'child_vlp_' . md5( $url );
    $lock_key  = 'child_vlp_lock_' . md5( $url );

    // Double-check: if cache already exists, nothing to do
    $cached = get_transient( $cache_key );
    if ( is_array( $cached ) ) {
        // remove lock if present
        if ( function_exists( 'wp_cache_delete' ) ) {
            wp_cache_delete( $lock_key, 'child_vlp' );
        } else {
            delete_option( $lock_key );
        }
        wp_die( 'cached' );
    }

    $response = wp_safe_remote_get( $url, [
        'timeout'     => 8,
        'redirection' => 5,
        'headers'     => [ 'user-agent' => 'WordPress; VisualLinkPreview/1.0' ],
    ] );

    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
        // clean up lock and exit
        if ( function_exists( 'wp_cache_delete' ) ) {
            wp_cache_delete( $lock_key, 'child_vlp' );
        } else {
            delete_option( $lock_key );
        }
        wp_die( 'fetch-failed' );
    }

    $html = wp_remote_retrieve_body( $response );
    if ( ! $html ) {
        if ( function_exists( 'wp_cache_delete' ) ) {
            wp_cache_delete( $lock_key, 'child_vlp' );
        } else {
            delete_option( $lock_key );
        }
        wp_die( 'empty-body' );
    }

    libxml_use_internal_errors( true );
    $doc = new DOMDocument();
    $loaded = $doc->loadHTML( $html );
    $title = '';
    $desc = '';
    $image = '';

    if ( $loaded ) {
        $xpath = new DOMXPath( $doc );
        $queries = [
            'title' => [
                "//meta[@property='og:title']/@content",
                "//meta[@name='twitter:title']/@content",
                '//title/text()'
            ],
            'desc' => [
                "//meta[@property='og:description']/@content",
                "//meta[@name='twitter:description']/@content",
                "//meta[@name='description']/@content"
            ],
            'image' => [
                "//meta[@property='og:image:secure_url']/@content",
                "//meta[@property='og:image']/@content",
                "//meta[@name='twitter:image']/@content"
            ],
        ];

        foreach ( $queries['title'] as $q ) {
            $nodes = $xpath->query( $q );
            if ( $nodes && $nodes->length ) { $title = trim( $nodes->item(0)->nodeValue ); break; }
        }
        foreach ( $queries['desc'] as $q ) {
            $nodes = $xpath->query( $q );
            if ( $nodes && $nodes->length ) { $desc = trim( $nodes->item(0)->nodeValue ); break; }
        }
        foreach ( $queries['image'] as $q ) {
            $nodes = $xpath->query( $q );
            if ( $nodes && $nodes->length ) { $image = trim( $nodes->item(0)->nodeValue ); break; }
        }
    }
    libxml_clear_errors();

    if ( $image && 0 === strpos( $image, '//' ) ) {
        $image = ( is_ssl() ? 'https:' : 'http:' ) . $image;
    } elseif ( $image && 0 === strpos( $image, '/' ) ) {
        $parts = wp_parse_url( $url );
        $scheme = isset( $parts['scheme'] ) ? $parts['scheme'] : 'https';
        $host   = isset( $parts['host'] ) ? $parts['host'] : ''; 
        $port   = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
        $image  = $scheme . '://' . $host . $port . $image;
    }

    $data = [ 'url' => $url, 'title' => $title, 'desc' => $desc, 'image' => $image ];
    set_transient( $cache_key, $data, HOUR_IN_SECONDS * 24 );

    // remove lock
    if ( function_exists( 'wp_cache_delete' ) ) {
        wp_cache_delete( $lock_key, 'child_vlp' );
    } else {
        delete_option( $lock_key );
    }

    wp_die( 'ok' );
} );

// also allow unauthenticated requests
add_action( 'admin_post_nopriv_child_vlp_fetch', function() {
    // reuse the same handler
    do_action( 'admin_post_child_vlp_fetch' );
} );
