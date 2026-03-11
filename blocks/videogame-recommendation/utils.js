/**
 * Platform Configuration
 * Maps platform names to display names and brand colors
 */
export const PLATFORM_CONFIG = {
	playstation5: { match: ['playstation 5', 'ps5'], name: 'PS5', color: '#003087' },
	playstation4: { match: ['playstation 4', 'ps4'], name: 'PS4', color: '#003087' },
	playstation: { match: ['playstation'], name: 'PlayStation', color: '#003087' },
	xboxSeries: { match: ['xbox series'], name: 'Xbox Series', color: '#107C10' },
	xboxOne: { match: ['xbox one'], name: 'Xbox One', color: '#107C10' },
	xbox: { match: ['xbox'], name: 'Xbox', color: '#107C10' },
	switch: { match: ['nintendo switch', 'switch'], name: 'Switch', color: '#E60012' },
	nintendo: { match: ['nintendo'], name: 'Nintendo', color: '#E60012' },
	pc: { match: ['pc', 'windows'], name: 'PC', color: '#0078D4' },
	ios: { match: ['ios', 'iphone'], name: 'iOS', color: '#555555' },
	android: { match: ['android'], name: 'Android', color: '#3DDC84' },
	linux: { match: ['linux'], name: 'Linux', color: '#FCC624' },
	macos: { match: ['mac'], name: 'macOS', color: '#999999' },
	default: { name: null, color: '#666666' }
};

/**
 * Get platform display info from platform name
 * @param {string} platformName - Raw platform name from API
 * @returns {Object} - { name: string, color: string }
 */
export const getPlatformInfo = (platformName) => {
	const name = platformName.toLowerCase();
	
	for (const config of Object.values(PLATFORM_CONFIG)) {
		if (config.match && config.match.some(match => name.includes(match))) {
			return { name: config.name, color: config.color };
		}
	}
	
	return { name: platformName, color: PLATFORM_CONFIG.default.color };
};

/**
 * Format date for display
 * @param {string} dateString - ISO date string
 * @param {string} locale - Locale string (default: 'de-DE')
 * @returns {string} - Formatted date string
 */
export const formatReleaseDate = (dateString, locale = 'de-DE') => {
	if (!dateString) return '';
	
	try {
		return new Date(dateString).toLocaleDateString(locale, {
			year: 'numeric',
			month: 'short',
			day: 'numeric'
		});
	} catch (error) {
		console.warn('Invalid date string:', dateString);
		return '';
	}
};

/**
 * Normalize image URL
 * @param {string} url - Image URL
 * @returns {string} - Normalized URL or empty string
 */
export const normalizeImageUrl = (url) => {
	return url || '';
};

/**
 * Transform API game data to block format
 * @param {Object} game - Raw game data from API
 * @returns {Object} - Transformed game data
 */
export const transformGameData = (game) => {
	return {
		id: `game-${game.id}`,
		rawgId: game.id,
		title: game.name || '',
		year: game.released ? new Date(game.released).getFullYear().toString() : '',
		releaseDate: game.released || '',
		cover: normalizeImageUrl(game.background_image),
		shopUrl: game.website || (game.slug ? `https://rawg.io/games/${game.slug}` : ''),
		platforms: game.platforms || [],
		genres: game.genres || []
	};
};
