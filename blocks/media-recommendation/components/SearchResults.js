import { __ } from '@wordpress/i18n';
import { SearchResultsList } from '../../shared/media/SearchResultsList';

export const SearchResults = ( { results, selectedId, onSelect } ) => (
	<SearchResultsList
		results={ results }
		selectedId={ selectedId }
		onSelect={ onSelect }
		className="media-search-results"
		getId={ ( media ) => media.id }
		getClassName={ ( media, isSelected ) => `media-search-result${ isSelected ? ' is-active' : '' }` }
	>
		{ ( media ) => (
			<>
				{ media.poster ? (
					<span className="media-search-result__thumb">
						<img src={ media.poster } alt={ media.title || '' } loading="lazy" />
					</span>
				) : (
					<span
						className="media-search-result__thumb media-search-result__thumb--placeholder"
						aria-hidden="true"
					>
						🎬
					</span>
				) }
				<span className="media-search-result__details">
					<span className="media-search-result__title">{ media.title }</span>
					{ media.year ? (
						<span className="media-search-result__year">{ media.year }</span>
					) : null }
					<span className="media-search-result__type">
						{ media.mediaType === 'movie' ? __( 'Film', 'child' ) : __( 'Serie', 'child' ) }
					</span>
				</span>
			</>
		) }
	</SearchResultsList>
);
