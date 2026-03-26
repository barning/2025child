<?php
/**
 * Dynamic block registration and shared style loading.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Get dynamic blocks owned by the child theme.
 *
 * @return array<string, array{block_name:string, render_file:string}>
 */
function child_get_dynamic_blocks(): array {
	return [
		'book-rating'              => [
			'block_name'  => 'child/book-rating',
			'render_file' => 'blocks/book-rating/render.php',
		],
		'magic-cards'              => [
			'block_name'  => 'child/magic-cards',
			'render_file' => 'blocks/magic-cards/render.php',
		],
		'media-recommendation'     => [
			'block_name'  => 'child/media-recommendation',
			'render_file' => 'blocks/media-recommendation/render.php',
		],
		'pixelfed-feed'            => [
			'block_name'  => 'child/pixelfed-feed',
			'render_file' => 'blocks/pixelfed-feed/render.php',
		],
		'open-stories-viewer'      => [
			'block_name'  => 'child/open-stories-viewer',
			'render_file' => 'blocks/open-stories-viewer/render.php',
		],
		'popular-posts'            => [
			'block_name'  => 'child/popular-posts',
			'render_file' => 'blocks/popular-posts/render.php',
		],
		'videogame-recommendation' => [
			'block_name'  => 'child/videogame-recommendation',
			'render_file' => 'blocks/videogame-recommendation/render.php',
		],
		'visual-link-preview'      => [
			'block_name'  => 'child/visual-link-preview',
			'render_file' => 'blocks/visual-link-preview/render.php',
		],
	];
}

/**
 * Register dynamic blocks and block-bound styles.
 */
function child_register_dynamic_blocks(): void {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();

	foreach ( child_get_dynamic_blocks() as $slug => $config ) {
		register_block_type(
			$theme_dir . '/build/' . $slug,
			[
				'render_callback' => require $theme_dir . '/' . $config['render_file'],
			]
		);

		$css_path = $theme_dir . '/build/' . $slug . '/style-index.css';
		if ( file_exists( $css_path ) ) {
			wp_enqueue_block_style(
				$config['block_name'],
				[
					'handle' => 'child-' . $slug . '-style',
					'src'    => $theme_uri . '/build/' . $slug . '/style-index.css',
					'path'   => $css_path,
				]
			);
		}
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
 * Global frontend style fallback for dynamic blocks.
 */
function child_enqueue_dynamic_block_styles_globally(): void {
	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();

	foreach ( array_keys( child_get_dynamic_blocks() ) as $slug ) {
		$css_path = $theme_dir . '/build/' . $slug . '/style-index.css';
		if ( ! file_exists( $css_path ) ) {
			continue;
		}

		wp_enqueue_style(
			'child-' . $slug . '-style-global',
			$theme_uri . '/build/' . $slug . '/style-index.css',
			[],
			filemtime( $css_path )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'child_enqueue_dynamic_block_styles_globally', 20 );

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
