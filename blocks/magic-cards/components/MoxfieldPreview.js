import { __ } from '@wordpress/i18n';

export const MoxfieldPreview = ( { url } ) => {
	if ( ! url?.trim() ) {
		return (
			<div className="magic-cards-preview--empty">
				{ __(
					'Gib eine Moxfield-Deck-URL ein, um das Deck einzubetten.',
					'child'
				) }
			</div>
		);
	}

	const deckMatch = url.match(
		/moxfield\.com\/decks\/([a-zA-Z0-9_-]{1,100})/
	);
	if ( ! deckMatch ) {
		return (
			<div className="magic-cards-preview--error">
				{ __(
					'Ungültige Moxfield-URL. Verwende eine gültige Deck-URL wie https://moxfield.com/decks/…',
					'child'
				) }
			</div>
		);
	}

	return (
		<div className="child-magic-moxfield">
			<div className="child-magic-moxfield__preview">
				<p>
					<strong>
						{ __( 'Eingebettetes Moxfield-Deck', 'child' ) }
					</strong>
				</p>
				<p>
					<small>
						{ __(
							'Das Deck wird auf der Website angezeigt.',
							'child'
						) }
					</small>
				</p>
				<p>
					<code>{ url }</code>
				</p>
			</div>
		</div>
	);
};
