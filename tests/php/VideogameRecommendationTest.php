<?php
/**
 * Videogame recommendation presentation tests.
 *
 * @package TwentyTwentyFiveChild
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/blocks/videogame-recommendation/utils.php';

final class VideogameRecommendationTest extends TestCase {
	public function test_platform_info_returns_shared_css_key(): void {
		self::assertSame(
			[ 'name' => 'PS5', 'color' => '#003087', 'key' => 'playstation5' ],
			child_get_platform_info( 'PlayStation 5' )
		);
	}

	public function test_unknown_platform_uses_default_css_key(): void {
		self::assertSame( 'default', child_get_platform_info( 'Dreamcast' )['key'] );
	}
}
