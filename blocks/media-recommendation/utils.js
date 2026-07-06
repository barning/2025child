const TMDB_IMAGE_BASE = 'https://image.tmdb.org/t/p/w500';

export const normalizePosterUrl = ( path ) => {
	if ( ! path ) {
		return '';
	}

	if ( path.startsWith( 'http' ) ) {
		return path;
	}

	return `${ TMDB_IMAGE_BASE }${ path }`;
};

export const mapTmdbResults = ( { movies = [], tv = [] } = {} ) => {
	const movieResults = ( movies || [] ).slice( 0, 3 ).map( ( item ) => ( {
		id: `movie-${ item.id }`,
		tmdbId: item.id,
		title: item.title || '',
		year: item.release_date ? new Date( item.release_date ).getFullYear().toString() : '',
		poster: normalizePosterUrl( item.poster_path ),
		mediaType: 'movie',
		serviceUrl: item.id ? `https://www.themoviedb.org/movie/${ item.id }` : '',
	} ) );

	const tvResults = ( tv || [] ).slice( 0, 3 ).map( ( item ) => ( {
		id: `tv-${ item.id }`,
		tmdbId: item.id,
		title: item.name || '',
		year: item.first_air_date ? new Date( item.first_air_date ).getFullYear().toString() : '',
		poster: normalizePosterUrl( item.poster_path ),
		mediaType: 'tv',
		serviceUrl: item.id ? `https://www.themoviedb.org/tv/${ item.id }` : '',
	} ) );

	return [ ...movieResults, ...tvResults ];
};
