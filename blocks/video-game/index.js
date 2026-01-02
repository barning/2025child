import { __, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button, Spinner, Notice } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import metadata from './block.json';
import './editor.css';
import './style.css';

// IGDB API endpoint - Note: In production, this would need proper OAuth authentication
// For now, we'll use a proxy or configure the API endpoint via WordPress settings
const IGDB_SEARCH_ENDPOINT = '/wp-json/child/v1/igdb-search';

const GamePreview = ({ gameTitle, coverUrl }) => {
    if (!gameTitle?.trim()) {
        return (
            <div className="game-preview--empty">
                {__('Bitte wähle ein Spiel aus der Suche aus oder gib die Details manuell ein.', 'child')}
            </div>
        );
    }

    return (
        <div className="child-game-card" aria-label={__('Videospiel', 'child')}>
            <div 
                className="child-game-card__media"
                data-cover-url={coverUrl || ''}
                style={coverUrl ? { '--cover-bg': `url('${coverUrl}')` } : {}}
            >
                {coverUrl ? (
                    <img
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
                    </span>
                </Button>
            ))}
        </div>
    );
};

function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    const { gameTitle, coverUrl } = attributes;
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
            const response = await fetch(
                `${IGDB_SEARCH_ENDPOINT}?search=${encodeURIComponent(trimmedTerm)}`
            );
            if (!response.ok) {
                throw new Error('Request failed');
            }
            const data = await response.json();

            const results = (data.games || []).map((item) => {
                return {
                    id: item.id,
                    title: item.name || '',
                    cover: item.cover_url || ''
                };
            });

            setSearchResults(results);
            setHasSearched(true);
            if (!results.length) {
                setSearchError(__('Keine Ergebnisse gefunden.', 'child'));
            }
        } catch (error) {
            console.error('Fehler beim Suchen:', error);
            setSearchError(__('Beim Suchen ist ein Fehler aufgetreten. Bitte versuche es erneut.', 'child'));
            setHasSearched(false);
        }
        setIsSearching(false);
    };

    const handleGameSelection = (game) => {
        setSelectedGameId(game.id);
        setSearchTerm(game.title);
        setAttributes({
            gameTitle: game.title || gameTitle,
            coverUrl: game.cover || coverUrl || ''
        });
    };

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Spiel finden', 'child')} initialOpen={true}>
                    <TextControl
                        label={__('Suche nach Titel', 'child')}
                        value={searchTerm}
                        onChange={setSearchTerm}
                        placeholder={__('Spieltitel eingeben...', 'child')}
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

                <PanelBody title={__('Spieldetails', 'child')} initialOpen={true}>
                    <TextControl
                        label={__('Titel', 'child')}
                        value={gameTitle}
                        onChange={(value) => setAttributes({ gameTitle: value })}
                    />
                    <TextControl
                        label={__('Cover-URL', 'child')}
                        value={coverUrl}
                        onChange={(value) => setAttributes({ coverUrl: value })}
                        help={__('Optional: Eigene Cover-Grafik einfügen', 'child')}
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
