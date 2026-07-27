import { useEffect, useRef, useState } from '@wordpress/element';
import createSearchRequestState from './searchRequestState';

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
	const requestState = useRef( null );

	if ( requestState.current === null ) {
		requestState.current = createSearchRequestState();
	}

	const beginSearch = ( {
		clearResults = true,
		clearSelected = true,
	} = {} ) => {
		const request = requestState.current.begin();

		setIsSearching( true );
		setSearchError( '' );

		if ( clearResults ) {
			setSearchResults( [] );
		}

		if ( clearSelected ) {
			setSelectedId( null );
		}

		return request;
	};

	const isCurrentRequest = ( requestId ) =>
		requestState.current.isCurrent( requestId );

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
		requestState.current.finish( requestId );
		return true;
	};

	const cancelSearch = () => {
		requestState.current.cancel();
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
			requestState.current.cancel();
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
