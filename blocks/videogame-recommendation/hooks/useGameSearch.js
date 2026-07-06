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
		
		if (!trimmedTerm) {
			search.failSearch(__('Bitte gib einen Suchbegriff ein.', 'child'));
			return;
		}

		search.beginSearch();
		
		try {
			const ajaxUrl = window.childGameSearch?.ajaxUrl || '/wp-admin/admin-ajax.php';
			const nonce = window.childGameSearch?.nonce || '';
			
			const response = await fetch(
				`${ajaxUrl}?action=child_rawg_search&query=${encodeURIComponent(trimmedTerm)}&nonce=${nonce}`
			);

			if (!response.ok) {
				throw new Error('Request failed');
			}

			const data = await response.json();
			
			if (!data.success) {
				throw new Error(data.data || 'Request failed');
			}

			const { games = [] } = data.data;
			const gameResults = games.slice(0, 6).map(transformGameData);

			search.completeSearch(gameResults, __('Keine Ergebnisse gefunden.', 'child'));
		} catch (error) {
			console.error('Fehler beim Suchen:', error);
			search.failSearch(
				error.message || 
				__('Beim Suchen ist ein Fehler aufgetreten. Bitte versuche es erneut.', 'child')
			);
		} finally {
			search.finishSearch();
		}
	};

	const selectGame = (game) => {
		return search.selectResult(game);
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
		selectGame
	};
};
