import { Notice, Spinner } from '@wordpress/components';

export const SearchFeedback = ( { isSearching, error, loadingClassName = '', errorStatus = 'error' } ) => (
	<>
		{ isSearching && loadingClassName ? (
			<div className={ loadingClassName }>
				<Spinner />
			</div>
		) : null }
		{ isSearching && ! loadingClassName ? <Spinner /> : null }
		{ error ? (
			<Notice status={ errorStatus } isDismissible={ false }>
				{ error }
			</Notice>
		) : null }
	</>
);
