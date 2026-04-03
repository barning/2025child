import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './editor.css';
import './style.css';

function Edit( { attributes, setAttributes } ) {
	const {
		resourceType = 'song',
		resourceId = '',
		storefront = 'us',
		buttonLabel = 'Play on Apple Music',
	} = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Apple Music Settings', 'child' ) }>
					<SelectControl
						label={ __( 'Resource type', 'child' ) }
						value={ resourceType }
						options={ [
							{ label: __( 'Song', 'child' ), value: 'song' },
							{ label: __( 'Album', 'child' ), value: 'album' },
							{ label: __( 'Playlist', 'child' ), value: 'playlist' },
						] }
						onChange={ ( value ) => setAttributes( { resourceType: value } ) }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Apple Music resource ID', 'child' ) }
						value={ resourceId }
						onChange={ ( value ) => setAttributes( { resourceId: value.trim() } ) }
						help={ __(
							'For example: 310730204 (song), 1560735414 (album), or pl.u-xxxx (playlist).',
							'child'
						) }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Storefront', 'child' ) }
						value={ storefront }
						onChange={ ( value ) => setAttributes( { storefront: value.trim().toLowerCase() } ) }
						help={ __( 'Two-letter storefront code (for example: us, gb, de).', 'child' ) }
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Button label', 'child' ) }
						value={ buttonLabel }
						onChange={ ( value ) => setAttributes( { buttonLabel: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				{ resourceId ? (
					<ServerSideRender block={ metadata.name } attributes={ attributes } />
				) : (
					<p>
						{ __(
							'Add an Apple Music resource ID in block settings to render the player.',
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
