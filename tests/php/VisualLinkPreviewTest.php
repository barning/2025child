<?php
/**
 * Visual Link Preview helper tests.
 *
 * @package TwentyTwentyFiveChild
 */

use PHPUnit\Framework\TestCase;

final class VisualLinkPreviewTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['child_test_options']    = array();
		$GLOBALS['child_test_transients'] = array();
		$GLOBALS['child_test_scheduled']  = array();
		$GLOBALS['child_test_response']   = array(
			'response' => array( 'code' => 200 ),
			'headers'  => array(),
			'body'     => '{}',
		);
	}

	public function test_cache_url_validation_rejects_credentials_and_non_web_ports(): void {
		self::assertSame( '', child_vlp_normalize_url_for_cache( 'https://user@example.com/page' ) );
		self::assertSame( '', child_vlp_normalize_url_for_cache( 'https://example.com:8080/page' ) );
		self::assertSame( 'https://example.com/page', child_vlp_normalize_url_for_cache( ' https://example.com/page ' ) );
	}

	public function test_fetch_validation_rejects_private_and_reserved_addresses(): void {
		self::assertSame( '', child_vlp_normalize_url( 'http://127.0.0.1/private' ) );
		self::assertSame( '', child_vlp_normalize_url( 'http://10.1.2.3/private' ) );
		self::assertSame( '', child_vlp_normalize_url( 'http://[::1]/private' ) );
		self::assertSame( 'https://8.8.8.8/public', child_vlp_normalize_url( 'https://8.8.8.8/public' ) );
	}

	public function test_cache_miss_falls_back_to_normalized_stale_metadata(): void {
		$url = 'https://example.com/page';
		set_transient(
			child_vlp_get_stale_cache_key( $url ),
			array(
				'url'   => $url,
				'title' => '<b>Old title</b>',
				'desc'  => 'Old description',
				'image' => 'https://example.com/image.jpg',
			)
		);

		$result = child_vlp_get_cached_metadata( $url );

		self::assertFalse( $result['fresh'] );
		self::assertSame( 'Old title', $result['data']['title'] );
		self::assertSame( $url, $result['data']['url'] );
	}

	public function test_negative_cache_entry_does_not_hide_rich_stale_metadata(): void {
		$url = 'https://example.com/page';
		set_transient( child_vlp_get_cache_key( $url ), child_vlp_empty_metadata( $url ) );
		set_transient(
			child_vlp_get_stale_cache_key( $url ),
			array(
				'url'   => $url,
				'title' => 'Last known title',
				'desc'  => 'Last known description',
				'image' => '',
			)
		);

		$result = child_vlp_get_cached_metadata( $url );

		self::assertTrue( $result['fresh'] );
		self::assertSame( 'Last known title', $result['data']['title'] );
		self::assertSame( 'Last known description', $result['data']['desc'] );
	}

	public function test_failed_refresh_keeps_stale_metadata_available_and_releases_lock(): void {
		$url = 'https://8.8.8.8/page';
		set_transient(
			child_vlp_get_stale_cache_key( $url ),
			array(
				'url'   => $url,
				'title' => 'Working stale title',
				'desc'  => '',
				'image' => '',
			)
		);
		$GLOBALS['child_test_response'] = new WP_Error( 'http_request_failed', 'Timed out' );

		child_vlp_refresh_metadata( $url );
		$result = child_vlp_get_cached_metadata( $url );

		self::assertTrue( $result['fresh'] );
		self::assertSame( 'Working stale title', $result['data']['title'] );
		self::assertArrayNotHasKey( child_vlp_get_lock_option( $url ), $GLOBALS['child_test_options'] );
	}

	public function test_fetch_lock_is_atomic_and_expired_locks_can_be_reclaimed(): void {
		$url = 'https://example.com/page';

		self::assertTrue( child_vlp_acquire_fetch_lock( $url ) );
		self::assertFalse( child_vlp_acquire_fetch_lock( $url ) );

		$GLOBALS['child_test_options'][ child_vlp_get_lock_option( $url ) ] = time() - 1;
		self::assertTrue( child_vlp_acquire_fetch_lock( $url ) );
	}

	public function test_refresh_scheduling_deduplicates_url_events(): void {
		$url = 'https://example.com/page';

		self::assertTrue( child_vlp_schedule_refresh( $url ) );
		self::assertFalse( child_vlp_schedule_refresh( $url ) );
		self::assertCount( 1, $GLOBALS['child_test_scheduled'] );
	}

	public function test_stale_frontend_render_schedules_background_refresh(): void {
		$url = 'https://example.com/page';
		set_transient(
			child_vlp_get_stale_cache_key( $url ),
			array(
				'url'   => $url,
				'title' => 'Stale title',
				'desc'  => '',
				'image' => '',
			)
		);
		$render = require dirname( __DIR__, 2 ) . '/blocks/visual-link-preview/render.php';

		$render( array( 'url' => $url ) );
		$render( array( 'url' => $url ) );

		self::assertCount( 1, $GLOBALS['child_test_scheduled'] );
	}
}
