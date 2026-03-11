import { __ } from '@wordpress/i18n';
import { useRef } from '@wordpress/element';
import { getPlatformInfo, formatReleaseDate } from '../utils';

/**
 * GamePreview Component
 * Displays a game card with cover image, platforms, title, and metadata
 */
export const GamePreview = ({ gameTitle, coverUrl, releaseDate, platforms, genres, shopUrl }) => {
	const imageRef = useRef(null);
	const containerRef = useRef(null);

	if (!gameTitle?.trim()) {
		return (
			<div className="game-preview--empty">
				{__('Bitte wähle ein Videospiel aus der Suche aus.', 'child')}
			</div>
		);
	}

	const formattedDate = formatReleaseDate(releaseDate);

	return (
		<div className="child-game-card" aria-label={__('Videospiel', 'child')}>
			<div className="child-game-card__media" ref={containerRef}>
				{coverUrl ? (
					<img
						ref={imageRef}
						className="child-game-card__cover"
						src={coverUrl}
						alt={gameTitle}
						loading="lazy"
					/>
				) : (
					<div className="child-game-card__placeholder" aria-hidden="true" />
				)}
			</div>
			
			<div className="child-game-card__meta">
				{platforms && platforms.length > 0 && (
					<PlatformChips platforms={platforms} />
				)}
				
				<h3 className="child-game-card__title">{gameTitle}</h3>
				
				{formattedDate && (
					<InfoRow 
						label={__('Release date:', 'child')} 
						value={formattedDate} 
					/>
				)}
				
				{genres && genres.length > 0 && (
					<InfoRow 
						label={__('Genres:', 'child')} 
						value={genres.slice(0, 3).join(', ')} 
					/>
				)}

				{shopUrl?.trim() && (
					<p className="child-game-card__link-row">
						<a
							className="child-game-card__link"
							href={shopUrl}
							target="_blank"
							rel="noopener noreferrer"
						>
							{__('Zum Shop', 'child')}
						</a>
					</p>
				)}
			</div>
		</div>
	);
};

/**
 * PlatformChips Component
 * Displays platform badges with brand colors
 */
const PlatformChips = ({ platforms }) => (
	<div className="child-game-card__platforms" aria-label={__('Plattformen', 'child')}>
		{platforms.slice(0, 5).map((platform, index) => {
			const platformInfo = getPlatformInfo(platform);
			return (
				<span 
					key={index} 
					className="child-game-card__platform-chip"
					style={{ backgroundColor: platformInfo.color }}
					title={platform}
				>
					{platformInfo.name}
				</span>
			);
		})}
	</div>
);

/**
 * InfoRow Component
 * Displays a metadata row with label and value
 */
const InfoRow = ({ label, value }) => (
	<div className="child-game-card__info-row">
		<span className="child-game-card__label">{label}</span>
		<span className="child-game-card__value">{value}</span>
	</div>
);
