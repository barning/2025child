/**
 * Platform utilities for PHP render
 * Shared platform configuration between JS and PHP
 */

/**
 * Get platform display info from platform name
 * @param string $platform_name - Raw platform name from API
 * @return array - ['name' => string, 'color' => string]
 */
function child_get_platform_info( $platform_name ) {
	$name = strtolower( $platform_name );
	
	$platforms = [
		['match' => ['playstation 5', 'ps5'], 'name' => 'PS5', 'color' => '#003087'],
		['match' => ['playstation 4', 'ps4'], 'name' => 'PS4', 'color' => '#003087'],
		['match' => ['playstation'], 'name' => 'PlayStation', 'color' => '#003087'],
		['match' => ['xbox series'], 'name' => 'Xbox Series', 'color' => '#107C10'],
		['match' => ['xbox one'], 'name' => 'Xbox One', 'color' => '#107C10'],
		['match' => ['xbox'], 'name' => 'Xbox', 'color' => '#107C10'],
		['match' => ['nintendo switch', 'switch'], 'name' => 'Switch', 'color' => '#E60012'],
		['match' => ['nintendo'], 'name' => 'Nintendo', 'color' => '#E60012'],
		['match' => ['pc', 'windows'], 'name' => 'PC', 'color' => '#0078D4'],
		['match' => ['ios', 'iphone'], 'name' => 'iOS', 'color' => '#555555'],
		['match' => ['android'], 'name' => 'Android', 'color' => '#3DDC84'],
		['match' => ['linux'], 'name' => 'Linux', 'color' => '#FCC624'],
		['match' => ['mac'], 'name' => 'macOS', 'color' => '#999999']
	];
	
	foreach ( $platforms as $platform ) {
		foreach ( $platform['match'] as $match ) {
			if ( strpos( $name, $match ) !== false ) {
				return ['name' => $platform['name'], 'color' => $platform['color']];
			}
		}
	}
	
	return ['name' => $platform_name, 'color' => '#666666'];
}
