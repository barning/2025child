<?php
/**
 * Child theme bootstrap loader.
 *
 * @package TwentyTwentyFiveChild
 */

define( 'CHILD_THEME_VERSION', wp_get_theme()->get( 'Version' ) );

require_once get_stylesheet_directory() . '/inc/bootstrap.php';
