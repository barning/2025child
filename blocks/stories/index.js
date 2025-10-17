/**
 * WordPress dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Register the block
 */
registerBlockType('twentytwentyfivechild/stories', {
    edit: function Edit() {
        const [stories, setStories] = useState([]);
        const blockProps = useBlockProps();

        useEffect(() => {
            apiFetch({ path: '/wp/v2/story?_embed' }).then((posts) => {
                setStories(posts);
            });
        }, []);

        return (
            <div {...blockProps}>
                <Placeholder
                    label={__('Stories', 'twentytwentyfivechild')}
                    instructions={__('Stories will be displayed here', 'twentytwentyfivechild')}
                >
                    {stories.length > 0 ? (
                        <div className="stories-preview-container">
                            {stories.map((story) => (
                                <div key={story.id} className="story-preview-item">
                                    {story.title.rendered}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p>{__('No stories found', 'twentytwentyfivechild')}</p>
                    )}
                </Placeholder>
            </div>
        );
    },

    save: function Save() {
        return null; // Dynamischer Block - wird serverseitig gerendert
    },
});