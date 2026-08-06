import { __ } from '@wordpress/i18n';
import { useSearchState } from '../../shared/media/useSearchState';
import { transformGameData } from '../utils';

/**
 * Custom hook for game search functionality
 */
export const useGameSearch = () => {
	const search = useSearchState();

	const searchGames = async () => {
		const trimmedTerm = search.searchTerm.trim();

		if ( ! trimmedTerm ) {
			search.failSearch(
				__( 'Bitte gib einen Suchbegriff ein.', 'child' )
			);
			return;
		}

		const { requestId, signal } = search.beginSearch();

		try {
			const ajaxUrl =
				window.childGameSearch?.ajaxUrl || '/wp-admin/admin-ajax.php';
			const nonce = window.childGameSearch?.nonce || '';

			const response = await fetch(
				`${ ajaxUrl }?action=child_igdb_search&query=${ encodeURIComponent(
					trimmedTerm
				) }&nonce=${ encodeURIComponent( nonce ) }`,
				{ signal }
			);

			if ( ! response.ok ) {
				throw new Error( 'Die Anfrage ist fehlgeschlagen.' );
			}

			const data = await response.json();

			if ( ! data.success ) {
				throw new Error(
					data.data || 'Die Anfrage ist fehlgeschlagen.'
				);
			}

			const { games = [] } = data.data;
			const gameResults = games.slice( 0, 6 ).map( transformGameData );

			search.completeSearch(
				gameResults,
				__( 'Keine Ergebnisse gefunden.', 'child' ),
				requestId
			);
		} catch ( error ) {
			if ( error.name !== 'AbortError' ) {
				search.failSearch(
					error.message ||
						__(
							'Beim Suchen ist ein Fehler aufgetreten. Bitte versuche es erneut.',
							'child'
						),
					requestId
				);
			}
		} finally {
			search.finishSearch( requestId );
		}
	};

	const selectGame = ( game ) => {
		return search.selectResult( game );
	};

	return {
		searchTerm: search.searchTerm,
		setSearchTerm: search.setSearchTerm,
		isSearching: search.isSearching,
		searchResults: search.searchResults,
		selectedGameId: search.selectedId,
		searchError: search.searchError,
		hasSearched: search.hasSearched,
		searchGames,
		selectGame,
	};
};
