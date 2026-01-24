import { __, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button, Spinner, Notice } from '@wordpress/components';
import { useState, useEffect, useRef } from '@wordpress/element';
import metadata from './block.json';
import './editor.css';
import './style.css';

const normalizeImageUrl = (url) => {
    if (!url) {
        return '';
    }
    if (url.startsWith('http')) {
        return url;
    }
    return url;
};

// Extract dominant colors from an image for ambilight effect
const extractDominantColor = (imageElement, callback) => {
    try {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        // Use small canvas for performance
        canvas.width = 50;
        canvas.height = 50;
        
        // Draw scaled down image
        ctx.drawImage(imageElement, 0, 0, 50, 50);
        
        // Get image data
        const imageData = ctx.getImageData(0, 0, 50, 50);
        const data = imageData.data;
        
        // Calculate average color from edges (for ambilight effect)
        let r = 0, g = 0, b = 0;
        let count = 0;
        
        // Sample from edges only
        for (let i = 0; i < data.length; i += 4) {
            const pixelIndex = i / 4;
            const x = pixelIndex % 50;
            const y = Math.floor(pixelIndex / 50);
            
            // Check if on edge
            if (x < 5 || x > 45 || y < 5 || y > 45) {
                r += data[i];
                g += data[i + 1];
                b += data[i + 2];
                count++;
            }
        }
        
        r = Math.round(r / count);
        g = Math.round(g / count);
        b = Math.round(b / count);
        
        callback(`rgb(${r}, ${g}, ${b})`);
    } catch (error) {
        // If there's a CORS error or any other issue, fallback to no ambilight
        console.warn('Could not extract colors for ambilight effect:', error);
        callback(null);
    }
};

const GamePreview = ({ gameTitle, coverUrl, releaseYear }) => {
    const imageRef = useRef(null);
    const containerRef = useRef(null);

    useEffect(() => {
        if (!coverUrl || !imageRef.current || !containerRef.current) {
            return;
        }

        const img = imageRef.current;
        
        // Wait for image to load
        const handleImageLoad = () => {
            extractDominantColor(img, (color) => {
                if (color && containerRef.current) {
                    containerRef.current.style.setProperty('--ambilight-color', color);
                }
            });
        };

        if (img.complete) {
            handleImageLoad();
        } else {
            img.addEventListener('load', handleImageLoad);
            return () => img.removeEventListener('load', handleImageLoad);
        }
    }, [coverUrl]);

    if (!gameTitle?.trim()) {
        return (
            <div className="game-preview--empty">
                {__('Bitte wähle ein Videospiel aus der Suche aus.', 'child')}
            </div>
        );
    }

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
                        crossOrigin="anonymous"
                    />
                ) : (
                    <div className="child-game-card__placeholder" aria-hidden="true" />
                )}
            </div>
            <div className="child-game-card__meta">
                <h3 className="child-game-card__title">{gameTitle}</h3>
                {releaseYear?.trim() ? (
                    <p className="child-game-card__year">
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
        <div className="game-search-results">
            {results.map((game) => (
                <Button
                    key={game.id}
                    variant={game.id === selectedId ? 'primary' : 'secondary'}
                    onClick={() => onSelect(game)}
                    className={`game-search-result${game.id === selectedId ? ' is-active' : ''}`}
                >
                    {game.cover ? (
                        <span className="game-search-result__thumb">
                            <img src={game.cover} alt={game.title || ''} loading="lazy" />
                        </span>
                    ) : (
                        <span
                            className="game-search-result__thumb game-search-result__thumb--placeholder"
                            aria-hidden="true"
                        >
                            🎮
                        </span>
                    )}
                    <span className="game-search-result__details">
                        <span className="game-search-result__title">{game.title}</span>
                        {game.year ? (
                            <span className="game-search-result__year">{game.year}</span>
                        ) : null}
                    </span>
                </Button>
            ))}
        </div>
    );
};

function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    const { gameTitle, coverUrl, releaseYear, rawgId } = attributes;
    const [searchTerm, setSearchTerm] = useState('');
    const [isSearching, setIsSearching] = useState(false);
    const [searchResults, setSearchResults] = useState([]);
    const [selectedGameId, setSelectedGameId] = useState(null);
    const [searchError, setSearchError] = useState('');
    const [hasSearched, setHasSearched] = useState(false);

    useEffect(() => {
        if (!gameTitle) {
            return;
        }

        setSearchTerm((currentValue) => (currentValue ? currentValue : gameTitle));
    }, [gameTitle]);

    const searchGames = async () => {
        const trimmedTerm = searchTerm.trim();
        if (!trimmedTerm) {
            setSearchError(__('Bitte gib einen Suchbegriff ein.', 'child'));
            setHasSearched(false);
            return;
        }

        setIsSearching(true);
        setSearchError('');
        setSearchResults([]);
        setSelectedGameId(null);
        
        try {
            // Use WordPress AJAX endpoint for server-side API calls
            const ajaxUrl = window.childGameSearch?.ajaxUrl || '/wp-admin/admin-ajax.php';
            const nonce = window.childGameSearch?.nonce || '';
            
            const response = await fetch(
                `${ajaxUrl}?action=child_rawg_search&query=${encodeURIComponent(trimmedTerm)}&nonce=${nonce}`
            );

            if (!response.ok) {
                throw new Error('Request failed');
            }

            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.data || 'Request failed');
            }

            const { games = [] } = data.data;

            const gameResults = (games || []).slice(0, 6).map((item) => ({
                id: `game-${item.id}`,
                rawgId: item.id,
                title: item.name || '',
                year: item.released ? new Date(item.released).getFullYear().toString() : '',
                cover: normalizeImageUrl(item.background_image)
            }));

            setSearchResults(gameResults);
            setHasSearched(true);
            
            if (!gameResults.length) {
                setSearchError(__('Keine Ergebnisse gefunden.', 'child'));
            }
        } catch (error) {
            console.error('Fehler beim Suchen:', error);
            setSearchError(error.message || __('Beim Suchen ist ein Fehler aufgetreten. Bitte versuche es erneut.', 'child'));
            setHasSearched(false);
        }
        setIsSearching(false);
    };

    const handleGameSelection = (game) => {
        setSelectedGameId(game.id);
        setSearchTerm(game.title);
        setAttributes({
            gameTitle: game.title || gameTitle,
            coverUrl: game.cover || coverUrl || '',
            releaseYear: game.year || releaseYear || '',
            rawgId: game.rawgId || 0
        });
    };

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Videospiel suchen', 'child')} initialOpen={true}>
                    <TextControl
                        label={__('Suche nach Titel', 'child')}
                        value={searchTerm}
                        onChange={setSearchTerm}
                        placeholder={__('Titel eingeben...', 'child')}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                searchGames();
                            }
                        }}
                    />
                    <Button
                        variant="primary"
                        onClick={searchGames}
                        disabled={isSearching}
                        className="game-search-button"
                    >
                        {isSearching ? __('Suche...', 'child') : __('Suchen', 'child')}
                    </Button>
                    {isSearching && (
                        <div className="game-search-loading">
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
                            selectedId={selectedGameId}
                            onSelect={handleGameSelection}
                        />
                    )}
                </PanelBody>

                <PanelBody title={__('Details', 'child')} initialOpen={true}>
                    <TextControl
                        label={__('Titel', 'child')}
                        value={gameTitle}
                        onChange={(value) => setAttributes({ gameTitle: value })}
                    />
                    <TextControl
                        label={__('Jahr', 'child')}
                        value={releaseYear}
                        onChange={(value) => setAttributes({ releaseYear: value })}
                    />
                    <TextControl
                        label={__('Cover-URL', 'child')}
                        value={coverUrl}
                        onChange={(value) => setAttributes({ coverUrl: value })}
                        help={__('Optional: Eigenes Cover einfügen', 'child')}
                    />
                </PanelBody>
            </InspectorControls>

            <GamePreview {...attributes} />
        </div>
    );
}

registerBlockType(metadata.name, {
    edit: Edit,
    save: () => null
});
