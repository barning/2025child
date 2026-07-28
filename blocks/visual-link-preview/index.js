import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './editor.css';
import './style.css';

function Edit( { attributes, setAttributes } ) {
	const { url = '' } = attributes;
	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Linkvorschau-Einstellungen', 'child' ) }
				>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'URL', 'child' ) }
						value={ url }
						onChange={ ( value ) =>
							setAttributes( { url: value } )
						}
						placeholder={ __( 'URL einfügen…', 'child' ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				{ url ? (
					<ServerSideRender
						block={ metadata.name }
						attributes={ attributes }
					/>
				) : (
					<strong>
						{ __( 'Gib eine URL für die Vorschau ein.', 'child' ) }
					</strong>
				) }
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
