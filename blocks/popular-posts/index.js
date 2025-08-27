import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { PostPicker } from '@wordpress/components';
import metadata from './block.json';
import './editor.css';
import './style.css';

function Edit({ attributes, setAttributes }) {
  const { selectedPosts = [], title = __('Some Favorites To Get You Started', 'child'), emoji = '✨' } = attributes;

  const posts = useSelect(
    (select) => {
      const { getEntityRecords } = select(coreStore);
      const postsData = getEntityRecords('postType', 'post', {
        include: selectedPosts,
        per_page: -1,
      });
      return postsData;
    },
    [selectedPosts]
  );
  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Popular Posts Settings', 'child')}>
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
          <PostPicker
            label={__('Select Posts', 'child')}
            postType="post"
            selectedPosts={selectedPosts}
            onChange={(value) => setAttributes({ selectedPosts: value })}
          />
        </PanelBody>
      </InspectorControls>
      <div {...useBlockProps({ className: 'child-popular-card' })}>
        <div className="child-popular-card__header">
          <div className="child-popular-card__emoji" aria-hidden="true">{emoji}</div>
          <h3 className="child-popular-card__title">{title}</h3>
        </div>
        <ol className="child-popular-card__list">
          {posts?.map((post) => (
            <li key={post.id} className="child-popular-card__item">
              <span className="child-popular-card__link">{post.title.rendered}</span>
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
