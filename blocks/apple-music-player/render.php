<?php
/**
 * Render Apple Music Player block.
 *
 * @return callable
 */

return function( $attributes ) {
	$resource_type = isset( $attributes['resourceType'] ) ? sanitize_key( (string) $attributes['resourceType'] ) : 'song';
	$allowed_types = [ 'song', 'album', 'playlist' ];
	if ( ! in_array( $resource_type, $allowed_types, true ) ) {
		$resource_type = 'song';
	}

	$resource_id = isset( $attributes['resourceId'] ) ? trim( (string) $attributes['resourceId'] ) : '';
	$storefront = isset( $attributes['storefront'] ) ? sanitize_key( (string) $attributes['storefront'] ) : 'us';
	$button_label = isset( $attributes['buttonLabel'] ) ? trim( (string) $attributes['buttonLabel'] ) : '';

	if ( '' === $button_label ) {
		$button_label = __( 'Play on Apple Music', 'child' );
	}

	if ( '' === $resource_id ) {
		return '';
	}

	$music_kit_config = apply_filters(
		'child_music_kit_config',
		[
			'developerToken' => '',
			'appName'        => get_bloginfo( 'name' ),
			'appBuild'       => '1.0.0',
		]
	);

	$developer_token = '';
	if ( is_array( $music_kit_config ) && isset( $music_kit_config['developerToken'] ) ) {
		$developer_token = trim( (string) $music_kit_config['developerToken'] );
	}

	if ( '' === $developer_token ) {
		return sprintf(
			'<p %s>%s</p>',
			get_block_wrapper_attributes(),
			esc_html__( 'Apple Music Player is not configured yet. Add a MusicKit developer token via the child_music_kit_config filter.', 'child' )
		);
	}

	wp_enqueue_script(
		'child-musickit-sdk',
		'https://js-cdn.music.apple.com/musickit/v3/musickit.js',
		[],
		null,
		true
	);

	$app_name = is_array( $music_kit_config ) && isset( $music_kit_config['appName'] )
		? trim( (string) $music_kit_config['appName'] )
		: get_bloginfo( 'name' );
	$app_build = is_array( $music_kit_config ) && isset( $music_kit_config['appBuild'] )
		? trim( (string) $music_kit_config['appBuild'] )
		: '1.0.0';

	ob_start();
	?>
	<div <?php echo get_block_wrapper_attributes(); ?>>
		<div
			class="child-apple-music-player"
			data-developer-token="<?php echo esc_attr( $developer_token ); ?>"
			data-app-name="<?php echo esc_attr( $app_name ); ?>"
			data-app-build="<?php echo esc_attr( $app_build ); ?>"
			data-storefront="<?php echo esc_attr( $storefront ); ?>"
			data-resource-type="<?php echo esc_attr( $resource_type ); ?>"
			data-resource-id="<?php echo esc_attr( $resource_id ); ?>"
		>
			<button type="button" class="child-apple-music-player__button">
				<?php echo esc_html( $button_label ); ?>
			</button>
			<p class="child-apple-music-player__status" aria-live="polite"></p>
		</div>
	</div>
	<?php

	return ob_get_clean();
};
