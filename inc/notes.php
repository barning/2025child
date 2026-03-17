<?php
/**
 * Note post type for short, headline-less thoughts.
 */
const CHILD_NOTE_POST_TYPE = 'note';

function child_register_note_post_type() {
	$labels = [
		'name'               => __( 'Notes', 'child' ),
		'singular_name'      => __( 'Note', 'child' ),
		'add_new'            => __( 'Add Note', 'child' ),
		'add_new_item'       => __( 'Add New Note', 'child' ),
		'edit_item'          => __( 'Edit Note', 'child' ),
		'new_item'           => __( 'New Note', 'child' ),
		'view_item'          => __( 'View Note', 'child' ),
		'view_items'         => __( 'View Notes', 'child' ),
		'search_items'       => __( 'Search Notes', 'child' ),
		'not_found'          => __( 'No notes found.', 'child' ),
		'not_found_in_trash' => __( 'No notes found in trash.', 'child' ),
		'all_items'          => __( 'All Notes', 'child' ),
		'archives'           => __( 'Note Archives', 'child' ),
		'menu_name'          => __( 'Notes', 'child' ),
	];

	register_post_type( CHILD_NOTE_POST_TYPE, [
		'labels'             => $labels,
		'public'             => true,
		'show_in_rest'       => true,
		'has_archive'        => true,
		'rewrite'            => [ 'slug' => 'notes' ],
		'menu_icon'          => 'dashicons-format-status',
		'menu_position'      => 21,
		'supports'           => [
			'editor',
			'excerpt',
			'author',
			'revisions',
		],
		'map_meta_cap'       => true,
		'publicly_queryable' => true,
	] );
}
add_action( 'init', 'child_register_note_post_type' );

function child_is_note_post_type( string $post_type ): bool {
	return $post_type === CHILD_NOTE_POST_TYPE;
}

function child_get_note_title_timestamp( string $date ): int {
	if ( $date === '' || $date === '0000-00-00 00:00:00' ) {
		return (int) current_time( 'timestamp' );
	}

	$timestamp = strtotime( $date );
	if ( $timestamp === false ) {
		return (int) current_time( 'timestamp' );
	}

	return $timestamp;
}

/**
 * Ensure notes have an internal title (date + time) for admin/feeds.
 */
function child_set_note_internal_title( array $data, array $postarr ): array {
	if ( ! child_is_note_post_type( (string) ( $data['post_type'] ?? '' ) ) ) {
		return $data;
	}

	// Keep auto-drafts untouched; set title only once the post is saved.
	if ( ( $data['post_status'] ?? '' ) === 'auto-draft' ) {
		return $data;
	}

	$title = trim( (string) ( $data['post_title'] ?? '' ) );
	if ( $title !== '' && strtolower( $title ) !== 'auto draft' ) {
		return $data;
	}

	$date = (string) ( $data['post_date'] ?? '' );
	$timestamp = child_get_note_title_timestamp( $date );
	$data['post_title'] = date_i18n( 'Y-m-d H:i', $timestamp );

	return $data;
}
add_filter( 'wp_insert_post_data', 'child_set_note_internal_title', 10, 2 );

function child_is_note_post_id( int $post_id ): bool {
	return get_post_type( $post_id ) === CHILD_NOTE_POST_TYPE;
}

/**
 * Hide the post title block for notes (e.g. in Query Loop).
 */
function child_hide_note_post_title_block( string $block_content, array $block ): string {
	if ( is_admin() ) {
		return $block_content;
	}

	if ( ( $block['blockName'] ?? '' ) !== 'core/post-title' ) {
		return $block_content;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return $block_content;
	}

	if ( ! child_is_note_post_id( $post_id ) ) {
		return $block_content;
	}

	return '';
}
add_filter( 'render_block', 'child_hide_note_post_title_block', 10, 2 );

/**
 * Flush rewrite rules once after introducing note archive rewrites.
 */
function child_maybe_flush_note_rewrite_rules() {
	if ( '1' === get_option( 'child_note_rewrite_flushed' ) ) {
		return;
	}

	child_register_note_post_type();
	flush_rewrite_rules( false );
	update_option( 'child_note_rewrite_flushed', '1' );
}
add_action( 'init', 'child_maybe_flush_note_rewrite_rules', 20 );

/**
 * Ensure rewrites are regenerated when the theme is activated.
 */
function child_flush_note_rewrite_rules_on_switch() {
	delete_option( 'child_note_rewrite_flushed' );
	child_register_note_post_type();
	flush_rewrite_rules( false );
	update_option( 'child_note_rewrite_flushed', '1' );
}
add_action( 'after_switch_theme', 'child_flush_note_rewrite_rules_on_switch' );
