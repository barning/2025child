<?php
/**
 * Server-side render for Flexible Spacer block
 */
return function( $attributes ) {
    $height = isset( $attributes['height'] ) ? intval( $attributes['height'] ) : 32;
    return '<div class="wp-block-child-flexible-spacer" style="height:' . esc_attr( $height ) . 'px;"></div>';
};
