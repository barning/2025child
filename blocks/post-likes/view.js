import apiFetch from '@wordpress/api-fetch';

const BUTTON_SELECTOR = '.wp-block-child-post-likes .child-post-likes__button';

const schedule = ( callback ) => {
	if ( typeof window.requestIdleCallback === 'function' ) {
		window.requestIdleCallback( callback, { timeout: 900 } );
		return;
	}

	window.setTimeout( callback, 1 );
};

const setLoadingState = ( button, isLoading ) => {
	button.classList.toggle( 'is-loading', isLoading );
	button.setAttribute( 'aria-busy', isLoading ? 'true' : 'false' );
	button.disabled = isLoading;
};

const updateButtonState = ( button, payload ) => {
	const countNode = button.querySelector( '.child-post-likes__count' );
	if ( countNode ) {
		countNode.textContent = String( payload.count ?? 0 );
	}

	if ( payload.liked ) {
		button.classList.add( 'is-liked' );
		button.setAttribute( 'aria-pressed', 'true' );
	} else {
		button.classList.remove( 'is-liked' );
		button.setAttribute( 'aria-pressed', 'false' );
	}
};

const initLikeButton = ( button ) => {
	if ( ! button || button.dataset.likesInitialized === '1' ) {
		return;
	}

	button.dataset.likesInitialized = '1';

	button.addEventListener( 'click', async () => {
		const postId = Number.parseInt( button.dataset.postId || '', 10 );
		if ( ! postId ) {
			return;
		}

		setLoadingState( button, true );

		try {
			const payload = await apiFetch( {
				path: `/child/v1/post-likes/${ postId }`,
				method: 'POST',
			} );

			updateButtonState( button, payload );
		} catch ( error ) {
			button.classList.add( 'has-error' );
			window.setTimeout( () => button.classList.remove( 'has-error' ), 1600 );
		} finally {
			setLoadingState( button, false );
		}
	} );
};

const initializePostLikes = () => {
	document.querySelectorAll( BUTTON_SELECTOR ).forEach( initLikeButton );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', () => schedule( initializePostLikes ), { once: true } );
} else {
	schedule( initializePostLikes );
}
