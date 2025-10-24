import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, RichText, MediaUpload, MediaUploadCheck, PanelColorSettings } from '@wordpress/block-editor';
import { PanelBody, Button, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './editor.css';
import './style.css';

const LAYOUT_OPTIONS = [
  { label: 'Text left', value: 'text-left' },
  { label: 'Text right', value: 'text-right' },
  { label: 'Text over image', value: 'text-over' }
];

function Edit({ attributes, setAttributes }) {
  const { title, subtitle, description, imageUrl, imageAlt, accentColor, layout } = attributes;
  const blockProps = useBlockProps({ className: `wp-block-child-hero-teaser ${layout}` });

  const onSelectImage = (media) => {
    setAttributes({ imageId: media.id, imageUrl: media.url, imageAlt: media.alt });
  };

  return (
    <div {...blockProps}>
      <InspectorControls>
        <PanelBody title={__('Hero settings', 'child')} initialOpen>
          <SelectControl
            label={__('Layout', 'child')}
            value={layout}
            options={LAYOUT_OPTIONS}
            onChange={(value) => setAttributes({ layout: value })}
          />
        </PanelBody>
        <PanelColorSettings
          title={__('Overlay / accent', 'child')}
          initialOpen={false}
          colorSettings={[{
            value: accentColor,
            onChange: (color) => setAttributes({ accentColor: color }),
            label: __('Accent overlay (RGBA or CSS color)', 'child')
          }]}
        />
      </InspectorControls>

      <div className="child-hero__media">
        {imageUrl ? (
          <img src={imageUrl} alt={imageAlt} style={{ width: '100%', height: 'auto', display: 'block' }} />
        ) : (
          <MediaUploadCheck>
            <MediaUpload
              onSelect={onSelectImage}
              allowedTypes={["image"]}
              value={attributes.imageId}
              render={({ open }) => (
                <Button onClick={open} isSecondary>
                  {__('Select background image', 'child')}
                </Button>
              )}
            />
          </MediaUploadCheck>
        )}
      </div>

      <div className="child-hero__overlay" style={{ background: `linear-gradient(${accentColor}, rgba(0,0,0,0.0))` }} />

      <div className="child-hero__content">
        <RichText tagName="div" className="child-hero__subtitle" value={subtitle} onChange={(val) => setAttributes({ subtitle: val })} placeholder={__('Subtitle / category', 'child')} />
        <RichText tagName="h2" className="child-hero__title" value={title} onChange={(val) => setAttributes({ title: val })} placeholder={__('Headline', 'child')} />
        <RichText tagName="div" className="child-hero__description" value={description} onChange={(val) => setAttributes({ description: val })} placeholder={__('Short description', 'child')} />
      </div>
    </div>
  );
}

registerBlockType(metadata.name, {
  edit: Edit,
  save: () => null,
});
