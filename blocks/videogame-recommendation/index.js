/* eslint-disable jsdoc/require-param-type */
import { __, _n, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import metadata from './block.json';
import { GamePreview } from './components/GamePreview';
import { SearchResults } from './components/SearchResults';
import { useGameSearch } from './hooks/useGameSearch';
import { SearchFeedback } from '../shared/media/SearchFeedback';
import './editor.css';
import './style.css';

/**
 * Edit Component
 * Block editor component for Videogame Recommendation
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 */
function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { gameTitle } = attributes;

	const {
		searchTerm,
		setSearchTerm,
		isSearching,
		searchResults,
		selectedGameId,
		searchError,
		hasSearched,
		searchGames,
		selectGame,
	} = useGameSearch();

	// Initialize search term from game title
	useEffect( () => {
		if ( gameTitle && ! searchTerm ) {
			setSearchTerm( gameTitle );
		}
	}, [ gameTitle, searchTerm, setSearchTerm ] );

	const handleGameSelection = ( game ) => {
		const selectedGame = selectGame( game );
		setAttributes( {
			gameTitle: selectedGame.title,
			coverUrl: selectedGame.cover,
			coverFormat: selectedGame.coverFormat,
			coverVariants: selectedGame.coverVariants,
			releaseYear: selectedGame.year,
			releaseDate: selectedGame.releaseDate,
			platforms: selectedGame.platforms,
			genres: selectedGame.genres,
			rawgId: selectedGame.rawgId,
			shopUrl: selectedGame.shopUrl || attributes.shopUrl || '',
		} );
	};

	const handleKeyDown = ( event ) => {
		if ( event.key === 'Enter' && ! isSearching ) {
			event.preventDefault();
			searchGames();
		}
	};

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Videospiel suchen', 'child' ) }
					initialOpen={ true }
				>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Suche nach Titel', 'child' ) }
						value={ searchTerm }
						onChange={ setSearchTerm }
						placeholder={ __( 'Titel eingeben…', 'child' ) }
						onKeyDown={ handleKeyDown }
					/>
					<Button
						variant="primary"
						onClick={ searchGames }
						disabled={ isSearching }
						className="game-search-button"
					>
						{ isSearching
							? __( 'Suche…', 'child' )
							: __( 'Suchen', 'child' ) }
					</Button>

					<SearchFeedback
						isSearching={ isSearching }
						error={ searchError }
						loadingClassName="game-search-loading"
						statusMessage={
							hasSearched
								? sprintf(
										/* translators: %d: number of search results */
										_n(
											'%d Ergebnis gefunden.',
											'%d Ergebnisse gefunden.',
											searchResults.length,
											'child'
										),
										searchResults.length
								  )
								: ''
						}
					/>

					{ ! isSearching && hasSearched && (
						<SearchResults
							results={ searchResults }
							selectedId={ selectedGameId }
							onSelect={ handleGameSelection }
						/>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Details', 'child' ) }
					initialOpen={ false }
				>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Titel', 'child' ) }
						value={ attributes.gameTitle }
						onChange={ ( value ) =>
							setAttributes( { gameTitle: value } )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Jahr', 'child' ) }
						value={ attributes.releaseYear }
						onChange={ ( value ) =>
							setAttributes( { releaseYear: value } )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Cover-URL', 'child' ) }
						value={ attributes.coverUrl }
						onChange={ ( value ) =>
							setAttributes( {
								coverUrl: value,
								coverVariants: [],
							} )
						}
						help={ __(
							'Optional: Eigenes Cover einfügen',
							'child'
						) }
					/>
					<CoverVariantPicker
						variants={ attributes.coverVariants }
						selectedUrl={ attributes.coverUrl }
						onSelect={ ( variant ) =>
							setAttributes( {
								coverUrl: variant.url,
								coverFormat: variant.coverFormat || 'portrait',
							} )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Shop-Link', 'child' ) }
						value={ attributes.shopUrl }
						onChange={ ( value ) =>
							setAttributes( { shopUrl: value } )
						}
						help={ __(
							'Wird bei der Suche automatisch befüllt (RAWG-Link), kann aber manuell überschrieben werden.',
							'child'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<GamePreview { ...attributes } />
		</div>
	);
}

const CoverVariantPicker = ( { variants = [], selectedUrl, onSelect } ) => {
	if ( ! variants.length ) {
		return null;
	}

	return (
		<div className="game-cover-variants">
			<div className="game-cover-variants__label">
				{ __( 'SteamGridDB-Cover', 'child' ) }
			</div>
			<div className="game-cover-variants__list">
				{ variants.map( ( variant, index ) => {
					const isSelected = variant.url === selectedUrl;

					return (
						<Button
							key={ `${ variant.url }-${ index }` }
							className={ `game-cover-variants__button${
								isSelected ? ' is-active' : ''
							}` }
							onClick={ () => onSelect( variant ) }
							aria-label={ __( 'Cover auswählen', 'child' ) }
							aria-pressed={ isSelected }
						>
							<img src={ variant.url } alt="" loading="lazy" />
						</Button>
					);
				} ) }
			</div>
		</div>
	);
};

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
