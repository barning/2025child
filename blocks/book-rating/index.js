import { __, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button, Spinner, Notice, RangeControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import metadata from './block.json';
import './editor.css';
import './style.css';

const GOOGLE_BOOKS_API = 'https://www.googleapis.com/books/v1/volumes';

const normalizeCoverUrl = (url) => {
    if (!url) {
        return '';
    }

    let normalizedUrl = url.replace(/^http:\/\//i, 'https://');

    if (normalizedUrl.includes('zoom=')) {
        normalizedUrl = normalizedUrl.replace(/zoom=\d/g, 'zoom=2');
    }

    return normalizedUrl;
};

const STARS = [1, 2, 3, 4, 5];

const BookPreview = ({ bookTitle, author, coverUrl, rating }) => {
    if (!bookTitle?.trim()) {
        return (
            <div className="book-preview--empty">
                {__('Bitte wähle ein Buch aus der Suche aus oder gib die Details manuell ein.', 'child')}
            </div>
        );
    }

    const normalizedRating = Number.isFinite(rating) ? Math.max(0, Math.min(5, rating)) : 0;

    return (
        <div className="child-book-card" aria-label={__('Buchbewertung', 'child')}>
            <div className="child-book-card__media">
                {coverUrl ? (
                    <img
                        className="child-book-card__cover"
                        src={coverUrl}
                        alt={bookTitle}
                        loading="lazy"
                    />
                ) : (
                    <div className="child-book-card__placeholder" aria-hidden="true" />
                )}
            </div>
            <div className="child-book-card__stars" aria-label={__('Bewertung', 'child')}>
                {STARS.map((star) => (
                    <span
                        key={star}
                        className={`child-book-card__star${star <= normalizedRating ? ' is-active' : ''}`}
                        aria-hidden="true"
                    >
                        ★
                    </span>
                ))}
                <span className="screen-reader-text">
                    {sprintf(
                        /* translators: %d: selected star */
                        __('Bewertet mit %d von 5 Sternen', 'child'),
                        normalizedRating
                    )}
                </span>
            </div>
            <div className="child-book-card__meta">
                <h3 className="child-book-card__title">{bookTitle}</h3>
                {author?.trim() ? (
                    <p className="child-book-card__author">
                        {sprintf(
                            /* translators: %s: author name */
                            __('Von %s', 'child'),
                            author
                        )}
                    </p>
                ) : null}
            </div>
        </div>
    );
};

const RatingControl = ({ value = 0, onChange }) => {
    const normalizedRating = Number.isFinite(value) ? Math.max(0, Math.min(5, value)) : 0;

    const handleSelect = (newValue) => {
        if (typeof onChange === 'function') {
            onChange(newValue);
        }
    };

    return (
        <div className="book-rating-control">
            <div className="book-rating-control__label">
                <span>{__('Bewertung', 'child')}</span>
                <span className="book-rating-control__value">{normalizedRating}/5</span>
            </div>
            <RangeControl
                value={normalizedRating}
                min={0}
                max={5}
                step={1}
                onChange={(nextValue) => handleSelect(Number(nextValue) || 0)}
                __nextHasNoMarginBottom
            />
        </div>
    );
};

const SearchResults = ({ results, selectedId, onSelect }) => {
    if (!results.length) {
        return null;
    }

    return (
        <div className="book-search-results">
            {results.map((book) => (
                <Button
                    key={book.id}
                    variant={book.id === selectedId ? 'primary' : 'secondary'}
                    onClick={() => onSelect(book)}
                    className={`book-search-result${book.id === selectedId ? ' is-active' : ''}`}
                >
                    {book.cover ? (
                        <span className="book-search-result__thumb">
                            <img src={book.cover} alt={book.title || ''} loading="lazy" />
                        </span>
                    ) : (
                        <span
                            className="book-search-result__thumb book-search-result__thumb--placeholder"
                            aria-hidden="true"
                        >
                            📘
                        </span>
                    )}
                    <span className="book-search-result__details">
                        <span className="book-search-result__title">{book.title}</span>
                        {book.subtitle ? (
                            <span className="book-search-result__subtitle">{book.subtitle}</span>
                        ) : null}
                        {book.authors.length ? (
                            <span className="book-search-result__author">
                                {book.authors.join(', ')}
                            </span>
                        ) : null}
                    </span>
                </Button>
            ))}
        </div>
    );
};

function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    const { bookTitle, author, rating, coverUrl } = attributes;
    const [searchTerm, setSearchTerm] = useState('');
    const [isSearching, setIsSearching] = useState(false);
    const [searchResults, setSearchResults] = useState([]);
    const [selectedBookId, setSelectedBookId] = useState(null);
    const [searchError, setSearchError] = useState('');
    const [hasSearched, setHasSearched] = useState(false);

    useEffect(() => {
        if (!bookTitle) {
            return;
        }

        setSearchTerm((currentValue) => (currentValue ? currentValue : bookTitle));
    }, [bookTitle]);

    const searchBooks = async () => {
        const trimmedTerm = searchTerm.trim();
        if (!trimmedTerm) {
            setSearchError(__('Bitte gib einen Suchbegriff ein.', 'child'));
            setHasSearched(false);
            return;
        }

        setIsSearching(true);
        setSearchError('');
        setSearchResults([]);
        setSelectedBookId(null);
        try {
            const response = await fetch(
                `${GOOGLE_BOOKS_API}?q=${encodeURIComponent(trimmedTerm)}&maxResults=5`
            );
            if (!response.ok) {
                throw new Error('Request failed');
            }
            const data = await response.json();

            const results = (data.items || []).map((item) => {
                const info = item.volumeInfo || {};
                const coverImage =
                    info?.imageLinks?.thumbnail ||
                    info?.imageLinks?.smallThumbnail ||
                    '';

                return {
                    id: item.id,
                    title: info?.title || '',
                    subtitle: info?.subtitle || '',
                    authors: info?.authors || [],
                    cover: normalizeCoverUrl(coverImage)
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

    const handleBookSelection = (book) => {
        const resolvedAuthor = book.authors.length ? book.authors.join(', ') : author;

        setSelectedBookId(book.id);
        setSearchTerm(book.title);
        setAttributes({
            bookTitle: book.title || bookTitle,
            author: resolvedAuthor,
            coverUrl: book.cover || coverUrl || ''
        });
    };

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title={__('Buch finden', 'child')} initialOpen={true}>
                    <TextControl
                        label={__('Suche nach Titel oder Autor', 'child')}
                        value={searchTerm}
                        onChange={setSearchTerm}
                        placeholder={__('Buchtitel eingeben...', 'child')}
                        onKeyDown={(event) => {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                searchBooks();
                            }
                        }}
                    />
                    <Button
                        variant="primary"
                        onClick={searchBooks}
                        disabled={isSearching}
                        className="book-search-button"
                    >
                        {isSearching ? __('Suche...', 'child') : __('Suchen', 'child')}
                    </Button>
                    {isSearching && (
                        <div className="book-search-loading">
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
                            selectedId={selectedBookId}
                            onSelect={handleBookSelection}
                        />
                    )}
                </PanelBody>

                <PanelBody title={__('Buchdetails', 'child')} initialOpen={true}>
                    <TextControl
                        label={__('Titel', 'child')}
                        value={bookTitle}
                        onChange={(value) => setAttributes({ bookTitle: value })}
                    />
                    <TextControl
                        label={__('Autor', 'child')}
                        value={author}
                        onChange={(value) => setAttributes({ author: value })}
                    />
                    <TextControl
                        label={__('Cover-URL', 'child')}
                        value={coverUrl}
                        onChange={(value) => setAttributes({ coverUrl: value })}
                        help={__('Optional: Eigene Cover-Grafik einfügen', 'child')}
                    />
                    <RatingControl
                        value={rating}
                        onChange={(nextValue) => setAttributes({ rating: nextValue })}
                    />
                </PanelBody>
            </InspectorControls>

            <BookPreview {...attributes} />
        </div>
    );
}

registerBlockType(metadata.name, {
    edit: Edit,
    save: () => null
});
