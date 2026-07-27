import { __, _n, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	Button,
	Spinner,
	RadioControl,
	SelectControl,
} from '@wordpress/components';
import metadata from './block.json';
import { SearchFeedback } from '../shared/media/SearchFeedback';
import { SearchResultsList } from '../shared/media/SearchResultsList';
import { useSearchState } from '../shared/media/useSearchState';
import './editor.css';
import './style.css';

const SCRYFALL_API = 'https://api.scryfall.com';

// Note: Scryfall API has a rate limit of 10 requests per second.
// For production use, consider implementing debouncing or caching.
// See: https://scryfall.com/docs/api

const MoxfieldPreview = ( { url } ) => {
	if ( ! url?.trim() ) {
		return (
			<div className="magic-cards-preview--empty">
				{ __(
					'Enter a Moxfield deck URL to display the embed.',
					'child'
				) }
			</div>
		);
	}

	// Extract deck ID from Moxfield URL
	const deckMatch = url.match(
		/moxfield\.com\/decks\/([a-zA-Z0-9_-]{1,100})/
	);
	if ( ! deckMatch ) {
		return (
			<div className="magic-cards-preview--error">
				{ __(
					'Invalid Moxfield URL. Please use a valid deck URL like: https://moxfield.com/decks/…',
					'child'
				) }
			</div>
		);
	}

	return (
		<div className="child-magic-moxfield">
			<div className="child-magic-moxfield__preview">
				<p>
					<strong>{ __( 'Moxfield Deck Embed', 'child' ) }</strong>
				</p>
				<p>
					<small>
						{ __(
							'The deck will be displayed on the frontend.',
							'child'
						) }
					</small>
				</p>
				<p>
					<code>{ url }</code>
				</p>
			</div>
		</div>
	);
};

const CardPreview = ( { cardName, cardImageUrl } ) => {
	if ( ! cardName?.trim() ) {
		return (
			<div className="magic-cards-preview--empty">
				{ __( 'Search for a card by name to display it.', 'child' ) }
			</div>
		);
	}

	return (
		<div className="child-magic-card">
			<div className="child-magic-card__media">
				{ cardImageUrl ? (
					<img
						className="child-magic-card__image"
						src={ cardImageUrl }
						alt={ cardName }
						loading="lazy"
					/>
				) : (
					<div
						className="child-magic-card__placeholder"
						aria-hidden="true"
					>
						<span>🃏</span>
					</div>
				) }
			</div>
			<div className="child-magic-card__meta">
				<h3 className="child-magic-card__name">{ cardName }</h3>
			</div>
		</div>
	);
};

const PrintSelector = ( { prints, selectedPrint, onSelect } ) => {
	if ( ! prints || prints.length === 0 ) {
		return null;
	}

	if ( prints.length === 1 ) {
		return (
			<p className="magic-cards-prints-info">
				{ __( 'Only one printing available', 'child' ) }
			</p>
		);
	}

	const options = prints.map( ( print ) => ( {
		label: `${ print.set_name } (${ print.set.toUpperCase() }) - ${
			print.released_at || 'Unknown'
		}`,
		value: print.id,
	} ) );

	return (
		<SelectControl
			label={ __( 'Select Print', 'child' ) }
			value={ selectedPrint?.id || '' }
			options={ [
				{ label: __( 'Select a print…', 'child' ), value: '' },
				...options,
			] }
			onChange={ ( value ) => {
				const print = prints.find( ( p ) => p.id === value );
				if ( print ) {
					onSelect( print );
				}
			} }
			help={ __(
				'Choose an alternative printing of this card',
				'child'
			) }
		/>
	);
};

function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const {
		displayType,
		moxfieldUrl,
		cardName,
		cardImageUrl,
		selectedPrint,
		scryfallId,
	} = attributes;
	const cardSearch = useSearchState( {
		initialTerm: cardName || '',
		initialSelectedId: scryfallId || null,
	} );
	const printsSearch = useSearchState();
	const {
		searchTerm,
		setSearchTerm,
		isSearching,
		searchResults,
		searchError,
		hasSearched,
	} = cardSearch;
	const availablePrints = printsSearch.searchResults;
	const isLoadingPrints = printsSearch.isSearching;

	const searchCards = async () => {
		const trimmedTerm = searchTerm.trim();
		if ( ! trimmedTerm ) {
			cardSearch.failSearch(
				__( 'Please enter a card name to search.', 'child' )
			);
			return;
		}

		const { requestId, signal } = cardSearch.beginSearch();
		printsSearch.resetResults();

		try {
			const response = await fetch(
				`${ SCRYFALL_API }/cards/search?q=${ encodeURIComponent(
					trimmedTerm
				) }&unique=cards`,
				{ signal }
			);

			if ( ! response.ok ) {
				if ( response.status === 404 ) {
					throw new Error(
						__( 'No cards found matching your search.', 'child' )
					);
				} else if ( response.status === 429 ) {
					throw new Error(
						__(
							'Too many requests. Please wait a moment and try again.',
							'child'
						)
					);
				}
				throw new Error(
					__(
						'Search failed. Please check your connection and try again.',
						'child'
					)
				);
			}

			const data = await response.json();
			const results = ( data.data || [] )
				.slice( 0, 10 )
				.map( ( card ) => ( {
					id: card.id,
					name: card.name,
					set: card.set,
					set_name: card.set_name,
					image:
						card.image_uris?.normal ||
						card.image_uris?.large ||
						card.image_uris?.small ||
						'',
					released_at: card.released_at,
				} ) );

			cardSearch.completeSearch(
				results,
				__( 'No cards found matching your search.', 'child' ),
				requestId
			);
		} catch ( error ) {
			if ( error.name !== 'AbortError' ) {
				cardSearch.failSearch(
					error.message ||
						__(
							'An unexpected error occurred. Please try again.',
							'child'
						),
					requestId
				);
			}
		} finally {
			cardSearch.finishSearch( requestId );
		}
	};

	const loadPrints = async ( selectedCardName ) => {
		const { requestId, signal } = printsSearch.beginSearch();
		try {
			const response = await fetch(
				`${ SCRYFALL_API }/cards/search?q=!"${ encodeURIComponent(
					selectedCardName
				) }"&unique=prints`,
				{ signal }
			);

			if ( ! response.ok ) {
				return;
			}

			const data = await response.json();
			const prints = ( data.data || [] ).map( ( card ) => ( {
				id: card.id,
				name: card.name,
				set: card.set,
				set_name: card.set_name,
				image:
					card.image_uris?.normal ||
					card.image_uris?.large ||
					card.image_uris?.small ||
					'',
				released_at: card.released_at,
			} ) );

			printsSearch.completeSearch( prints, '', requestId );
		} catch ( error ) {
			if ( error.name !== 'AbortError' ) {
				printsSearch.failSearch( '', requestId );
			}
		} finally {
			printsSearch.finishSearch( requestId );
		}
	};

	const handleCardSelection = ( card ) => {
		setAttributes( {
			cardName: card.name,
			cardImageUrl: card.image,
			scryfallId: card.id,
			selectedPrint: card,
		} );
		cardSearch.selectResult( card, { getTitle: ( item ) => item.name } );
		cardSearch.resetResults();
		loadPrints( card.name );
	};

	const handlePrintSelection = ( print ) => {
		setAttributes( {
			cardImageUrl: print.image,
			scryfallId: print.id,
			selectedPrint: print,
		} );
	};

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Display Type', 'child' ) }
					initialOpen={ true }
				>
					<RadioControl
						label={ __( 'What do you want to display?', 'child' ) }
						selected={ displayType }
						options={ [
							{
								label: __( 'Single Card', 'child' ),
								value: 'single',
							},
							{
								label: __( 'Moxfield Deck Embed', 'child' ),
								value: 'moxfield',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { displayType: value } )
						}
					/>
				</PanelBody>

				{ displayType === 'moxfield' && (
					<PanelBody
						title={ __( 'Moxfield Settings', 'child' ) }
						initialOpen={ true }
					>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Moxfield Deck URL', 'child' ) }
							value={ moxfieldUrl }
							onChange={ ( value ) =>
								setAttributes( { moxfieldUrl: value } )
							}
							placeholder="https://moxfield.com/decks/..."
							help={ __(
								'Enter the full URL of the Moxfield deck',
								'child'
							) }
						/>
					</PanelBody>
				) }

				{ displayType === 'single' && (
					<>
						<PanelBody
							title={ __( 'Search for Card', 'child' ) }
							initialOpen={ true }
						>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Card Name', 'child' ) }
								value={ searchTerm }
								onChange={ setSearchTerm }
								placeholder={ __(
									'Enter card name…',
									'child'
								) }
								onKeyDown={ ( event ) => {
									if (
										event.key === 'Enter' &&
										! isSearching
									) {
										event.preventDefault();
										searchCards();
									}
								} }
							/>
							<Button
								variant="primary"
								onClick={ searchCards }
								disabled={ isSearching }
								className="magic-cards-search-button"
							>
								{ isSearching
									? __( 'Searching…', 'child' )
									: __( 'Search', 'child' ) }
							</Button>
							<SearchFeedback
								isSearching={ isSearching }
								error={ searchError }
								loadingClassName="magic-cards-loading"
								statusMessage={
									hasSearched
										? sprintf(
												/* translators: %d: number of search results */
												_n(
													'%d result found.',
													'%d results found.',
													searchResults.length,
													'child'
												),
												searchResults.length
										  )
										: ''
								}
							/>
							{ searchResults.length > 0 && (
								<div className="magic-cards-results">
									<p>
										<strong>
											{ __( 'Select a card:', 'child' ) }
										</strong>
									</p>
									<SearchResultsList
										results={ searchResults }
										selectedId={ cardSearch.selectedId }
										onSelect={ handleCardSelection }
										className="magic-cards-results__list"
										getId={ ( card ) => card.id }
										getClassName={ () =>
											'magic-cards-result'
										}
									>
										{ ( card ) => (
											<>
												<span className="magic-cards-result__name">
													{ card.name }
												</span>
												<span className="magic-cards-result__set">
													{ card.set_name } (
													{ card.set.toUpperCase() })
												</span>
											</>
										) }
									</SearchResultsList>
								</div>
							) }
						</PanelBody>

						{ cardName && (
							<PanelBody
								title={ __( 'Card Details', 'child' ) }
								initialOpen={ true }
							>
								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Card Name', 'child' ) }
									value={ cardName }
									onChange={ ( value ) =>
										setAttributes( { cardName: value } )
									}
									disabled
								/>
								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Image URL', 'child' ) }
									value={ cardImageUrl }
									onChange={ ( value ) =>
										setAttributes( { cardImageUrl: value } )
									}
									help={ __(
										'Custom image URL (optional)',
										'child'
									) }
								/>
								{ isLoadingPrints && (
									<div className="magic-cards-loading">
										<Spinner />
										<span>
											{ __(
												'Loading alternative prints…',
												'child'
											) }
										</span>
									</div>
								) }
								{ ! isLoadingPrints &&
									availablePrints.length > 0 && (
										<PrintSelector
											prints={ availablePrints }
											selectedPrint={ selectedPrint }
											onSelect={ handlePrintSelection }
										/>
									) }
							</PanelBody>
						) }
					</>
				) }
			</InspectorControls>

			{ displayType === 'moxfield' ? (
				<MoxfieldPreview url={ moxfieldUrl } />
			) : (
				<CardPreview
					cardName={ cardName }
					cardImageUrl={ cardImageUrl }
				/>
			) }
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
