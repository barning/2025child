<?php
/**
 * Deterministic content fixture for local WordPress Playground layout checks.
 *
 * Mount tests/visual/mu-plugins at /wordpress/wp-content/mu-plugins. This file
 * is not included in release packages.
 *
 * @package TwentyTwentyFiveChild
 */

const CHILD_LAYOUT_FIXTURE_VERSION = '4';
const CHILD_LAYOUT_FIXTURE_META_KEY = '_child_layout_fixture_key';

/**
 * Keep the disposable fixture deterministic even when Playground workers boot
 * before the persisted WPLANG option becomes visible.
 *
 * @return string
 */
function child_layout_fixture_locale(): string {
	return 'de_DE';
}
add_filter( 'locale', 'child_layout_fixture_locale' );

/**
 * Serialize a dynamic child-theme block.
 *
 * @param string               $name       Block slug without namespace.
 * @param array<string, mixed> $attributes Block attributes.
 * @return string
 */
function child_layout_fixture_block( string $name, array $attributes = array() ): string {
	return serialize_block(
		array(
			'blockName'    => 'child/' . $name,
			'attrs'        => $attributes,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		)
	);
}

/**
 * Create or update a fixture post using a stable identity.
 *
 * Legacy title matches are claimed and tagged during migration. If both an
 * English v2 row and a translated v3 row exist, the older English row is
 * reused and the duplicate translated row is deleted.
 *
 * @param string        $fixture_key  Stable fixture identity.
 * @param string        $title        Current display title.
 * @param string        $content      Post content.
 * @param string        $type         Post type.
 * @param array<string> $legacy_titles Previous display titles.
 * @return int
 */
function child_layout_fixture_upsert_post(
	string $fixture_key,
	string $title,
	string $content,
	string $type = 'post',
	array $legacy_titles = array()
): int {
	$tagged_posts = get_posts(
		array(
			'post_type'        => $type,
			'post_status'      => 'any',
			'posts_per_page'   => -1,
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'meta_key'         => CHILD_LAYOUT_FIXTURE_META_KEY,
			'meta_value'       => $fixture_key,
			'suppress_filters' => true,
		)
	);
	$candidates   = array();

	foreach ( $tagged_posts as $tagged_post ) {
		if ( $tagged_post instanceof WP_Post ) {
			$candidates[ $tagged_post->ID ] = $tagged_post;
		}
	}

	foreach ( array_merge( $legacy_titles, array( $title ) ) as $candidate_title ) {
		$matching_post = get_page_by_title( $candidate_title, OBJECT, $type );
		if ( $matching_post instanceof WP_Post ) {
			$candidates[ $matching_post->ID ] = $matching_post;
		}
	}

	$existing = reset( $candidates );
	$postarr  = array(
		'post_title'   => $title,
		'post_content' => $content,
		'post_status'  => 'publish',
		'post_type'    => $type,
	);

	if ( $existing instanceof WP_Post ) {
		$postarr['ID'] = $existing->ID;
	}

	$post_id = wp_insert_post( $postarr, true );
	if ( is_wp_error( $post_id ) ) {
		return 0;
	}

	$post_id = (int) $post_id;
	update_post_meta( $post_id, CHILD_LAYOUT_FIXTURE_META_KEY, $fixture_key );

	foreach ( array_keys( $candidates ) as $candidate_id ) {
		if ( $candidate_id !== $post_id ) {
			wp_delete_post( $candidate_id, true );
		}
	}

	return $post_id;
}

/**
 * Seed representative content for every custom block.
 */
function child_layout_fixture_seed(): void {
	if ( CHILD_LAYOUT_FIXTURE_VERSION === get_option( 'child_layout_fixture_version' ) ) {
		return;
	}

	update_option( 'WPLANG', 'de_DE' );

	$image_url = get_template_directory_uri() . '/screenshot.png';
	$book      = child_layout_fixture_block(
		'book-rating',
		array(
			'bookTitle' => 'The Left Hand of Darkness',
			'author'    => 'Ursula K. Le Guin',
			'coverUrl'  => $image_url,
			'shopUrl'   => 'https://example.com/book',
		)
	);
	$movie     = child_layout_fixture_block(
		'media-recommendation',
		array(
			'mediaTitle'  => 'In the Mood for Love',
			'mediaType'   => 'movie',
			'posterUrl'   => $image_url,
			'releaseYear' => '2000',
			'tmdbId'      => 843,
			'serviceUrl'  => 'https://example.com/movie',
		)
	);
	$music     = child_layout_fixture_block(
		'music-recommendation',
		array(
			'musicType'  => 'album',
			'title'      => 'Blue',
			'artist'     => 'Joni Mitchell',
			'albumTitle' => 'Blue',
			'releaseYear' => '1971',
			'coverUrl'   => $image_url,
			'provider'   => 'apple',
			'providerId' => 'fixture-album',
			'providerUrl' => 'https://example.com/music',
		)
	);
	$game      = child_layout_fixture_block(
		'videogame-recommendation',
		array(
			'gameTitle'   => 'Journey',
			'coverUrl'    => $image_url,
			'coverFormat' => 'landscape',
			'releaseDate' => '2012-03-13',
			'platforms'   => array( 'PlayStation 5', 'PC' ),
			'genres'      => array( 'Adventure', 'Indie' ),
			'rawgId'      => 3254,
			'shopUrl'     => 'https://example.com/game',
		)
	);

	$source_ids = array_filter(
		array(
			child_layout_fixture_upsert_post( 'source-book', 'Testquelle Buch', $book, 'post', array( 'Fixture Book Source' ) ),
			child_layout_fixture_upsert_post( 'source-movie', 'Testquelle Film', $movie, 'post', array( 'Fixture Movie Source' ) ),
			child_layout_fixture_upsert_post( 'source-music', 'Testquelle Musik', $music, 'post', array( 'Fixture Music Source' ) ),
			child_layout_fixture_upsert_post( 'source-game', 'Testquelle Spiel', $game, 'post', array( 'Fixture Game Source' ) ),
		)
	);

	$preview_url = 'https://example.com/fixture-preview';
	set_transient(
		'child_vlp_' . md5( $preview_url ),
		array(
			'url'   => $preview_url,
			'title' => 'Eine deterministische Linkvorschau',
			'desc'  => 'Lang genug, um den Zeilenumbruch ohne externe Anfrage zu prüfen.',
			'image' => $image_url,
		),
		DAY_IN_SECONDS
	);

	$content  = $book;
	$content .= $movie;
	$content .= $music;
	$content .= $game;
	$content .= child_layout_fixture_block(
		'magic-cards',
		array(
			'displayType'  => 'card',
			'cardName'     => 'Black Lotus',
			'cardImageUrl' => $image_url,
		)
	);
	$content .= child_layout_fixture_block(
		'popular-posts',
		array(
			'selectedPosts' => array_values( $source_ids ),
			'title'         => 'Testfavoriten mit einer absichtlich langen Überschrift',
			'emoji'         => '✨',
		)
	);
	$content .= child_layout_fixture_block(
		'media-cover-grid',
		array(
			'mediaTypes'     => array( 'book', 'movie', 'series', 'game', 'music' ),
			'linkTo'         => 'post',
			'sortOrder'      => 'newest',
			'showTitle'      => true,
			'showMeta'       => true,
			'showType'       => true,
			'allowDuplicates' => false,
		)
	);
	$content .= child_layout_fixture_block(
		'post-likes',
		array(
			'buttonSize'    => 'md',
			'buttonAlign'   => 'center',
			'ctaText'       => 'Gefällt dir diese Testseite?',
			'reactionEmoji' => '❤️',
		)
	);
	$content .= child_layout_fixture_block( 'visual-link-preview', array( 'url' => $preview_url ) );
	$content .= child_layout_fixture_block( 'pixelfed-feed', array( 'feedUrl' => '', 'itemsToShow' => 3 ) );

	$page_id = child_layout_fixture_upsert_post(
		'layout-page',
		'Layout-Testseite des Child-Themes',
		$content,
		'page',
		array( 'Child Theme Layout Fixture' )
	);
	if ( $page_id ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $page_id );
	}

	update_option( 'child_layout_fixture_version', CHILD_LAYOUT_FIXTURE_VERSION, false );
}
add_action( 'init', 'child_layout_fixture_seed', 100 );
