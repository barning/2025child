import { Notice, Spinner } from '@wordpress/components';

export const SearchFeedback = ( {
	isSearching,
	error,
	loadingClassName = '',
	errorStatus = 'error',
	statusMessage = '',
} ) => (
	<div role="status" aria-live="polite" aria-atomic="true">
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
		{ ! isSearching && ! error && statusMessage ? (
			<span className="screen-reader-text">{ statusMessage }</span>
		) : null }
	</div>
);
