<?php
/**
 * Editor UI for selecting per-post HTML language.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Register post meta used for per-post HTML language.
 */
function child_register_editor_language_meta(): void {
	$post_types = get_post_types(
		[
			'public' => true,
		],
		'names'
	);

	if ( ! is_array( $post_types ) ) {
		return;
	}

	foreach ( $post_types as $post_type ) {
		register_post_meta(
			$post_type,
			'_child_html_lang',
			[
				'single'            => true,
				'type'              => 'string',
				'show_in_rest'      => true,
				'sanitize_callback' => static function( $value ): string {
					$allowed = [ 'de', 'en' ];
					$value   = is_string( $value ) ? strtolower( trim( $value ) ) : '';

					return in_array( $value, $allowed, true ) ? $value : '';
				},
				'auth_callback'     => static function(): bool {
					return current_user_can( 'edit_posts' );
				},
			]
		);
	}
}
add_action( 'init', 'child_register_editor_language_meta', 20 );

/**
 * Enqueue block editor panel for choosing HTML language.
 */
function child_enqueue_editor_language_panel(): void {
	$script = <<<'JS'
(function (wp) {
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editor || wp.editPost;
	const { SelectControl } = wp.components;
	const { useSelect, useDispatch } = wp.data;
	const { createElement: el } = wp.element;

	if (!registerPlugin || !PluginDocumentSettingPanel) {
		return;
	}

	const LanguagePanel = () => {
		const postType = useSelect((select) => select('core/editor').getCurrentPostType(), []);
		const supportedPostTypes = ['post', 'page'];
		if (!supportedPostTypes.includes(postType)) {
			return null;
		}

		const meta = useSelect((select) => select('core/editor').getEditedPostAttribute('meta') || {}, []);
		const { editPost } = useDispatch('core/editor');
		const current = meta._child_html_lang || 'en';

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'child-html-language-panel',
				title: 'HTML Language',
				className: 'child-html-language-panel'
			},
			el(SelectControl, {
				label: 'Choose language for this post',
				value: current,
				options: [
					{ label: 'English (en)', value: 'en' },
					{ label: 'Deutsch (de)', value: 'de' }
				],
				onChange: (value) => editPost({ meta: { ...meta, _child_html_lang: value } }),
				help: 'Sets the <html lang="..."> value on the frontend for this post.'
			})
		);
	};

	registerPlugin('child-html-language-plugin', {
		render: LanguagePanel,
		icon: null
	});
})(window.wp);
JS;

	wp_register_script(
		'child-editor-language-panel',
		false,
		[ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-editor' ],
		CHILD_THEME_VERSION,
		true
	);
	wp_enqueue_script( 'child-editor-language-panel' );
	wp_add_inline_script( 'child-editor-language-panel', $script );
}
add_action( 'enqueue_block_editor_assets', 'child_enqueue_editor_language_panel' );

/**
 * Override singular page lang attribute from post meta.
 *
 * @param string $output Existing language attributes string.
 * @return string
 */
function child_override_html_lang_attribute( string $output ): string {
	if ( ! is_singular() ) {
		return $output;
	}

	$post_id = get_queried_object_id();
	if ( ! $post_id ) {
		return $output;
	}

	$lang = get_post_meta( $post_id, '_child_html_lang', true );
	$lang = is_string( $lang ) ? strtolower( trim( $lang ) ) : '';

	if ( ! in_array( $lang, [ 'de', 'en' ], true ) ) {
		return $output;
	}

	$output = preg_replace( '/lang="[^"]*"/i', 'lang="' . esc_attr( $lang ) . '"', $output );

	if ( ! is_string( $output ) || '' === trim( $output ) ) {
		return 'lang="' . esc_attr( $lang ) . '"';
	}

	if ( ! preg_match( '/\blang="/i', $output ) ) {
		$output .= ' lang="' . esc_attr( $lang ) . '"';
	}

	return $output;
}
add_filter( 'language_attributes', 'child_override_html_lang_attribute' );
