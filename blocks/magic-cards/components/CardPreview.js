import { __ } from '@wordpress/i18n';

export const CardPreview = ( { cardName, cardImageUrl } ) => {
	if ( ! cardName?.trim() ) {
		return (
			<div className="magic-cards-preview--empty">
				{ __(
					'Suche eine Karte nach Namen, um sie anzuzeigen.',
					'child'
				) }
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
						width="488"
						height="680"
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
				<p className="child-magic-card__name">{ cardName }</p>
			</div>
		</div>
	);
};
