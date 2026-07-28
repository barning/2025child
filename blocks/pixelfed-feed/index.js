import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, RangeControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './editor.css';
import './style.css';

function Edit( { attributes, setAttributes } ) {
	const { feedUrl = '', itemsToShow = 9 } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Pixelfed-Feed-Einstellungen', 'child' ) }
				>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Pixelfed RSS URL', 'child' ) }
						value={ feedUrl }
						onChange={ ( value ) =>
							setAttributes( { feedUrl: value } )
						}
						placeholder={ __(
							'https://pixelfed.example/users/username.atom',
							'child'
						) }
						help={ __(
							'Füge die RSS-/Atom-Feed-URL eines Pixelfed-Profils ein.',
							'child'
						) }
					/>
					<RangeControl
						label={ __( 'Anzahl der Bilder', 'child' ) }
						value={ itemsToShow }
						onChange={ ( value ) =>
							setAttributes( { itemsToShow: value || 9 } )
						}
						min={ 1 }
						max={ 18 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				{ feedUrl ? (
					<ServerSideRender
						block={ metadata.name }
						attributes={ attributes }
					/>
				) : (
					<p>
						{ __(
							'Füge in den Block-Einstellungen eine Pixelfed-RSS-Feed-URL hinzu.',
							'child'
						) }
					</p>
				) }
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
