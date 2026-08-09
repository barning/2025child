<?php
/**
 * Tests for feed-safe block rendering helpers.
 *
 * @package TwentyTwentyFiveChild
 */

use PHPUnit\Framework\TestCase;

final class RssBlockFallbacksTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['child_test_is_feed']   = false;
		$GLOBALS['child_test_post_id']   = 0;
		$GLOBALS['child_test_permalink'] = '';
	}

	public function test_feed_context_is_forwarded(): void {
		$this->assertFalse( child_is_feed_render() );
		$GLOBALS['child_test_is_feed'] = true;
		$this->assertTrue( child_is_feed_render() );
	}

	public function test_card_is_semantic_and_self_contained(): void {
		$html = child_render_feed_card(
			array(
				'title'        => 'A <Great> Book',
				'url'          => 'https://example.com/book',
				'image'        => 'https://example.com/cover.jpg',
				'image_width'  => 600,
				'image_height' => 900,
				'meta'         => array( 'Book', '<b>Author</b>' ),
				'description'  => 'A concise description.',
				'links'        => array(
					array(
						'url'   => 'https://example.com/post',
						'label' => 'View post',
					),
				),
			)
		);

		$this->assertStringContainsString( 'class="child-rss-card"', $html );
		$this->assertStringContainsString( 'max-width:100%', $html );
		$this->assertStringContainsString( 'width="600" height="900"', $html );
		$this->assertStringContainsString( 'A &lt;Great&gt; Book', $html );
		$this->assertStringContainsString( 'Book · Author', $html );
		$this->assertStringContainsString( 'View post', $html );
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringNotContainsString( '<button', $html );
		$this->assertStringNotContainsString( '<iframe', $html );
	}

	public function test_empty_card_is_omitted(): void {
		$this->assertSame( '', child_render_feed_card( array( 'title' => '' ) ) );
	}

	public function test_post_link_requires_a_current_permalink(): void {
		$this->assertSame( '', child_render_feed_post_link( 'View post' ) );

		$GLOBALS['child_test_post_id']   = 42;
		$GLOBALS['child_test_permalink'] = 'https://example.com/post';
		$this->assertSame(
			'<p><a href="https://example.com/post">View post</a></p>',
			child_render_feed_post_link( 'View post' )
		);
	}
}
