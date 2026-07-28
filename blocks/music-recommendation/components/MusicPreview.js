import { __ } from '@wordpress/i18n';

export const MusicPreview = ( { attributes } ) => {
	const { musicType, title, artist, albumTitle, releaseYear, coverUrl } =
		attributes;

	if ( ! title?.trim() ) {
		return (
			<div className="music-preview--empty">
				{ __( 'Suche einen Song oder ein Album aus.', 'child' ) }
			</div>
		);
	}

	const typeLabel =
		musicType === 'album' ? __( 'Album', 'child' ) : __( 'Song', 'child' );

	return (
		<div className="child-music-card" role="group" aria-label={ typeLabel }>
			<div className="child-music-card__media">
				{ coverUrl ? (
					<img
						className="child-music-card__cover"
						src={ coverUrl }
						alt={ title }
						loading="lazy"
						width="600"
						height="600"
					/>
				) : (
					<div
						className="child-music-card__placeholder"
						aria-hidden="true"
					>
						♪
					</div>
				) }
			</div>
			<div className="child-music-card__meta">
				<span className="child-music-card__type">{ typeLabel }</span>
				<p className="child-music-card__title">{ title }</p>
				{ artist ? (
					<p className="child-music-card__artist">{ artist }</p>
				) : null }
				{ musicType === 'song' && albumTitle && albumTitle !== title ? (
					<p className="child-music-card__album">{ albumTitle }</p>
				) : null }
				{ releaseYear ? (
					<p className="child-music-card__year">{ releaseYear }</p>
				) : null }
			</div>
		</div>
	);
};
