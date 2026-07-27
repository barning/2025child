<?php
/**
 * Provider HTTP helper tests.
 *
 * @package TwentyTwentyFiveChild
 */

use PHPUnit\Framework\TestCase;

final class ProviderHttpTest extends TestCase {
	protected function setUp(): void {
		$GLOBALS['child_test_options']    = array();
		$GLOBALS['child_test_transients'] = array();
		$GLOBALS['child_test_response']   = array(
			'response' => array( 'code' => 200 ),
			'body'     => '{"id":42}',
		);
	}

	public function test_cache_key_does_not_expose_request_secrets(): void {
		$key = child_provider_cache_key( 'TMDB Search', 'api_key=super-secret&query=Example' );

		self::assertStringStartsWith( 'child_api_tmdbsearch_', $key );
		self::assertStringNotContainsString( 'super-secret', $key );
		self::assertStringNotContainsString( 'Example', $key );
	}

	public function test_valid_json_response_is_cached(): void {
		$result = child_provider_get_json( 'https://example.com/items', array(), 'test' );

		self::assertSame( array( 'id' => 42 ), $result );
		self::assertCount( 1, $GLOBALS['child_test_transients'] );
	}

	public function test_provider_error_status_is_preserved_for_actionable_responses(): void {
		$GLOBALS['child_test_response'] = array(
			'response' => array( 'code' => 429 ),
			'body'     => '{}',
		);

		$result = child_provider_get_json( 'https://example.com/rate-limited', array(), 'test' );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'provider_http_error', $result->code );
		self::assertSame( 429, $result->data['status'] );
	}

	public function test_invalid_json_is_rejected(): void {
		$GLOBALS['child_test_response']['body'] = '{not-json';

		$result = child_provider_get_json( 'https://example.com/invalid', array(), 'test' );

		self::assertInstanceOf( WP_Error::class, $result );
		self::assertSame( 'provider_invalid_json', $result->code );
	}

	public function test_cache_registry_remains_bounded(): void {
		for ( $index = 0; $index < CHILD_PROVIDER_CACHE_MAX_KEYS + 5; ++$index ) {
			child_provider_cache_set( 'bounded', 'key-' . $index, array( $index ), HOUR_IN_SECONDS );
		}

		self::assertCount(
			CHILD_PROVIDER_CACHE_MAX_KEYS,
			$GLOBALS['child_test_options']['child_api_keys_bounded']
		);
		self::assertArrayNotHasKey( 'key-0', $GLOBALS['child_test_transients'] );
		self::assertArrayHasKey( 'key-104', $GLOBALS['child_test_transients'] );
	}
}
