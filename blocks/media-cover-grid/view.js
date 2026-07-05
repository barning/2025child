const BLOCK_SELECTOR = '.wp-block-child-media-cover-grid, .child-media-cover-grid-block';
const FILTER_SELECTOR = '[data-child-media-filter-group]';
const ITEM_SELECTOR = '[data-child-media-type][data-child-media-format]';

const schedule = ( callback ) => {
	if ( typeof window.requestIdleCallback === 'function' ) {
		window.requestIdleCallback( callback, { timeout: 900 } );
		return;
	}

	window.setTimeout( callback, 1 );
};

const getActiveValues = ( block, group ) => {
	const buttons = Array.from(
		block.querySelectorAll( `${ FILTER_SELECTOR }[data-child-media-filter-group="${ group }"]` )
	);

	if ( buttons.length === 0 ) {
		return null;
	}

	return new Set(
		buttons
			.filter( ( button ) => button.classList.contains( 'is-active' ) )
			.map( ( button ) => button.dataset.childMediaFilterValue )
	);
};

const updateGrid = ( block ) => {
	const activeTypes = getActiveValues( block, 'type' );
	const activeFormats = getActiveValues( block, 'format' );
	let visibleItems = 0;

	block.querySelectorAll( ITEM_SELECTOR ).forEach( ( item ) => {
		const typeMatches = activeTypes === null || activeTypes.has( item.dataset.childMediaType );
		const formatMatches = activeFormats === null || activeFormats.has( item.dataset.childMediaFormat );
		const isVisible = typeMatches && formatMatches;

		item.hidden = ! isVisible;
		item.setAttribute( 'aria-hidden', isVisible ? 'false' : 'true' );

		if ( isVisible ) {
			visibleItems += 1;
		}
	} );

	const emptyMessage = block.querySelector( '.child-media-cover-grid__empty--filtered' );
	if ( emptyMessage ) {
		emptyMessage.hidden = visibleItems > 0;
	}
};

const initFilterButton = ( block, button ) => {
	if ( button.dataset.childMediaFilterInitialized === '1' ) {
		return;
	}

	button.dataset.childMediaFilterInitialized = '1';

	button.addEventListener( 'click', () => {
		const isActive = ! button.classList.contains( 'is-active' );

		button.classList.toggle( 'is-active', isActive );
		button.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
		updateGrid( block );
	} );
};

const initMediaCoverGrid = ( block ) => {
	if ( ! block || block.dataset.childMediaCoverGridInitialized === '1' ) {
		return;
	}

	block.dataset.childMediaCoverGridInitialized = '1';
	block.querySelectorAll( FILTER_SELECTOR ).forEach( ( button ) => initFilterButton( block, button ) );
	updateGrid( block );
};

const initializeMediaCoverGrids = () => {
	document.querySelectorAll( BLOCK_SELECTOR ).forEach( initMediaCoverGrid );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', () => schedule( initializeMediaCoverGrids ), { once: true } );
} else {
	schedule( initializeMediaCoverGrids );
}
