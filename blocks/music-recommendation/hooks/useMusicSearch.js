import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { useSearchState } from '../../shared/media/useSearchState';

export const useMusicSearch = ( { initialTerm = '', initialSelectedId = '' } = {} ) => {
	const search = useSearchState( { initialTerm, initialSelectedId } );

	const searchMusic = async ( musicType ) => {
		if ( ! search.searchTerm.trim() ) {
			search.failSearch( __( 'Bitte gib einen Suchbegriff ein.', 'child' ) );
			return;
		}

		search.beginSearch( { clearResults: false, clearSelected: false } );

		try {
			const response = await apiFetch( {
				path: addQueryArgs( '/child/v1/music', {
					q: search.searchTerm,
					musicType,
				} ),
			} );
			const found = response?.results || [];

			search.completeSearch( found, __( 'Keine Musik gefunden.', 'child' ) );
		} catch ( fetchError ) {
			search.failSearch( fetchError?.message || __( 'Die Musiksuche konnte nicht geladen werden.', 'child' ) );
		} finally {
			search.finishSearch();
		}
	};

	const selectMusic = ( item ) => search.selectResult( item );

	return {
		...search,
		results: search.searchResults,
		error: search.searchError,
		selectedId: search.selectedId,
		searchMusic,
		selectMusic,
	};
};
