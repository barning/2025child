/* eslint-disable jsdoc/require-param-type */
import { SearchResultsList } from '../../shared/media/SearchResultsList';

/**
 * SearchResults Component
 * Displays a list of game search results
 * @param root0
 * @param root0.results
 * @param root0.selectedId
 * @param root0.onSelect
 */
export const SearchResults = ( { results, selectedId, onSelect } ) => (
	<SearchResultsList
		results={ results }
		selectedId={ selectedId }
		onSelect={ onSelect }
		className="game-search-results"
		getId={ ( game ) => game.id }
		getClassName={ ( game, isSelected ) =>
			`game-search-result${ isSelected ? ' is-active' : '' }`
		}
	>
		{ ( game ) => (
			<>
				<SearchResultThumb cover={ game.cover } />
				<SearchResultDetails title={ game.title } year={ game.year } />
			</>
		) }
	</SearchResultsList>
);

/**
 * SearchResultThumb Component
 * Thumbnail for search result
 * @param root0
 * @param root0.cover
 */
const SearchResultThumb = ( { cover } ) =>
	cover ? (
		<span className="game-search-result__thumb">
			<img src={ cover } alt="" loading="lazy" />
		</span>
	) : (
		<span
			className="game-search-result__thumb game-search-result__thumb--placeholder"
			aria-hidden="true"
		>
			🎮
		</span>
	);

/**
 * SearchResultDetails Component
 * Title and year for search result
 * @param root0
 * @param root0.title
 * @param root0.year
 */
const SearchResultDetails = ( { title, year } ) => (
	<span className="game-search-result__details">
		<span className="game-search-result__title">{ title }</span>
		{ year && <span className="game-search-result__year">{ year }</span> }
	</span>
);
