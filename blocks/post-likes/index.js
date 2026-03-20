import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import metadata from './block.json';
import './editor.css';
import './style.css';

function Edit() {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<button
				type="button"
				className="child-post-likes__button is-editor-preview"
				disabled
			>
				<span className="child-post-likes__icon" aria-hidden="true">
					❤
				</span>
				<span className="child-post-likes__count">0</span>
			</button>
			<p className="child-post-likes__help">
				{ __( 'Frontend visitors can toggle likes on this post.', 'child' ) }
			</p>
		</div>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
