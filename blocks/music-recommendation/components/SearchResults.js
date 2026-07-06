import { SearchResultsList } from '../../shared/media/SearchResultsList';

export const SearchResults = ( { results, onSelect, selectedId } ) => (
	<SearchResultsList
		results={ results }
		selectedId={ selectedId }
		onSelect={ onSelect }
		className="music-search-results"
		getKey={ ( item ) => `${ item.musicType }-${ item.id }` }
		getId={ ( item ) => item.id }
		getClassName={ ( item, isSelected ) => `music-search-result${ isSelected ? ' is-active' : '' }` }
	>
		{ ( item ) => (
			<>
				{ item.coverUrl ? (
					<span className="music-search-result__thumb"><img src={ item.coverUrl } alt="" loading="lazy" /></span>
				) : (
					<span className="music-search-result__thumb music-search-result__thumb--placeholder" aria-hidden="true">♪</span>
				) }
				<span className="music-search-result__details">
					<span className="music-search-result__title">{ item.title }</span>
					{ item.artist ? <span className="music-search-result__artist">{ item.artist }</span> : null }
					{ item.releaseYear ? <span className="music-search-result__year">{ item.releaseYear }</span> : null }
				</span>
			</>
		) }
	</SearchResultsList>
);
