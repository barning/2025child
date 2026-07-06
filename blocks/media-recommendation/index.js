import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button, SelectControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import metadata from './block.json';
import { MediaPreview } from './components/MediaPreview';
import { SearchResults } from './components/SearchResults';
import { useMediaSearch } from './hooks/useMediaSearch';
import { SearchFeedback } from '../shared/media/SearchFeedback';
import './editor.css';
import './style.css';

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { mediaTitle, mediaType, posterUrl, releaseYear, serviceUrl } = attributes;
	const {
		searchTerm,
		setSearchTerm,
		isSearching,
		searchResults,
		selectedMediaId,
		searchError,
		hasSearched,
		searchMedia,
		selectMedia,
	} = useMediaSearch();

	useEffect( () => {
		if ( ! mediaTitle ) {
			return;
		}

		setSearchTerm( ( currentValue ) => ( currentValue ? currentValue : mediaTitle ) );
	}, [ mediaTitle, setSearchTerm ] );

	const handleMediaSelection = ( media ) => {
		selectMedia( media );
		setAttributes( {
			mediaTitle: media.title || mediaTitle,
			mediaType: media.mediaType,
			posterUrl: media.poster || posterUrl || '',
			releaseYear: media.year || releaseYear || '',
			tmdbId: media.tmdbId || 0,
			serviceUrl: media.serviceUrl || serviceUrl || '',
		} );
	};

	const handleSearchKeyDown = ( event ) => {
		if ( event.key === 'Enter' ) {
			event.preventDefault();
			searchMedia();
		}
	};

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Film/Serie suchen', 'child' ) } initialOpen={ true }>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Suche nach Titel', 'child' ) }
						value={ searchTerm }
						onChange={ setSearchTerm }
						placeholder={ __( 'Titel eingeben...', 'child' ) }
						onKeyDown={ handleSearchKeyDown }
					/>
					<Button
						variant="primary"
						onClick={ searchMedia }
						disabled={ isSearching }
						className="media-search-button"
					>
						{ isSearching ? __( 'Suche...', 'child' ) : __( 'Suchen', 'child' ) }
					</Button>
					<SearchFeedback
						isSearching={ isSearching }
						error={ searchError }
						loadingClassName="media-search-loading"
					/>
					{ ! isSearching && hasSearched && (
						<SearchResults
							results={ searchResults }
							selectedId={ selectedMediaId }
							onSelect={ handleMediaSelection }
						/>
					) }
				</PanelBody>

				<PanelBody title={ __( 'Details', 'child' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Typ', 'child' ) }
						value={ mediaType }
						options={ [
							{ label: __( 'Film', 'child' ), value: 'movie' },
							{ label: __( 'Serie', 'child' ), value: 'tv' },
						] }
						onChange={ ( value ) => setAttributes( { mediaType: value } ) }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Titel', 'child' ) }
						value={ mediaTitle }
						onChange={ ( value ) => setAttributes( { mediaTitle: value } ) }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Jahr', 'child' ) }
						value={ releaseYear }
						onChange={ ( value ) => setAttributes( { releaseYear: value } ) }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Poster-URL', 'child' ) }
						value={ posterUrl }
						onChange={ ( value ) => setAttributes( { posterUrl: value } ) }
						help={ __( 'Optional: Eigenes Poster einfügen', 'child' ) }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Streaming-Link', 'child' ) }
						value={ serviceUrl }
						onChange={ ( value ) => setAttributes( { serviceUrl: value } ) }
						help={ __( 'Wird bei der Suche automatisch befüllt (TMDB-Link), kann aber manuell überschrieben werden.', 'child' ) }
					/>
				</PanelBody>
			</InspectorControls>

			<MediaPreview { ...attributes } />
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
