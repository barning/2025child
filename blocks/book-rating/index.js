import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl, Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './editor.css';
import './style.css';

const GOOGLE_BOOKS_API = 'https://www.googleapis.com/books/v1/volumes';

function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    const { bookTitle, author, genre, coverUrl, rating } = attributes;
    const [searchTerm, setSearchTerm] = useState('');
    const [isSearching, setIsSearching] = useState(false);

    const searchBooks = async () => {
        if (!searchTerm) return;
        
        setIsSearching(true);
        try {
            const response = await fetch(
                `${GOOGLE_BOOKS_API}?q=${encodeURIComponent(searchTerm)}&maxResults=5`
            );
            const data = await response.json();
            
            if (data.items && data.items[0]) {
                const book = data.items[0].volumeInfo;
                setAttributes({
                    bookTitle: book.title,
                    author: book.authors ? book.authors[0] : '',
                    genre: book.categories ? book.categories[0] : '',
                    coverUrl: book.imageLinks?.thumbnail || ''
                });
            }
        } catch (error) {
            console.error('Fehler beim Suchen:', error);
        }
        setIsSearching(false);
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
                    </div>
                    <TextControl
                        label={__('Autor', 'child')}
                        value={author}
                        onChange={(value) => setAttributes({ author: value })}
                    />
                    <TextControl
                        label={__('Genre', 'child')}
                        value={genre}
                        onChange={(value) => setAttributes({ genre: value })}
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
            
            <ServerSideRender
                block="child/book-rating"
                attributes={attributes}
            />
        </div>
    );
}

registerBlockType(metadata.name, {
    edit: Edit,
    save: () => null
});