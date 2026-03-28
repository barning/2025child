import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import {
	AlignmentToolbar,
	BlockControls,
	InspectorControls,
	PanelColorSettings,
	useBlockProps,
} from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import metadata from './block.json';
import './editor.css';
import './style.css';

const SIZE_CONFIG = {
	sm: {
		fontSize: '0.95rem',
		paddingY: '0.5rem',
		paddingX: '0.9rem',
	},
	md: {
		fontSize: '1rem',
		paddingY: '0.8rem',
		paddingX: '1.3rem',
	},
	lg: {
		fontSize: '1.1rem',
		paddingY: '1rem',
		paddingX: '1.6rem',
	},
};

function Edit( { attributes, setAttributes } ) {
	const {
		buttonBackground,
		buttonBorder,
		buttonText,
		buttonHoverBorder,
		buttonSize,
		buttonAlign,
		buttonLikedBackground,
		buttonFocusOutline,
		buttonErrorBorder,
		ctaText,
		reactionEmoji,
	} = attributes;

	const style = {};
	const sizeConfig = SIZE_CONFIG[ buttonSize ] || SIZE_CONFIG.md;
	if ( buttonBackground ) {
		style[ '--child-post-likes-bg' ] = buttonBackground;
	}
	if ( buttonBorder ) {
		style[ '--child-post-likes-border' ] = buttonBorder;
	}
	if ( buttonText ) {
		style[ '--child-post-likes-text' ] = buttonText;
	}
	if ( buttonHoverBorder ) {
		style[ '--child-post-likes-hover-border' ] = buttonHoverBorder;
	}
	if ( sizeConfig ) {
		style[ '--child-post-likes-font-size' ] = sizeConfig.fontSize;
		style[ '--child-post-likes-padding-y' ] = sizeConfig.paddingY;
		style[ '--child-post-likes-padding-x' ] = sizeConfig.paddingX;
	}
	if ( buttonAlign ) {
		style[ '--child-post-likes-align' ] = buttonAlign;
	}
	if ( buttonLikedBackground ) {
		style[ '--child-post-likes-liked-bg' ] = buttonLikedBackground;
	}
	if ( buttonFocusOutline ) {
		style[ '--child-post-likes-focus' ] = buttonFocusOutline;
	}
	if ( buttonErrorBorder ) {
		style[ '--child-post-likes-error-border' ] = buttonErrorBorder;
	}

	const blockProps = useBlockProps( { style } );

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ buttonAlign }
					onChange={ ( value ) =>
						setAttributes( { buttonAlign: value || 'left' } )
					}
				/>
			</BlockControls>
			<InspectorControls>
				<PanelBody title={ __( 'Layout', 'child' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Button Size', 'child' ) }
						value={ buttonSize }
						options={ [
							{ label: __( 'Small', 'child' ), value: 'sm' },
							{ label: __( 'Medium', 'child' ), value: 'md' },
							{ label: __( 'Large', 'child' ), value: 'lg' },
						] }
						onChange={ ( value ) =>
							setAttributes( { buttonSize: value } )
						}
					/>
					<TextControl
						label={ __( 'CTA Message', 'child' ) }
						value={ ctaText }
						onChange={ ( value ) => setAttributes( { ctaText: value } ) }
						help={ __(
							'Short prompt shown before the emoji and like count.',
							'child'
						) }
					/>
					<SelectControl
						label={ __( 'Emoji', 'child' ) }
						value={ reactionEmoji }
						options={ [
							{ label: '❤️ Heart', value: '❤️' },
							{ label: '💖 Sparkling Heart', value: '💖' },
							{ label: '👍 Thumbs Up', value: '👍' },
							{ label: '🔥 Fire', value: '🔥' },
							{ label: '😂 Laugh', value: '😂' },
							{ label: '🎉 Party', value: '🎉' },
							{ label: '🍰 Cake', value: '🍰' },
							{ label: '⭐ Star', value: '⭐' },
						] }
						onChange={ ( value ) =>
							setAttributes( { reactionEmoji: value } )
						}
					/>
				</PanelBody>
				<PanelColorSettings
					title={ __( 'Button Colors', 'child' ) }
					colorSettings={ [
						{
							label: __( 'Background', 'child' ),
							value: buttonBackground,
							onChange: ( value ) =>
								setAttributes( { buttonBackground: value } ),
						},
						{
							label: __( 'Border', 'child' ),
							value: buttonBorder,
							onChange: ( value ) =>
								setAttributes( { buttonBorder: value } ),
						},
						{
							label: __( 'Text', 'child' ),
							value: buttonText,
							onChange: ( value ) => setAttributes( { buttonText: value } ),
						},
						{
							label: __( 'Hover Border', 'child' ),
							value: buttonHoverBorder,
							onChange: ( value ) =>
								setAttributes( { buttonHoverBorder: value } ),
						},
						{
							label: __( 'Liked Background', 'child' ),
							value: buttonLikedBackground,
							onChange: ( value ) =>
								setAttributes( { buttonLikedBackground: value } ),
						},
						{
							label: __( 'Focus Outline', 'child' ),
							value: buttonFocusOutline,
							onChange: ( value ) =>
								setAttributes( { buttonFocusOutline: value } ),
						},
						{
							label: __( 'Error Border', 'child' ),
							value: buttonErrorBorder,
							onChange: ( value ) =>
								setAttributes( { buttonErrorBorder: value } ),
						},
					] }
				/>
			</InspectorControls>
			<div { ...blockProps }>
			<button
				type="button"
				className="child-post-likes__button is-editor-preview"
				disabled
			>
				<span className="child-post-likes__cta">{ ctaText }</span>
				<span className="child-post-likes__icon" aria-hidden="true">
					{ reactionEmoji }
				</span>
				<span className="child-post-likes__count">0</span>
			</button>
			<p className="child-post-likes__help">
				{ __( 'Frontend visitors can toggle likes on this post.', 'child' ) }
			</p>
			</div>
		</>
	);
}

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
