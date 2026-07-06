import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, Button, Notice } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import metadata from './block.json';
import { MusicPreview } from './components/MusicPreview';
import { SearchResults } from './components/SearchResults';
import { useMusicSearch } from './hooks/useMusicSearch';
import { SearchFeedback } from '../shared/media/SearchFeedback';
import './editor.css';
import './style.css';

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { musicType, title, artist, albumTitle, releaseYear, coverUrl, providerUrl, previewUrl } = attributes;
	const {
		searchTerm,
		setSearchTerm,
		results,
		isSearching,
		error,
		selectedId,
		searchMusic,
		selectMusic,
		resetResults,
	} = useMusicSearch( {
		initialTerm: title || '',
		initialSelectedId: attributes.providerId || '',
	} );

	useEffect( () => {
		if ( ! searchTerm && title ) {
			setSearchTerm( title );
		}
	}, [ title, searchTerm, setSearchTerm ] );

	const updateMusicType = ( value ) => {
		setAttributes( { musicType: value, previewUrl: value === 'album' ? '' : previewUrl } );
		resetResults();
	};

	const handleMusicSearch = () => {
		searchMusic( musicType );
	};

	const handleMusicSelection = ( item ) => {
		selectMusic( item );
		setAttributes( {
			musicType: item.musicType || musicType,
			title: item.title || title,
			artist: item.artist || artist,
			albumTitle: item.albumTitle || albumTitle,
			releaseYear: item.releaseYear || releaseYear,
			coverUrl: item.coverUrl || coverUrl,
			provider: item.provider || 'Apple/iTunes',
			providerId: item.id || '',
			providerUrl: item.providerUrl || providerUrl,
			previewUrl: item.previewUrl || '',
		} );
	};

	const handleSearchKeyDown = ( event ) => {
		if ( event.key === 'Enter' ) {
			event.preventDefault();
			handleMusicSearch();
		}
	};

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Musik-Einstellungen', 'child' ) } initialOpen>
					<SelectControl
						label={ __( 'Typ', 'child' ) }
						value={ musicType }
						options={ [
							{ label: __( 'Song', 'child' ), value: 'song' },
							{ label: __( 'Album', 'child' ), value: 'album' },
						] }
						onChange={ updateMusicType }
					/>
					<TextControl label={ __( 'Titel', 'child' ) } value={ title } onChange={ ( value ) => setAttributes( { title: value } ) } />
					<TextControl label={ __( 'Künstler:in', 'child' ) } value={ artist } onChange={ ( value ) => setAttributes( { artist: value } ) } />
					<TextControl label={ __( 'Album', 'child' ) } value={ albumTitle } onChange={ ( value ) => setAttributes( { albumTitle: value } ) } />
					<TextControl label={ __( 'Jahr', 'child' ) } value={ releaseYear } onChange={ ( value ) => setAttributes( { releaseYear: value } ) } />
					<TextControl label={ __( 'Cover-URL', 'child' ) } value={ coverUrl } onChange={ ( value ) => setAttributes( { coverUrl: value } ) } />
					<TextControl label={ __( 'Anbieter-Link', 'child' ) } value={ providerUrl } onChange={ ( value ) => setAttributes( { providerUrl: value } ) } />
					<TextControl label={ __( 'Hörprobe-URL', 'child' ) } value={ previewUrl } onChange={ ( value ) => setAttributes( { previewUrl: value } ) } help={ __( 'Nur rechtmäßig bereitgestellte Preview-URLs verwenden; keine vollständigen Songs ohne Lizenz einbinden.', 'child' ) } />
				</PanelBody>
				<PanelBody title={ __( 'Recht & Datenschutz', 'child' ) } initialOpen={ false }>
					<Notice status="info" isDismissible={ false }>
						{ __( 'Der Block nutzt Anbieter-Metadaten als Empfehlung/Promotion. Hörproben werden im Frontend erst nach Klick geladen, damit keine externe Audio-Verbindung ohne Aktion der Leser:innen aufgebaut wird.', 'child' ) }
					</Notice>
				</PanelBody>
			</InspectorControls>

			<div className="music-search-panel">
				<SelectControl
					label={ __( 'Was möchtest du empfehlen?', 'child' ) }
					value={ musicType }
					options={ [
						{ label: __( 'Song', 'child' ), value: 'song' },
						{ label: __( 'Album', 'child' ), value: 'album' },
					] }
					onChange={ updateMusicType }
				/>
				<TextControl
					label={ __( 'Musik suchen', 'child' ) }
					value={ searchTerm }
					onChange={ setSearchTerm }
					onKeyDown={ handleSearchKeyDown }
				/>
				<Button variant="primary" onClick={ handleMusicSearch } disabled={ isSearching } className="music-search-button">
					{ isSearching ? __( 'Suche…', 'child' ) : __( 'Suchen', 'child' ) }
				</Button>
				<SearchFeedback isSearching={ isSearching } error={ error } errorStatus="warning" />
				<SearchResults results={ results } onSelect={ handleMusicSelection } selectedId={ selectedId } />
			</div>

			<MusicPreview attributes={ attributes } />
		</div>
	);
}

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
} );
