const BLOCK_SELECTOR = '.wp-block-child-media-cover-grid, .child-media-cover-grid-block';
const FILTER_SELECTOR = '[data-child-media-filter-group="type"]';
const ITEM_SELECTOR = '[data-child-media-type]';

const schedule = ( callback ) => {
	if ( typeof window.requestAnimationFrame === 'function' ) {
		window.requestAnimationFrame( callback );
		return;
	}

	window.setTimeout( callback, 1 );
};

const getActiveTypes = ( block ) => {
	const buttons = Array.from( block.querySelectorAll( FILTER_SELECTOR ) );

	if ( buttons.length === 0 ) {
		return null;
	}

	return new Set(
		buttons
			.filter( ( button ) => button.classList.contains( 'is-active' ) )
			.map( ( button ) => button.dataset.childMediaFilterValue )
	);
};

const layoutMasonryGrid = ( block ) => {
	const grid = block.querySelector( '.child-media-cover-grid' );
	if ( ! grid ) {
		return;
	}

	const styles = window.getComputedStyle( grid );
	const rowSize = Number.parseFloat( styles.getPropertyValue( 'grid-auto-rows' ) ) || 8;
	const rowGap = Number.parseFloat( styles.getPropertyValue( 'row-gap' ) ) || 0;

	grid.querySelectorAll( ITEM_SELECTOR ).forEach( ( item ) => {
		item.style.gridRowEnd = '';

		if ( item.hidden ) {
			return;
		}

		const itemHeight = item.getBoundingClientRect().height;
		const rowSpan = Math.max( 1, Math.ceil( ( itemHeight + rowGap ) / ( rowSize + rowGap ) ) );
		item.style.gridRowEnd = `span ${ rowSpan }`;
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

	schedule( () => layoutMasonryGrid( block ) );
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

	block.querySelectorAll( '.child-media-cover-grid__cover img' ).forEach( ( image ) => {
		if ( image.complete ) {
			return;
		}

		image.addEventListener( 'load', () => schedule( () => layoutMasonryGrid( block ) ), { once: true } );
	} );

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

window.addEventListener( 'resize', () => {
	schedule( () => {
		document.querySelectorAll( BLOCK_SELECTOR ).forEach( layoutMasonryGrid );
	} );
} );
