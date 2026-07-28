/* eslint-disable jsdoc/require-param-type */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	ComboboxControl,
	Button,
} from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import metadata from './block.json';
import './editor.css';
import './style.css';

// Constants
const DEFAULTS = {
	TITLE: __( 'Ein paar Favoriten zum Einstieg', 'child' ),
	EMOJI: '✨',
	EMPTY_POST_ID: 0,
};

const styles = {
	selectorRow: {
		display: 'flex',
		alignItems: 'center',
		gap: '10px',
		marginBottom: '10px',
	},
	removeButton: ( isFirst ) => ( {
		marginTop: isFirst ? '0' : '20px',
	} ),
	addButton: {
		marginTop: '10px',
	},
};

/**
 * Custom hook to manage post selection state and actions
 * @param selectedPosts
 * @param setAttributes
 */
const usePostSelection = ( selectedPosts, setAttributes ) => {
	const addNewPostSelector = () => {
		setAttributes( {
			selectedPosts: [ ...selectedPosts, DEFAULTS.EMPTY_POST_ID ],
		} );
	};

	const removePostSelector = ( index ) => {
		const newSelectedPosts = [ ...selectedPosts ];
		newSelectedPosts.splice( index, 1 );
		setAttributes( { selectedPosts: newSelectedPosts } );
	};

	const updatePostSelection = ( value, index ) => {
		const newSelectedPosts = [ ...selectedPosts ];
		newSelectedPosts[ index ] = value
			? parseInt( value )
			: DEFAULTS.EMPTY_POST_ID;
		setAttributes( { selectedPosts: newSelectedPosts } );
	};

	return { addNewPostSelector, removePostSelector, updatePostSelection };
};

/**
 * Custom hook to fetch posts data
 * @param selectedPosts
 */
const usePosts = ( selectedPosts ) => {
	return useSelect(
		( select ) => {
			const { getEntityRecords } = select( coreStore );

			return {
				posts: selectedPosts.length
					? getEntityRecords( 'postType', 'post', {
							include: selectedPosts,
							per_page: 100,
					  } )
					: [],
				allPosts:
					getEntityRecords( 'postType', 'post', {
						per_page: 100,
						orderby: 'title',
						order: 'asc',
					} ) || [],
			};
		},
		[ selectedPosts ]
	);
};

/**
 * Settings Panel Component
 * @param root0
 * @param root0.title
 * @param root0.emoji
 * @param root0.selectedPosts
 * @param root0.allPosts
 * @param root0.onUpdatePost
 * @param root0.onRemovePost
 * @param root0.onAddPost
 * @param root0.setAttributes
 */
const SettingsPanel = ( {
	title,
	emoji,
	selectedPosts,
	allPosts,
	onUpdatePost,
	onRemovePost,
	onAddPost,
	setAttributes,
} ) => (
	<InspectorControls>
		<PanelBody
			title={ __( 'Einstellungen für beliebte Beiträge', 'child' ) }
		>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Titel', 'child' ) }
				value={ title }
				onChange={ ( value ) => setAttributes( { title: value } ) }
			/>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Emoji', 'child' ) }
				value={ emoji }
				onChange={ ( value ) => setAttributes( { emoji: value } ) }
			/>
			<PostSelector
				selectedPosts={ selectedPosts }
				allPosts={ allPosts }
				onUpdatePost={ onUpdatePost }
				onRemovePost={ onRemovePost }
				onAddPost={ onAddPost }
			/>
		</PanelBody>
	</InspectorControls>
);

/**
 * Post Selector Component
 * @param root0
 * @param root0.selectedPosts
 * @param root0.allPosts
 * @param root0.onUpdatePost
 * @param root0.onRemovePost
 * @param root0.onAddPost
 */
const PostSelector = ( {
	selectedPosts,
	allPosts,
	onUpdatePost,
	onRemovePost,
	onAddPost,
} ) => (
	<div className="child-post-selector">
		{ selectedPosts.map( ( selectedId, index ) => (
			<div
				key={ index }
				className="child-post-selector__row"
				style={ styles.selectorRow }
			>
				<ComboboxControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Beitrag auswählen oder suchen', 'child' ) }
					value={ selectedId.toString() }
					options={ allPosts.map( ( post ) => ( {
						label: decodeEntities( post.title.rendered ),
						value: post.id.toString(),
					} ) ) }
					onChange={ ( value ) => onUpdatePost( value, index ) }
					allowReset={ false }
					placeholder={ __( 'Beitrag suchen…', 'child' ) }
					__experimentalShowSelectedSuggestion={ true }
				/>
				{ index > 0 && (
					<Button
						onClick={ () => onRemovePost( index ) }
						style={ styles.removeButton( index === 0 ) }
						isSmall
						isDestructive
						icon="trash"
						label={ __( 'Diesen Beitrag entfernen', 'child' ) }
						tooltipPosition="top"
					/>
				) }
			</div>
		) ) }
		<Button
			onClick={ onAddPost }
			variant="secondary"
			className="child-post-selector__add"
			style={ styles.addButton }
			isSmall
		>
			+ { __( 'Weiteren Beitrag hinzufügen', 'child' ) }
		</Button>
	</div>
);

/**
 * Preview Component
 * @param root0
 * @param root0.title
 * @param root0.emoji
 * @param root0.posts
 */
const Preview = ( { title, emoji, posts } ) => {
	const blockProps = useBlockProps( {
		className: 'wp-block-child-popular-posts',
	} );

	return (
		<div { ...blockProps }>
			<div className="child-popular-card">
				<div className="child-popular-card__header">
					<div
						className="child-popular-card__emoji"
						aria-hidden="true"
					>
						{ emoji }
					</div>
					<p className="child-popular-card__title">{ title }</p>
				</div>
				<ul className="child-popular-card__list">
					{ posts?.length ? (
						posts.map( ( post ) => (
							<li
								key={ post.id }
								className="child-popular-card__item"
							>
								<span className="child-popular-card__link">
									{ decodeEntities( post.title.rendered ) }
								</span>
							</li>
						) )
					) : (
						<li className="child-popular-card__item">
							<span className="child-popular-card__link child-popular-card__link--placeholder">
								{ __( 'Bitte wähle Beiträge aus.', 'child' ) }
							</span>
						</li>
					) }
				</ul>
			</div>
		</div>
	);
};

/**
 * Main Edit Component
 * @param root0
 * @param root0.attributes
 * @param root0.setAttributes
 */
function Edit( { attributes, setAttributes } ) {
	const {
		selectedPosts: storedSelectedPosts,
		title = DEFAULTS.TITLE,
		emoji = DEFAULTS.EMOJI,
	} = attributes;
	const selectedPosts = storedSelectedPosts?.length
		? storedSelectedPosts
		: [ DEFAULTS.EMPTY_POST_ID ];

	const { posts, allPosts } = usePosts(
		selectedPosts.filter( ( id ) => id !== DEFAULTS.EMPTY_POST_ID )
	);
	const { addNewPostSelector, removePostSelector, updatePostSelection } =
		usePostSelection( selectedPosts, setAttributes );

	const selectedPostsData = posts
		? selectedPosts
				.map( ( id ) => posts.find( ( post ) => post.id === id ) )
				.filter( Boolean )
		: [];

	return (
		<>
			<SettingsPanel
				title={ title }
				emoji={ emoji }
				selectedPosts={ selectedPosts }
				allPosts={ allPosts }
				onUpdatePost={ updatePostSelection }
				onRemovePost={ removePostSelector }
				onAddPost={ addNewPostSelector }
				setAttributes={ setAttributes }
			/>
			<Preview
				title={ title }
				emoji={ emoji }
				posts={ selectedPostsData }
			/>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
