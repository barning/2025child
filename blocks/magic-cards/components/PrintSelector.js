import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';

export const PrintSelector = ( { prints, selectedPrint, onSelect } ) => {
	if ( ! prints || prints.length === 0 ) {
		return null;
	}

	if ( prints.length === 1 ) {
		return (
			<p className="magic-cards-prints-info">
				{ __( 'Nur ein Druck verfügbar', 'child' ) }
			</p>
		);
	}

	const options = prints.map( ( print ) => ( {
		label: `${ print.set_name } (${ print.set.toUpperCase() }) - ${
			print.released_at || __( 'Unbekannt', 'child' )
		}`,
		value: print.id,
	} ) );

	return (
		<SelectControl
			label={ __( 'Druck auswählen', 'child' ) }
			value={ selectedPrint?.id || '' }
			options={ [
				{ label: __( 'Druck auswählen…', 'child' ), value: '' },
				...options,
			] }
			onChange={ ( value ) => {
				const print = prints.find( ( item ) => item.id === value );
				if ( print ) {
					onSelect( print );
				}
			} }
			help={ __(
				'Wähle einen alternativen Druck dieser Karte.',
				'child'
			) }
		/>
	);
};
