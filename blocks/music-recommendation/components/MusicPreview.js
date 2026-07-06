import { __ } from '@wordpress/i18n';

export const MusicPreview = ( { attributes } ) => {
	const { musicType, title, artist, albumTitle, releaseYear, coverUrl } = attributes;

	if ( ! title?.trim() ) {
		return <div className="music-preview--empty">{ __( 'Suche einen Song oder ein Album aus.', 'child' ) }</div>;
	}

	const typeLabel = musicType === 'album' ? __( 'Album', 'child' ) : __( 'Song', 'child' );

	return (
		<div className="child-music-card" aria-label={ typeLabel }>
			<div className="child-music-card__media">
				{ coverUrl ? (
					<img className="child-music-card__cover" src={ coverUrl } alt={ title } loading="lazy" />
				) : (
					<div className="child-music-card__placeholder" aria-hidden="true">♪</div>
				) }
			</div>
			<div className="child-music-card__meta">
				<span className="child-music-card__type">{ typeLabel }</span>
				<h3 className="child-music-card__title">{ title }</h3>
				{ artist ? <p className="child-music-card__artist">{ artist }</p> : null }
				{ musicType === 'song' && albumTitle && albumTitle !== title ? (
					<p className="child-music-card__album">{ albumTitle }</p>
				) : null }
				{ releaseYear ? <p className="child-music-card__year">{ releaseYear }</p> : null }
			</div>
		</div>
	);
};
