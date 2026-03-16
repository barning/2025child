<?php
/**
 * Note post type for short, headline-less thoughts.
 */
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

	register_post_type( 'note', [
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
