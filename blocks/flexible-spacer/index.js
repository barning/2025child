import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl } from '@wordpress/components';
import metadata from './block.json';
import './editor.css';
import './style.css';

function Edit({ attributes, setAttributes }) {
  const { height = 32 } = attributes;
  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Spacer Settings', 'child')}>
          <RangeControl
            label={__('Height (px)', 'child')}
            value={height}
            onChange={(value) => setAttributes({ height: value })}
            min={8}
            max={256}
          />
        </PanelBody>
      </InspectorControls>
      <div {...useBlockProps()} style={{ height: `${height}px` }} />
    </>
  );
}

registerBlockType(metadata.name, {
  edit: Edit,
  save: () => null,
});
