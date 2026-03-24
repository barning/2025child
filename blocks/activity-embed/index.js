import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, URLInput } from '@wordpress/block-editor';
import { PanelBody, TextControl, Placeholder } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './editor.css';
import './style.css';

function Edit( { attributes, setAttributes } ) {
	const { url = '' } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Activity Embed Settings', 'child' ) } initialOpen>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Activity URL', 'child' ) }
						value={ url }
						onChange={ ( value ) => setAttributes( { url: value } ) }
						placeholder={ __( 'Paste Garmin or Strava activity link', 'child' ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				<Placeholder
					label={ __( 'Activity Embed', 'child' ) }
					instructions={ __( 'Paste Garmin or Strava activity link', 'child' ) }
				>
					<URLInput
						value={ url }
						onChange={ ( value ) => setAttributes( { url: value } ) }
						placeholder={ __( 'Paste Garmin or Strava activity link', 'child' ) }
					/>
				</Placeholder>

				{ url ? <ServerSideRender block={ metadata.name } attributes={ attributes } /> : null }
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
