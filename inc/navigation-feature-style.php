<?php
/**
 * Optional feature navigation block style.
 *
 * @package TwentyTwentyFiveChild
 */

/**
 * Register the opt-in block style for core/navigation.
 */
function child_register_navigation_feature_block_style(): void {
	register_block_style(
		'core/navigation',
		[
			'name'  => 'child-feature-nav',
			'label' => __( 'Feature Navigation', 'child' ),
		]
	);
}
add_action( 'init', 'child_register_navigation_feature_block_style' );
