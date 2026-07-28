import { __ } from '@wordpress/i18n';
import { useSearchState } from '../../shared/media/useSearchState';
import { loadScryfallPrints, searchScryfallCards } from '../api/scryfall';

export const useScryfallSearch = ( {
	cardName,
	scryfallId,
	setAttributes,
} ) => {
	const cardSearch = useSearchState( {
		initialTerm: cardName || '',
		initialSelectedId: scryfallId || null,
	} );
	const printsSearch = useSearchState();

	const searchCards = async () => {
		const trimmedTerm = cardSearch.searchTerm.trim();
		if ( ! trimmedTerm ) {
			cardSearch.failSearch(
				__( 'Gib einen Kartennamen für die Suche ein.', 'child' )
			);
			return;
		}

		const { requestId, signal } = cardSearch.beginSearch();
		printsSearch.resetResults();

		try {
			const results = await searchScryfallCards( trimmedTerm, signal );
			cardSearch.completeSearch(
				results,
				__( 'Keine passenden Karten gefunden.', 'child' ),
				requestId
			);
		} catch ( error ) {
			if ( error.name !== 'AbortError' ) {
				cardSearch.failSearch(
					error.message ||
						__(
							'Ein unerwarteter Fehler ist aufgetreten. Versuche es erneut.',
							'child'
						),
					requestId
				);
			}
		} finally {
			cardSearch.finishSearch( requestId );
		}
	};

	const loadPrints = async ( selectedCardName ) => {
		const { requestId, signal } = printsSearch.beginSearch();
		try {
			const prints = await loadScryfallPrints( selectedCardName, signal );
			if ( prints !== null ) {
				printsSearch.completeSearch( prints, '', requestId );
			}
		} catch ( error ) {
			if ( error.name !== 'AbortError' ) {
				printsSearch.failSearch( '', requestId );
			}
		} finally {
			printsSearch.finishSearch( requestId );
		}
	};

	const handleCardSelection = ( card ) => {
		setAttributes( {
			cardName: card.name,
			cardImageUrl: card.image,
			scryfallId: card.id,
			selectedPrint: card,
		} );
		cardSearch.selectResult( card, { getTitle: ( item ) => item.name } );
		cardSearch.resetResults();
		loadPrints( card.name );
	};

	const handlePrintSelection = ( print ) => {
		setAttributes( {
			cardImageUrl: print.image,
			scryfallId: print.id,
			selectedPrint: print,
		} );
	};

	return {
		cardSearch,
		printsSearch,
		searchCards,
		handleCardSelection,
		handlePrintSelection,
	};
};
