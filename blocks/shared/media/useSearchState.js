import { useEffect, useRef, useState } from '@wordpress/element';

export const useSearchState = ( {
	initialTerm = '',
	initialSelectedId = null,
} = {} ) => {
	const [ searchTerm, setSearchTerm ] = useState( initialTerm );
	const [ isSearching, setIsSearching ] = useState( false );
	const [ searchResults, setSearchResults ] = useState( [] );
	const [ selectedId, setSelectedId ] = useState( initialSelectedId );
	const [ searchError, setSearchError ] = useState( '' );
	const [ hasSearched, setHasSearched ] = useState( false );
	const activeRequest = useRef( null );
	const requestSequence = useRef( 0 );

	const beginSearch = ( {
		clearResults = true,
		clearSelected = true,
	} = {} ) => {
		activeRequest.current?.controller.abort();

		const requestId = requestSequence.current + 1;
		const controller = new AbortController();
		requestSequence.current = requestId;
		activeRequest.current = { controller, requestId };

		setIsSearching( true );
		setSearchError( '' );

		if ( clearResults ) {
			setSearchResults( [] );
		}

		if ( clearSelected ) {
			setSelectedId( null );
		}

		return { requestId, signal: controller.signal };
	};

	const isCurrentRequest = ( requestId ) =>
		requestId === undefined ||
		activeRequest.current?.requestId === requestId;

	const completeSearch = ( results, emptyMessage = '', requestId ) => {
		if ( ! isCurrentRequest( requestId ) ) {
			return false;
		}

		setSearchResults( results );
		setHasSearched( true );

		if ( ! results.length && emptyMessage ) {
			setSearchError( emptyMessage );
		}

		return true;
	};

	const failSearch = ( message, requestId ) => {
		if ( ! isCurrentRequest( requestId ) ) {
			return false;
		}

		setSearchError( message );
		setHasSearched( false );
		return true;
	};

	const finishSearch = ( requestId ) => {
		if ( ! isCurrentRequest( requestId ) ) {
			return false;
		}

		setIsSearching( false );
		activeRequest.current = null;
		return true;
	};

	const cancelSearch = () => {
		activeRequest.current?.controller.abort();
		activeRequest.current = null;
		setIsSearching( false );
	};

	const selectResult = (
		result,
		{ getId = ( item ) => item.id, getTitle = ( item ) => item.title } = {}
	) => {
		setSelectedId( getId( result ) );
		setSearchTerm( getTitle( result ) || '' );
		return result;
	};

	const resetResults = () => {
		cancelSearch();
		setSearchResults( [] );
		setHasSearched( false );
		setSearchError( '' );
	};

	useEffect(
		() => () => {
			activeRequest.current?.controller.abort();
		},
		[]
	);

	return {
		searchTerm,
		setSearchTerm,
		isSearching,
		searchResults,
		selectedId,
		setSelectedId,
		searchError,
		setSearchError,
		hasSearched,
		beginSearch,
		completeSearch,
		failSearch,
		finishSearch,
		cancelSearch,
		isCurrentRequest,
		selectResult,
		resetResults,
	};
};
