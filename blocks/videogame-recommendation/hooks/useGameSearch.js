import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { transformGameData } from '../utils';

/**
 * Custom hook for game search functionality
 */
export const useGameSearch = () => {
	const [searchTerm, setSearchTerm] = useState('');
	const [isSearching, setIsSearching] = useState(false);
	const [searchResults, setSearchResults] = useState([]);
	const [selectedGameId, setSelectedGameId] = useState(null);
	const [searchError, setSearchError] = useState('');
	const [hasSearched, setHasSearched] = useState(false);

	const searchGames = async () => {
		const trimmedTerm = searchTerm.trim();
		
		if (!trimmedTerm) {
			setSearchError(__('Bitte gib einen Suchbegriff ein.', 'child'));
			setHasSearched(false);
			return;
		}

		setIsSearching(true);
		setSearchError('');
		setSearchResults([]);
		setSelectedGameId(null);
		
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

			setSearchResults(gameResults);
			setHasSearched(true);
			
			if (!gameResults.length) {
				setSearchError(__('Keine Ergebnisse gefunden.', 'child'));
			}
		} catch (error) {
			console.error('Fehler beim Suchen:', error);
			setSearchError(
				error.message || 
				__('Beim Suchen ist ein Fehler aufgetreten. Bitte versuche es erneut.', 'child')
			);
			setHasSearched(false);
		} finally {
			setIsSearching(false);
		}
	};

	const selectGame = (game) => {
		setSelectedGameId(game.id);
		setSearchTerm(game.title);
		return game;
	};

	return {
		searchTerm,
		setSearchTerm,
		isSearching,
		searchResults,
		selectedGameId,
		searchError,
		hasSearched,
		searchGames,
		selectGame
	};
};
