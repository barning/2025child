const PLAYER_SELECTOR = '.wp-block-child-apple-music-player .child-apple-music-player';

const state = {
	configured: false,
};

const setStatus = ( element, message ) => {
	const status = element.querySelector( '.child-apple-music-player__status' );
	if ( status ) {
		status.textContent = message;
	}
};

const queueFromDataset = ( dataset ) => {
	if ( dataset.resourceType === 'album' ) {
		return { album: dataset.resourceId };
	}

	if ( dataset.resourceType === 'playlist' ) {
		return { playlist: dataset.resourceId };
	}

	return { song: dataset.resourceId };
};

const configureMusicKit = ( element ) => {
	if ( state.configured ) {
		return;
	}

	if ( ! window.MusicKit || typeof window.MusicKit.configure !== 'function' ) {
		throw new Error( 'MusicKit JS failed to load.' );
	}

	window.MusicKit.configure( {
		developerToken: element.dataset.developerToken,
		app: {
			name: element.dataset.appName || 'WordPress Apple Music Player',
			build: element.dataset.appBuild || '1.0.0',
		},
	} );

	state.configured = true;
};

const handlePlay = async ( event ) => {
	const button = event.currentTarget;
	const element = button.closest( '.child-apple-music-player' );

	if ( ! element ) {
		return;
	}

	button.disabled = true;
	setStatus( element, 'Loading Apple Music playback…' );

	try {
		configureMusicKit( element );
		const music = window.MusicKit.getInstance();
		await music.setQueue( queueFromDataset( element.dataset ) );
		await music.play();
		setStatus( element, 'Now playing.' );
	} catch ( error ) {
		setStatus(
			element,
			'Playback failed. Ensure MusicKit developer token and Apple Music entitlements are configured.'
		);
	} finally {
		button.disabled = false;
	}
};

const initAppleMusicPlayerBlocks = () => {
	const elements = document.querySelectorAll( PLAYER_SELECTOR );
	if ( ! elements.length ) {
		return;
	}

	elements.forEach( ( element ) => {
		const button = element.querySelector( '.child-apple-music-player__button' );
		if ( ! button || button.dataset.ready === '1' ) {
			return;
		}

		button.dataset.ready = '1';
		button.addEventListener( 'click', handlePlay );
	} );
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', initAppleMusicPlayerBlocks, { once: true } );
} else {
	initAppleMusicPlayerBlocks();
}
