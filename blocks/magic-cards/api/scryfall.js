import { __ } from '@wordpress/i18n';

const SCRYFALL_API = 'https://api.scryfall.com';

const normalizeCard = ( card ) => ( {
	id: card.id,
	name: card.name,
	set: card.set,
	set_name: card.set_name,
	image:
		card.image_uris?.normal ||
		card.image_uris?.large ||
		card.image_uris?.small ||
		'',
	released_at: card.released_at,
} );

export const searchScryfallCards = async ( searchTerm, signal ) => {
	const response = await fetch(
		`${ SCRYFALL_API }/cards/search?q=${ encodeURIComponent(
			searchTerm
		) }&unique=cards`,
		{ signal }
	);

	if ( ! response.ok ) {
		if ( response.status === 404 ) {
			throw new Error(
				__( 'Keine passenden Karten gefunden.', 'child' )
			);
		}

		if ( response.status === 429 ) {
			throw new Error(
				__(
					'Zu viele Anfragen. Warte einen Moment und versuche es erneut.',
					'child'
				)
			);
		}

		throw new Error(
			__(
				'Die Suche ist fehlgeschlagen. Prüfe deine Verbindung und versuche es erneut.',
				'child'
			)
		);
	}

	const data = await response.json();
	return ( data.data || [] ).slice( 0, 10 ).map( normalizeCard );
};

export const loadScryfallPrints = async ( cardName, signal ) => {
	const response = await fetch(
		`${ SCRYFALL_API }/cards/search?q=!"${ encodeURIComponent(
			cardName
		) }"&unique=prints`,
		{ signal }
	);

	if ( ! response.ok ) {
		return null;
	}

	const data = await response.json();
	return ( data.data || [] ).map( normalizeCard );
};
