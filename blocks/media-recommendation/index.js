import { __, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button, Spinner, Notice, SelectControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import metadata from './block.json';
import './editor.css';
import './style.css';

const TMDB_IMAGE_BASE = 'https://image.tmdb.org/t/p/w500';

const normalizePosterUrl = (path) => {
    if (!path) {
        return '';
    }
    if (path.startsWith('http')) {
        return path;
    }
    return `${TMDB_IMAGE_BASE}${path}`;
};

const MediaPreview = ({ mediaTitle, mediaType, posterUrl, releaseYear }) => {
    if (!mediaTitle?.trim()) {
        return (
            <div className="media-preview--empty">
                {__('Bitte wähle einen Film oder eine Serie aus der Suche aus.', 'child')}
            </div>
        );
    }

    return (
        <div className="child-media-card" aria-label={mediaType === 'movie' ? __('Film', 'child') : __('Serie', 'child')}>
            <div className="child-media-card__media">
                {posterUrl ? (
                    <img
                        className="child-media-card__poster"
                        src={posterUrl}
                        alt={mediaTitle}
                        loading="lazy"
                    />
                ) : (
                    <div className="child-media-card__placeholder" aria-hidden="true" />
                )}
            </div>
            <div className="child-media-card__meta">
                <h3 className="child-media-card__title">{mediaTitle}</h3>
                {releaseYear?.trim() ? (
                    <p className="child-media-card__year">
                        {releaseYear}
                    </p>
                ) : null}
            </div>
        </div>
    );
};

const SearchResults = ({ results, selectedId, onSelect }) => {
    if (!results.length) {
        return null;
    }

    return (
        <div className="media-search-results">
            {results.map((media) => (
                <Button
                    key={media.id}
                    variant={media.id === selectedId ? 'primary' : 'secondary'}
                    onClick={() => onSelect(media)}
                    className={`media-search-result${media.id === selectedId ? ' is-active' : ''}`}
                >
                    {media.poster ? (
                        <span className="media-search-result__thumb">
                            <img src={media.poster} alt={media.title || ''} loading="lazy" />
                        </span>
                    ) : (
                        <span
                            className="media-search-result__thumb media-search-result__thumb--placeholder"
                            aria-hidden="true"
                        >
                            🎬
                        </span>
                    )}
                    <span className="media-search-result__details">
                        <span className="media-search-result__title">{media.title}</span>
                        {media.year ? (
                            <span className="media-search-result__year">{media.year}</span>
                        ) : null}
                        <span className="media-search-result__type">
                            {media.mediaType === 'movie' ? __('Film', 'child') : __('Serie', 'child')}
                        </span>
                    </span>
                </Button>
            ))}
        </div>
    );
};

function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    const { mediaTitle, mediaType, posterUrl, releaseYear, tmdbId } = attributes;
    const [searchTerm, setSearchTerm] = useState('');
    const [isSearching, setIsSearching] = useState(false);
    const [searchResults, setSearchResults] = useState([]);
    const [selectedMediaId, setSelectedMediaId] = useState(null);
    const [searchError, setSearchError] = useState('');
    const [hasSearched, setHasSearched] = useState(false);

    useEffect(() => {
        if (!mediaTitle) {
            return;
        }

        setSearchTerm((currentValue) => (currentValue ? currentValue : mediaTitle));
    }, [mediaTitle]);

    const searchMedia = async () => {
        const trimmedTerm = searchTerm.trim();
        if (!trimmedTerm) {
            setSearchError(__('Bitte gib einen Suchbegriff ein.', 'child'));
            setHasSearched(false);
            return;
        }

        setIsSearching(true);
        setSearchError('');
        setSearchResults([]);
        setSelectedMediaId(null);
        
        try {
            // Use WordPress AJAX endpoint for server-side API calls
            const ajaxUrl = window.childMediaSearch?.ajaxUrl || '/wp-admin/admin-ajax.php';
            const nonce = window.childMediaSearch?.nonce || '';
            
            const response = await fetch(
                `${ajaxUrl}?action=child_tmdb_search&query=${encodeURIComponent(trimmedTerm)}&nonce=${nonce}`
            );

            if (!response.ok) {
                throw new Error('Request failed');
            }

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data || 'Request failed');
            }

            const { movies = [], tv = [] } = data.data;

            const movieResults = (movies || []).slice(0, 3).map((item) => ({
                id: `movie-${item.id}`,
                tmdbId: item.id,
                title: item.title || '',
                year: item.release_date ? new Date(item.release_date).getFullYear().toString() : '',
                poster: normalizePosterUrl(item.poster_path),
                mediaType: 'movie'
            }));

            const tvResults = (tv || []).slice(0, 3).map((item) => ({
                id: `tv-${item.id}`,
                tmdbId: item.id,
                title: item.name || '',
                year: item.first_air_date ? new Date(item.first_air_date).getFullYear().toString() : '',
                poster: normalizePosterUrl(item.poster_path),
                mediaType: 'tv'
            }));

            const results = [...movieResults, ...tvResults];
            setSearchResults(results);
            setHasSearched(true);
            
            if (!results.length) {
                setSearchError(__('Keine Ergebnisse gefunden.', 'child'));
            }
        } catch (error) {
            console.error('Fehler beim Suchen:', error);
            setSearchError(error.message || __('Beim Suchen ist ein Fehler aufgetreten. Bitte versuche es erneut.', 'child'));
            setHasSearched(false);
        }
        setIsSearching(false);
    };

    const handleMediaSelection = (media) => {
        setSelectedMediaId(media.id);
        setSearchTerm(media.title);
        setAttributes({
            mediaTitle: media.title || mediaTitle,
            mediaType: media.mediaType,
            posterUrl: media.poster || posterUrl || '',
            releaseYear: media.year || releaseYear || '',
            tmdbId: media.tmdbId || 0
        });
    };

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Film/Serie suchen', 'child')} initialOpen={true}>
                    <TextControl
                        label={__('Suche nach Titel', 'child')}
                        value={searchTerm}
                        onChange={setSearchTerm}
                        placeholder={__('Titel eingeben...', 'child')}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                searchMedia();
                            }
                        }}
                    />
                    <Button
                        variant="primary"
                        onClick={searchMedia}
                        disabled={isSearching}
                        className="media-search-button"
                    >
                        {isSearching ? __('Suche...', 'child') : __('Suchen', 'child')}
                    </Button>
                    {isSearching && (
                        <div className="media-search-loading">
                            <Spinner />
                        </div>
                    )}
                    {searchError && (
                        <Notice status="error" isDismissible={false}>
                            {searchError}
                        </Notice>
                    )}
                    {!isSearching && hasSearched && (
                        <SearchResults
                            results={searchResults}
                            selectedId={selectedMediaId}
                            onSelect={handleMediaSelection}
                        />
                    )}
                </PanelBody>

                <PanelBody title={__('Details', 'child')} initialOpen={true}>
                    <SelectControl
                        label={__('Typ', 'child')}
                        value={mediaType}
                        options={[
                            { label: __('Film', 'child'), value: 'movie' },
                            { label: __('Serie', 'child'), value: 'tv' }
                        ]}
                        onChange={(value) => setAttributes({ mediaType: value })}
                    />
                    <TextControl
                        label={__('Titel', 'child')}
                        value={mediaTitle}
                        onChange={(value) => setAttributes({ mediaTitle: value })}
                    />
                    <TextControl
                        label={__('Jahr', 'child')}
                        value={releaseYear}
                        onChange={(value) => setAttributes({ releaseYear: value })}
                    />
                    <TextControl
                        label={__('Poster-URL', 'child')}
                        value={posterUrl}
                        onChange={(value) => setAttributes({ posterUrl: value })}
                        help={__('Optional: Eigenes Poster einfügen', 'child')}
                    />
                </PanelBody>
            </InspectorControls>

            <MediaPreview {...attributes} />
        </div>
    );
}

registerBlockType(metadata.name, {
    edit: Edit,
    save: () => null
});
