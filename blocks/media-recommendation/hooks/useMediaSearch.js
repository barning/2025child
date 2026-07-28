import { __ } from '@wordpress/i18n';
import { useSearchState } from '../../shared/media/useSearchState';
import { mapTmdbResults } from '../utils';

export const useMediaSearch = () => {
	const search = useSearchState();

	const searchMedia = async () => {
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
				window.childMediaSearch?.ajaxUrl || '/wp-admin/admin-ajax.php';
			const nonce = window.childMediaSearch?.nonce || '';
			const response = await fetch(
				`${ ajaxUrl }?action=child_tmdb_search&query=${ encodeURIComponent(
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

			search.completeSearch(
				mapTmdbResults( data.data ),
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

	const selectMedia = ( media ) => search.selectResult( media );

	return {
		...search,
		selectedMediaId: search.selectedId,
		searchMedia,
		selectMedia,
	};
};
