import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './editor.css';
import './style.css';

function Edit( { attributes, setAttributes } ) {
	const {
		feedUrl = '',
		buttonText = 'View Stories',
		loading = 'eager',
		duration = 5,
		showMetadata = false,
		isHighlight = false,
	} = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Open Stories Settings', 'child' ) }>
					<TextControl __next40pxDefaultSize __nextHasNoMarginBottom
						label={ __( 'Feed URL', 'child' ) }
						value={ feedUrl }
						onChange={ ( value ) => setAttributes( { feedUrl: value } ) }
						placeholder={ __( 'https://example.com/open-stories.json', 'child' ) }
					/>
					<TextControl __next40pxDefaultSize __nextHasNoMarginBottom
						label={ __( 'Button text', 'child' ) }
						value={ buttonText }
						onChange={ ( value ) => setAttributes( { buttonText: value } ) }
					/>
					<SelectControl
						label={ __( 'Loading mode', 'child' ) }
						value={ loading }
						onChange={ ( value ) => setAttributes( { loading: value } ) }
						options={ [
							{ label: __( 'Eager', 'child' ), value: 'eager' },
							{ label: __( 'Lazy', 'child' ), value: 'lazy' },
						] }
					/>
					<RangeControl
						label={ __( 'Default story duration (seconds)', 'child' ) }
						value={ duration }
						onChange={ ( value ) => setAttributes( { duration: value || 5 } ) }
						min={ 1 }
						max={ 30 }
					/>
					<ToggleControl
						label={ __( 'Show metadata/captions', 'child' ) }
						checked={ showMetadata }
						onChange={ ( value ) => setAttributes( { showMetadata: value } ) }
					/>
					<ToggleControl
						label={ __( 'Highlight mode (no read history)', 'child' ) }
						checked={ isHighlight }
						onChange={ ( value ) => setAttributes( { isHighlight: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...useBlockProps() }>
				{ feedUrl ? (
					<ServerSideRender block={ metadata.name } attributes={ attributes } />
				) : (
					<p>{ __( 'Add an Open Stories feed URL in block settings.', 'child' ) }</p>
				) }
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
