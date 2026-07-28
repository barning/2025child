import { __ } from '@wordpress/i18n';

export const CardPreview = ( { cardName, cardImageUrl } ) => {
	if ( ! cardName?.trim() ) {
		return (
			<div className="magic-cards-preview--empty">
				{ __( 'Search for a card by name to display it.', 'child' ) }
			</div>
		);
	}

	return (
		<div className="child-magic-card">
			<div className="child-magic-card__media">
				{ cardImageUrl ? (
					<img
						className="child-magic-card__image"
						src={ cardImageUrl }
						alt={ cardName }
						loading="lazy"
					/>
				) : (
					<div
						className="child-magic-card__placeholder"
						aria-hidden="true"
					>
						<span>🃏</span>
					</div>
				) }
			</div>
			<div className="child-magic-card__meta">
				<h3 className="child-magic-card__name">{ cardName }</h3>
			</div>
		</div>
	);
};
