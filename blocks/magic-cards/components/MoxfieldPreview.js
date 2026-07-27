import { __ } from '@wordpress/i18n';

export const MoxfieldPreview = ( { url } ) => {
	if ( ! url?.trim() ) {
		return (
			<div className="magic-cards-preview--empty">
				{ __(
					'Enter a Moxfield deck URL to display the embed.',
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
					'Invalid Moxfield URL. Please use a valid deck URL like: https://moxfield.com/decks/…',
					'child'
				) }
			</div>
		);
	}

	return (
		<div className="child-magic-moxfield">
			<div className="child-magic-moxfield__preview">
				<p>
					<strong>{ __( 'Moxfield Deck Embed', 'child' ) }</strong>
				</p>
				<p>
					<small>
						{ __(
							'The deck will be displayed on the frontend.',
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
