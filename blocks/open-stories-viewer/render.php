<?php
/**
 * Render Open Stories Viewer block.
 *
 * @return callable
 */

return function( $attributes ) {
	$feed_url = isset( $attributes['feedUrl'] ) ? trim( (string) $attributes['feedUrl'] ) : '';

	if ( '' === $feed_url || ! wp_http_validate_url( $feed_url ) ) {
		return sprintf(
			'<p %s>%s</p>',
			get_block_wrapper_attributes(),
			esc_html__( 'Please provide a valid Open Stories feed URL.', 'child' )
		);
	}

	$button_text   = isset( $attributes['buttonText'] ) ? (string) $attributes['buttonText'] : __( 'View Stories', 'child' );
	$loading       = ( isset( $attributes['loading'] ) && 'lazy' === $attributes['loading'] ) ? 'lazy' : 'eager';
	$duration      = isset( $attributes['duration'] ) ? max( 1, min( 30, (int) $attributes['duration'] ) ) : 5;
	$show_metadata = ! empty( $attributes['showMetadata'] );
	$is_highlight  = ! empty( $attributes['isHighlight'] );

	$script_handle = 'child-open-stories-element';
	if ( ! wp_script_is( $script_handle, 'registered' ) ) {
		wp_register_script(
			$script_handle,
			'https://unpkg.com/open-stories-element@0.0.30',
			[],
			null,
			true
		);
		wp_script_add_data( $script_handle, 'type', 'module' );
	}
	wp_enqueue_script( $script_handle );

	$attrs = [
		'src="' . esc_url( $feed_url ) . '"',
		'loading="' . esc_attr( $loading ) . '"',
		'duration="' . esc_attr( (string) $duration ) . '"',
	];

	if ( $show_metadata ) {
		$attrs[] = 'show-metadata';
	}

	if ( $is_highlight ) {
		$attrs[] = 'is-highlight';
	}

	return sprintf(
		'<div %1$s><open-stories %2$s>%3$s</open-stories></div>',
		get_block_wrapper_attributes(),
		implode( ' ', $attrs ),
		esc_html( $button_text )
	);
};
