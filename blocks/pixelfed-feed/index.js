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
				<PanelBody title={ __( 'Pixelfed Feed Settings', 'child' ) }>
					<TextControl
						label={ __( 'Pixelfed RSS URL', 'child' ) }
						value={ feedUrl }
						onChange={ ( value ) => setAttributes( { feedUrl: value } ) }
						placeholder={ __( 'https://pixelfed.example/users/username.atom', 'child' ) }
						help={ __( 'Paste the RSS/Atom feed URL of a Pixelfed profile.', 'child' ) }
					/>
					<RangeControl
						label={ __( 'Number of images', 'child' ) }
						value={ itemsToShow }
						onChange={ ( value ) => setAttributes( { itemsToShow: value || 9 } ) }
						min={ 1 }
						max={ 18 }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...useBlockProps() }>
				{ feedUrl ? (
					<ServerSideRender block={ metadata.name } attributes={ attributes } />
				) : (
					<p>{ __( 'Add a Pixelfed RSS feed URL in the block settings.', 'child' ) }</p>
				) }
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
