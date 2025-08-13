import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './editor.css';
import './style.css';

function Edit({ attributes, setAttributes }) {
  const { url = '' } = attributes;
  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Link Preview Settings', 'child')}>
          <TextControl
            label={__('URL', 'child')}
            value={url}
            onChange={(value) => setAttributes({ url: value })}
            placeholder={__('Paste a URL…', 'child')}
          />
        </PanelBody>
      </InspectorControls>
      <div {...useBlockProps()}>
        {url ? (
          <ServerSideRender block={metadata.name} attributes={attributes} />
        ) : (
          <strong>{__('Enter a URL to preview.', 'child')}</strong>
        )}
      </div>
    </>
  );
}

registerBlockType(metadata.name, {
  edit: Edit,
  save: () => null,
});
