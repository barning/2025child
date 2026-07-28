<?php
/**
 * Dynamic block registration and shared style loading.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Get dynamic blocks owned by the child theme.
 *
 * @return array<string, array{block_name:string, render_file:string,build_dir:string}>
 */
function child_get_dynamic_blocks(): array {
	$theme_dir      = get_stylesheet_directory();
	$dynamic_blocks = [];
	$manifests      = glob( $theme_dir . '/build/*/block.json' );

	if ( ! is_array( $manifests ) ) {
		return [];
	}

	sort( $manifests );

	foreach ( $manifests as $manifest ) {
		if ( ! is_readable( $manifest ) ) {
			continue;
		}

		$slug        = basename( dirname( $manifest ) );
		$build_dir   = dirname( $manifest );
		$render_file = 'blocks/' . $slug . '/render.php';
		$metadata    = json_decode( (string) file_get_contents( $manifest ), true );

		if (
			! preg_match( '/^[a-z0-9-]+$/', $slug )
			|| ! is_array( $metadata )
			|| ( $metadata['name'] ?? '' ) !== 'child/' . $slug
			|| ! is_readable( $theme_dir . '/' . $render_file )
		) {
			continue;
		}

		$dynamic_blocks[ $slug ] = [
			'block_name'  => 'child/' . $slug,
			'render_file' => $render_file,
			'build_dir'   => $build_dir,
		];
	}

	return $dynamic_blocks;
}

/**
 * Register dynamic blocks and block-bound styles.
 */
function child_register_dynamic_blocks(): void {
	$theme_dir = get_stylesheet_directory();
	$registry  = WP_Block_Type_Registry::get_instance();

	foreach ( child_get_dynamic_blocks() as $slug => $config ) {
		if ( $registry->is_registered( $config['block_name'] ) ) {
			continue;
		}

		$render_callback = require $theme_dir . '/' . $config['render_file'];
		if ( ! is_callable( $render_callback ) ) {
			continue;
		}

		register_block_type(
			$config['build_dir'],
			[
				'render_callback' => $render_callback,
			]
		);
	}
}
add_action( 'init', 'child_register_dynamic_blocks' );

/**
 * Ensure theme and block styles are available in the editor for accurate previews.
 */
function child_setup_editor_styles(): void {
	add_theme_support( 'editor-styles' );
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'child_setup_editor_styles' );

/**
 * Enqueue dynamic block styles inside the block editor so previews match the frontend.
 */
function child_enqueue_dynamic_block_styles_in_editor(): void {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();

	foreach ( array_keys( child_get_dynamic_blocks() ) as $slug ) {
		$editor_css = $theme_dir . '/build/' . $slug . '/index.css';
		if ( file_exists( $editor_css ) ) {
			wp_enqueue_style(
				'child-' . $slug . '-editor',
				$theme_uri . '/build/' . $slug . '/index.css',
				[],
				filemtime( $editor_css )
			);
		}

		$frontend_css = $theme_dir . '/build/' . $slug . '/style-index.css';
		if ( file_exists( $frontend_css ) ) {
			wp_enqueue_style(
				'child-' . $slug . '-style-editor',
				$theme_uri . '/build/' . $slug . '/style-index.css',
				[],
				filemtime( $frontend_css )
			);
		}
	}
}
add_action( 'enqueue_block_editor_assets', 'child_enqueue_dynamic_block_styles_in_editor', 20 );

/**
 * Localize data to a block's editor script handle (iframe-safe).
 *
 * @param string               $block_name  Full block name (e.g., child/media-recommendation).
 * @param string               $object_name JS global object name.
 * @param array<string, mixed> $data        Data to expose.
 */
function child_localize_block_editor_script( string $block_name, string $object_name, array $data ): void {
	if ( ! class_exists( 'WP_Block_Type_Registry' ) ) {
		return;
	}

	$registry   = WP_Block_Type_Registry::get_instance();
	$block_type = $registry->get_registered( $block_name );

	if ( ! $block_type || empty( $block_type->editor_script_handles ) ) {
		return;
	}

	foreach ( $block_type->editor_script_handles as $handle ) {
		wp_localize_script( $handle, $object_name, $data );
	}
}
