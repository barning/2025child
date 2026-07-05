import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl, Button, Spinner, Notice } from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import metadata from './block.json';
import './editor.css';
import './style.css';

const MusicPreview = ({ attributes }) => {
    const { musicType, title, artist, albumTitle, releaseYear, coverUrl, provider, providerUrl, previewUrl } = attributes;

    if (!title?.trim()) {
        return <div className="music-preview--empty">{__('Suche einen Song oder ein Album aus.', 'child')}</div>;
    }

    const typeLabel = musicType === 'album' ? __('Album', 'child') : __('Song', 'child');

    return (
        <div className="child-music-card" aria-label={typeLabel}>
            <div className="child-music-card__media">
                {coverUrl ? (
                    <img className="child-music-card__cover" src={coverUrl} alt={title} loading="lazy" />
                ) : (
                    <div className="child-music-card__placeholder" aria-hidden="true">♪</div>
                )}
            </div>
            <div className="child-music-card__meta">
                <span className="child-music-card__type">{typeLabel}</span>
                <h3 className="child-music-card__title">{title}</h3>
                {artist ? <p className="child-music-card__artist">{artist}</p> : null}
                {musicType === 'song' && albumTitle && albumTitle !== title ? (
                    <p className="child-music-card__album">{albumTitle}</p>
                ) : null}
                {releaseYear ? <p className="child-music-card__year">{releaseYear}</p> : null}
                {previewUrl ? (
                    <p className="child-music-card__privacy-note">
                        {__('Die Hörprobe wird erst nach einem Klick geladen. Dabei kann eine Verbindung zum Anbieter hergestellt werden.', 'child')}
                    </p>
                ) : null}
                {providerUrl ? (
                    <a className="child-music-card__provider-link" href={providerUrl} target="_blank" rel="noopener noreferrer">
                        {__('Bei Anbieter öffnen', 'child')} ({provider || 'Apple/iTunes'})
                    </a>
                ) : null}
            </div>
        </div>
    );
};

const SearchResults = ({ results, onSelect, selectedId }) => {
    if (!results.length) {
        return null;
    }

    return (
        <div className="music-search-results">
            {results.map((item) => (
                <Button
                    key={`${item.musicType}-${item.id}`}
                    variant={item.id === selectedId ? 'primary' : 'secondary'}
                    onClick={() => onSelect(item)}
                    className={`music-search-result${item.id === selectedId ? ' is-active' : ''}`}
                >
                    {item.coverUrl ? (
                        <span className="music-search-result__thumb"><img src={item.coverUrl} alt="" loading="lazy" /></span>
                    ) : (
                        <span className="music-search-result__thumb music-search-result__thumb--placeholder" aria-hidden="true">♪</span>
                    )}
                    <span className="music-search-result__details">
                        <span className="music-search-result__title">{item.title}</span>
                        {item.artist ? <span className="music-search-result__artist">{item.artist}</span> : null}
                        {item.releaseYear ? <span className="music-search-result__year">{item.releaseYear}</span> : null}
                    </span>
                </Button>
            ))}
        </div>
    );
};

registerBlockType(metadata.name, {
    ...metadata,
    edit: ({ attributes, setAttributes }) => {
        const blockProps = useBlockProps();
        const { musicType, title, artist, albumTitle, releaseYear, coverUrl, providerUrl, previewUrl } = attributes;
        const [searchTerm, setSearchTerm] = useState(title || '');
        const [results, setResults] = useState([]);
        const [isSearching, setIsSearching] = useState(false);
        const [error, setError] = useState('');
        const [selectedId, setSelectedId] = useState(attributes.providerId || '');

        useEffect(() => {
            if (!searchTerm && title) {
                setSearchTerm(title);
            }
        }, [title]);

        const searchMusic = async () => {
            if (!searchTerm.trim()) {
                setError(__('Bitte gib einen Suchbegriff ein.', 'child'));
                return;
            }

            setIsSearching(true);
            setError('');

            try {
                const response = await apiFetch({
                    path: addQueryArgs('/child/v1/music', {
                        q: searchTerm,
                        musicType,
                    }),
                });
                const found = response?.results || [];
                setResults(found);
                if (!found.length) {
                    setError(__('Keine Musik gefunden.', 'child'));
                }
            } catch (fetchError) {
                setError(fetchError?.message || __('Die Musiksuche konnte nicht geladen werden.', 'child'));
            } finally {
                setIsSearching(false);
            }
        };

        const selectMusic = (item) => {
            setSelectedId(item.id);
            setSearchTerm(item.title);
            setAttributes({
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
            });
        };

        return (
            <div {...blockProps}>
                <InspectorControls>
                    <PanelBody title={__('Musik-Einstellungen', 'child')} initialOpen>
                        <SelectControl
                            label={__('Typ', 'child')}
                            value={musicType}
                            options={[
                                { label: __('Song', 'child'), value: 'song' },
                                { label: __('Album', 'child'), value: 'album' },
                            ]}
                            onChange={(value) => {
                                setAttributes({ musicType: value, previewUrl: value === 'album' ? '' : previewUrl });
                                setResults([]);
                            }}
                        />
                        <TextControl label={__('Titel', 'child')} value={title} onChange={(value) => setAttributes({ title: value })} />
                        <TextControl label={__('Künstler:in', 'child')} value={artist} onChange={(value) => setAttributes({ artist: value })} />
                        <TextControl label={__('Album', 'child')} value={albumTitle} onChange={(value) => setAttributes({ albumTitle: value })} />
                        <TextControl label={__('Jahr', 'child')} value={releaseYear} onChange={(value) => setAttributes({ releaseYear: value })} />
                        <TextControl label={__('Cover-URL', 'child')} value={coverUrl} onChange={(value) => setAttributes({ coverUrl: value })} />
                        <TextControl label={__('Anbieter-Link', 'child')} value={providerUrl} onChange={(value) => setAttributes({ providerUrl: value })} />
                        <TextControl label={__('Hörprobe-URL', 'child')} value={previewUrl} onChange={(value) => setAttributes({ previewUrl: value })} help={__('Nur rechtmäßig bereitgestellte Preview-URLs verwenden; keine vollständigen Songs ohne Lizenz einbinden.', 'child')} />
                    </PanelBody>
                    <PanelBody title={__('Recht & Datenschutz', 'child')} initialOpen={false}>
                        <Notice status="info" isDismissible={false}>
                            {__('Der Block nutzt Anbieter-Metadaten als Empfehlung/Promotion. Hörproben werden im Frontend erst nach Klick geladen, damit keine externe Audio-Verbindung ohne Aktion der Leser:innen aufgebaut wird.', 'child')}
                        </Notice>
                    </PanelBody>
                </InspectorControls>

                <div className="music-search-panel">
                    <SelectControl
                        label={__('Was möchtest du empfehlen?', 'child')}
                        value={musicType}
                        options={[
                            { label: __('Song', 'child'), value: 'song' },
                            { label: __('Album', 'child'), value: 'album' },
                        ]}
                        onChange={(value) => {
                            setAttributes({ musicType: value, previewUrl: value === 'album' ? '' : previewUrl });
                            setResults([]);
                        }}
                    />
                    <TextControl
                        label={__('Musik suchen', 'child')}
                        value={searchTerm}
                        onChange={setSearchTerm}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                searchMusic();
                            }
                        }}
                    />
                    <Button variant="primary" onClick={searchMusic} disabled={isSearching} className="music-search-button">
                        {isSearching ? __('Suche…', 'child') : __('Suchen', 'child')}
                    </Button>
                    {isSearching ? <Spinner /> : null}
                    {error ? <Notice status="warning" isDismissible={false}>{error}</Notice> : null}
                    <SearchResults results={results} onSelect={selectMusic} selectedId={selectedId} />
                </div>

                <MusicPreview attributes={attributes} />
            </div>
        );
    },
});
