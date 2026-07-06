import { Button } from '@wordpress/components';

export const SearchResultsList = ( { results, selectedId, onSelect, className, getKey, getId, getClassName, children } ) => {
	if ( ! results.length ) {
		return null;
	}

	return (
		<div className={ className }>
			{ results.map( ( result ) => {
				const id = getId( result );
				const isSelected = id === selectedId;

				return (
					<Button
						key={ getKey ? getKey( result ) : id }
						variant={ isSelected ? 'primary' : 'secondary' }
						onClick={ () => onSelect( result ) }
						className={ getClassName( result, isSelected ) }
					>
						{ children( result, isSelected ) }
					</Button>
				);
			} ) }
		</div>
	);
};
