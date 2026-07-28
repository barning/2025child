<?php
/**
 * Post Likes helper tests.
 *
 * @package TwentyTwentyFiveChild
 */

use PHPUnit\Framework\TestCase;

final class PostLikesTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['wpdb']                  = new Child_Test_WPDB();
		$GLOBALS['child_test_transients'] = array();
		$GLOBALS['child_test_post_status'] = array();
		$_COOKIE[ CHILD_POST_LIKES_COOKIE ] = '11111111-1111-4111-8111-111111111111';
	}

	public function test_desired_state_normalization_accepts_only_supported_values(): void {
		self::assertTrue( child_post_likes_normalize_desired_state( true ) );
		self::assertTrue( child_post_likes_normalize_desired_state( ' YES ' ) );
		self::assertFalse( child_post_likes_normalize_desired_state( 0 ) );
		self::assertFalse( child_post_likes_normalize_desired_state( 'false' ) );
		self::assertFalse( child_post_likes_normalize_desired_state( 2 ) );
		self::assertNull( child_post_likes_normalize_desired_state( array() ) );
	}

	public function test_mutations_are_idempotent(): void {
		self::assertSame( array( 'liked' => true, 'count' => 1 ), child_post_likes_set_state( 42, true ) );
		self::assertSame( array( 'liked' => true, 'count' => 1 ), child_post_likes_set_state( 42, true ) );
		self::assertSame( array( 'liked' => false, 'count' => 0 ), child_post_likes_set_state( 42, false ) );
		self::assertSame( array( 'liked' => false, 'count' => 0 ), child_post_likes_set_state( 42, false ) );
	}

	public function test_post_validation_requires_a_positive_published_post(): void {
		$GLOBALS['child_test_post_status'][42] = 'publish';
		$GLOBALS['child_test_post_status'][43] = 'draft';

		self::assertTrue( child_post_likes_validate_post_id( 42 ) );
		self::assertFalse( child_post_likes_validate_post_id( 43 ) );
		self::assertFalse( child_post_likes_validate_post_id( 0 ) );
	}

	public function test_rate_limit_rejects_mutations_after_twenty_attempts(): void {
		for ( $attempt = 0; $attempt < 20; ++$attempt ) {
			self::assertTrue( child_post_likes_check_rate_limit() );
		}

		$result = child_post_likes_check_rate_limit();
		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'likes_rate_limited', $result->code );
		self::assertSame( 429, $result->data['status'] );
	}
}
