import { __, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button, Spinner, Notice } from '@wordpress/components';
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

const BookPreview = ({ bookTitle, author, coverUrl, rating, onRatingChange }) => {
    if (!bookTitle?.trim()) {
        return (
            <div className="book-preview--empty">
                {__('Bitte wähle ein Buch aus der Suche aus oder gib die Details manuell ein.', 'child')}
            </div>
        );
    }

    const normalizedRating = Number.isFinite(rating) ? Math.max(0, Math.min(5, rating)) : 0;

    const handleStarClick = (value) => {
        if (typeof onRatingChange === 'function') {
            onRatingChange(value);
        }
    };

    return (
        <div className="book-display" aria-label={__('Buchbewertung', 'child')}>
            <div className="book-display__card">
                {coverUrl ? (
                    <div className="book-cover-frame">
                        <img
                            className="book-cover"
                            src={coverUrl}
                            alt={bookTitle}
                            loading="lazy"
                        />
                    </div>
                ) : (
                    <div className="book-cover book-cover--placeholder" aria-hidden="true" />
                )}
                <div className="book-rating" aria-label={__('Bewertung', 'child')}>
                    {[1, 2, 3, 4, 5].map((star) => (
                        <button
                            key={star}
                            type="button"
                            className={`star-button${star <= normalizedRating ? ' active' : ''}`}
                            onClick={() => handleStarClick(star)}
                            aria-pressed={star <= normalizedRating}
                            aria-label={sprintf(
                                /* translators: %d: selected star */
                                __('Bewertung mit %d Sternen wählen', 'child'),
                                star
                            )}
                        >
                            ★
                        </button>
                    ))}
                </div>
            </div>
            <div className="book-info">
                <h3 className="book-title">{bookTitle}</h3>
                {author?.trim() ? (
                    <p className="book-author">
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
                const coverImage =
                    item.volumeInfo?.imageLinks?.thumbnail ||
                    item.volumeInfo?.imageLinks?.smallThumbnail ||
                    '';

                return {
                    id: item.id,
                    title: item.volumeInfo?.title || '',
                    authors: item.volumeInfo?.authors || [],
                    categories: item.volumeInfo?.categories || [],
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
                <PanelBody title={__('Buchdetails', 'child')}>
                    <div style={{ marginBottom: '1rem' }}>
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
                            isPrimary
                            onClick={searchBooks}
                            disabled={isSearching}
                            style={{ marginTop: '0.5rem' }}
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
                        {!isSearching && hasSearched && searchResults.length > 0 && (
                            <div className="book-search-results">
                                {searchResults.map((book) => (
                                    <Button
                                        key={book.id}
                                        variant={book.id === selectedBookId ? 'primary' : 'secondary'}
                                        onClick={() => handleBookSelection(book)}
                                        className="book-search-result"
                                    >
                                        <span className="book-search-result__title">{book.title}</span>
                                        {book.authors.length > 0 && (
                                            <span className="book-search-result__author">
                                                {book.authors.join(', ')}
                                            </span>
                                        )}
                                    </Button>
                                ))}
                            </div>
                        )}
                    </div>
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
                    <div className="book-rating-control">
                        <label>{__('Bewertung', 'child')}</label>
                        <div className="star-rating">
                            {[1, 2, 3, 4, 5].map((star) => (
                                <button
                                    key={star}
                                    type="button"
                                    className={`star-button ${star <= rating ? 'active' : ''}`}
                                    onClick={() => setAttributes({ rating: star })}
                                >
                                    ★
                                </button>
                            ))}
                        </div>
                    </div>
                </PanelBody>
            </InspectorControls>
            
            <BookPreview
                {...attributes}
                onRatingChange={(value) => setAttributes({ rating: value })}
            />
        </div>
    );
}

registerBlockType(metadata.name, {
    edit: Edit,
    save: () => null
});
