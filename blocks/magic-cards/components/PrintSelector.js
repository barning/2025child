import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';

export const PrintSelector = ( { prints, selectedPrint, onSelect } ) => {
	if ( ! prints || prints.length === 0 ) {
		return null;
	}

	if ( prints.length === 1 ) {
		return (
			<p className="magic-cards-prints-info">
				{ __( 'Only one printing available', 'child' ) }
			</p>
		);
	}

	const options = prints.map( ( print ) => ( {
		label: `${ print.set_name } (${ print.set.toUpperCase() }) - ${
			print.released_at || 'Unknown'
		}`,
		value: print.id,
	} ) );

	return (
		<SelectControl
			label={ __( 'Select Print', 'child' ) }
			value={ selectedPrint?.id || '' }
			options={ [
				{ label: __( 'Select a print…', 'child' ), value: '' },
				...options,
			] }
			onChange={ ( value ) => {
				const print = prints.find( ( item ) => item.id === value );
				if ( print ) {
					onSelect( print );
				}
			} }
			help={ __(
				'Choose an alternative printing of this card',
				'child'
			) }
		/>
	);
};
