<?php
/**
 * Linear Note threads with ActivityPub and AT Protocol federation support.
 *
 * @package TwentyTwentyFiveChild
 */

const CHILD_NOTE_THREAD_PARENT_META             = '_child_note_thread_parent_id';
const CHILD_NOTE_THREAD_LOCK_META               = '_child_note_thread_locked_parent_id';
const CHILD_NOTE_THREAD_ATMOSPHERE_PENDING_META = '_child_note_thread_atmosphere_pending_hook';
const CHILD_NOTE_THREAD_LOCK_REQUEST_PARAM      = '_child_note_thread_parent_lock';
const CHILD_NOTE_THREAD_MAX_DEPTH               = 100;

/**
 * Allow editors to manage the thread parent through the REST API.
 *
 * @param bool   $allowed  Whether access is currently allowed.
 * @param string $meta_key Meta key being authorized.
 * @param int    $post_id  Post ID being edited.
 */
function child_note_thread_meta_auth( bool $allowed, string $meta_key, int $post_id ): bool {
	unset( $allowed, $meta_key );

	return current_user_can( 'edit_post', $post_id );
}

/**
 * Register the parent pointer used by the block editor and REST clients.
 */
function child_register_note_thread_meta(): void {
	register_post_meta(
		CHILD_NOTE_POST_TYPE,
		CHILD_NOTE_THREAD_PARENT_META,
		[
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'sanitize_callback' => 'absint',
			'auth_callback'     => 'child_note_thread_meta_auth',
			'show_in_rest'      => true,
		]
	);
}
add_action( 'init', 'child_register_note_thread_meta', 11 );

/**
 * Get the immediate parent Note ID.
 */
function child_note_thread_get_parent_id( int $post_id ): int {
	return absint( get_post_meta( $post_id, CHILD_NOTE_THREAD_PARENT_META, true ) );
}

/**
 * Determine whether a Note's thread relationship is immutable.
 *
 * Published Notes created before thread support do not have lock metadata, but
 * their existing relationship must still remain unchanged.
 */
function child_note_thread_is_relationship_locked( int $post_id ): bool {
	return metadata_exists( 'post', $post_id, CHILD_NOTE_THREAD_LOCK_META )
		|| 'publish' === get_post_status( $post_id );
}

/**
 * Get the immediate child Note ID.
 *
 * Drafts reserve their parent so two editors cannot intentionally create a
 * branch. Trashed notes release the parent and can only be restored while the
 * parent remains available.
 *
 * @param int      $parent_id Parent Note ID.
 * @param bool     $published_only Limit the lookup to public descendants.
 * @param int|null $exclude_id Note ID to exclude from the lookup.
 */
function child_note_thread_get_child_id( int $parent_id, bool $published_only = false, ?int $exclude_id = null ): int {
	if ( $parent_id <= 0 ) {
		return 0;
	}

	$post_status = $published_only
		? [ 'publish' ]
		: [ 'publish', 'future', 'draft', 'pending', 'private' ];

	$args = [
		'post_type'              => CHILD_NOTE_POST_TYPE,
		'post_status'            => $post_status,
		'posts_per_page'         => 1,
		'fields'                 => 'ids',
		'orderby'                => [
			'date' => 'ASC',
			'ID'   => 'ASC',
		],
		'meta_key'               => CHILD_NOTE_THREAD_PARENT_META,
		'meta_value'             => $parent_id,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	];

	if ( $exclude_id ) {
		$args['post__not_in'] = [ $exclude_id ];
	}

	$children = get_posts( $args );

	return empty( $children ) ? 0 : absint( $children[0] );
}

/**
 * Return ancestor IDs in root-to-parent order.
 *
 * @return int[]
 */
function child_note_thread_get_ancestor_ids( int $post_id ): array {
	$ancestors = [];
	$visited   = [ $post_id => true ];
	$parent_id = child_note_thread_get_parent_id( $post_id );
	$depth     = 0;

	while ( $parent_id > 0 && $depth < CHILD_NOTE_THREAD_MAX_DEPTH ) {
		if ( isset( $visited[ $parent_id ] ) ) {
			break;
		}

		$parent = get_post( $parent_id );
		if ( ! $parent instanceof WP_Post || CHILD_NOTE_POST_TYPE !== $parent->post_type ) {
			break;
		}

		$visited[ $parent_id ] = true;
		$ancestors[]           = $parent_id;
		$parent_id             = child_note_thread_get_parent_id( $parent_id );
		++$depth;
	}

	return array_reverse( $ancestors );
}

/**
 * Return published descendant IDs in chronological chain order.
 *
 * @return int[]
 */
function child_note_thread_get_descendant_ids( int $post_id ): array {
	$descendants = [];
	$visited     = [ $post_id => true ];
	$parent_id   = $post_id;
	$depth       = 0;

	while ( $depth < CHILD_NOTE_THREAD_MAX_DEPTH ) {
		$child_id = child_note_thread_get_child_id( $parent_id, true );
		if ( $child_id <= 0 || isset( $visited[ $child_id ] ) ) {
			break;
		}

		$visited[ $child_id ] = true;
		$descendants[]        = $child_id;
		$parent_id            = $child_id;
		++$depth;
	}

	return $descendants;
}

/**
 * Resolve the root Note for a chain.
 */
function child_note_thread_get_root_id( int $post_id ): int {
	$ancestors = child_note_thread_get_ancestor_ids( $post_id );

	return empty( $ancestors ) ? $post_id : (int) $ancestors[0];
}

/**
 * Read ATmosphere's ordered Bluesky records without requiring the plugin.
 *
 * @return array<int, array{uri:string,cid:string,tid:string}>
 */
function child_note_thread_get_atmosphere_records( int $post_id ): array {
	$thread_key = '_atmosphere_bsky_thread_records';
	$uri_key    = '_atmosphere_bsky_uri';
	$cid_key    = '_atmosphere_bsky_cid';
	$tid_key    = '_atmosphere_bsky_tid';

	if ( class_exists( '\\Atmosphere\\Transformer\\Post' ) ) {
		$thread_key = constant( '\\Atmosphere\\Transformer\\Post::META_THREAD_RECORDS' );
		$uri_key    = constant( '\\Atmosphere\\Transformer\\Post::META_URI' );
		$cid_key    = constant( '\\Atmosphere\\Transformer\\Post::META_CID' );
		$tid_key    = constant( '\\Atmosphere\\Transformer\\Post::META_TID' );
	}

	$stored  = get_post_meta( $post_id, $thread_key, true );
	$records = [];

	if ( is_array( $stored ) ) {
		foreach ( $stored as $record ) {
			if ( ! is_array( $record ) || empty( $record['uri'] ) || empty( $record['cid'] ) ) {
				continue;
			}

			$records[] = [
				'uri' => (string) $record['uri'],
				'cid' => (string) $record['cid'],
				'tid' => (string) ( $record['tid'] ?? '' ),
			];
		}
	}

	if ( ! empty( $records ) ) {
		return $records;
	}

	$uri = (string) get_post_meta( $post_id, $uri_key, true );
	$cid = (string) get_post_meta( $post_id, $cid_key, true );
	if ( '' === $uri || '' === $cid ) {
		return [];
	}

	return [
		[
			'uri' => $uri,
			'cid' => $cid,
			'tid' => (string) get_post_meta( $post_id, $tid_key, true ),
		],
	];
}

/**
 * Build AT Protocol strong references for a threaded Note.
 *
 * @return array{root:array{uri:string,cid:string},parent:array{uri:string,cid:string}}|null
 */
function child_note_thread_get_atmosphere_reply_for_parent( int $parent_id ): ?array {
	if ( $parent_id <= 0 ) {
		return null;
	}

	$root_id        = child_note_thread_get_root_id( $parent_id );
	$root_records   = child_note_thread_get_atmosphere_records( $root_id );
	$parent_records = child_note_thread_get_atmosphere_records( $parent_id );

	if ( empty( $root_records ) || empty( $parent_records ) ) {
		return null;
	}

	$root   = $root_records[0];
	$parent = $parent_records[ count( $parent_records ) - 1 ];

	return [
		'root'   => [
			'uri' => $root['uri'],
			'cid' => $root['cid'],
		],
		'parent' => [
			'uri' => $parent['uri'],
			'cid' => $parent['cid'],
		],
	];
}

/**
 * Build AT Protocol strong references for a saved threaded Note.
 *
 * @return array{root:array{uri:string,cid:string},parent:array{uri:string,cid:string}}|null
 */
function child_note_thread_get_atmosphere_reply( int $post_id ): ?array {
	return child_note_thread_get_atmosphere_reply_for_parent(
		child_note_thread_get_parent_id( $post_id )
	);
}

/**
 * Determine whether ATmosphere would automatically publish a saved Note.
 *
 * Current ATmosphere versions expose helpers that include the connection,
 * connection-only mode, stored option, and filters. The option fallback keeps
 * older compatible versions from blocking local publication when auto-publish
 * is explicitly disabled.
 */
function child_note_thread_atmosphere_will_publish(): bool {
	if ( ! defined( 'ATMOSPHERE_VERSION' ) ) {
		return false;
	}

	$is_connected = '\\Atmosphere\\is_connected';
	if ( function_exists( $is_connected ) && ! (bool) call_user_func( $is_connected ) ) {
		return false;
	}

	$is_auto_publish_enabled = '\\Atmosphere\\is_auto_publish_enabled';
	if ( function_exists( $is_auto_publish_enabled ) ) {
		return (bool) call_user_func( $is_auto_publish_enabled );
	}

	return '1' === (string) get_option( 'atmosphere_auto_publish', '1' );
}

/**
 * Acquire a database advisory lock for a parent Note.
 *
 * The lock is connection-scoped, so the database releases it automatically if
 * persistence aborts before the normal REST or restore completion hook.
 */
function child_note_thread_acquire_parent_lock( int $parent_id ): ?string {
	global $wpdb;

	$database  = defined( 'DB_NAME' ) ? DB_NAME : '';
	$lock_name = 'child-note-thread-' . md5( $database . ':' . $wpdb->prefix . ':' . $parent_id );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$acquired = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK( %s, 0 )', $lock_name ) );

	return '1' === (string) $acquired ? $lock_name : null;
}

/**
 * Release a parent Note's database advisory lock.
 */
function child_note_thread_release_parent_lock( string $lock_name ): void {
	global $wpdb;

	if ( '' === $lock_name ) {
		return;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK( %s )', $lock_name ) );
}

/**
 * Validate a proposed parent relationship.
 *
 * @return true|WP_Error
 */
function child_note_thread_validate_parent( int $post_id, int $parent_id, bool $require_atmosphere = false ) {
	if ( 0 === $parent_id ) {
		return true;
	}

	if ( $post_id > 0 && $post_id === $parent_id ) {
		return new WP_Error(
			'note_thread_invalid_parent',
			__( 'A Note cannot continue itself.', 'child' ),
			[ 'status' => 400 ]
		);
	}

	$parent = get_post( $parent_id );
	if (
		! $parent instanceof WP_Post
		|| CHILD_NOTE_POST_TYPE !== $parent->post_type
		|| 'publish' !== $parent->post_status
	) {
		return new WP_Error(
			'note_thread_invalid_parent',
			__( 'Choose a published Note as the previous item in this thread.', 'child' ),
			[ 'status' => 400 ]
		);
	}

	$visited = $post_id > 0 ? [ $post_id => true ] : [];
	$cursor  = $parent_id;

	for ( $depth = 0; $cursor > 0 && $depth < CHILD_NOTE_THREAD_MAX_DEPTH; ++$depth ) {
		if ( isset( $visited[ $cursor ] ) ) {
			return new WP_Error(
				'note_thread_invalid_parent',
				__( 'That relationship would create a cycle in the thread.', 'child' ),
				[ 'status' => 400 ]
			);
		}

		$visited[ $cursor ] = true;
		$cursor             = child_note_thread_get_parent_id( $cursor );
	}

	if ( $cursor > 0 ) {
		return new WP_Error(
			'note_thread_invalid_parent',
			__( 'The selected thread is too deep or contains an invalid cycle.', 'child' ),
			[ 'status' => 400 ]
		);
	}

	$existing_child = child_note_thread_get_child_id( $parent_id, false, $post_id ?: null );
	if ( $existing_child > 0 ) {
		return new WP_Error(
			'note_thread_parent_has_child',
			__( 'That Note already has a continuation. Choose the latest Note in the thread.', 'child' ),
			[ 'status' => 409 ]
		);
	}

	if ( $require_atmosphere ) {
		if ( null === child_note_thread_get_atmosphere_reply_for_parent( $parent_id ) ) {
			return new WP_Error(
				'note_thread_parent_not_on_bluesky',
				__( 'The previous Note has not finished publishing to Bluesky. Keep this Note as a draft and try again after the parent has synced.', 'child' ),
				[ 'status' => 409 ]
			);
		}
	}

	return true;
}

/**
 * Validate the block editor's parent choice before WordPress writes the Note.
 *
 * @param stdClass        $prepared_post Prepared post object.
 * @param WP_REST_Request $request       REST request.
 * @return stdClass|WP_Error
 */
function child_note_thread_validate_rest_insert( stdClass $prepared_post, WP_REST_Request $request ) {
	$request->set_param( CHILD_NOTE_THREAD_LOCK_REQUEST_PARAM, '' );

	$post_id = absint( $request->get_param( 'id' ) ?: ( $prepared_post->ID ?? 0 ) );
	$meta    = $request->get_param( 'meta' );
	$meta    = is_array( $meta ) ? $meta : [];

	$parent_id = array_key_exists( CHILD_NOTE_THREAD_PARENT_META, $meta )
		? absint( $meta[ CHILD_NOTE_THREAD_PARENT_META ] )
		: child_note_thread_get_parent_id( $post_id );

	$status = (string) ( ( $prepared_post->post_status ?? '' ) ?: get_post_status( $post_id ) );

	if (
		$post_id > 0
		&& 'publish' === get_post_status( $post_id )
		&& 'publish' !== $status
		&& child_note_thread_get_child_id( $post_id ) > 0
	) {
		return new WP_Error(
			'note_thread_parent_has_child',
			__( 'A Note with a continuation must remain published.', 'child' ),
			[ 'status' => 409 ]
		);
	}

	if ( $post_id > 0 && child_note_thread_is_relationship_locked( $post_id ) ) {
		$locked_parent = metadata_exists( 'post', $post_id, CHILD_NOTE_THREAD_LOCK_META )
			? absint( get_post_meta( $post_id, CHILD_NOTE_THREAD_LOCK_META, true ) )
			: child_note_thread_get_parent_id( $post_id );
		if ( $locked_parent !== $parent_id ) {
			return new WP_Error(
				'note_thread_relationship_locked',
				__( 'A published Note cannot be moved to another thread.', 'child' ),
				[ 'status' => 409 ]
			);
		}
	}

	$sharing_disabled   = array_key_exists( 'atmosphere_disabled', $meta )
		? rest_sanitize_boolean( $meta['atmosphere_disabled'] )
		: ( '1' === (string) get_post_meta( $post_id, 'atmosphere_disabled', true ) );
	$require_atmosphere = in_array( $status, [ 'publish', 'future' ], true )
		&& $parent_id > 0
		&& child_note_thread_atmosphere_will_publish()
		&& ! $sharing_disabled;

	$lock_name = null;
	if ( $parent_id > 0 ) {
		$lock_name = child_note_thread_acquire_parent_lock( $parent_id );
		if ( null === $lock_name ) {
			return new WP_Error(
				'note_thread_parent_has_child',
				__( 'That Note is being claimed by another continuation. Try again.', 'child' ),
				[ 'status' => 409 ]
			);
		}
		$request->set_param( CHILD_NOTE_THREAD_LOCK_REQUEST_PARAM, $lock_name );
	}

	$valid = child_note_thread_validate_parent( $post_id, $parent_id, $require_atmosphere );
	if ( is_wp_error( $valid ) ) {
		child_note_thread_release_parent_lock( (string) $lock_name );
		$request->set_param( CHILD_NOTE_THREAD_LOCK_REQUEST_PARAM, '' );
		return $valid;
	}

	return $prepared_post;
}
add_filter( 'rest_pre_insert_' . CHILD_NOTE_POST_TYPE, 'child_note_thread_validate_rest_insert', 10, 2 );

/**
 * Preserve a published parent outside REST when it has active continuations.
 *
 * REST callers receive an explicit error above. Classic editor, WP-CLI, and
 * programmatic updates instead retain the published status so a child never
 * becomes attached to an unavailable parent.
 *
 * @param array<string, mixed> $data    Sanitized post data.
 * @param array<string, mixed> $postarr Raw post data.
 * @return array<string, mixed>
 */
function child_note_thread_preserve_non_tail_status( array $data, array $postarr ): array {
	$post_id = absint( $postarr['ID'] ?? 0 );

	if (
		$post_id > 0
		&& CHILD_NOTE_POST_TYPE === ( $data['post_type'] ?? '' )
		&& 'publish' === get_post_status( $post_id )
		&& 'publish' !== ( $data['post_status'] ?? '' )
		&& child_note_thread_get_child_id( $post_id ) > 0
	) {
		$data['post_status'] = 'publish';
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'child_note_thread_preserve_non_tail_status', 20, 2 );

/**
 * Lock the relationship the first time a Note is published.
 */
function child_note_thread_lock_relationship( WP_Post $post ): void {
	if (
		CHILD_NOTE_POST_TYPE !== $post->post_type
		|| 'publish' !== $post->post_status
		|| metadata_exists( 'post', $post->ID, CHILD_NOTE_THREAD_LOCK_META )
	) {
		return;
	}

	add_post_meta(
		$post->ID,
		CHILD_NOTE_THREAD_LOCK_META,
		child_note_thread_get_parent_id( $post->ID ),
		true
	);
}

/**
 * Release the parent lock after REST meta has been persisted.
 */
function child_note_thread_after_rest_insert( WP_Post $post, WP_REST_Request $request ): void {
	try {
		child_note_thread_lock_relationship( $post );
	} finally {
		child_note_thread_release_parent_lock(
			(string) $request->get_param( CHILD_NOTE_THREAD_LOCK_REQUEST_PARAM )
		);
	}
}
add_action( 'rest_after_insert_' . CHILD_NOTE_POST_TYPE, 'child_note_thread_after_rest_insert', 10, 2 );

/**
 * Lock relationships created outside the REST API.
 */
function child_note_thread_after_insert_post( int $post_id, WP_Post $post ): void {
	unset( $post_id );

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	child_note_thread_lock_relationship( $post );
}
add_action( 'wp_after_insert_post', 'child_note_thread_after_insert_post', 10, 2 );

/**
 * Prevent removing a Note that still has an active continuation.
 *
 * @param mixed   $decision Short-circuit value from an earlier filter.
 * @param WP_Post $post     Post being removed.
 * @return mixed
 */
function child_note_thread_prevent_non_tail_removal( $decision, WP_Post $post ) {
	if (
		CHILD_NOTE_POST_TYPE === $post->post_type
		&& child_note_thread_get_child_id( $post->ID ) > 0
	) {
		return false;
	}

	return $decision;
}
add_filter( 'pre_trash_post', 'child_note_thread_prevent_non_tail_removal', 10, 2 );
add_filter( 'pre_delete_post', 'child_note_thread_prevent_non_tail_removal', 10, 2 );

/**
 * Prevent restoring a Note when its parent has since received another child.
 *
 * @param mixed   $decision Short-circuit value from an earlier filter.
 * @param WP_Post $post     Post being restored.
 * @return mixed
 */
function child_note_thread_prevent_branching_restore( $decision, WP_Post $post ) {
	if ( CHILD_NOTE_POST_TYPE !== $post->post_type || null !== $decision ) {
		return $decision;
	}

	$parent_id = child_note_thread_get_parent_id( $post->ID );
	if ( $parent_id > 0 ) {
		$lock_name = child_note_thread_acquire_parent_lock( $parent_id );
		if ( null === $lock_name ) {
			return false;
		}

		$parent = get_post( $parent_id );
		if (
			! $parent instanceof WP_Post
			|| CHILD_NOTE_POST_TYPE !== $parent->post_type
			|| 'publish' !== $parent->post_status
			|| child_note_thread_get_child_id( $parent_id, false, $post->ID ) > 0
		) {
			child_note_thread_release_parent_lock( $lock_name );
			return false;
		}

		$GLOBALS['child_note_thread_restore_locks'][ $post->ID ] = $lock_name;
	}

	return $decision;
}
add_filter( 'pre_untrash_post', 'child_note_thread_prevent_branching_restore', 10, 2 );

/**
 * Release the parent lock after WordPress restores the continuation.
 */
function child_note_thread_release_restore_lock( int $post_id ): void {
	$lock_name = (string) ( $GLOBALS['child_note_thread_restore_locks'][ $post_id ] ?? '' );
	unset( $GLOBALS['child_note_thread_restore_locks'][ $post_id ] );
	child_note_thread_release_parent_lock( $lock_name );
}
add_action( 'untrashed_post', 'child_note_thread_release_restore_lock' );

/**
 * Return computed thread state for REST clients.
 *
 * @param array<string, mixed> $prepared Prepared REST object.
 * @return array<string, mixed>
 */
function child_note_thread_rest_state( array $prepared ): array {
	$post_id  = absint( $prepared['id'] ?? 0 );
	$child_id = child_note_thread_get_child_id( $post_id );

	return [
		'parent_id'          => child_note_thread_get_parent_id( $post_id ),
		'child_id'           => $child_id,
		'root_id'            => child_note_thread_get_root_id( $post_id ),
		'locked'             => child_note_thread_is_relationship_locked( $post_id ),
		'appendable'         => 0 === $child_id,
		'atmosphere_active'  => child_note_thread_atmosphere_will_publish(),
		'atmosphere_ready'   => ! empty( child_note_thread_get_atmosphere_records( $post_id ) ),
		'activitypub_active' => defined( 'ACTIVITYPUB_VERSION' ) || class_exists( '\\Activitypub\\Transformer\\Factory' ),
		'permalink'          => (string) get_permalink( $post_id ),
	];
}

/**
 * Register computed read-only thread state on Note REST responses.
 */
function child_register_note_thread_rest_field(): void {
	register_rest_field(
		CHILD_NOTE_POST_TYPE,
		'child_note_thread',
		[
			'get_callback' => 'child_note_thread_rest_state',
			'schema'       => [
				'description' => __( 'Computed Note thread state.', 'child' ),
				'type'        => 'object',
				'context'     => [ 'view', 'edit' ],
				'readonly'    => true,
				'properties'  => [
					'parent_id'          => [ 'type' => 'integer' ],
					'child_id'           => [ 'type' => 'integer' ],
					'root_id'            => [ 'type' => 'integer' ],
					'locked'             => [ 'type' => 'boolean' ],
					'appendable'         => [ 'type' => 'boolean' ],
					'atmosphere_active'  => [ 'type' => 'boolean' ],
					'atmosphere_ready'   => [ 'type' => 'boolean' ],
					'activitypub_active' => [ 'type' => 'boolean' ],
					'permalink'          => [
						'type'   => 'string',
						'format' => 'uri',
					],
				],
			],
		]
	);
}
add_action( 'rest_api_init', 'child_register_note_thread_rest_field' );

/**
 * Render a related Note body through WordPress's password-aware content API.
 */
function child_note_thread_render_body( WP_Post $note ): string {
	return (string) apply_filters( 'the_content', get_the_content( null, false, $note ) );
}

/**
 * Render the linked publication date shared by every thought in a thread.
 */
function child_note_thread_render_date( WP_Post $note ): string {
	return sprintf(
		'<header class="child-note-thread__meta"><a class="child-note-thread__date" href="%1$s"><time datetime="%2$s">%3$s</time></a></header>',
		esc_url( get_permalink( $note ) ),
		esc_attr( get_post_time( DATE_W3C, false, $note ) ),
		esc_html( get_the_date( '', $note ) )
	);
}

/**
 * Render one related Note inside a thread list.
 */
function child_note_thread_render_item( int $post_id ): string {
	$note = get_post( $post_id );
	if ( ! $note instanceof WP_Post || 'publish' !== $note->post_status ) {
		return '';
	}

	$previous_post = $GLOBALS['post'] ?? null;
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Related blocks need the related Note as their post context.
	$GLOBALS['post'] = $note;
	setup_postdata( $note );
	$GLOBALS['child_note_thread_rendering'] = true;
	$content                                = child_note_thread_render_body( $note );
	$GLOBALS['child_note_thread_rendering'] = false;

	if ( $previous_post instanceof WP_Post ) {
		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Restore the main query's post after related content renders.
		$GLOBALS['post'] = $previous_post;
		setup_postdata( $previous_post );
	} else {
		wp_reset_postdata();
	}

	return sprintf(
		'<li class="child-note-thread__entry"><article>%1$s<div class="child-note-thread__body">%2$s</div></article></li>',
		child_note_thread_render_date( $note ),
		$content
	);
}

/**
 * Render a list of thread relatives.
 *
 * @param int[]  $post_ids Related Note IDs.
 * @param string $class_name Modifier class.
 * @param string $label Accessible section label.
 */
function child_note_thread_render_section( array $post_ids, string $class_name, string $label ): string {
	$items = '';
	foreach ( $post_ids as $post_id ) {
		$items .= child_note_thread_render_item( (int) $post_id );
	}

	if ( '' === $items ) {
		return '';
	}

	return sprintf(
		'<section class="child-note-thread__section child-note-thread__section--%1$s" aria-label="%2$s"><ol class="child-note-thread__list">%3$s</ol></section>',
		esc_attr( $class_name ),
		esc_attr( $label ),
		$items
	);
}

/**
 * Surround the current Note with its complete local conversation.
 */
function child_note_thread_render_content( string $content ): string {
	if (
		! empty( $GLOBALS['child_note_thread_rendering'] )
		|| is_admin()
		|| is_feed()
		|| ! is_singular( CHILD_NOTE_POST_TYPE )
		|| ! in_the_loop()
		|| ! is_main_query()
		|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
		|| apply_filters( 'activitypub_is_activitypub_request', false )
	) {
		return $content;
	}

	$post_id     = get_the_ID();
	$ancestors   = child_note_thread_get_ancestor_ids( $post_id );
	$descendants = child_note_thread_get_descendant_ids( $post_id );
	$current     = get_post( $post_id );

	if ( ! $current instanceof WP_Post ) {
		return $content;
	}

	$earlier = child_note_thread_render_section(
		$ancestors,
		'ancestors',
		__( 'Earlier in this conversation', 'child' )
	);
	$later   = child_note_thread_render_section(
		$descendants,
		'replies',
		__( 'Later in this conversation', 'child' )
	);

	return '<div class="child-note-thread">' . $earlier
		. '<div class="child-note-thread__current"><article>'
		. child_note_thread_render_date( $current )
		. '<div class="child-note-thread__body">' . $content . '</div></article></div>'
		. $later . '</div>';
}
add_filter( 'the_content', 'child_note_thread_render_content', 20 );

/**
 * Load the frontend thread presentation only on Note permalinks.
 */
function child_note_thread_enqueue_styles(): void {
	if ( ! is_singular( CHILD_NOTE_POST_TYPE ) ) {
		return;
	}

	$path = get_stylesheet_directory() . '/assets/note-threads.css';
	if ( ! is_readable( $path ) ) {
		return;
	}

	wp_enqueue_style(
		'child-note-threads',
		get_stylesheet_directory_uri() . '/assets/note-threads.css',
		[],
		(string) filemtime( $path )
	);
}
add_action( 'wp_enqueue_scripts', 'child_note_thread_enqueue_styles' );

/**
 * Load the Note thread parent picker in the block editor.
 */
function child_note_thread_enqueue_editor_assets(): void {
	$screen = get_current_screen();
	if ( ! $screen || CHILD_NOTE_POST_TYPE !== $screen->post_type ) {
		return;
	}

	$script_path = get_stylesheet_directory() . '/assets/note-thread-editor.js';
	if ( ! is_readable( $script_path ) ) {
		return;
	}

	wp_enqueue_script(
		'child-note-thread-editor',
		get_stylesheet_directory_uri() . '/assets/note-thread-editor.js',
		[
			'wp-components',
			'wp-core-data',
			'wp-data',
			'wp-edit-post',
			'wp-element',
			'wp-i18n',
			'wp-plugins',
		],
		(string) filemtime( $script_path ),
		true
	);

	wp_localize_script(
		'child-note-thread-editor',
		'childNoteThreadSettings',
		[
			'postType'      => CHILD_NOTE_POST_TYPE,
			'parentMetaKey' => CHILD_NOTE_THREAD_PARENT_META,
		]
	);
}
add_action( 'enqueue_block_editor_assets', 'child_note_thread_enqueue_editor_assets' );

/**
 * Resolve a Note's immediate parent to its canonical ActivityPub object ID.
 *
 * @param mixed $in_reply_to Existing ActivityPub reply target.
 * @param mixed $item        Object being transformed.
 * @return mixed
 */
function child_note_thread_activitypub_in_reply_to( $in_reply_to, $item ) {
	if (
		! $item instanceof WP_Post
		|| CHILD_NOTE_POST_TYPE !== $item->post_type
		|| ! class_exists( '\\Activitypub\\Transformer\\Factory' )
	) {
		return $in_reply_to;
	}

	$parent_id = child_note_thread_get_parent_id( $item->ID );
	$parent    = $parent_id > 0 ? get_post( $parent_id ) : null;
	if ( ! $parent instanceof WP_Post ) {
		return $in_reply_to;
	}

	$transformer = \Activitypub\Transformer\Factory::get_transformer( $parent );
	if ( is_wp_error( $transformer ) ) {
		return $in_reply_to;
	}

	$parent_id = $transformer->to_id();

	return is_string( $parent_id ) && '' !== $parent_id ? $parent_id : $in_reply_to;
}

/**
 * Give ActivityPub's post transformer a virtual Reply block for child Notes.
 *
 * ActivityPub only invokes its property-value filters for non-null values. A
 * transformer built from an in-memory copy is therefore required for Notes
 * that do not contain an explicit ActivityPub Reply block. The saved post
 * content remains unchanged.
 *
 * @param mixed  $transformer Transformer selected by an earlier integration.
 * @param mixed  $item        Object being transformed.
 * @param string $object_class Source object class.
 * @return mixed
 */
function child_note_thread_activitypub_transformer( $transformer, $item, string $object_class ) {
	$transformer_class = '\\Activitypub\\Transformer\\Post';

	if (
		null !== $transformer
		|| ! $item instanceof WP_Post
		|| 'WP_Post' !== $object_class
		|| CHILD_NOTE_POST_TYPE !== $item->post_type
		|| ! class_exists( $transformer_class )
	) {
		return $transformer;
	}

	$parent_id = child_note_thread_activitypub_in_reply_to( null, $item );
	if ( ! is_string( $parent_id ) || '' === $parent_id ) {
		return $transformer;
	}

	$thread_item               = clone $item;
	$thread_item->post_content = serialize_block(
		[
			'blockName'    => 'activitypub/reply',
			'attrs'        => [
				'url'                => $parent_id,
				'isValidActivityPub' => true,
			],
			'innerBlocks'  => [],
			'innerHTML'    => '',
			'innerContent' => [],
		]
	) . "\n" . $thread_item->post_content;

	return new $transformer_class( $thread_item );
}
add_filter( 'activitypub_transformer', 'child_note_thread_activitypub_transformer', 20, 3 );

/**
 * Keep one WordPress Note mapped to one Bluesky record.
 */
function child_note_thread_atmosphere_composition( string $strategy, WP_Post $post ): string {
	return CHILD_NOTE_POST_TYPE === $post->post_type ? 'link-card' : $strategy;
}
add_filter( 'atmosphere_long_form_composition', 'child_note_thread_atmosphere_composition', 10, 2 );

/**
 * Defer ATmosphere publication until a child Note can carry native reply refs.
 *
 * This guards scheduled WordPress publication and lifecycle paths that enqueue
 * ATmosphere jobs without REST validation. Returning false from schedule_event
 * prevents a standalone Bluesky record. A successful parent publish resumes
 * the deferred hook below.
 *
 * @param mixed $event Event object being scheduled.
 * @return mixed
 */
function child_note_thread_defer_atmosphere_event( $event ) {
	if ( ! is_object( $event ) ) {
		return $event;
	}

	$hook = (string) ( $event->hook ?? '' );
	if ( ! in_array( $hook, [ 'atmosphere_publish_post', 'atmosphere_update_post' ], true ) ) {
		return $event;
	}

	$post_id = absint( $event->args[0] ?? 0 );
	$post    = get_post( $post_id );
	if ( ! $post instanceof WP_Post || CHILD_NOTE_POST_TYPE !== $post->post_type ) {
		return $event;
	}

	$parent_id = child_note_thread_get_parent_id( $post_id );
	if (
		$parent_id <= 0
		|| '1' === (string) get_post_meta( $post_id, 'atmosphere_disabled', true )
	) {
		delete_post_meta( $post_id, CHILD_NOTE_THREAD_ATMOSPHERE_PENDING_META );
		return $event;
	}

	if ( null === child_note_thread_get_atmosphere_reply_for_parent( $parent_id ) ) {
		$pending_hook = empty( child_note_thread_get_atmosphere_records( $post_id ) )
			? 'atmosphere_publish_post'
			: 'atmosphere_update_post';
		update_post_meta( $post_id, CHILD_NOTE_THREAD_ATMOSPHERE_PENDING_META, $pending_hook );
		return false;
	}

	delete_post_meta( $post_id, CHILD_NOTE_THREAD_ATMOSPHERE_PENDING_META );
	return $event;
}
add_filter( 'schedule_event', 'child_note_thread_defer_atmosphere_event' );

/**
 * Resume a deferred child publication after its parent reaches Bluesky.
 *
 * @param WP_Post        $post   Parent Note that completed publication.
 * @param array|WP_Error $result ATmosphere publication result.
 */
function child_note_thread_resume_atmosphere_child( WP_Post $post, $result ): void {
	if ( is_wp_error( $result ) || CHILD_NOTE_POST_TYPE !== $post->post_type ) {
		return;
	}

	$child_id = child_note_thread_get_child_id( $post->ID );
	if ( $child_id <= 0 ) {
		return;
	}

	$hook = (string) get_post_meta( $child_id, CHILD_NOTE_THREAD_ATMOSPHERE_PENDING_META, true );
	if ( ! in_array( $hook, [ 'atmosphere_publish_post', 'atmosphere_update_post' ], true ) ) {
		return;
	}

	delete_post_meta( $child_id, CHILD_NOTE_THREAD_ATMOSPHERE_PENDING_META );
	wp_schedule_single_event( time(), $hook, [ $child_id ] );
}
add_action( 'atmosphere_publish_post_result', 'child_note_thread_resume_atmosphere_child', 10, 2 );

/**
 * Add native Bluesky thread references to a child Note record.
 *
 * @param array<string, mixed> $record  Bluesky record.
 * @param WP_Post              $post    WordPress post being transformed.
 * @param array<string, mixed> $context ATmosphere transformation context.
 * @return array<string, mixed>
 */
function child_note_thread_atmosphere_record( array $record, WP_Post $post, array $context ): array {
	if (
		CHILD_NOTE_POST_TYPE !== $post->post_type
		|| child_note_thread_get_parent_id( $post->ID ) <= 0
		|| ! empty( $context['is_thread_reply'] )
	) {
		return $record;
	}

	$reply = child_note_thread_get_atmosphere_reply( $post->ID );
	if ( null !== $reply ) {
		$record['reply'] = $reply;
	}

	return $record;
}
add_filter( 'atmosphere_transform_bsky_post', 'child_note_thread_atmosphere_record', 10, 3 );
