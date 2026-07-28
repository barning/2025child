import { __, _n, sprintf } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RadioControl,
	Spinner,
	TextControl,
} from '@wordpress/components';
import metadata from './block.json';
import { SearchFeedback } from '../shared/media/SearchFeedback';
import { SearchResultsList } from '../shared/media/SearchResultsList';
import { CardPreview } from './components/CardPreview';
import { MoxfieldPreview } from './components/MoxfieldPreview';
import { PrintSelector } from './components/PrintSelector';
import { useScryfallSearch } from './hooks/useScryfallSearch';
import './editor.css';
import './style.css';

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
	const {
		cardSearch,
		printsSearch,
		searchCards,
		handleCardSelection,
		handlePrintSelection,
	} = useScryfallSearch( { cardName, scryfallId, setAttributes } );
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
