const assert = require( 'node:assert/strict' );
const path = require( 'node:path' );
const test = require( 'node:test' );

const createSearchRequestState = require( path.resolve(
	__dirname,
	'../../blocks/shared/media/searchRequestState.js'
) );

test( 'starting a new search aborts the previous request', () => {
	const state = createSearchRequestState();
	const first = state.begin();
	const second = state.begin();

	assert.equal( first.signal.aborted, true );
	assert.equal( second.signal.aborted, false );
	assert.equal( state.isCurrent( first.requestId ), false );
	assert.equal( state.isCurrent( second.requestId ), true );
} );

test( 'stale requests cannot finish the active request', () => {
	const state = createSearchRequestState();
	const first = state.begin();
	const second = state.begin();

	assert.equal( state.finish( first.requestId ), false );
	assert.equal( state.isCurrent( second.requestId ), true );
	assert.equal( state.finish( second.requestId ), true );
	assert.equal( state.isCurrent( second.requestId ), false );
} );

test( 'cancelling aborts and clears the active request', () => {
	const state = createSearchRequestState();
	const request = state.begin();

	state.cancel();

	assert.equal( request.signal.aborted, true );
	assert.equal( state.isCurrent( request.requestId ), false );
} );
