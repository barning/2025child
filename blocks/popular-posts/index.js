import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import metadata from './block.json';
import './editor.css';
import './style.css';

function Edit({ attributes, setAttributes }) {
  const { number = 5 } = attributes;
  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Popular Posts Settings', 'child')}>
          <RangeControl
            label={__('Number of posts', 'child')}
            value={number}
            onChange={(value) => setAttributes({ number: value })}
            min={1}
            max={20}
          />
        </PanelBody>
      </InspectorControls>
      <div {...useBlockProps()}>
        <strong>{__('Popular Posts will appear here.', 'child')}</strong>
      </div>
    </>
  );
}

registerBlockType(metadata.name, {
  edit: Edit,
  save: () => null,
});
