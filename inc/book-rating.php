<?php
/**
 * Book Rating Block Registration
 */

// Register the block + styles similar to other replacements
add_action( 'init', function() {
    register_block_type( get_stylesheet_directory() . '/build/book-rating', [
        'render_callback' => require get_stylesheet_directory() . '/blocks/book-rating/render.php'
    ] );

    $css_path = get_stylesheet_directory() . '/build/book-rating/style-index.css';
    if ( file_exists( $css_path ) ) {
        wp_enqueue_block_style( 'child/book-rating', [
            'handle' => 'child-book-rating-style',
            'src'    => get_stylesheet_directory_uri() . '/build/book-rating/style-index.css',
            'path'   => $css_path,
        ] );
    }
} );

// Fallback: ensure frontend always has the CSS even without block supports
add_action( 'wp_enqueue_scripts', function() {
    static $css_mtime = null;
    if ( null === $css_mtime ) {
        $css_path = get_stylesheet_directory() . '/build/book-rating/style-index.css';
        $css_mtime = file_exists( $css_path ) ? filemtime( $css_path ) : false;
    }
    if ( false !== $css_mtime ) {
        wp_enqueue_style( 'child-book-rating-style-global', get_stylesheet_directory_uri() . '/build/book-rating/style-index.css', [], $css_mtime );
    }
}, 20 );

// Add frontend ambilight effect script for book covers
add_action( 'wp_footer', function() {
    if ( ! has_block( 'child/book-rating' ) ) {
        return;
    }
    ?>
    <script>
    (function() {
        window.childBookAmbilightInit = function(containerId, imgElement) {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                const container = document.getElementById(containerId);

                if (!container || !ctx) return;

                if (!imgElement.complete || !imgElement.naturalWidth) {
                    imgElement.addEventListener('load', function() {
                        window.childBookAmbilightInit(containerId, imgElement);
                    });
                    return;
                }

                canvas.width = 50;
                canvas.height = 50;

                ctx.drawImage(imgElement, 0, 0, 50, 50);

                const imageData = ctx.getImageData(0, 0, 50, 50);
                const data = imageData.data;

                let r = 0, g = 0, b = 0;
                let count = 0;

                for (let i = 0; i < data.length; i += 4) {
                    const pixelIndex = i / 4;
                    const x = pixelIndex % 50;
                    const y = Math.floor(pixelIndex / 50);

                    if (x < 5 || x > 45 || y < 5 || y > 45) {
                        r += data[i];
                        g += data[i + 1];
                        b += data[i + 2];
                        count++;
                    }
                }

                if (count > 0) {
                    r = Math.round(r / count);
                    g = Math.round(g / count);
                    b = Math.round(b / count);
                    container.style.setProperty('--ambilight-color', `rgb(${r}, ${g}, ${b})`);
                }
            } catch (error) {
                console.warn('Book ambilight effect error:', error);
            }
        };
    })();
    </script>
    <?php
}, 100 );
