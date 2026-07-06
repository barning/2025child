import { useState } from '@wordpress/element';

export const useSearchState = ( { initialTerm = '', initialSelectedId = null } = {} ) => {
	const [ searchTerm, setSearchTerm ] = useState( initialTerm );
	const [ isSearching, setIsSearching ] = useState( false );
	const [ searchResults, setSearchResults ] = useState( [] );
	const [ selectedId, setSelectedId ] = useState( initialSelectedId );
	const [ searchError, setSearchError ] = useState( '' );
	const [ hasSearched, setHasSearched ] = useState( false );

	const beginSearch = ( { clearResults = true, clearSelected = true } = {} ) => {
		setIsSearching( true );
		setSearchError( '' );

		if ( clearResults ) {
			setSearchResults( [] );
		}

		if ( clearSelected ) {
			setSelectedId( null );
		}
	};

	const completeSearch = ( results, emptyMessage = '' ) => {
		setSearchResults( results );
		setHasSearched( true );

		if ( ! results.length && emptyMessage ) {
			setSearchError( emptyMessage );
		}
	};

	const failSearch = ( message ) => {
		setSearchError( message );
		setHasSearched( false );
	};

	const finishSearch = () => {
		setIsSearching( false );
	};

	const selectResult = ( result, { getId = ( item ) => item.id, getTitle = ( item ) => item.title } = {} ) => {
		setSelectedId( getId( result ) );
		setSearchTerm( getTitle( result ) || '' );
		return result;
	};

	const resetResults = () => {
		setSearchResults( [] );
		setHasSearched( false );
		setSearchError( '' );
	};

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
		selectResult,
		resetResults,
	};
};
