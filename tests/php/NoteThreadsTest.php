<?php
/**
 * Note thread and federation helper tests.
 *
 * @package TwentyTwentyFiveChild
 */

use PHPUnit\Framework\TestCase;

final class NoteThreadsTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['child_test_posts']     = array();
		$GLOBALS['child_test_post_meta'] = array();
		$GLOBALS['child_test_scheduled'] = array();
	}

	private function add_note( int $post_id, string $status = 'publish' ): WP_Post {
		$post = new WP_Post(
			array(
				'ID'          => $post_id,
				'post_type'   => CHILD_NOTE_POST_TYPE,
				'post_status' => $status,
				'post_date'   => sprintf( '2026-01-%02d 12:00:00', $post_id ),
			)
		);

		$GLOBALS['child_test_posts'][ $post_id ] = $post;

		return $post;
	}

	private function connect( int $post_id, int $parent_id ): void {
		update_post_meta( $post_id, CHILD_NOTE_THREAD_PARENT_META, $parent_id );
	}

	public function test_traverses_a_complete_linear_chain(): void {
		$this->add_note( 1 );
		$this->add_note( 2 );
		$this->add_note( 3 );
		$this->connect( 2, 1 );
		$this->connect( 3, 2 );

		self::assertSame( array( 1, 2 ), child_note_thread_get_ancestor_ids( 3 ) );
		self::assertSame( array( 2, 3 ), child_note_thread_get_descendant_ids( 1 ) );
		self::assertSame( 1, child_note_thread_get_root_id( 3 ) );
		self::assertSame( 3, child_note_thread_get_child_id( 2 ) );
	}

	public function test_rejects_a_second_child_even_when_the_first_child_is_a_draft(): void {
		$this->add_note( 1 );
		$this->add_note( 2, 'draft' );
		$this->add_note( 3, 'draft' );
		$this->connect( 2, 1 );

		$result = child_note_thread_validate_parent( 3, 1 );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'note_thread_parent_has_child', $result->get_error_code() );
		self::assertSame( 409, $result->get_error_data()['status'] );
	}

	public function test_rejects_a_parent_that_would_create_a_cycle(): void {
		$this->add_note( 1 );
		$this->add_note( 2 );
		$this->add_note( 3 );
		$this->connect( 1, 3 );
		$this->connect( 2, 1 );

		$result = child_note_thread_validate_parent( 3, 2 );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'note_thread_invalid_parent', $result->get_error_code() );
	}

	public function test_builds_bluesky_strong_references_from_the_root_and_immediate_parent(): void {
		$this->add_note( 1 );
		$this->add_note( 2 );
		$this->connect( 2, 1 );

		update_post_meta(
			1,
			'_atmosphere_bsky_thread_records',
			array(
				array(
					'uri' => 'at://did:plc:test/post/root',
					'cid' => 'root-cid',
					'tid' => 'root',
				),
				array(
					'uri' => 'at://did:plc:test/post/root-card',
					'cid' => 'root-card-cid',
					'tid' => 'root-card',
				),
			)
		);
		update_post_meta(
			2,
			'_atmosphere_bsky_thread_records',
			array(
				array(
					'uri' => 'at://did:plc:test/post/parent',
					'cid' => 'parent-cid',
					'tid' => 'parent',
				),
				array(
					'uri' => 'at://did:plc:test/post/parent-card',
					'cid' => 'parent-card-cid',
					'tid' => 'parent-card',
				),
			)
		);

		self::assertSame(
			array(
				'root'   => array(
					'uri' => 'at://did:plc:test/post/root',
					'cid' => 'root-cid',
				),
				'parent' => array(
					'uri' => 'at://did:plc:test/post/parent-card',
					'cid' => 'parent-card-cid',
				),
			),
			child_note_thread_get_atmosphere_reply_for_parent( 2 )
		);
	}

	public function test_requires_the_parent_to_be_synced_before_bluesky_publication(): void {
		$this->add_note( 1 );
		$this->add_note( 2, 'draft' );

		$result = child_note_thread_validate_parent( 2, 1, true );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'note_thread_parent_not_on_bluesky', $result->get_error_code() );
		self::assertSame( 409, $result->get_error_data()['status'] );
	}

	public function test_atmosphere_filter_adds_native_reply_references_to_child_notes(): void {
		$this->add_note( 1 );
		$child = $this->add_note( 2 );
		$this->connect( 2, 1 );
		update_post_meta( 1, '_atmosphere_bsky_uri', 'at://did:plc:test/post/root' );
		update_post_meta( 1, '_atmosphere_bsky_cid', 'root-cid' );

		$record = child_note_thread_atmosphere_record(
			array( 'text' => 'Continuation' ),
			$child,
			array()
		);

		self::assertSame(
			array(
				'root'   => array(
					'uri' => 'at://did:plc:test/post/root',
					'cid' => 'root-cid',
				),
				'parent' => array(
					'uri' => 'at://did:plc:test/post/root',
					'cid' => 'root-cid',
				),
			),
			$record['reply']
		);
	}

	public function test_atmosphere_composition_keeps_each_note_as_one_link_card_record(): void {
		$note = $this->add_note( 1 );
		$post = new WP_Post(
			array(
				'ID'        => 2,
				'post_type' => 'post',
			)
		);

		self::assertSame( 'link-card', child_note_thread_atmosphere_composition( 'thread', $note ) );
		self::assertSame( 'thread', child_note_thread_atmosphere_composition( 'thread', $post ) );
	}

	public function test_only_the_tail_can_be_removed(): void {
		$root  = $this->add_note( 1 );
		$child = $this->add_note( 2 );
		$this->connect( 2, 1 );

		self::assertFalse( child_note_thread_prevent_non_tail_removal( null, $root ) );
		self::assertNull( child_note_thread_prevent_non_tail_removal( null, $child ) );
	}

	public function test_a_continuation_cannot_be_restored_without_a_published_parent(): void {
		$this->add_note( 1, 'trash' );
		$child = $this->add_note( 2, 'trash' );
		$this->connect( 2, 1 );

		self::assertFalse( child_note_thread_prevent_branching_restore( null, $child ) );
	}

	public function test_related_password_protected_notes_do_not_expose_their_content(): void {
		$note                = $this->add_note( 1 );
		$note->post_content  = 'Secret thread content';
		$note->post_password = 'secret';

		$content = child_note_thread_render_body( $note );

		self::assertSame( 'Password required.', $content );
		self::assertStringNotContainsString( 'Secret thread content', $content );
	}

	public function test_scheduled_children_require_the_parent_to_be_ready_for_bluesky(): void {
		if ( ! defined( 'ATMOSPHERE_VERSION' ) ) {
			define( 'ATMOSPHERE_VERSION', '2.5.0-test' );
		}

		$this->add_note( 1 );
		$this->add_note( 2, 'future' );
		$prepared = (object) array(
			'ID'          => 2,
			'post_status' => 'future',
		);
		$request  = new WP_REST_Request(
			array(
				'id'   => 2,
				'meta' => array( CHILD_NOTE_THREAD_PARENT_META => 1 ),
			)
		);

		$result = child_note_thread_validate_rest_insert( $prepared, $request );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'note_thread_parent_not_on_bluesky', $result->get_error_code() );
	}

	public function test_non_rest_updates_keep_a_non_tail_note_published(): void {
		$this->add_note( 1 );
		$this->add_note( 2 );
		$this->connect( 2, 1 );

		$data = child_note_thread_preserve_non_tail_status(
			array(
				'post_type'   => CHILD_NOTE_POST_TYPE,
				'post_status' => 'draft',
			),
			array( 'ID' => 1 )
		);

		self::assertSame( 'publish', $data['post_status'] );
	}

	public function test_rest_updates_reject_unpublishing_a_non_tail_note(): void {
		$this->add_note( 1 );
		$this->add_note( 2 );
		$this->connect( 2, 1 );
		$prepared = (object) array(
			'ID'          => 1,
			'post_status' => 'draft',
		);
		$request  = new WP_REST_Request(
			array(
				'id'   => 1,
				'meta' => array(),
			)
		);

		$result = child_note_thread_validate_rest_insert( $prepared, $request );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'note_thread_parent_has_child', $result->get_error_code() );
	}

	public function test_legacy_published_notes_are_locked_to_their_existing_relationship(): void {
		$this->add_note( 1 );
		$this->add_note( 2 );
		$prepared = (object) array(
			'ID'          => 2,
			'post_status' => 'publish',
		);
		$request  = new WP_REST_Request(
			array(
				'id'   => 2,
				'meta' => array( CHILD_NOTE_THREAD_PARENT_META => 1 ),
			)
		);

		$result = child_note_thread_validate_rest_insert( $prepared, $request );
		$state  = child_note_thread_rest_state( array( 'id' => 2 ) );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'note_thread_relationship_locked', $result->get_error_code() );
		self::assertTrue( $state['locked'] );
	}

	public function test_atmosphere_event_is_deferred_and_resumed_after_parent_sync(): void {
		$parent = $this->add_note( 1 );
		$this->add_note( 2 );
		$this->connect( 2, 1 );
		$event = (object) array(
			'hook' => 'atmosphere_publish_post',
			'args' => array( 2 ),
		);

		self::assertFalse( child_note_thread_defer_atmosphere_event( $event ) );
		self::assertSame(
			'atmosphere_publish_post',
			get_post_meta( 2, CHILD_NOTE_THREAD_ATMOSPHERE_PENDING_META, true )
		);

		update_post_meta( 1, '_atmosphere_bsky_uri', 'at://did:plc:test/post/root' );
		update_post_meta( 1, '_atmosphere_bsky_cid', 'root-cid' );
		child_note_thread_resume_atmosphere_child( $parent, array( 'success' => true ) );

		self::assertFalse( metadata_exists( 'post', 2, CHILD_NOTE_THREAD_ATMOSPHERE_PENDING_META ) );
		self::assertNotFalse( wp_next_scheduled( 'atmosphere_publish_post', array( 2 ) ) );
		self::assertSame( $event, child_note_thread_defer_atmosphere_event( $event ) );
	}
}
