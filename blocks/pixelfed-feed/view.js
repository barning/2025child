const RATIO_CLASSES = [ 'is-ratio-landscape', 'is-ratio-square', 'is-ratio-portrait' ];

const classifyImageRatio = ( item, image ) => {
	if ( ! item || ! image || ! image.naturalWidth || ! image.naturalHeight ) {
		return;
	}

	const ratio = image.naturalWidth / image.naturalHeight;
	let ratioClass = 'is-ratio-square';

	if ( ratio >= 1.2 ) {
		ratioClass = 'is-ratio-landscape';
	} else if ( ratio <= 0.83 ) {
		ratioClass = 'is-ratio-portrait';
	}

	item.classList.remove( ...RATIO_CLASSES );
	item.classList.add( ratioClass );
	item.removeAttribute( 'data-needs-ratio-check' );
};

const schedule = ( callback ) => {
	if ( typeof window.requestIdleCallback === 'function' ) {
		window.requestIdleCallback( callback, { timeout: 1200 } );
		return;
	}

	window.setTimeout( callback, 1 );
};

const initPixelfedFeedRatios = () => {
	const itemsNeedingChecks = document.querySelectorAll(
		'.wp-block-child-pixelfed-feed .child-pixelfed-feed-item[data-needs-ratio-check="1"]'
	);

	itemsNeedingChecks.forEach( ( item ) => {
		const image = item.querySelector( '.child-pixelfed-feed-item__image' );
		if ( ! image ) {
			return;
		}

		if ( image.complete ) {
			schedule( () => classifyImageRatio( item, image ) );
			return;
		}

		image.addEventListener(
			'load',
			() => {
				schedule( () => classifyImageRatio( item, image ) );
			},
			{ once: true }
		);
	} );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initPixelfedFeedRatios, { once: true } );
} else {
	initPixelfedFeedRatios();
}
