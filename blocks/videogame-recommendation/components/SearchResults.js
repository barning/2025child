import { Button } from '@wordpress/components';

/**
 * SearchResults Component
 * Displays a list of game search results
 */
export const SearchResults = ({ results, selectedId, onSelect }) => {
	if (!results.length) {
		return null;
	}

	return (
		<div className="game-search-results">
			{results.map((game) => (
				<SearchResultItem
					key={game.id}
					game={game}
					isSelected={game.id === selectedId}
					onSelect={onSelect}
				/>
			))}
		</div>
	);
};

/**
 * SearchResultItem Component
 * Individual search result item
 */
const SearchResultItem = ({ game, isSelected, onSelect }) => (
	<Button
		variant={isSelected ? 'primary' : 'secondary'}
		onClick={() => onSelect(game)}
		className={`game-search-result${isSelected ? ' is-active' : ''}`}
	>
		<SearchResultThumb cover={game.cover} title={game.title} />
		<SearchResultDetails title={game.title} year={game.year} />
	</Button>
);

/**
 * SearchResultThumb Component
 * Thumbnail for search result
 */
const SearchResultThumb = ({ cover, title }) => (
	cover ? (
		<span className="game-search-result__thumb">
			<img src={cover} alt={title || ''} loading="lazy" />
		</span>
	) : (
		<span
			className="game-search-result__thumb game-search-result__thumb--placeholder"
			aria-hidden="true"
		>
			🎮
		</span>
	)
);

/**
 * SearchResultDetails Component
 * Title and year for search result
 */
const SearchResultDetails = ({ title, year }) => (
	<span className="game-search-result__details">
		<span className="game-search-result__title">{title}</span>
		{year && <span className="game-search-result__year">{year}</span>}
	</span>
);
