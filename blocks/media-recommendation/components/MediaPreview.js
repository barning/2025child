import { __ } from '@wordpress/i18n';

export const MediaPreview = ( {
	mediaTitle,
	mediaType,
	posterUrl,
	releaseYear,
	serviceUrl,
} ) => {
	if ( ! mediaTitle?.trim() ) {
		return (
			<div className="media-preview--empty">
				{ __(
					'Bitte wähle einen Film oder eine Serie aus der Suche aus.',
					'child'
				) }
			</div>
		);
	}

	const posterLink = serviceUrl?.trim();
	const posterImage = posterUrl ? (
		<img
			className="child-media-card__poster"
			src={ posterUrl }
			alt={ mediaTitle }
			loading="lazy"
		/>
	) : null;
	const poster = posterLink ? (
		<a
			className="child-media-card__poster-link"
			href={ posterLink }
			target="_blank"
			rel="noopener noreferrer"
		>
			{ posterImage }
		</a>
	) : (
		posterImage
	);

	return (
		<div
			className="child-media-card"
			role="group"
			aria-label={
				mediaType === 'movie'
					? __( 'Film', 'child' )
					: __( 'Serie', 'child' )
			}
		>
			<div className="child-media-card__media">
				{ posterUrl ? (
					poster
				) : (
					<div
						className="child-media-card__placeholder"
						aria-hidden="true"
					/>
				) }
			</div>
			<div className="child-media-card__meta">
				<h3 className="child-media-card__title">{ mediaTitle }</h3>
				{ releaseYear?.trim() ? (
					<p className="child-media-card__year">{ releaseYear }</p>
				) : null }
			</div>
		</div>
	);
};
