<?php
/**
 * Media grid helper tests.
 *
 * @package TwentyTwentyFiveChild
 */

use PHPUnit\Framework\TestCase;

final class MediaCoverGridTest extends TestCase {
	public function test_provider_ids_produce_stable_dedupe_keys(): void {
		self::assertSame(
			'movie:tmdb:42',
			child_get_media_cover_grid_dedupe_key(
				[
					'type'   => 'movie',
					'title'  => 'Example',
					'tmdbId' => 42,
				]
			)
		);
	}

	public function test_duplicate_mentions_keep_newest_item_and_all_sources(): void {
		$items = [
			[
				'type'                => 'book',
				'title'               => 'A Book',
				'meta'                => 'An Author',
				'sourcePostId'        => 1,
				'sourcePostTitle'     => 'Older',
				'sourcePostUrl'       => 'https://example.com/older',
				'sourcePostTimestamp' => 100,
			],
			[
				'type'                => 'book',
				'title'               => 'A Book',
				'meta'                => 'An Author',
				'sourcePostId'        => 2,
				'sourcePostTitle'     => 'Newer',
				'sourcePostUrl'       => 'https://example.com/newer',
				'sourcePostTimestamp' => 200,
			],
		];

		$result = child_dedupe_media_cover_grid_items( $items );

		self::assertCount( 1, $result );
		self::assertSame( 2, $result[0]['sourcePostId'] );
		self::assertSame( 2, $result[0]['mentionCount'] );
		self::assertSame( [ 2, 1 ], array_column( $result[0]['sourcePosts'], 'id' ) );
	}

	public function test_desired_like_state_normalization_is_strict(): void {
		self::assertTrue( child_post_likes_normalize_desired_state( 'yes' ) );
		self::assertFalse( child_post_likes_normalize_desired_state( '0' ) );
		self::assertNull( child_post_likes_normalize_desired_state( 'sometimes' ) );
	}
}
