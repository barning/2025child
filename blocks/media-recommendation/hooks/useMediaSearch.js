import { __ } from '@wordpress/i18n';
import { useSearchState } from '../../shared/media/useSearchState';
import { mapTmdbResults } from '../utils';

export const useMediaSearch = () => {
	const search = useSearchState();

	const searchMedia = async () => {
		const trimmedTerm = search.searchTerm.trim();

		if ( ! trimmedTerm ) {
			search.failSearch( __( 'Bitte gib einen Suchbegriff ein.', 'child' ) );
			return;
		}

		search.beginSearch();

		try {
			const ajaxUrl = window.childMediaSearch?.ajaxUrl || '/wp-admin/admin-ajax.php';
			const nonce = window.childMediaSearch?.nonce || '';
			const response = await fetch(
				`${ ajaxUrl }?action=child_tmdb_search&query=${ encodeURIComponent( trimmedTerm ) }&nonce=${ nonce }`
			);

			if ( ! response.ok ) {
				throw new Error( 'Request failed' );
			}

			const data = await response.json();

			if ( ! data.success ) {
				throw new Error( data.data || 'Request failed' );
			}

			search.completeSearch( mapTmdbResults( data.data ), __( 'Keine Ergebnisse gefunden.', 'child' ) );
		} catch ( error ) {
			console.error( 'Fehler beim Suchen:', error );
			search.failSearch(
				error.message || __( 'Beim Suchen ist ein Fehler aufgetreten. Bitte versuche es erneut.', 'child' )
			);
		} finally {
			search.finishSearch();
		}
	};

	const selectMedia = ( media ) => search.selectResult( media );

	return {
		...search,
		selectedMediaId: search.selectedId,
		searchMedia,
		selectMedia,
	};
};
