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
					title={ __( 'Anzeigetyp', 'child' ) }
					initialOpen={ true }
				>
					<RadioControl
						label={ __( 'Was möchtest du anzeigen?', 'child' ) }
						selected={ displayType }
						options={ [
							{
								label: __( 'Einzelne Karte', 'child' ),
								value: 'single',
							},
							{
								label: __(
									'Eingebettetes Moxfield-Deck',
									'child'
								),
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
						title={ __( 'Moxfield-Einstellungen', 'child' ) }
						initialOpen={ true }
					>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Moxfield-Deck-URL', 'child' ) }
							value={ moxfieldUrl }
							onChange={ ( value ) =>
								setAttributes( { moxfieldUrl: value } )
							}
							placeholder="https://moxfield.com/decks/..."
							help={ __(
								'Gib die vollständige URL des Moxfield-Decks ein.',
								'child'
							) }
						/>
					</PanelBody>
				) }

				{ displayType === 'single' && (
					<>
						<PanelBody
							title={ __( 'Karte suchen', 'child' ) }
							initialOpen={ true }
						>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Kartenname', 'child' ) }
								value={ searchTerm }
								onChange={ setSearchTerm }
								placeholder={ __(
									'Kartenname eingeben…',
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
									? __( 'Suche…', 'child' )
									: __( 'Suchen', 'child' ) }
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
													'%d Ergebnis gefunden.',
													'%d Ergebnisse gefunden.',
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
											{ __(
												'Karte auswählen:',
												'child'
											) }
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
								title={ __( 'Kartendetails', 'child' ) }
								initialOpen={ true }
							>
								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Kartenname', 'child' ) }
									value={ cardName }
									onChange={ ( value ) =>
										setAttributes( { cardName: value } )
									}
									disabled
								/>
								<TextControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Bild-URL', 'child' ) }
									value={ cardImageUrl }
									onChange={ ( value ) =>
										setAttributes( { cardImageUrl: value } )
									}
									help={ __(
										'Eigene Bild-URL (optional)',
										'child'
									) }
								/>
								{ isLoadingPrints && (
									<div className="magic-cards-loading">
										<Spinner />
										<span>
											{ __(
												'Alternative Drucke werden geladen…',
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
