import { __, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Button, Spinner, Notice, RadioControl, SelectControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import metadata from './block.json';
import './editor.css';
import './style.css';

const SCRYFALL_API = 'https://api.scryfall.com';

const MoxfieldPreview = ({ url }) => {
	if (!url?.trim()) {
		return (
			<div className="magic-cards-preview--empty">
				{__('Enter a Moxfield deck URL to display the embed.', 'child')}
			</div>
		);
	}

	// Extract deck ID from Moxfield URL
	const deckMatch = url.match(/moxfield\.com\/decks\/([a-zA-Z0-9_-]+)/);
	if (!deckMatch) {
		return (
			<div className="magic-cards-preview--error">
				{__('Invalid Moxfield URL. Please use a valid deck URL like: https://moxfield.com/decks/...', 'child')}
			</div>
		);
	}

	const deckId = deckMatch[1];
	const embedUrl = `https://www.moxfield.com/embed/${deckId}`;

	return (
		<div className="child-magic-moxfield">
			<div className="child-magic-moxfield__preview">
				<p><strong>{__('Moxfield Deck Embed', 'child')}</strong></p>
				<p><small>{__('The deck will be displayed on the frontend.', 'child')}</small></p>
				<p><code>{url}</code></p>
			</div>
		</div>
	);
};

const CardPreview = ({ cardName, cardImageUrl }) => {
	if (!cardName?.trim()) {
		return (
			<div className="magic-cards-preview--empty">
				{__('Search for a card by name to display it.', 'child')}
			</div>
		);
	}

	return (
		<div className="child-magic-card">
			<div className="child-magic-card__media">
				{cardImageUrl ? (
					<img
						className="child-magic-card__image"
						src={cardImageUrl}
						alt={cardName}
						loading="lazy"
					/>
				) : (
					<div className="child-magic-card__placeholder" aria-hidden="true">
						<span>🃏</span>
					</div>
				)}
			</div>
			<div className="child-magic-card__meta">
				<h3 className="child-magic-card__name">{cardName}</h3>
			</div>
		</div>
	);
};

const PrintSelector = ({ prints, selectedPrint, onSelect }) => {
	if (!prints || prints.length === 0) {
		return null;
	}

	if (prints.length === 1) {
		return (
			<p className="magic-cards-prints-info">
				{__('Only one printing available', 'child')}
			</p>
		);
	}

	const options = prints.map((print) => ({
		label: `${print.set_name} (${print.set.toUpperCase()}) - ${print.released_at || 'Unknown'}`,
		value: print.id
	}));

	return (
		<SelectControl
			label={__('Select Print', 'child')}
			value={selectedPrint?.id || ''}
			options={[
				{ label: __('Select a print...', 'child'), value: '' },
				...options
			]}
			onChange={(value) => {
				const print = prints.find(p => p.id === value);
				if (print) {
					onSelect(print);
				}
			}}
			help={__('Choose an alternative printing of this card', 'child')}
		/>
	);
};

function Edit({ attributes, setAttributes }) {
	const blockProps = useBlockProps();
	const { displayType, moxfieldUrl, cardName, cardImageUrl, selectedPrint, scryfallId } = attributes;
	const [searchTerm, setSearchTerm] = useState('');
	const [isSearching, setIsSearching] = useState(false);
	const [searchResults, setSearchResults] = useState([]);
	const [searchError, setSearchError] = useState('');
	const [availablePrints, setAvailablePrints] = useState([]);
	const [isLoadingPrints, setIsLoadingPrints] = useState(false);

	useEffect(() => {
		if (!cardName) {
			return;
		}
		setSearchTerm((currentValue) => (currentValue ? currentValue : cardName));
	}, [cardName]);

	const searchCards = async () => {
		const trimmedTerm = searchTerm.trim();
		if (!trimmedTerm) {
			setSearchError(__('Please enter a card name to search.', 'child'));
			return;
		}

		setIsSearching(true);
		setSearchError('');
		setSearchResults([]);
		setAvailablePrints([]);

		try {
			const response = await fetch(
				`${SCRYFALL_API}/cards/search?q=${encodeURIComponent(trimmedTerm)}&unique=cards`
			);

			if (!response.ok) {
				throw new Error('Search failed');
			}

			const data = await response.json();
			const results = (data.data || []).slice(0, 10).map((card) => ({
				id: card.id,
				name: card.name,
				set: card.set,
				set_name: card.set_name,
				image: card.image_uris?.normal || card.image_uris?.large || card.image_uris?.small || '',
				released_at: card.released_at
			}));

			setSearchResults(results);
			if (!results.length) {
				setSearchError(__('No cards found.', 'child'));
			}
		} catch (error) {
			console.error('Error searching cards:', error);
			setSearchError(__('An error occurred while searching. Please try again.', 'child'));
		}
		setIsSearching(false);
	};

	const loadPrints = async (cardName) => {
		setIsLoadingPrints(true);
		try {
			const response = await fetch(
				`${SCRYFALL_API}/cards/search?q=!"${encodeURIComponent(cardName)}"&unique=prints`
			);

			if (!response.ok) {
				throw new Error('Failed to load prints');
			}

			const data = await response.json();
			const prints = (data.data || []).map((card) => ({
				id: card.id,
				name: card.name,
				set: card.set,
				set_name: card.set_name,
				image: card.image_uris?.normal || card.image_uris?.large || card.image_uris?.small || '',
				released_at: card.released_at
			}));

			setAvailablePrints(prints);
		} catch (error) {
			console.error('Error loading prints:', error);
		}
		setIsLoadingPrints(false);
	};

	const handleCardSelection = (card) => {
		setAttributes({
			cardName: card.name,
			cardImageUrl: card.image,
			scryfallId: card.id,
			selectedPrint: card
		});
		setSearchResults([]);
		loadPrints(card.name);
	};

	const handlePrintSelection = (print) => {
		setAttributes({
			cardImageUrl: print.image,
			scryfallId: print.id,
			selectedPrint: print
		});
	};

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody title={__('Display Type', 'child')} initialOpen={true}>
					<RadioControl
						label={__('What do you want to display?', 'child')}
						selected={displayType}
						options={[
							{ label: __('Single Card', 'child'), value: 'single' },
							{ label: __('Moxfield Deck Embed', 'child'), value: 'moxfield' }
						]}
						onChange={(value) => setAttributes({ displayType: value })}
					/>
				</PanelBody>

				{displayType === 'moxfield' && (
					<PanelBody title={__('Moxfield Settings', 'child')} initialOpen={true}>
						<TextControl
							label={__('Moxfield Deck URL', 'child')}
							value={moxfieldUrl}
							onChange={(value) => setAttributes({ moxfieldUrl: value })}
							placeholder="https://moxfield.com/decks/..."
							help={__('Enter the full URL of the Moxfield deck', 'child')}
						/>
					</PanelBody>
				)}

				{displayType === 'single' && (
					<>
						<PanelBody title={__('Search for Card', 'child')} initialOpen={true}>
							<TextControl
								label={__('Card Name', 'child')}
								value={searchTerm}
								onChange={setSearchTerm}
								placeholder={__('Enter card name...', 'child')}
								onKeyDown={(event) => {
									if (event.key === 'Enter') {
										event.preventDefault();
										searchCards();
									}
								}}
							/>
							<Button
								variant="primary"
								onClick={searchCards}
								disabled={isSearching}
								className="magic-cards-search-button"
							>
								{isSearching ? __('Searching...', 'child') : __('Search', 'child')}
							</Button>
							{isSearching && (
								<div className="magic-cards-loading">
									<Spinner />
								</div>
							)}
							{searchError && (
								<Notice status="error" isDismissible={false}>
									{searchError}
								</Notice>
							)}
							{searchResults.length > 0 && (
								<div className="magic-cards-results">
									<p><strong>{__('Select a card:', 'child')}</strong></p>
									{searchResults.map((card) => (
										<Button
											key={card.id}
											variant={card.id === scryfallId ? 'primary' : 'secondary'}
											onClick={() => handleCardSelection(card)}
											className="magic-cards-result"
										>
											<span className="magic-cards-result__name">{card.name}</span>
											<span className="magic-cards-result__set">
												{card.set_name} ({card.set.toUpperCase()})
											</span>
										</Button>
									))}
								</div>
							)}
						</PanelBody>

						{cardName && (
							<PanelBody title={__('Card Details', 'child')} initialOpen={true}>
								<TextControl
									label={__('Card Name', 'child')}
									value={cardName}
									onChange={(value) => setAttributes({ cardName: value })}
									disabled
								/>
								<TextControl
									label={__('Image URL', 'child')}
									value={cardImageUrl}
									onChange={(value) => setAttributes({ cardImageUrl: value })}
									help={__('Custom image URL (optional)', 'child')}
								/>
								{isLoadingPrints && (
									<div className="magic-cards-loading">
										<Spinner />
										<span>{__('Loading alternative prints...', 'child')}</span>
									</div>
								)}
								{!isLoadingPrints && availablePrints.length > 0 && (
									<PrintSelector
										prints={availablePrints}
										selectedPrint={selectedPrint}
										onSelect={handlePrintSelection}
									/>
								)}
							</PanelBody>
						)}
					</>
				)}
			</InspectorControls>

			{displayType === 'moxfield' ? (
				<MoxfieldPreview url={moxfieldUrl} />
			) : (
				<CardPreview cardName={cardName} cardImageUrl={cardImageUrl} />
			)}
		</div>
	);
}

registerBlockType(metadata.name, {
	edit: Edit,
	save: () => null
});
