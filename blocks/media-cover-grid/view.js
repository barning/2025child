const BLOCK_SELECTOR = '.wp-block-child-media-cover-grid, .child-media-cover-grid-block';
const FILTER_SELECTOR = '[data-child-media-filter-group="type"]';
const ITEM_SELECTOR = '[data-child-media-type]';
const YEAR_SELECTOR = '[data-child-media-year]';
const ALL_FILTER_VALUE = 'all';

const getActiveTypes = ( block ) => {
	const buttons = Array.from( block.querySelectorAll( FILTER_SELECTOR ) );

	if ( buttons.length === 0 ) {
		return null;
	}

	const activeValues = buttons
		.filter( ( button ) => button.classList.contains( 'is-active' ) )
		.map( ( button ) => button.dataset.childMediaFilterValue );

	if ( activeValues.length === 0 || activeValues.includes( ALL_FILTER_VALUE ) ) {
		return null;
	}

	return new Set( activeValues );
};

const syncFilterButtons = ( block, activeValues ) => {
	block.querySelectorAll( FILTER_SELECTOR ).forEach( ( button ) => {
		const isActive = activeValues.has( button.dataset.childMediaFilterValue );

		button.classList.toggle( 'is-active', isActive );
		button.setAttribute( 'aria-pressed', isActive ? 'true' : 'false' );
	} );
};

const updateGrid = ( block ) => {
	const activeTypes = getActiveTypes( block );
	let visibleItems = 0;

	block.querySelectorAll( ITEM_SELECTOR ).forEach( ( item ) => {
		const isVisible = activeTypes === null || activeTypes.has( item.dataset.childMediaType );

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

	const grid = block.querySelector( '.child-media-cover-grid' );
	if ( ! grid ) {
		return;
	}

	let currentYear = null;
	let currentYearHasVisibleItems = false;

	const flushYearVisibility = () => {
		if ( ! currentYear ) {
			return;
		}

		currentYear.hidden = ! currentYearHasVisibleItems;
		currentYear.setAttribute( 'aria-hidden', currentYearHasVisibleItems ? 'false' : 'true' );
	};

	Array.from( grid.children ).forEach( ( child ) => {
		if ( child.matches( YEAR_SELECTOR ) ) {
			flushYearVisibility();
			currentYear = child;
			currentYearHasVisibleItems = false;
			return;
		}

		if ( child.matches( ITEM_SELECTOR ) && ! child.hidden ) {
			currentYearHasVisibleItems = true;
		}
	} );

	flushYearVisibility();
};

const initFilterButton = ( block, button ) => {
	if ( button.dataset.childMediaFilterInitialized === '1' ) {
		return;
	}

	button.dataset.childMediaFilterInitialized = '1';

	button.addEventListener( 'click', () => {
		const value = button.dataset.childMediaFilterValue;
		const buttons = Array.from( block.querySelectorAll( FILTER_SELECTOR ) );
		const activeValues = new Set(
			buttons
				.filter( ( currentButton ) => currentButton.classList.contains( 'is-active' ) )
				.map( ( currentButton ) => currentButton.dataset.childMediaFilterValue )
		);

		if ( value === ALL_FILTER_VALUE ) {
			syncFilterButtons( block, new Set( [ ALL_FILTER_VALUE ] ) );
			updateGrid( block );
			return;
		}

		activeValues.delete( ALL_FILTER_VALUE );

		if ( activeValues.has( value ) ) {
			activeValues.delete( value );
		} else {
			activeValues.add( value );
		}

		if ( activeValues.size === 0 ) {
			activeValues.add( ALL_FILTER_VALUE );
		}

		syncFilterButtons( block, activeValues );
		updateGrid( block );
	} );
};

const initMediaCoverGrid = ( block ) => {
	if ( ! block || block.dataset.childMediaCoverGridInitialized === '1' ) {
		return;
	}

	block.dataset.childMediaCoverGridInitialized = '1';
	block.querySelectorAll( FILTER_SELECTOR ).forEach( ( button ) => initFilterButton( block, button ) );
	syncFilterButtons( block, new Set( [ ALL_FILTER_VALUE ] ) );

	updateGrid( block );
};

const initializeMediaCoverGrids = () => {
	document.querySelectorAll( BLOCK_SELECTOR ).forEach( initMediaCoverGrid );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initializeMediaCoverGrids, { once: true } );
} else {
	initializeMediaCoverGrids();
}
