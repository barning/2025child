<?php
/**
 * Note post type for short, headline-less thoughts.
 */
add_action( 'init', function() {
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
} );
