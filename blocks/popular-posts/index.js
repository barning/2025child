import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ComboboxControl, Button } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import metadata from './block.json';
import './editor.css';
import './style.css';

// Constants
const DEFAULTS = {
  TITLE: __('Some Favorites To Get You Started', 'child'),
  EMOJI: '✨',
  EMPTY_POST_ID: 0
};

const styles = {
  selectorRow: {
    display: 'flex',
    alignItems: 'center',
    gap: '10px',
    marginBottom: '10px'
  },
  removeButton: (isFirst) => ({
    marginTop: isFirst ? '0' : '20px'
  }),
  addButton: {
    marginTop: '10px'
  }
};

/**
 * Custom hook to manage post selection state and actions
 */
const usePostSelection = (selectedPosts, setAttributes) => {
  const addNewPostSelector = () => {
    setAttributes({ selectedPosts: [...selectedPosts, DEFAULTS.EMPTY_POST_ID] });
  };

  const removePostSelector = (index) => {
    const newSelectedPosts = [...selectedPosts];
    newSelectedPosts.splice(index, 1);
    setAttributes({ selectedPosts: newSelectedPosts });
  };

  const updatePostSelection = (value, index) => {
    const newSelectedPosts = [...selectedPosts];
    newSelectedPosts[index] = parseInt(value);
    
    if (newSelectedPosts.length > 1) {
      setAttributes({ 
        selectedPosts: newSelectedPosts.filter(id => id !== DEFAULTS.EMPTY_POST_ID) 
      });
    } else {
      setAttributes({ selectedPosts: newSelectedPosts });
    }
  };

  return { addNewPostSelector, removePostSelector, updatePostSelection };
};

/**
 * Custom hook to fetch posts data
 */
const usePosts = (selectedPosts) => {
  return useSelect((select) => {
    const { getEntityRecords } = select(coreStore);
    
    return {
      posts: selectedPosts.length ? getEntityRecords('postType', 'post', {
        include: selectedPosts,
        per_page: -1,
      }) : [],
      allPosts: getEntityRecords('postType', 'post', {
        per_page: -1,
        orderby: 'title',
        order: 'asc',
      }) || []
    };
  }, [selectedPosts]);
};

/**
 * Settings Panel Component
 */
const SettingsPanel = ({ title, emoji, selectedPosts, allPosts, onUpdatePost, onRemovePost, onAddPost, setAttributes }) => (
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
      <PostSelector
        selectedPosts={selectedPosts}
        allPosts={allPosts}
        onUpdatePost={onUpdatePost}
        onRemovePost={onRemovePost}
        onAddPost={onAddPost}
      />
    </PanelBody>
  </InspectorControls>
);

/**
 * Post Selector Component
 */
const PostSelector = ({ selectedPosts, allPosts, onUpdatePost, onRemovePost, onAddPost }) => (
  <div className="child-post-selector">
    {selectedPosts.map((selectedId, index) => (
      <div key={index} className="child-post-selector__row" style={styles.selectorRow}>
        <ComboboxControl
          label={index === 0 ? __('Select or Search Post', 'child') : ''}
          value={selectedId || ''}
          options={allPosts.map(post => ({
            label: decodeEntities(post.title.rendered),
            value: post.id.toString()
          }))}
          onFilterValueChange={() => {}}
          onChange={(value) => onUpdatePost(value, index)}
          allowReset={true}
        />
        {index > 0 && (
          <Button
            onClick={() => onRemovePost(index)}
            style={styles.removeButton(index === 0)}
            isSmall
            isDestructive
          >
            ×
          </Button>
        )}
      </div>
    ))}
    <Button
      onClick={onAddPost}
      variant="secondary"
      className="child-post-selector__add"
      style={styles.addButton}
      isSmall
    >
      + {__('Add another post', 'child')}
    </Button>
  </div>
);

/**
 * Preview Component
 */
const Preview = ({ title, emoji, posts }) => (
  <div {...useBlockProps({ className: 'child-popular-card' })}>
    <div className="child-popular-card__header">
      <div className="child-popular-card__emoji" aria-hidden="true">{emoji}</div>
      <h3 className="child-popular-card__title">{title}</h3>
    </div>
    <ul className="child-popular-card__list">
      {posts?.length ? (
        posts.map((post) => (
          <li key={post.id} className="child-popular-card__item">
            <span className="child-popular-card__link">
              {decodeEntities(post.title.rendered)}
            </span>
          </li>
        ))
      ) : (
        <li className="child-popular-card__item">
          <span className="child-popular-card__link">
            {__('Please select some posts', 'child')}
          </span>
        </li>
      )}
    </ul>
  </div>
);

/**
 * Main Edit Component
 */
function Edit({ attributes, setAttributes }) {
  const { 
    selectedPosts = [DEFAULTS.EMPTY_POST_ID], 
    title = DEFAULTS.TITLE, 
    emoji = DEFAULTS.EMOJI 
  } = attributes;
  
  // Initialize with one empty selector if none exists
  if (!attributes.selectedPosts?.length) {
    setAttributes({ selectedPosts: [DEFAULTS.EMPTY_POST_ID] });
  }

  const { posts, allPosts } = usePosts(selectedPosts);
  const { addNewPostSelector, removePostSelector, updatePostSelection } = usePostSelection(selectedPosts, setAttributes);

  return (
    <>
      <SettingsPanel
        title={title}
        emoji={emoji}
        selectedPosts={selectedPosts}
        allPosts={allPosts}
        onUpdatePost={updatePostSelection}
        onRemovePost={removePostSelector}
        onAddPost={addNewPostSelector}
        setAttributes={setAttributes}
      />
      <Preview
        title={title}
        emoji={emoji}
        posts={posts}
      />
    </>
  );
}

registerBlockType(metadata.name, {
  edit: Edit,
  save: () => null,
});
