import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl } from '@wordpress/components';
import metadata from './block.json';
import './editor.css';
import './style.css';

function Edit({ attributes, setAttributes }) {
  const { number = 5, title = __('Some Favorites To Get You Started', 'child'), emoji = '✨' } = attributes;
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
          <TextControl
            label={__('Title', 'child')}
            value={title}
            onChange={(value) => setAttributes({ title: value })}
          />
          <TextControl
            label={__('Emoji', 'child')}
            value={emoji}
            onChange={(value) => setAttributes({ emoji: value })}
          />
        </PanelBody>
      </InspectorControls>
      <div {...useBlockProps({ className: 'child-popular-card' })}>
        <div className="child-popular-card__header">
          <div className="child-popular-card__emoji" aria-hidden="true">{emoji}</div>
          <h3 className="child-popular-card__title">{title}</h3>
        </div>
        <ol className="child-popular-card__list">
          {[...Array(number)].map((_, i) => (
            <li key={i} className="child-popular-card__item">
              <span className="child-popular-card__link">{__('Example post', 'child')} #{i + 1}</span>
            </li>
          ))}
        </ol>
      </div>
    </>
  );
}

registerBlockType(metadata.name, {
  edit: Edit,
  save: () => null,
});
